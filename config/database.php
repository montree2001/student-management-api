<?php
// ==================== config/database.php ====================
class Database {
    private $host = 'localhost';
    private $dbname = 'student_db';
    private $username = 'root';
    private $password = '';
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// ==================== includes/functions.php ====================

// ฟังก์ชันสำหรับการตอบกลับ JSON
function jsonResponse($success, $data = null, $message = '', $errorCode = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'error_code' => $errorCode
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ฟังก์ชันแปลงวันที่ไทยเป็น MySQL Date
function parseThaiDate($thaiDate) {
    if (empty($thaiDate)) return null;
    
    $thaiMonths = [
        'มกราคม' => '01', 'กุมภาพันธ์' => '02', 'มีนาคม' => '03',
        'เมษายน' => '04', 'พฤษภาคม' => '05', 'มิถุนายน' => '06',
        'กรกฎาคม' => '07', 'สิงหาคม' => '08', 'กันยายน' => '09',
        'ตุลาคม' => '10', 'พฤศจิกายน' => '11', 'ธันวาคม' => '12'
    ];
    
    $parts = explode(' ', $thaiDate);
    if (count($parts) >= 3) {
        $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $month = $thaiMonths[$parts[1]] ?? null;
        $year = intval($parts[2]) - 543; // แปลง พ.ศ. เป็น ค.ศ.
        
        if ($month && $year) {
            return "$year-$month-$day";
        }
    }
    
    return null;
}

// ฟังก์ชันตรวจสอบเลขบัตรประชาชน
function validateCitizenId($citizenId) {
    if (strlen($citizenId) != 13) return false;
    
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += intval($citizenId[$i]) * (13 - $i);
    }
    
    $checkDigit = (11 - ($sum % 11)) % 10;
    return $checkDigit == intval($citizenId[12]);
}

// ==================== classes/Student.php ====================
class Student {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // ดึงรายการนักเรียนทั้งหมด
    public function getAll($page = 1, $limit = 20, $search = '', $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT s.*, a.province, a.district 
                FROM students s 
                LEFT JOIN student_addresses a ON s.id = a.student_id 
                WHERE 1=1";
        
        $params = [];
        
        // เงื่อนไขการค้นหา
        if (!empty($search)) {
            $sql .= " AND (s.first_name LIKE :search 
                          OR s.last_name LIKE :search 
                          OR s.student_id LIKE :search 
                          OR s.citizen_id LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($filters['class_group'])) {
            $sql .= " AND s.class_group = :class_group";
            $params[':class_group'] = $filters['class_group'];
        }
        
        if (!empty($filters['major'])) {
            $sql .= " AND s.major LIKE :major";
            $params[':major'] = "%{$filters['major']}%";
        }
        
        if (!empty($filters['gender'])) {
            $sql .= " AND s.gender = :gender";
            $params[':gender'] = $filters['gender'];
        }
        
        if (!empty($filters['student_status'])) {
            $sql .= " AND s.student_status = :student_status";
            $params[':student_status'] = $filters['student_status'];
        }
        
        // นับจำนวนทั้งหมด
        $countSql = str_replace("SELECT s.*, a.province, a.district", "SELECT COUNT(*)", $sql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
        
        // เพิ่ม LIMIT และ OFFSET
        $sql .= " ORDER BY s.first_name ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'students' => $students,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $limit),
                'total_records' => $totalRecords,
                'per_page' => $limit
            ]
        ];
    }
    
    // ดึงข้อมูลนักเรียนรายคน
    public function getById($id) {
        $sql = "SELECT * FROM students WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) return null;
        
        // ดึงข้อมูลที่อยู่
        $addressSql = "SELECT * FROM student_addresses WHERE student_id = :student_id";
        $addressStmt = $this->db->prepare($addressSql);
        $addressStmt->execute([':student_id' => $id]);
        $address = $addressStmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลผู้ปกครอง
        $parentsSql = "SELECT * FROM student_parents WHERE student_id = :student_id";
        $parentsStmt = $this->db->prepare($parentsSql);
        $parentsStmt->execute([':student_id' => $id]);
        $parents = $parentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'student' => $student,
            'address' => $address,
            'parents' => $parents
        ];
    }
    
    // ค้นหาด้วยเลขบัตรประชาชน
    public function getByCitizenId($citizenId) {
        $sql = "SELECT * FROM students WHERE citizen_id = :citizen_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':citizen_id' => $citizenId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ค้นหาด้วยรหัสนักเรียน
    public function getByStudentId($studentId) {
        $sql = "SELECT * FROM students WHERE student_id = :student_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // เพิ่มนักเรียนใหม่
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // เพิ่มข้อมูลนักเรียน
            $sql = "INSERT INTO students (
                student_id, citizen_id, class_group, title_name, first_name, last_name,
                nickname, gender, birth_date, age, student_type, subject_type, major,
                disability_type, nationality, ethnicity, religion, height, weight,
                disadvantaged_status, phone_number, class_level, enrollment_date,
                enrollment_year, enrollment_term, student_status, education_format,
                verification_status, created_at
            ) VALUES (
                :student_id, :citizen_id, :class_group, :title_name, :first_name, :last_name,
                :nickname, :gender, :birth_date, :age, :student_type, :subject_type, :major,
                :disability_type, :nationality, :ethnicity, :religion, :height, :weight,
                :disadvantaged_status, :phone_number, :class_level, :enrollment_date,
                :enrollment_year, :enrollment_term, :student_status, :education_format,
                :verification_status, NOW()
            )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $studentId = $this->db->lastInsertId();
            
            $this->db->commit();
            return $studentId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // อัพเดทข้อมูลนักเรียน
    public function update($id, $data) {
        $sql = "UPDATE students SET 
                first_name = :first_name,
                last_name = :last_name,
                phone_number = :phone_number,
                student_status = :student_status,
                updated_at = NOW()
                WHERE id = :id";
        
        $data[':id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    // ลบนักเรียน
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // ลบข้อมูลที่เกี่ยวข้อง
            $this->db->prepare("DELETE FROM student_addresses WHERE student_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM student_parents WHERE student_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // นำเข้าข้อมูลจาก Excel
    public function importFromExcel($excelData) {
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($excelData as $rowIndex => $row) {
                try {
                    // ตรวจสอบข้อมูลซ้ำ
                    if ($this->getByCitizenId($row['citizen_id']) || $this->getByStudentId($row['student_id'])) {
                        $failedCount++;
                        $errors[] = [
                            'row' => $rowIndex + 2,
                            'error' => 'ข้อมูลซ้ำ: รหัสนักเรียนหรือเลขบัตรประชาชนมีอยู่แล้ว'
                        ];
                        continue;
                    }
                    
                    // ตรวจสอบเลขบัตรประชาชน
                    if (!empty($row['citizen_id']) && !validateCitizenId($row['citizen_id'])) {
                        $failedCount++;
                        $errors[] = [
                            'row' => $rowIndex + 2,
                            'error' => 'เลขบัตรประชาชนไม่ถูกต้อง'
                        ];
                        continue;
                    }
                    
                    $this->create($row);
                    $successCount++;
                    
                } catch (Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowIndex + 2,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $this->db->commit();
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        
        return [
            'total_records' => count($excelData),
            'success_records' => $successCount,
            'failed_records' => $failedCount,
            'errors' => array_slice($errors, 0, 10) // แสดงแค่ 10 errors แรก
        ];
    }
    
    // สถิติภาพรวม
    public function getStatistics() {
        $stats = [];
        
        // จำนวนนักเรียนทั้งหมด
        $stmt = $this->db->query("SELECT COUNT(*) FROM students");
        $stats['total_students'] = $stmt->fetchColumn();
        
        // จำนวนนักเรียนที่กำลังศึกษา
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM students WHERE student_status = ?");
        $stmt->execute(['กำลังศึกษา']);
        $stats['active_students'] = $stmt->fetchColumn();
        
        // สถิติตามเพศ
        $stmt = $this->db->query("SELECT gender, COUNT(*) as count FROM students GROUP BY gender");
        $genderStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['by_gender'] = [];
        foreach ($genderStats as $row) {
            $stats['by_gender'][$row['gender']] = $row['count'];
        }
        
        // สถิติตามระดับชั้น
        $stmt = $this->db->query("SELECT class_level, COUNT(*) as count FROM students GROUP BY class_level");
        $classStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['by_class_level'] = [];
        foreach ($classStats as $row) {
            $stats['by_class_level'][$row['class_level']] = $row['count'];
        }
        
        return $stats;
    }
}

// ==================== classes/ExcelReader.php ====================
require_once 'vendor/autoload.php'; // สำหรับ PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader {
    
    public function readStudentData($filePath) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();
            
            // ลบ header row
            array_shift($data);
            
            $students = [];
            foreach ($data as $rowIndex => $row) {
                if (empty($row[1]) && empty($row[2])) continue; // ข้าม row ว่าง
                
                $students[] = [
                    'student_id' => $row[2] ?? '',
                    'citizen_id' => $row[1] ?? '',
                    'class_group' => $row[3] ?? '',
                    'title_name' => $row[4] ?? '',
                    'first_name' => $row[5] ?? '',
                    'last_name' => $row[6] ?? '',
                    'gender' => $row[7] ?? '',
                    'nickname' => $row[8] ?? '',
                    'birth_date' => parseThaiDate($row[9] ?? ''),
                    'age' => $row[10] ?? '',
                    'student_type' => $row[11] ?? '',
                    'subject_type' => $row[12] ?? '',
                    'major' => $row[13] ?? '',
                    'disability_type' => $row[14] ?? '',
                    'nationality' => $row[15] ?? '',
                    'ethnicity' => $row[16] ?? '',
                    'religion' => $row[17] ?? '',
                    'height' => !empty($row[18]) ? floatval($row[18]) : null,
                    'weight' => !empty($row[19]) ? floatval($row[19]) : null,
                    'disadvantaged_status' => $row[20] ?? '',
                    'phone_number' => $row[21] ?? '',
                    'class_level' => $row[22] ?? '',
                    'enrollment_date' => parseThaiDate($row[23] ?? ''),
                    'enrollment_year' => !empty($row[24]) ? intval($row[24]) : null,
                    'enrollment_term' => !empty($row[25]) ? intval($row[25]) : null,
                    'student_status' => $row[26] ?? '',
                    'education_format' => $row[27] ?? '',
                    'verification_status' => $row[36] ?? ''
                ];
            }
            
            return $students;
            
        } catch (Exception $e) {
            throw new Exception("Error reading Excel file: " . $e->getMessage());
        }
    }
}

// ==================== API Routes ====================

// api.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'includes/functions.php';
require_once 'classes/Student.php';
require_once 'classes/ExcelReader.php';

$student = new Student();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// API Routes
switch ($method) {
    case 'GET':
        if ($pathParts[1] == 'students') {
            if (isset($pathParts[2])) {
                // GET /api/students/{id}
                $result = $student->getById($pathParts[2]);
                if ($result) {
                    jsonResponse(true, $result);
                } else {
                    jsonResponse(false, null, 'ไม่พบข้อมูลนักเรียน', 'NOT_FOUND');
                }
            } else {
                // GET /api/students
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $search = $_GET['search'] ?? '';
                $filters = [
                    'class_group' => $_GET['class'] ?? '',
                    'major' => $_GET['major'] ?? '',
                    'gender' => $_GET['gender'] ?? '',
                    'student_status' => $_GET['status'] ?? ''
                ];
                
                $result = $student->getAll($page, $limit, $search, $filters);
                jsonResponse(true, $result);
            }
        } elseif ($pathParts[1] == 'stats') {
            // GET /api/stats
            $stats = $student->getStatistics();
            jsonResponse(true, $stats);
        }
        break;
        
    case 'POST':
        if ($pathParts[1] == 'upload') {
            // POST /api/upload
            if (!isset($_FILES['file'])) {
                jsonResponse(false, null, 'กรุณาเลือกไฟล์', 'NO_FILE');
            }
            
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filePath = $uploadDir . uniqid() . '_' . $_FILES['file']['name'];
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                try {
                    $excelReader = new ExcelReader();
                    $excelData = $excelReader->readStudentData($filePath);
                    $result = $student->importFromExcel($excelData);
                    
                    // ลบไฟล์หลังจากประมวลผลเสร็จ
                    unlink($filePath);
                    
                    jsonResponse(true, $result, 'นำเข้าข้อมูลเสร็จสิ้น');
                    
                } catch (Exception $e) {
                    unlink($filePath);
                    jsonResponse(false, null, 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage(), 'IMPORT_ERROR');
                }
            } else {
                jsonResponse(false, null, 'ไม่สามารถอัพโหลดไฟล์ได้', 'UPLOAD_ERROR');
            }
        } elseif ($pathParts[1] == 'students') {
            // POST /api/students (เพิ่มนักเรียนใหม่)
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $studentId = $student->create($data);
                jsonResponse(true, ['id' => $studentId], 'เพิ่มนักเรียนสำเร็จ');
            } catch (Exception $e) {
                jsonResponse(false, null, 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage(), 'CREATE_ERROR');
            }
        }
        break;
        
    case 'PUT':
        if ($pathParts[1] == 'students' && isset($pathParts[2])) {
            // PUT /api/students/{id}
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $success = $student->update($pathParts[2], $data);
                if ($success) {
                    jsonResponse(true, null, 'อัพเดทข้อมูลสำเร็จ');
                } else {
                    jsonResponse(false, null, 'ไม่สามารถอัพเดทข้อมูลได้', 'UPDATE_ERROR');
                }
            } catch (Exception $e) {
                jsonResponse(false, null, 'เกิดข้อผิดพลาดในการอัพเดท: ' . $e->getMessage(), 'UPDATE_ERROR');
            }
        }
        break;
        
    case 'DELETE':
        if ($pathParts[1] == 'students' && isset($pathParts[2])) {
            // DELETE /api/students/{id}
            try {
                $success = $student->delete($pathParts[2]);
                if ($success) {
                    jsonResponse(true, null, 'ลบข้อมูลสำเร็จ');
                } else {
                    jsonResponse(false, null, 'ไม่สามารถลบข้อมูลได้', 'DELETE_ERROR');
                }
            } catch (Exception $e) {
                jsonResponse(false, null, 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage(), 'DELETE_ERROR');
            }
        }
        break;
        
    default:
        jsonResponse(false, null, 'Method not allowed', 'METHOD_NOT_ALLOWED');
        break;
}
?>