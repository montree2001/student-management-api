<?php
require_once '../config/database.php';
require_once '../models/Student.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);

// สร้างตารางถ้ายังไม่มี
$student->createTable();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 20;

if (!empty($search)) {
    $stmt = $student->search($search);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($students);
} else {
    $stmt = $student->read($page, $limit);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = $student->count();
}

$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลนักเรียน - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            border-radius: 8px;
            margin: 2px 0;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white !important;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
        }
        .table th {
            background: #667eea;
            color: white;
            border: none;
        }
        .badge {
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white"><i class="fas fa-graduation-cap"></i> Student Admin</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="import.php">
                            <i class="fas fa-file-upload"></i> นำเข้าข้อมูล Excel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="api_doc.php">
                            <i class="fas fa-code"></i> เอกสาร API
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../api/students.php?request=count" target="_blank">
                            <i class="fas fa-chart-bar"></i> สถิติข้อมูล
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> จัดการข้อมูลนักเรียน</h2>
                    <div>
                        <a href="import.php" class="btn btn-success me-2">
                            <i class="fas fa-file-import"></i> นำเข้าข้อมูล
                        </a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus"></i> เพิ่มนักเรียน
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control" name="search" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="ค้นหาด้วย เลขประชาชน, รหัสนักเรียน, ชื่อ, นามสกุล หรือเบอร์โทร">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4><?= number_format($total) ?></h4>
                                <p class="text-muted">นักเรียนทั้งหมด</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-male fa-2x text-info mb-2"></i>
                                <h4><?= count(array_filter($students, function($s) { return $s['gender'] == 'ช'; })) ?></h4>
                                <p class="text-muted">นักเรียนชาย</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-female fa-2x text-danger mb-2"></i>
                                <h4><?= count(array_filter($students, function($s) { return $s['gender'] == 'ญ'; })) ?></h4>
                                <p class="text-muted">นักเรียนหญิง</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-graduation-cap fa-2x text-success mb-2"></i>
                                <h4><?= count(array_filter($students, function($s) { return $s['student_status'] == 'กำลังศึกษา'; })) ?></h4>
                                <p class="text-muted">กำลังศึกษา</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>รหัสนักเรียน</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>เพศ</th>
                                        <th>กลุ่มเรียน</th>
                                        <th>สาขาวิชา</th>
                                        <th>สถานะ</th>
                                        <th>เบอร์โทร</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['student_code']) ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($s['title_name']) ?> 
                                            <?= htmlspecialchars($s['first_name']) ?> 
                                            <?= htmlspecialchars($s['last_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $s['gender'] == 'ช' ? 'bg-primary' : 'bg-danger' ?>">
                                                <?= $s['gender'] == 'ช' ? 'ชาย' : 'หญิง' ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($s['class_group']) ?></td>
                                        <td><?= htmlspecialchars($s['major']) ?></td>
                                        <td>
                                            <span class="badge <?= $s['student_status'] == 'กำลังศึกษา' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= htmlspecialchars($s['student_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($s['phone']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewStudent(<?= $s['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editStudent(<?= $s['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?= $s['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1 && empty($search)): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มนักเรียนใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">เลขประจำตัวประชาชน *</label>
                                    <input type="text" class="form-control" name="national_id" required maxlength="13">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">รหัสประจำตัวนักเรียน *</label>
                                    <input type="text" class="form-control" name="student_code" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">คำนำหน้า</label>
                                    <select class="form-control" name="title_name">
                                        <option value="นาย">นาย</option>
                                        <option value="นางสาว">นางสาว</option>
                                        <option value="นาง">นาง</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อ *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">นามสกุล *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">เพศ</label>
                                    <select class="form-control" name="gender">
                                        <option value="ช">ชาย</option>
                                        <option value="ญ">หญิง</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">กลุ่มเรียน</label>
                                    <input type="text" class="form-control" name="class_group">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">สาขาวิชา</label>
                                    <input type="text" class="form-control" name="major">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add student form submission
        document.getElementById('addForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('../api/students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('เพิ่มนักเรียนสำเร็จ');
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            })
            .catch(error => {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            });
        });

        function viewStudent(id) {
            fetch(`../api/students.php?id=${id}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const student = result.data;
                    let info = `
                        <strong>ข้อมูลนักเรียน</strong><br>
                        รหัส: ${student.student_code}<br>
                        ชื่อ: ${student.title_name} ${student.first_name} ${student.last_name}<br>
                        เลขประชาชน: ${student.national_id}<br>
                        เพศ: ${student.gender == 'ช' ? 'ชาย' : 'หญิง'}<br>
                        กลุ่มเรียน: ${student.class_group || '-'}<br>
                        สาขาวิชา: ${student.major || '-'}<br>
                        เบอร์โทร: ${student.phone || '-'}<br>
                        สถานะ: ${student.student_status || '-'}
                    `;
                    
                    const modal = new bootstrap.Modal(document.createElement('div'));
                    document.body.insertAdjacentHTML('beforeend', `
                        <div class="modal fade" id="viewModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">ข้อมูลนักเรียน</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">${info}</div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                }
            });
        }

        function editStudent(id) {
            // Implementation for edit student
            alert('ฟีเจอร์แก้ไขจะพัฒนาในขั้นตอนต่อไป');
        }

        function deleteStudent(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูลนักเรียนนี้?')) {
                fetch(`../api/students.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('ลบข้อมูลสำเร็จ');
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + result.message);
                    }
                });
            }
        }
    </script>
</body>
</html>