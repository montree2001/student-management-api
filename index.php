<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลนักเรียน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .upload-area.dragover {
            border-color: #667eea;
            background-color: #f0f4ff;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
        }
        .search-box {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-white mb-4">
                    <i class="fas fa-graduation-cap"></i> ระบบจัดการนักเรียน
                </h4>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt me-2"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#students" onclick="showSection('students')">
                            <i class="fas fa-users me-2"></i> รายชื่อนักเรียน
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#upload" onclick="showSection('upload')">
                            <i class="fas fa-upload me-2"></i> นำเข้าข้อมูล
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="#api-docs" onclick="showSection('api-docs')">
                            <i class="fas fa-code me-2"></i> API Documentation
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Dashboard Section -->
                <div id="dashboard" class="section">
                    <h2 class="mb-4">แดชบอร์ด</h2>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h3 class="mb-0" id="total-students">0</h3>
                                    <p class="mb-0">นักเรียนทั้งหมด</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-check fa-2x mb-2"></i>
                                    <h3 class="mb-0" id="active-students">0</h3>
                                    <p class="mb-0">กำลังศึกษา</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-mars fa-2x mb-2"></i>
                                    <h3 class="mb-0" id="male-students">0</h3>
                                    <p class="mb-0">นักเรียนชาย</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-venus fa-2x mb-2"></i>
                                    <h3 class="mb-0" id="female-students">0</h3>
                                    <p class="mb-0">นักเรียนหญิง</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>กิจกรรมล่าสุด</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush" id="recent-activities">
                                <div class="list-group-item">
                                    <i class="fas fa-upload text-success me-2"></i>
                                    นำเข้าข้อมูลจากไฟล์ Excel สำเร็จ 1,374 รายการ
                                    <small class="text-muted float-end">2 ชั่วโมงที่แล้ว</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Section -->
                <div id="students" class="section" style="display: none;">
                    <h2 class="mb-4">รายชื่อนักเรียน</h2>
                    
                    <!-- Search and Filter -->
                    <div class="search-box">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="search-input" placeholder="ค้นหา ชื่อ, รหัส, เลขบัตรประชาชน">
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="class-filter">
                                    <option value="">ทุกชั้นเรียน</option>
                                    <option value="ปวช.1">ปวช.1</option>
                                    <option value="ปวช.2">ปวช.2</option>
                                    <option value="ปวช.3">ปวช.3</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="major-filter">
                                    <option value="">ทุกสาขา</option>
                                    <option value="ช่างยนต์">ช่างยนต์</option>
                                    <option value="ช่างไฟฟ้า">ช่างไฟฟ้า</option>
                                    <option value="คอมพิวเตอร์">คอมพิวเตอร์</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="status-filter">
                                    <option value="">ทุกสถานะ</option>
                                    <option value="กำลังศึกษา">กำลังศึกษา</option>
                                    <option value="พักการศึกษา">พักการศึกษา</option>
                                    <option value="จบการศึกษา">จบการศึกษา</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary me-2" onclick="searchStudents()">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                                <button class="btn btn-success" onclick="showAddStudentModal()">
                                    <i class="fas fa-plus"></i> เพิ่มนักเรียน
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i>รายชื่อนักเรียน</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>รหัสนักเรียน</th>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th>เพศ</th>
                                            <th>ชั้นเรียน</th>
                                            <th>สาขา</th>
                                            <th>โทรศัพท์</th>
                                            <th>สถานะ</th>
                                            <th>การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-table-body">
                                        <!-- Students data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center" id="pagination">
                                    <!-- Pagination will be generated here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div id="upload" class="section" style="display: none;">
                    <h2 class="mb-4">นำเข้าข้อมูลจากไฟล์ Excel</h2>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>อัพโหลดไฟล์</h5>
                                </div>
                                <div class="card-body">
                                    <div class="upload-area" id="upload-area">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h5>ลากไฟล์มาวางที่นี่</h5>
                                        <p class="text-muted">หรือคลิกเพื่อเลือกไฟล์ Excel (.xlsx, .xls)</p>
                                        <input type="file" id="excel-file" accept=".xlsx,.xls" style="display: none;">
                                        <button class="btn btn-primary" onclick="document.getElementById('excel-file').click()">
                                            <i class="fas fa-folder-open me-2"></i>เลือกไฟล์
                                        </button>
                                    </div>
                                    
                                    <div id="upload-progress" style="display: none;">
                                        <div class="progress mt-3">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <p class="text-center mt-2">กำลังประมวลผลไฟล์...</p>
                                    </div>
                                    
                                    <div id="upload-result" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>คำแนะนำ</h5>
                                </div>
                                <div class="card-body">
                                    <h6>รูปแบบไฟล์ที่รองรับ:</h6>
                                    <ul>
                                        <li>Microsoft Excel (.xlsx)</li>
                                        <li>Microsoft Excel 97-2003 (.xls)</li>
                                    </ul>
                                    
                                    <h6 class="mt-3">โครงสร้างไฟล์:</h6>
                                    <ul>
                                        <li>แถวแรกต้องเป็น Header</li>
                                        <li>ข้อมูลเริ่มจากแถวที่ 2</li>
                                        <li>คอลัมน์ตามรูปแบบ ศธ.02</li>
                                    </ul>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <small>กรุณาตรวจสอบข้อมูลให้ถูกต้องก่อนนำเข้า</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Documentation Section -->
                <div id="api-docs" class="section" style="display: none;">
                    <h2 class="mb-4">API Documentation</h2>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-code me-2"></i>Student Management API</h5>
                        </div>
                        <div class="card-body">
                            <p>Base URL: <code>https://your-domain.com/api/</code></p>
                            
                            <h6 class="mt-4">การใช้งาน API หลัก:</h6>
                            
                            <div class="accordion" id="api-accordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#students-api">
                                            GET /students - ดึงรายชื่อนักเรียน
                                        </button>
                                    </h2>
                                    <div id="students-api" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <p><strong>Parameters:</strong></p>
                                            <ul>
                                                <li><code>page</code> - หมายเลขหน้า (default: 1)</li>
                                                <li><code>limit</code> - จำนวนรายการต่อหน้า (default: 20)</li>
                                                <li><code>search</code> - คำค้นหา</li>
                                                <li><code>class</code> - กรองตามชั้นเรียน</li>
                                                <li><code>major</code> - กรองตามสาขา</li>
                                            </ul>
                                            
                                            <p><strong>Example:</strong></p>
                                            <pre><code>GET /api/students?page=1&limit=20&search=กนก&class=ปวช.1</code></pre>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#student-detail-api">
                                            GET /students/{id} - ดึงข้อมูลนักเรียนรายคน
                                        </button>
                                    </h2>
                                    <div id="student-detail-api" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <p>ดึงข้อมูลนักเรียนครบถ้วน รวมที่อยู่และข้อมูลผู้ปกครอง</p>
                                            <p><strong>Example:</strong></p>
                                            <pre><code>GET /api/students/1</code></pre>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#upload-api">
                                            POST /upload - นำเข้าข้อมูลจากไฟล์ Excel
                                        </button>
                                    </h2>
                                    <div id="upload-api" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <p><strong>Content-Type:</strong> multipart/form-data</p>
                                            <p><strong>Field:</strong> <code>file</code> - ไฟล์ Excel</p>
                                            
                                            <p><strong>Example using cURL:</strong></p>
                                            <pre><code>curl -X POST \
  -F "file=@students.xlsx" \
  http://your-domain.com/api/upload</code></pre>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#stats-api">
                                            GET /stats - ดึงสถิติ
                                        </button>
                                    </h2>
                                    <div id="stats-api" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <p>ดึงสถิติภาพรวมของนักเรียน</p>
                                            <p><strong>Example:</strong></p>
                                            <pre><code>GET /api/stats</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Detail Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ข้อมูลนักเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="student-modal-body">
                    <!-- Student details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        let currentFilters = {};

        // Show/Hide sections
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';
            
            // Update active nav
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Load section-specific data
            if (sectionId === 'dashboard') {
                loadStats();
            } else if (sectionId === 'students') {
                loadStudents();
            }
        }

        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch('/api/stats');
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.data;
                    document.getElementById('total-students').textContent = stats.total_students || 0;
                    document.getElementById('active-students').textContent = stats.active_students || 0;
                    document.getElementById('male-students').textContent = stats.by_gender['ช'] || 0;
                    document.getElementById('female-students').textContent = stats.by_gender['ญ'] || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load students
        async function loadStudents(page = 1) {
            const params = new URLSearchParams({
                page: page,
                limit: 20,
                ...currentFilters
            });
            
            try {
                const response = await fetch(`/api/students?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    displayStudents(result.data.students);
                    displayPagination(result.data.pagination);
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }

        // Display students in table
        function displayStudents(students) {
            const tbody = document.getElementById('students-table-body');
            tbody.innerHTML = '';
            
            students.forEach(student => {
                const row = `
                    <tr>
                        <td>${student.student_id}</td>
                        <td>${student.first_name} ${student.last_name}</td>
                        <td>${student.gender === 'ช' ? 'ชาย' : 'หญิง'}</td>
                        <td>${student.class_group || '-'}</td>
                        <td>${student.major || '-'}</td>
                        <td>${student.phone_number || '-'}</td>
                        <td>
                            <span class="badge ${student.student_status === 'กำลังศึกษา' ? 'bg-success' : 'bg-secondary'}">
                                ${student.student_status || '-'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewStudent('${student.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="editStudent('${student.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteStudent('${student.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Search students
        function searchStudents() {
            currentFilters = {
                search: document.getElementById('search-input').value,
                class: document.getElementById('class-filter').value,
                major: document.getElementById('major-filter').value,
                status: document.getElementById('status-filter').value
            };
            loadStudents(1);
        }

        // File upload handling
        document.getElementById('excel-file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadFile(file);
            }
        });

        // Drag and drop
        const uploadArea = document.getElementById('upload-area');
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) {
                uploadFile(file);
            }
        });

        // Upload file
        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            
            const progressDiv = document.getElementById('upload-progress');
            const resultDiv = document.getElementById('upload-result');
            
            progressDiv.style.display = 'block';
            resultDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/upload', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                progressDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>นำเข้าข้อมูลสำเร็จ!</h6>
                            <p class="mb-1">จำนวนข้อมูลทั้งหมด: ${result.data.total_records}</p>
                            <p class="mb-1">นำเข้าสำเร็จ: ${result.data.success_records}</p>
                            <p class="mb-0">ล้มเหลว: ${result.data.failed_records}</p>
                        </div>
                    `;
                    loadStats(); // Refresh stats
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>เกิดข้อผิดพลาด!</h6>
                            <p class="mb-0">${result.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                progressDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>เกิดข้อผิดพลาด!</h6>
                        <p class="mb-0">ไม่สามารถอัพโหลดไฟล์ได้</p>
                    </div>
                `;
                console.error('Upload error:', error);
            }
        }

        // View student details
        async function viewStudent(studentId) {
            try {
                const response = await fetch(`/api/students/${studentId}`);
                const result = await response.json();
                
                if (result.success) {
                    const student = result.data.student;
                    const address = result.data.address;
                    const parents = result.data.parents;
                    
                    const modalBody = document.getElementById('student-modal-body');
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>ข้อมูลส่วนตัว</h6>
                                <p><strong>รหัสนักเรียน:</strong> ${student.student_id}</p>
                                <p><strong>เลขบัตรประชาชน:</strong> ${student.citizen_id || '-'}</p>
                                <p><strong>ชื่อ-นามสกุล:</strong> ${student.title_name || ''} ${student.first_name} ${student.last_name}</p>
                                <p><strong>เพศ:</strong> ${student.gender === 'ช' ? 'ชาย' : 'หญิง'}</p>
                                <p><strong>โทรศัพท์:</strong> ${student.phone_number || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>ข้อมูลการศึกษา</h6>
                                <p><strong>กลุ่มเรียน:</strong> ${student.class_group || '-'}</p>
                                <p><strong>สาขาวิชา:</strong> ${student.major || '-'}</p>
                                <p><strong>สถานะ:</strong> ${student.student_status || '-'}</p>
                            </div>
                        </div>
                        ${address ? `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>ที่อยู่</h6>
                                    <p>${address.house_number || ''} หมู่ ${address.village_no || ''} 
                                       ตำบล${address.subdistrict || ''} อำเภอ${address.district || ''} 
                                       จังหวัด${address.province || ''} ${address.postal_code || ''}</p>
                                </div>
                            </div>
                        ` : ''}
                    `;
                    
                    new bootstrap.Modal(document.getElementById('studentModal')).show();
                }
            } catch (error) {
                console.error('Error loading student details:', error);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
        });
    </script>
</body>
</html>