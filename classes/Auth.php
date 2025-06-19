<?php
// ==================== classes/Auth.php ====================
class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // ล็อกอิน
    public function login($username, $password) {
        $sql = "SELECT id, username, email, password_hash, full_name, role, is_active 
                FROM users 
                WHERE (username = :username OR email = :username) AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // อัพเดท last_login
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([':id' => $user['id']]);
            
            // สร้าง session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];
        }
        
        return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }
    
    // ล็อกเอาท์
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'ออกจากระบบสำเร็จ'];
    }
    
    // ตรวจสอบสิทธิ์
    public function checkAuth() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // ตรวจสอบว่า user ยังมีอยู่ในระบบ
        $sql = "SELECT id FROM users WHERE id = :id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $_SESSION['user_id']]);
        
        return $stmt->fetchColumn() ? true : false;
    }
    
    // ตรวจสอบสิทธิ์ admin
    public function checkAdminAuth() {
        session_start();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    // สร้างผู้ใช้ใหม่
    public function createUser($data) {
        try {
            // ตรวจสอบข้อมูลซ้ำ
            $checkSql = "SELECT id FROM users WHERE username = :username OR email = :email";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email']
            ]);
            
            if ($checkStmt->fetchColumn()) {
                return ['success' => false, 'message' => 'ชื่อผู้ใช้หรืออีเมลซ้ำ'];
            }
            
            $sql = "INSERT INTO users (username, email, password_hash, full_name, role, created_at) 
                    VALUES (:username, :email, :password_hash, :full_name, :role, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':full_name' => $data['full_name'],
                ':role' => $data['role'] ?? 'user'
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'สร้างผู้ใช้สำเร็จ', 'user_id' => $this->db->lastInsertId()];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถสร้างผู้ใช้ได้'];
    }
    
    // เปลี่ยนรหัสผ่าน
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // ตรวจสอบรหัสผ่านเก่า
            $sql = "SELECT password_hash FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'รหัสผ่านเก่าไม่ถูกต้อง'];
            }
            
            // อัพเดทรหัสผ่านใหม่
            $updateSql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $result = $updateStmt->execute([
                ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':id' => $userId
            ]);
            
            return $result ? 
                ['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'] : 
                ['success' => false, 'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้'];
                
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    // ดึงข้อมูลผู้ใช้ทั้งหมด (admin only)
    public function getAllUsers($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        // นับจำนวนทั้งหมด
        $countSql = "SELECT COUNT(*) FROM users";
        $countStmt = $this->db->query($countSql);
        $totalRecords = $countStmt->fetchColumn();
        
        // ดึงข้อมูล
        $sql = "SELECT id, username, email, full_name, role, is_active, last_login, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $limit),
                'total_records' => $totalRecords,
                'per_page' => $limit
            ]
        ];
    }
    
    // เปิด/ปิด ผู้ใช้
    public function toggleUserStatus($userId) {
        try {
            $sql = "UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $userId]);
            
            return $result ? 
                ['success' => true, 'message' => 'อัพเดทสถานะผู้ใช้สำเร็จ'] : 
                ['success' => false, 'message' => 'ไม่สามารถอัพเดทสถานะได้'];
                
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}

// ==================== login.php ====================
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการข้อมูลนักเรียน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <h4>ระบบจัดการข้อมูลนักเรียน</h4>
                        <p class="mb-0">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
                    </div>
                    
                    <div class="login-body">
                        <form id="loginForm">
                            <div class="mb-3">
                                <label class="form-label">ชื่อผู้ใช้หรืออีเมล</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">รหัสผ่าน</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                                </button>
                            </div>
                        </form>
                        
                        <div id="loginResult" class="mt-3"></div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                ไม่มีบัญชีผู้ใช้? 
                                <a href="#" class="text-decoration-none">ติดต่อผู้ดูแลระบบ</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('loginResult');
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>เข้าสู่ระบบสำเร็จ กำลังเปลี่ยนหน้า...
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>เกิดข้อผิดพลาดในการเข้าสู่ระบบ
                    </div>
                `;
            }
        });
    </script>
</body>
</html>

<?php
// ==================== auth.php ====================
require_once 'includes/functions.php';
require_once 'classes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $auth = new Auth();
    
    switch ($action) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                jsonResponse(false, null, 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน', 'VALIDATION_ERROR');
            }
            
            $result = $auth->login($username, $password);
            jsonResponse($result['success'], $result['user'] ?? null, $result['message'] ?? '', 
                        $result['success'] ? null : 'LOGIN_FAILED');
            break;
            
        case 'logout':
            $result = $auth->logout();
            jsonResponse($result['success'], null, $result['message']);
            break;
            
        case 'change_password':
            if (!$auth->checkAuth()) {
                jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
            }
            
            session_start();
            $userId = $_SESSION['user_id'];
            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($oldPassword) || empty($newPassword)) {
                jsonResponse(false, null, 'กรุณากรอกรหัสผ่านเก่าและใหม่', 'VALIDATION_ERROR');
            }
            
            if (strlen($newPassword) < 6) {
                jsonResponse(false, null, 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร', 'VALIDATION_ERROR');
            }
            
            $result = $auth->changePassword($userId, $oldPassword, $newPassword);
            jsonResponse($result['success'], null, $result['message'], 
                        $result['success'] ? null : 'CHANGE_PASSWORD_FAILED');
            break;
            
        case 'create_user':
            if (!$auth->checkAdminAuth()) {
                jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
            }
            
            $userData = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'role' => $_POST['role'] ?? 'user'
            ];
            
            // Validation
            if (empty($userData['username']) || empty($userData['email']) || 
                empty($userData['password']) || empty($userData['full_name'])) {
                jsonResponse(false, null, 'กรุณากรอกข้อมูลให้ครบถ้วน', 'VALIDATION_ERROR');
            }
            
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                jsonResponse(false, null, 'รูปแบบอีเมลไม่ถูกต้อง', 'VALIDATION_ERROR');
            }
            
            $result = $auth->createUser($userData);
            jsonResponse($result['success'], ['user_id' => $result['user_id'] ?? null], 
                        $result['message'], $result['success'] ? null : 'CREATE_USER_FAILED');
            break;
            
        default:
            jsonResponse(false, null, 'Action ไม่ถูกต้อง', 'INVALID_ACTION');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $auth = new Auth();
    
    switch ($action) {
        case 'check':
            $isAuth = $auth->checkAuth();
            session_start();
            $userData = $isAuth ? [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'full_name' => $_SESSION['full_name']
            ] : null;
            
            jsonResponse($isAuth, $userData, $isAuth ? 'ผู้ใช้ได้รับการยืนยันแล้ว' : 'ไม่ได้รับอนุญาต');
            break;
            
        case 'users':
            if (!$auth->checkAdminAuth()) {
                jsonResponse(false, null, 'ไม่ได้รับอนุญาต', 'UNAUTHORIZED');
            }
            
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $result = $auth->getAllUsers($page, $limit);
            jsonResponse(true, $result);
            break;
            
        default:
            jsonResponse(false, null, 'Action ไม่ถูกต้อง', 'INVALID_ACTION');
    }
} else {
    jsonResponse(false, null, 'Method ไม่ได้รับอนุญาต', 'METHOD_NOT_ALLOWED');
}
?>