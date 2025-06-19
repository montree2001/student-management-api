<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

include_once '../config/database.php';
include_once '../models/Student.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);

$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['request']) ? $_GET['request'] : '';

switch ($method) {
    case 'GET':
        handleGetRequest($student, $request);
        break;
    case 'POST':
        handlePostRequest($student);
        break;
    case 'PUT':
        handlePutRequest($student);
        break;
    case 'DELETE':
        handleDeleteRequest($student);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}

function handleGetRequest($student, $request) {
    switch ($request) {
        case 'all':
            // GET /api/students.php?request=all&page=1&limit=50
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            
            $stmt = $student->read($page, $limit);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = $student->count();
            
            echo json_encode([
                'success' => true,
                'data' => $students,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'search':
            // GET /api/students.php?request=search&q=searchterm
            $searchTerm = isset($_GET['q']) ? $_GET['q'] : '';
            if (empty($searchTerm)) {
                echo json_encode(['success' => false, 'message' => 'Search term required']);
                return;
            }
            
            $stmt = $student->search($searchTerm);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $students,
                'count' => count($students)
            ]);
            break;
            
        case 'by_national_id':
            // GET /api/students.php?request=by_national_id&national_id=1234567890123
            $nationalId = isset($_GET['national_id']) ? $_GET['national_id'] : '';
            if (empty($nationalId)) {
                echo json_encode(['success' => false, 'message' => 'National ID required']);
                return;
            }
            
            $studentData = $student->getByNationalId($nationalId);
            if ($studentData) {
                echo json_encode(['success' => true, 'data' => $studentData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
            break;
            
        case 'by_student_code':
            // GET /api/students.php?request=by_student_code&student_code=68201010001
            $studentCode = isset($_GET['student_code']) ? $_GET['student_code'] : '';
            if (empty($studentCode)) {
                echo json_encode(['success' => false, 'message' => 'Student code required']);
                return;
            }
            
            $studentData = $student->getByStudentCode($studentCode);
            if ($studentData) {
                echo json_encode(['success' => true, 'data' => $studentData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
            break;
            
        case 'count':
            // GET /api/students.php?request=count
            $total = $student->count();
            echo json_encode(['success' => true, 'total' => $total]);
            break;
            
        default:
            // GET /api/students.php?id=1
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id > 0) {
                $studentData = $student->readOne($id);
                if ($studentData) {
                    echo json_encode(['success' => true, 'data' => $studentData]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                }
            } else {
                // Default: get all students
                $stmt = $student->read();
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $students]);
            }
            break;
    }
}

function handlePostRequest($student) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    $required = ['national_id', 'student_code', 'first_name', 'last_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Field {$field} is required"]);
            return;
        }
    }
    
    try {
        if ($student->create($data)) {
            echo json_encode(['success' => true, 'message' => 'Student created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create student']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handlePutRequest($student) {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        return;
    }
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    try {
        if ($student->update($id, $data)) {
            echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update student']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleDeleteRequest($student) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id === 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        return;
    }
    
    try {
        if ($student->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>