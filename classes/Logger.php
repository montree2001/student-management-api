<?php
// ==================== classes/Logger.php ====================
class Logger {
    private $logDir = 'logs/';
    private $maxFileSize = 10485760; // 10MB
    private $maxFiles = 10;
    
    public function __construct() {
        // สร้างโฟลเดอร์ log ถ้าไม่มี
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    // เขียน log ทั่วไป
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        $this->writeToFile('app.log', $logLine);
    }
    
    // Log API calls
    public function logApiCall($method, $endpoint, $params, $response, $executionTime) {
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'anonymous';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'API_CALL',
            'method' => $method,
            'endpoint' => $endpoint,
            'user_id' => $userId,
            'username' => $username,
            'params' => $params,
            'response_status' => $response['success'] ?? false,
            'execution_time' => $executionTime,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        $this->writeToFile('api.log', $logLine);
    }
    
    // Log การเข้าสู่ระบบ
    public function logAuth($action, $username, $success, $message = '') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'AUTH',
            'action' => $action,
            'username' => $username,
            'success' => $success,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        $this->writeToFile('auth.log', $logLine);
    }
    
    // Log การ import ข้อมูล
    public function logImport($filename, $totalRecords, $successRecords, $failedRecords, $errors = []) {
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'anonymous';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'IMPORT',
            'filename' => $filename,
            'user_id' => $userId,
            'username' => $username,
            'total_records' => $totalRecords,
            'success_records' => $successRecords,
            'failed_records' => $failedRecords,
            'errors' => array_slice($errors, 0, 5), // เก็บแค่ 5 errors แรก
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        $this->writeToFile('import.log', $logLine);
    }
    
    // Log error
    public function logError($error, $file = '', $line = 0, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'ERROR',
            'error' => $error,
            'file' => $file,
            'line' => $line,
            'context' => $context,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        $this->writeToFile('error.log', $logLine);
    }
    
    // เขียนลงไฟล์
    private function writeToFile($filename, $content) {
        $filepath = $this->logDir . $filename;
        
        // ตรวจสอบขนาดไฟล์
        if (file_exists($filepath) && filesize($filepath) > $this->maxFileSize) {
            $this->rotateLogFile($filepath);
        }
        
        file_put_contents($filepath, $content, FILE_APPEND | LOCK_EX);
    }
    
    // หมุนไฟล์ log
    private function rotateLogFile($filepath) {
        $pathInfo = pathinfo($filepath);
        $baseName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = $pathInfo['dirname'];
        
        // ย้ายไฟล์เก่า
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = "{$directory}/{$baseName}.{$i}.{$extension}";
            $newFile = "{$directory}/{$baseName}." . ($i + 1) . ".{$extension}";
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        // ย้ายไฟล์ปัจจุบัน
        $newFile = "{$directory}/{$baseName}.1.{$extension}";
        rename($filepath, $newFile);
        
        // ลบไฟล์เก่าเกินขีดจำกัด
        $deleteFile = "{$directory}/{$baseName}." . ($this->maxFiles + 1) . ".{$extension}";
        if (file_exists($deleteFile)) {
            unlink($deleteFile);
        }
    }
    
    // อ่าน log
    public function readLog($filename, $lines = 100) {
        $filepath = $this->logDir . $filename;
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $content = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $content = array_reverse($content); // ล่าสุดก่อน
        $content = array_slice($content, 0, $lines);
        
        $logs = [];
        foreach ($content as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                $logs[] = $decoded;
            }
        }
        
        return $logs;
    }
    
    // ดึงรายการไฟล์ log
    public function getLogFiles() {
        $files = [];
        
        if (is_dir($this->logDir)) {
            $scanFiles = scandir($this->logDir);
            
            foreach ($scanFiles as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filepath = $this->logDir . $file;
                if (is_file($filepath) && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $files[] = [
                        'filename' => $file,
                        'size' => filesize($filepath),
                        'modified' => date('Y-m-d H:i:s', filemtime($filepath))
                    ];
                }
            }
        }
        
        return $files;
    }
}

// ==================== classes/SystemMonitor.php ====================
class SystemMonitor {
    private $db;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->logger = new Logger();
    }
    
    // ตรวจสอบสถานะระบบ
    public function getSystemStatus() {
        $status = [];
        
        // ตรวจสอบฐานข้อมูล
        $status['database'] = $this->checkDatabase();
        
        // ตรวจสอบ disk space
        $status['disk_space'] = $this->checkDiskSpace();
        
        // ตรวจสอบ memory usage
        $status['memory_usage'] = $this->checkMemoryUsage();
        
        // ตรวจสอบ PHP configuration
        $status['php_config'] = $this->checkPhpConfig();
        
        // ตรวจสอบ permissions
        $status['permissions'] = $this->checkPermissions();
        
        // สถานะโดยรวม
        $status['overall'] = $this->calculateOverallStatus($status);
        
        return $status;
    }
    
    // ตรวจสอบฐานข้อมูล
    private function checkDatabase() {
        try {
            $stmt = $this->db->query("SELECT 1");
            $result = $stmt->fetchColumn();
            
            if ($result === 1) {
                return [
                    'status' => 'ok',
                    'message' => 'เชื่อมต่อฐานข้อมูลปกติ'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage()
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'การเชื่อมต่อฐานข้อมูลมีปัญหา'
        ];
    }
    
    // ตรวจสอบพื้นที่ disk
    private function checkDiskSpace() {
        $freeBytes = disk_free_space('.');
        $totalBytes = disk_total_space('.');
        $usedBytes = $totalBytes - $freeBytes;
        $usagePercent = ($usedBytes / $totalBytes) * 100;
        
        $status = 'ok';
        $message = 'พื้นที่ disk เพียงพอ';
        
        if ($usagePercent > 90) {
            $status = 'critical';
            $message = 'พื้นที่ disk เหลือน้อยมาก';
        } elseif ($usagePercent > 80) {
            $status = 'warning';
            $message = 'พื้นที่ disk เหลือน้อย';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'free_space' => $this->formatBytes($freeBytes),
            'total_space' => $this->formatBytes($totalBytes),
            'usage_percent' => round($usagePercent, 2)
        ];
    }
    
    // ตรวจสอบ memory usage
    private function checkMemoryUsage() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        $usagePercent = ($memoryUsage / $memoryLimit) * 100;
        
        $status = 'ok';
        $message = 'การใช้ memory ปกติ';
        
        if ($usagePercent > 90) {
            $status = 'critical';
            $message = 'การใช้ memory สูงมาก';
        } elseif ($usagePercent > 70) {
            $status = 'warning';
            $message = 'การใช้ memory สูง';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'current_usage' => $this->formatBytes($memoryUsage),
            'memory_limit' => $this->formatBytes($memoryLimit),
            'usage_percent' => round($usagePercent, 2)
        ];
    }
    
    // ตรวจสอบ PHP configuration
    private function checkPhpConfig() {
        $issues = [];
        
        // ตรวจสอบ upload_max_filesize
        $uploadMax = $this->parseSize(ini_get('upload_max_filesize'));
        if ($uploadMax < 50 * 1024 * 1024) { // 50MB
            $issues[] = 'upload_max_filesize ควรมีค่าอย่างน้อย 50MB';
        }
        
        // ตรวจสอบ post_max_size
        $postMax = $this->parseSize(ini_get('post_max_size'));
        if ($postMax < 50 * 1024 * 1024) { // 50MB
            $issues[] = 'post_max_size ควรมีค่าอย่างน้อย 50MB';
        }
        
        // ตรวจสอบ max_execution_time
        $maxExecTime = ini_get('max_execution_time');
        if ($maxExecTime < 300 && $maxExecTime != 0) { // 5 minutes
            $issues[] = 'max_execution_time ควรมีค่าอย่างน้อย 300 วินาที';
        }
        
        // ตรวจสอบ extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'zip', 'gd'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "ไม่พบ PHP extension: {$ext}";
            }
        }
        
        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'message' => empty($issues) ? 'PHP configuration ถูกต้อง' : 'พบปัญหา PHP configuration',
            'issues' => $issues
        ];
    }
    
    // ตรวจสอบ permissions
    private function checkPermissions() {
        $directories = ['uploads/', 'exports/', 'backups/', 'logs/'];
        $issues = [];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            
            if (!is_writable($dir)) {
                $issues[] = "ไม่สามารถเขียนไฟล์ในโฟลเดอร์ {$dir}";
            }
        }
        
        return [
            'status' => empty($issues) ? 'ok' : 'error',
            'message' => empty($issues) ? 'Permissions ถูกต้อง' : 'พบปัญหา permissions',
            'issues' => $issues
        ];
    }
    
    // คำนวณสถานะโดยรวม
    private function calculateOverallStatus($status) {
        $hasError = false;
        $hasWarning = false;
        
        foreach ($status as $key => $check) {
            if ($key === 'overall') continue;
            
            if (isset($check['status'])) {
                if ($check['status'] === 'error' || $check['status'] === 'critical') {
                    $hasError = true;
                } elseif ($check['status'] === 'warning') {
                    $hasWarning = true;
                }
            }
        }
        
        if ($hasError) {
            return [
                'status' => 'error',
                'message' => 'ระบบมีปัญหาที่ต้องแก้ไข'
            ];
        } elseif ($hasWarning) {
            return [
                'status' => 'warning',
                'message' => 'ระบบทำงานได้แต่มีข้อควรระวัง'
            ];
        } else {
            return [
                'status' => 'ok',
                'message' => 'ระบบทำงานปกติ'
            ];
        }
    }
    
    // แปลงขนาดจาก string เป็น bytes
    private function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
    
    // แปลง bytes เป็น readable format
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    // ดึงสถิติการใช้งาน
    public function getUsageStats($days = 7) {
        $stats = [];
        
        // API calls per day
        $stats['api_calls'] = $this->getApiCallsStats($days);
        
        // Login attempts
        $stats['login_attempts'] = $this->getLoginStats($days);
        
        // Import activities
        $stats['imports'] = $this->getImportStats($days);
        
        // Error rates
        $stats['errors'] = $this->getErrorStats($days);
        
        return $stats;
    }
    
    // สถิติ API calls
    private function getApiCallsStats($days) {
        // อ่านจาก log file (simplified)
        $logs = $this->logger->readLog('api.log', 1000);
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stats[$date] = 0;
        }
        
        foreach ($logs as $log) {
            $date = date('Y-m-d', strtotime($log['timestamp']));
            if (isset($stats[$date])) {
                $stats[$date]++;
            }
        }
        
        return $stats;
    }
    
    // สถิติ login
    private function getLoginStats($days) {
        $logs = $this->logger->readLog('auth.log', 1000);
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stats[$date] = ['success' => 0, 'failed' => 0];
        }
        
        foreach ($logs as $log) {
            $date = date('Y-m-d', strtotime($log['timestamp']));
            if (isset($stats[$date])) {
                if ($log['success']) {
                    $stats[$date]['success']++;
                } else {
                    $stats[$date]['failed']++;
                }
            }
        }
        
        return $stats;
    }
    
    // สถิติ import
    private function getImportStats($days) {
        $logs = $this->logger->readLog('import.log', 100);
        $stats = [];
        
        foreach ($logs as $log) {
            if (strtotime($log['timestamp']) >= strtotime("-{$days} days")) {
                $stats[] = [
                    'date' => date('Y-m-d', strtotime($log['timestamp'])),
                    'filename' => $log['filename'],
                    'total_records' => $log['total_records'],
                    'success_records' => $log['success_records'],
                    'failed_records' => $log['failed_records']
                ];
            }
        }
        
        return $stats;
    }
    
    // สถิติ error
    private function getErrorStats($days) {
        $logs = $this->logger->readLog('error.log', 500);
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stats[$date] = 0;
        }
        
        foreach ($logs as $log) {
            $date = date('Y-m-d', strtotime($log['timestamp']));
            if (isset($stats[$date])) {
                $stats[$date]++;
            }
        }
        
        return $stats;
    }
}

// ==================== monitor.php ====================
require_once 'includes/functions.php';
require_once 'classes/Auth.php';
require_once 'classes/Logger.php';
require_once 'classes/SystemMonitor.php';

// ตรวจสอบสิทธิ์ admin
$auth = new Auth();
if (!$auth->checkAdminAuth()) {
    jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
}

$action = $_GET['action'] ?? '';
$logger = new Logger();
$monitor = new SystemMonitor();

switch ($action) {
    case 'system_status':
        $status = $monitor->getSystemStatus();
        jsonResponse(true, $status);
        break;
        
    case 'usage_stats':
        $days = $_GET['days'] ?? 7;
        $stats = $monitor->getUsageStats($days);
        jsonResponse(true, $stats);
        break;
        
    case 'log_files':
        $files = $logger->getLogFiles();
        jsonResponse(true, ['files' => $files]);
        break;
        
    case 'read_log':
        $filename = $_GET['filename'] ?? '';
        $lines = $_GET['lines'] ?? 100;
        
        if (empty($filename)) {
            jsonResponse(false, null, 'กรุณาระบุชื่อไฟล์', 'VALIDATION_ERROR');
        }
        
        $logs = $logger->readLog($filename, $lines);
        jsonResponse(true, ['logs' => $logs]);
        break;
        
    case 'clear_log':
        $filename = $_POST['filename'] ?? '';
        
        if (empty($filename)) {
            jsonResponse(false, null, 'กรุณาระบุชื่อไฟล์', 'VALIDATION_ERROR');
        }
        
        $filepath = 'logs/' . $filename;
        if (file_exists($filepath)) {
            file_put_contents($filepath, '');
            jsonResponse(true, null, 'ล้างไฟล์ log สำเร็จ');
        } else {
            jsonResponse(false, null, 'ไม่พบไฟล์ log', 'FILE_NOT_FOUND');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Action ไม่ถูกต้อง', 'INVALID_ACTION');
}

// ==================== การใช้งาน Logger ใน API ====================
// ตัวอย่างการเพิ่ม logging ใน api.php

// ในจุดเริ่มต้นของ API call
$startTime = microtime(true);
$logger = new Logger();

// ในจุดสิ้นสุดของ API call
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2); // milliseconds

$logger->logApiCall(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
    $_REQUEST,
    $response, // response ที่ส่งกลับ
    $executionTime
);

// สำหรับ error handling
set_error_handler(function($severity, $message, $file, $line) use ($logger) {
    $logger->logError($message, $file, $line, [
        'severity' => $severity,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
    ]);
});

set_exception_handler(function($exception) use ($logger) {
    $logger->logError(
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        [
            'trace' => $exception->getTraceAsString(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ]
    );
});
?>