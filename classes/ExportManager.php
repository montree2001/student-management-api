<?php
// ==================== classes/ExportManager.php ====================
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Export ข้อมูลนักเรียนเป็น Excel
    public function exportStudentsToExcel($filters = []) {
        try {
            // ดึงข้อมูลนักเรียน
            $sql = "SELECT 
                        s.*,
                        a.house_number, a.village_no, a.road, a.province, a.district, a.subdistrict, a.postal_code,
                        GROUP_CONCAT(DISTINCT CONCAT(p.parent_type, ':', p.title_name, ' ', p.first_name, ' ', p.last_name) SEPARATOR '; ') as parents_info
                    FROM students s
                    LEFT JOIN student_addresses a ON s.id = a.student_id
                    LEFT JOIN student_parents p ON s.id = p.student_id
                    WHERE 1=1";
            
            $params = [];
            
            // เพิ่มเงื่อนไขกรอง
            if (!empty($filters['class_group'])) {
                $sql .= " AND s.class_group = :class_group";
                $params[':class_group'] = $filters['class_group'];
            }
            
            if (!empty($filters['major'])) {
                $sql .= " AND s.major LIKE :major";
                $params[':major'] = "%{$filters['major']}%";
            }
            
            if (!empty($filters['student_status'])) {
                $sql .= " AND s.student_status = :student_status";
                $params[':student_status'] = $filters['student_status'];
            }
            
            if (!empty($filters['province'])) {
                $sql .= " AND a.province = :province";
                $params[':province'] = $filters['province'];
            }
            
            $sql .= " GROUP BY s.id ORDER BY s.student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // สร้าง Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('รายชื่อนักเรียน');
            
            // หัวตาราง
            $headers = [
                'A1' => 'ลำดับ',
                'B1' => 'รหัสนักเรียน',
                'C1' => 'เลขบัตรประชาชน',
                'D1' => 'คำนำหน้า',
                'E1' => 'ชื่อ',
                'F1' => 'นามสกุล',
                'G1' => 'เพศ',
                'H1' => 'วันเกิด',
                'I1' => 'อายุ',
                'J1' => 'กลุ่มเรียน',
                'K1' => 'สาขาวิชา',
                'L1' => 'ระดับชั้น',
                'M1' => 'สถานะ',
                'N1' => 'โทรศัพท์',
                'O1' => 'ที่อยู่',
                'P1' => 'จังหวัด',
                'Q1' => 'ข้อมูลผู้ปกครอง'
            ];
            
            // ใส่หัวตาราง
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // จัดรูปแบบหัวตาราง
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            
            $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);
            
            // ใส่ข้อมูล
            $row = 2;
            foreach ($students as $index => $student) {
                $address = '';
                if ($student['house_number']) {
                    $address = "บ้านเลขที่ {$student['house_number']} ";
                    if ($student['village_no']) $address .= "หมู่ {$student['village_no']} ";
                    if ($student['road']) $address .= "ถนน{$student['road']} ";
                    if ($student['subdistrict']) $address .= "ต.{$student['subdistrict']} ";
                    if ($student['district']) $address .= "อ.{$student['district']} ";
                    if ($student['postal_code']) $address .= "{$student['postal_code']}";
                }
                
                $sheet->setCellValue("A{$row}", $index + 1);
                $sheet->setCellValue("B{$row}", $student['student_id']);
                $sheet->setCellValue("C{$row}", $student['citizen_id']);
                $sheet->setCellValue("D{$row}", $student['title_name']);
                $sheet->setCellValue("E{$row}", $student['first_name']);
                $sheet->setCellValue("F{$row}", $student['last_name']);
                $sheet->setCellValue("G{$row}", $student['gender']);
                $sheet->setCellValue("H{$row}", $student['birth_date']);
                $sheet->setCellValue("I{$row}", $student['age']);
                $sheet->setCellValue("J{$row}", $student['class_group']);
                $sheet->setCellValue("K{$row}", $student['major']);
                $sheet->setCellValue("L{$row}", $student['class_level']);
                $sheet->setCellValue("M{$row}", $student['student_status']);
                $sheet->setCellValue("N{$row}", $student['phone_number']);
                $sheet->setCellValue("O{$row}", $address);
                $sheet->setCellValue("P{$row}", $student['province']);
                $sheet->setCellValue("Q{$row}", $student['parents_info']);
                
                $row++;
            }
            
            // จัดขนาดคอลัมน์
            foreach (range('A', 'Q') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // สร้างไฟล์
            $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = 'exports/' . $filename;
            
            // สร้างโฟลเดอร์ถ้าไม่มี
            if (!is_dir('exports')) {
                mkdir('exports', 0777, true);
            }
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'records' => count($students),
                'message' => 'Export ข้อมูลสำเร็จ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการ Export: ' . $e->getMessage()
            ];
        }
    }
    
    // Export ข้อมูลเป็น CSV
    public function exportStudentsToCSV($filters = []) {
        try {
            // ดึงข้อมูลเช่นเดียวกับ Excel
            $sql = "SELECT 
                        s.student_id, s.citizen_id, s.title_name, s.first_name, s.last_name,
                        s.gender, s.birth_date, s.age, s.class_group, s.major, s.class_level,
                        s.student_status, s.phone_number, a.province, a.district
                    FROM students s
                    LEFT JOIN student_addresses a ON s.id = a.student_id
                    WHERE 1=1";
            
            $params = [];
            
            // เพิ่มเงื่อนไขกรอง (เหมือน Excel)
            if (!empty($filters['class_group'])) {
                $sql .= " AND s.class_group = :class_group";
                $params[':class_group'] = $filters['class_group'];
            }
            
            $sql .= " ORDER BY s.student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = 'exports/' . $filename;
            
            // สร้างโฟลเดอร์ถ้าไม่มี
            if (!is_dir('exports')) {
                mkdir('exports', 0777, true);
            }
            
            $file = fopen($filepath, 'w');
            
            // เขียน BOM สำหรับ UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // หัวตาราง
            $headers = [
                'รหัสนักเรียน', 'เลขบัตรประชาชน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล',
                'เพศ', 'วันเกิด', 'อายุ', 'กลุ่มเรียน', 'สาขาวิชา', 'ระดับชั้น',
                'สถานะ', 'โทรศัพท์', 'จังหวัด', 'อำเภอ'
            ];
            
            fputcsv($file, $headers);
            
            // ข้อมูล
            foreach ($students as $student) {
                fputcsv($file, [
                    $student['student_id'],
                    $student['citizen_id'],
                    $student['title_name'],
                    $student['first_name'],
                    $student['last_name'],
                    $student['gender'],
                    $student['birth_date'],
                    $student['age'],
                    $student['class_group'],
                    $student['major'],
                    $student['class_level'],
                    $student['student_status'],
                    $student['phone_number'],
                    $student['province'],
                    $student['district']
                ]);
            }
            
            fclose($file);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'records' => count($students),
                'message' => 'Export ข้อมูลสำเร็จ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการ Export: ' . $e->getMessage()
            ];
        }
    }
    
    // สร้างรายงานสถิติ
    public function generateStatisticsReport() {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('สถิตินักเรียน');
            
            // หัวเรื่อง
            $sheet->setCellValue('A1', 'รายงานสถิตินักเรียน');
            $sheet->setCellValue('A2', 'ณ วันที่ ' . date('d/m/Y H:i:s'));
            
            $row = 4;
            
            // สถิติทั่วไป
            $sheet->setCellValue("A{$row}", 'สถิติทั่วไป');
            $row++;
            
            $stats = $this->getGeneralStatistics();
            foreach ($stats as $label => $value) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->setCellValue("B{$row}", $value);
                $row++;
            }
            
            $row += 2;
            
            // สถิติตามเพศ
            $sheet->setCellValue("A{$row}", 'สถิติตามเพศ');
            $row++;
            
            $genderStats = $this->getGenderStatistics();
            foreach ($genderStats as $gender => $count) {
                $sheet->setCellValue("A{$row}", $gender === 'ช' ? 'ชาย' : 'หญิง');
                $sheet->setCellValue("B{$row}", $count);
                $row++;
            }
            
            $row += 2;
            
            // สถิติตามสาขา
            $sheet->setCellValue("A{$row}", 'สถิติตามสาขาวิชา');
            $row++;
            
            $majorStats = $this->getMajorStatistics();
            foreach ($majorStats as $major => $count) {
                $sheet->setCellValue("A{$row}", $major);
                $sheet->setCellValue("B{$row}", $count);
                $row++;
            }
            
            // จัดรูปแบบ
            $sheet->getColumnDimension('A')->setWidth(30);
            $sheet->getColumnDimension('B')->setWidth(15);
            
            $filename = 'statistics_report_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = 'exports/' . $filename;
            
            if (!is_dir('exports')) {
                mkdir('exports', 0777, true);
            }
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'message' => 'สร้างรายงานสถิติสำเร็จ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างรายงาน: ' . $e->getMessage()
            ];
        }
    }
    
    // สถิติทั่วไป
    private function getGeneralStatistics() {
        $stats = [];
        
        // จำนวนนักเรียนทั้งหมด
        $stmt = $this->db->query("SELECT COUNT(*) FROM students");
        $stats['จำนวนนักเรียนทั้งหมด'] = $stmt->fetchColumn();
        
        // จำนวนนักเรียนที่กำลังศึกษา
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM students WHERE student_status = ?");
        $stmt->execute(['กำลังศึกษา']);
        $stats['จำนวนนักเรียนที่กำลังศึกษา'] = $stmt->fetchColumn();
        
        // จำนวนนักเรียนที่พักการศึกษา
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM students WHERE student_status = ?");
        $stmt->execute(['พักการศึกษา']);
        $stats['จำนวนนักเรียนที่พักการศึกษา'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    // สถิติตามเพศ
    private function getGenderStatistics() {
        $stmt = $this->db->query("SELECT gender, COUNT(*) as count FROM students GROUP BY gender");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [];
        foreach ($result as $row) {
            $stats[$row['gender']] = $row['count'];
        }
        
        return $stats;
    }
    
    // สถิติตามสาขา
    private function getMajorStatistics() {
        $stmt = $this->db->query("SELECT major, COUNT(*) as count FROM students WHERE major IS NOT NULL GROUP BY major ORDER BY count DESC");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [];
        foreach ($result as $row) {
            $stats[$row['major']] = $row['count'];
        }
        
        return $stats;
    }
}

// ==================== classes/BackupManager.php ====================
class BackupManager {
    private $db;
    private $backupDir = 'backups/';
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        // สร้างโฟลเดอร์ backup ถ้าไม่มี
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }
    
    // สำรองข้อมูลฐานข้อมูล
    public function createDatabaseBackup() {
        try {
            $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . $filename;
            
            // ดึงการตั้งค่าฐานข้อมูล
            $host = 'localhost'; // ควรดึงจาก config
            $dbname = 'student_db';
            $username = 'root';
            $password = '';
            
            // สร้างคำสั่ง mysqldump
            $command = "mysqldump --host={$host} --user={$username}";
            if ($password) {
                $command .= " --password={$password}";
            }
            $command .= " --single-transaction --routines --triggers {$dbname} > {$filepath}";
            
            // รันคำสั่ง
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($filepath)) {
                // บีบอัดไฟล์
                $gzFilepath = $filepath . '.gz';
                $gzFile = gzopen($gzFilepath, 'wb9');
                gzwrite($gzFile, file_get_contents($filepath));
                gzclose($gzFile);
                
                // ลบไฟล์ SQL เดิม
                unlink($filepath);
                
                return [
                    'success' => true,
                    'filename' => basename($gzFilepath),
                    'filepath' => $gzFilepath,
                    'size' => filesize($gzFilepath),
                    'message' => 'สำรองข้อมูลฐานข้อมูลสำเร็จ'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถสำรองข้อมูลฐานข้อมูลได้'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสำรองข้อมูล: ' . $e->getMessage()
            ];
        }
    }
    
    // สำรองไฟล์อัพโหลด
    public function createFilesBackup() {
        try {
            $filename = 'files_backup_' . date('Y-m-d_H-i-s') . '.zip';
            $filepath = $this->backupDir . $filename;
            
            $zip = new ZipArchive();
            
            if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างไฟล์ ZIP ได้'
                ];
            }
            
            // เพิ่มโฟลเดอร์ uploads
            if (is_dir('uploads')) {
                $this->addFolderToZip('uploads', $zip);
            }
            
            // เพิ่มโฟลเดอร์ exports
            if (is_dir('exports')) {
                $this->addFolderToZip('exports', $zip);
            }
            
            $zip->close();
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'message' => 'สำรองข้อมูลไฟล์สำเร็จ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสำรองไฟล์: ' . $e->getMessage()
            ];
        }
    }
    
    // เพิ่มโฟลเดอร์เข้าไปใน ZIP
    private function addFolderToZip($folder, &$zip, $zipPath = '') {
        $files = scandir($folder);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $folder . '/' . $file;
            $zipFilePath = $zipPath . $file;
            
            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipFilePath);
                $this->addFolderToZip($filePath, $zip, $zipFilePath . '/');
            } else {
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }
    
    // ดึงรายการไฟล์ backup
    public function getBackupList() {
        $backups = [];
        
        if (is_dir($this->backupDir)) {
            $files = scandir($this->backupDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filepath = $this->backupDir . $file;
                $backups[] = [
                    'filename' => $file,
                    'filepath' => $filepath,
                    'size' => filesize($filepath),
                    'created_at' => date('Y-m-d H:i:s', filemtime($filepath)),
                    'type' => $this->getBackupType($file)
                ];
            }
            
            // เรียงตามวันที่สร้าง (ใหม่ที่สุดก่อน)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }
        
        return $backups;
    }
    
    // กำหนดประเภทของ backup
    private function getBackupType($filename) {
        if (strpos($filename, 'database_backup') !== false) {
            return 'database';
        } elseif (strpos($filename, 'files_backup') !== false) {
            return 'files';
        } else {
            return 'unknown';
        }
    }
    
    // ลบไฟล์ backup
    public function deleteBackup($filename) {
        try {
            $filepath = $this->backupDir . $filename;
            
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบไฟล์ backup'
                ];
            }
            
            if (unlink($filepath)) {
                return [
                    'success' => true,
                    'message' => 'ลบไฟล์ backup สำเร็จ'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถลบไฟล์ backup ได้'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบไฟล์: ' . $e->getMessage()
            ];
        }
    }
    
    // ทำความสะอาด backup เก่า (เก็บแค่ 10 ไฟล์ล่าสุด)
    public function cleanupOldBackups($keepCount = 10) {
        try {
            $backups = $this->getBackupList();
            
            if (count($backups) > $keepCount) {
                $toDelete = array_slice($backups, $keepCount);
                $deletedCount = 0;
                
                foreach ($toDelete as $backup) {
                    if (unlink($backup['filepath'])) {
                        $deletedCount++;
                    }
                }
                
                return [
                    'success' => true,
                    'deleted_count' => $deletedCount,
                    'message' => "ลบไฟล์ backup เก่า {$deletedCount} ไฟล์"
                ];
            }
            
            return [
                'success' => true,
                'deleted_count' => 0,
                'message' => 'ไม่มีไฟล์ backup เก่าที่ต้องลบ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการทำความสะอาด: ' . $e->getMessage()
            ];
        }
    }
}

// ==================== export.php ====================
require_once 'includes/functions.php';
require_once 'classes/Auth.php';
require_once 'classes/ExportManager.php';
require_once 'classes/BackupManager.php';

// ตรวจสอบสิทธิ์
$auth = new Auth();
if (!$auth->checkAuth()) {
    jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'export_excel':
        $exportManager = new ExportManager();
        $filters = [
            'class_group' => $_GET['class_group'] ?? '',
            'major' => $_GET['major'] ?? '',
            'student_status' => $_GET['student_status'] ?? '',
            'province' => $_GET['province'] ?? ''
        ];
        
        $result = $exportManager->exportStudentsToExcel($filters);
        jsonResponse($result['success'], $result, $result['message']);
        break;
        
    case 'export_csv':
        $exportManager = new ExportManager();
        $filters = [
            'class_group' => $_GET['class_group'] ?? '',
            'major' => $_GET['major'] ?? '',
            'student_status' => $_GET['student_status'] ?? '',
            'province' => $_GET['province'] ?? ''
        ];
        
        $result = $exportManager->exportStudentsToCSV($filters);
        jsonResponse($result['success'], $result, $result['message']);
        break;
        
    case 'statistics_report':
        $exportManager = new ExportManager();
        $result = $exportManager->generateStatisticsReport();
        jsonResponse($result['success'], $result, $result['message']);
        break;
        
    case 'backup_database':
        if (!$auth->checkAdminAuth()) {
            jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
        }
        
        $backupManager = new BackupManager();
        $result = $backupManager->createDatabaseBackup();
        jsonResponse($result['success'], $result, $result['message']);
        break;
        
    case 'backup_files':
        if (!$auth->checkAdminAuth()) {
            jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
        }
        
        $backupManager = new BackupManager();
        $result = $backupManager->createFilesBackup();
        jsonResponse($result['success'], $result, $result['message']);
        break;
        
    case 'list_backups':
        if (!$auth->checkAdminAuth()) {
            jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
        }
        
        $backupManager = new BackupManager();
        $backups = $backupManager->getBackupList();
        jsonResponse(true, ['backups' => $backups]);
        break;
        
    case 'delete_backup':
        if (!$auth->checkAdminAuth()) {
            jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
        }
        
        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            jsonResponse(false, null, 'กรุณาระบุชื่อไฟล์', 'VALIDATION_ERROR');
        }
        
        $backupManager = new BackupManager();
        $result = $backupManager->deleteBackup($filename);
        jsonResponse($result['success'], null, $result['message']);
        break;
        
    case 'download':
        $filename = $_GET['filename'] ?? '';
        $type = $_GET['type'] ?? 'export';
        
        if (empty($filename)) {
            jsonResponse(false, null, 'กรุณาระบุชื่อไฟล์', 'VALIDATION_ERROR');
        }
        
        $basePath = $type === 'backup' ? 'backups/' : 'exports/';
        $filepath = $basePath . $filename;
        
        if (!file_exists($filepath)) {
            jsonResponse(false, null, 'ไม่พบไฟล์', 'FILE_NOT_FOUND');
        }
        
        // ส่งไฟล์ให้ download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
        break;
        
    default:
        jsonResponse(false, null, 'Action ไม่ถูกต้อง', 'INVALID_ACTION');
}
?>