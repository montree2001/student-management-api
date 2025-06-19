<?php
// admin/import.php
require_once '../config/database.php';
require_once '../models/Student.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        require_once '../vendor/autoload.php'; // สำหรับ PhpSpreadsheet
        
        $inputFileName = $_FILES['excel_file']['tmp_name'];
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // ข้ามแถวหัวตาราง
        array_shift($rows);
        
        $students = [];
        $errors = [];
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 เพราะ array เริ่มจาก 0 และมีแถวหัวตาราง
            
            // ตรวจสอบว่าแถวไม่ว่างเปล่า
            if (empty(array_filter($row))) continue;
            
            try {
                // แปลงวันที่เกิด
                $birthDate = null;
                if (!empty($row[9])) {
                    $birthDateStr = $row[9];
                    if (is_numeric($birthDateStr)) {
                        // Excel date format
                        $birthDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($birthDateStr)->format('Y-m-d');
                    } else {
                        // Thai date format
                        $birthDate = convertThaiDate($birthDateStr);
                    }
                }
                
                // แปลงวันที่เข้าเรียน
                $enrollmentDate = null;
                if (!empty($row[23])) {
                    $enrollmentDateStr = $row[23];
                    if (is_numeric($enrollmentDateStr)) {
                        $enrollmentDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($enrollmentDateStr)->format('Y-m-d');
                    } else {
                        $enrollmentDate = convertThaiDate($enrollmentDateStr);
                    }
                }
                
                $studentData = [
                    $row[1],  // national_id
                    $row[2],  // student_code
                    $row[3],  // class_group
                    $row[4],  // title_name
                    $row[5],  // first_name
                    $row[6],  // last_name
                    $row[7],  // gender
                    $row[8],  // nickname
                    $birthDate, // birth_date
                    $row[10], // age
                    $row[11], // student_type
                    $row[12], // subject_type
                    $row[13], // major
                    $row[14], // disability
                    $row[15], // nationality
                    $row[16], // race
                    $row[17], // religion
                    is_numeric($row[18]) ? (int)$row[18] : null, // height
                    is_numeric($row[19]) ? (int)$row[19] : null, // weight
                    $row[20], // disadvantaged
                    $row[21], // phone
                    $row[22], // grade_level
                    $enrollmentDate, // enrollment_date
                    $row[24], // enrollment_year
                    $row[25], // enrollment_term
                    $row[26], // student_status
                    $row[27], // education_format
                    $row[28], // address_code
                    $row[29], // house_number
                    $row[30], // village_number
                    $row[31], // street
                    $row[32], // province
                    $row[33], // district
                    $row[34], // sub_district
                    $row[35], // postal_code
                    $row[36], // qualification_status
                    $row[37], // father_title
                    $row[38], // father_name
                    $row[39], // father_surname
                    $row[40], // father_status
                    $row[41], // father_occupation
                    $row[42], // mother_middle_name
                    $row[43], // mother_status
                    $row[44], // mother_occupation
                    $row[45], // mother_nationality
                    $row[46], // mother_disability
                    $row[47], // mother_salary
                    $row[48], // parent_address_code
                    $row[49], // parent_house_number
                    $row[50], // parent_village_number
                    $row[51], // parent_soi
                    $row[52], // parent_street
                    $row[53], // parent_province
                    $row[54], // parent_district
                    $row[55], // parent_sub_district
                    $row[56], // parent_postal_code
                    $row[57], // parent_phone
                    $row[58], // guardian_national_id
                    $row[59], // guardian_title
                    $row[60], // guardian_name
                    $row[61], // guardian_surname
                    $row[62]  // guardian_occupation
                ];
                
                $students[] = $studentData;
                
            } catch (Exception $e) {
                $errors[] = "แถว {$rowNumber}: " . $e->getMessage();
            }
        }
        
        if (!empty($students)) {
            $student->bulkInsert($students);
            $message = "นำเข้าข้อมูลสำเร็จ จำนวน " . count($students) . " รายการ";
            $messageType = "success";
            
            if (!empty($errors)) {
                $message .= "<br>มีข้อผิดพลาดบางรายการ:<br>" . implode("<br>", $errors);
                $messageType = "warning";
            }
        } else {
            $message = "ไม่พบข้อมูลที่สามารถนำเข้าได้";
            $messageType = "danger";
        }
        
    } catch (Exception $e) {
        $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $messageType = "danger";
    }
}

function convertThaiDate($thaiDateStr) {
    // แปลงวันที่ภาษาไทยเป็น Y-m-d format
    $thaiMonths = [
        'มกราคม' => '01', 'กุมภาพันธ์' => '02', 'มีนาคม' => '03',
        'เมษายน' => '04', 'พฤษภาคม' => '05', 'มิถุนายน' => '06',
        'กรกฎาคม' => '07', 'สิงหาคม' => '08', 'กันยายน' => '09',
        'ตุลาคม' => '10', 'พฤศจิกายน' => '11', 'ธันวาคม' => '12'
    ];
    
    foreach ($thaiMonths as $thai => $month) {
        if (strpos($thaiDateStr, $thai) !== false) {
            $parts = explode(' ', $thaiDateStr);
            if (count($parts) >= 3) {
                $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $year = (int)$parts[2] - 543; // แปลงจาก พ.ศ. เป็น ค.ศ.
                return "$year-$month-$day";
            }
        }
    }
    
    return null;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าข้อมูล Excel - ระบบจัดการข้อมูลนักเรียน</title>
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
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 15px;
            padding: 50px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f0ff;
        }
        .upload-area.dragover {
            border-color: #764ba2;
            background: #e8e8ff;
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="import.php">
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
                    <h2><i class="fas fa-file-upload"></i> นำเข้าข้อมูลจากไฟล์ Excel</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Instructions -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-info-circle"></i> คำแนะนำการใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>ข้อกำหนดไฟล์:</h6>
                                <ul>
                                    <li>ไฟล์ต้องเป็นนามสกุล .xlsx หรือ .xls</li>
                                    <li>ใช้รูปแบบเดียวกับไฟล์ ศธ.02</li>
                                    <li>แถวแรกต้องเป็นหัวตาราง (Header)</li>
                                    <li>ข้อมูลเริ่มจากแถวที่ 2</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>ฟิลด์ที่จำเป็น:</h6>
                                <ul>
                                    <li>เลขประจำตัวประชาชน (13 หลัก)</li>
                                    <li>รหัสประจำตัวนักเรียน</li>
                                    <li>ชื่อ (ไทย)</li>
                                    <li>นามสกุล (ไทย)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>ข้อควรระวัง:</strong> การนำเข้าข้อมูลใหม่จะลบข้อมูลเก่าทั้งหมดในระบบ กรุณาตรวจสอบให้แน่ใจก่อนดำเนินการ
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h4>อัพโหลดไฟล์ Excel</h4>
                                <p class="text-muted">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                                <input type="file" name="excel_file" id="fileInput" accept=".xlsx,.xls" required style="display: none;">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-folder-open"></i> เลือกไฟล์
                                </button>
                            </div>
                            
                            <div id="fileInfo" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-file-excel"></i>
                                    <span id="fileName"></span>
                                    <span id="fileSize" class="badge bg-secondary ms-2"></span>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                    <i class="fas fa-upload"></i> นำเข้าข้อมูล
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Progress -->
                <div id="progressSection" class="card mt-4" style="display: none;">
                    <div class="card-body">
                        <h5><i class="fas fa-sync-alt fa-spin"></i> กำลังประมวลผล...</h5>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%" id="progressBar"></div>
                        </div>
                        <p class="mt-2 text-muted">กรุณารอสักครู่ ระบบกำลังนำเข้าข้อมูลของคุณ</p>
                    </div>
                </div>

                <!-- Sample Data Format -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-table"></i> ตัวอย่างรูปแบบข้อมูล</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ลำดับ</th>
                                        <th>เลขประจำตัวประชาชน</th>
                                        <th>รหัสประจำตัว</th>
                                        <th>กลุ่มเรียน</th>
                                        <th>คำนำหน้าชื่อ</th>
                                        <th>ชื่อ (ไทย)</th>
                                        <th>นามสกุล (ไทย)</th>
                                        <th>เพศ</th>
                                        <th>...</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>1101000268630</td>
                                        <td>68201010001</td>
                                        <td>ปวช.1/1</td>
                                        <td>นางสาว</td>
                                        <td>กนกวลัย</td>
                                        <td>จันทะโร</td>
                                        <td>ญ</td>
                                        <td>...</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>1319200107716</td>
                                        <td>68201010002</td>
                                        <td>ปวช.1/1</td>
                                        <td>นาย</td>
                                        <td>กวีวัฒน์</td>
                                        <td>ศรีสุข</td>
                                        <td>ช</td>
                                        <td>...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File input handling
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitBtn = document.getElementById('submitBtn');
        const uploadForm = document.getElementById('uploadForm');
        const progressSection = document.getElementById('progressSection');

        fileInput.addEventListener('change', handleFileSelect);
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', handleDragOver);
        uploadArea.addEventListener('drop', handleDrop);
        uploadArea.addEventListener('dragleave', handleDragLeave);

        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) {
                displayFileInfo(file);
            }
        }

        function handleDragOver(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.includes('sheet') || file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
                    fileInput.files = files;
                    displayFileInfo(file);
                } else {
                    alert('กรุณาเลือกไฟล์ Excel (.xlsx หรือ .xls) เท่านั้น');
                }
            }
        }

        function displayFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
            submitBtn.disabled = false;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!fileInput.files[0]) {
                alert('กรุณาเลือกไฟล์ก่อน');
                return;
            }
            
            if (!confirm('คุณแน่ใจหรือไม่ที่จะนำเข้าข้อมูลใหม่? ข้อมูลเก่าทั้งหมดจะถูกลบ')) {
                return;
            }
            
            // Show progress
            progressSection.style.display = 'block';
            submitBtn.disabled = true;
            
            // Simulate progress
            let progress = 0;
            const progressBar = document.getElementById('progressBar');
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 500);
            
            // Submit form
            const formData = new FormData(this);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                clearInterval(interval);
                progressBar.style.width = '100%';
                setTimeout(() => {
                    document.body.innerHTML = html;
                }, 1000);
            })
            .catch(error => {
                clearInterval(interval);
                progressSection.style.display = 'none';
                submitBtn.disabled = false;
                alert('เกิดข้อผิดพลาด: ' + error.message);
            });
        });
    </script>
</body>
</html>

<?php
// admin/api_doc.php
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เอกสาร API - ระบบจัดการข้อมูลนักเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
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
        .method-badge {
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.8em;
        }
        .method-get { background: #28a745; }
        .method-post { background: #007bff; }
        .method-put { background: #ffc107; color: #000; }
        .method-delete { background: #dc3545; }
        
        pre[class*="language-"] {
            border-radius: 10px;
            font-size: 0.9em;
        }
        
        .endpoint-card {
            transition: transform 0.2s;
        }
        .endpoint-card:hover {
            transform: translateY(-2px);
        }
        
        .response-example {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 0 10px 10px 0;
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="import.php">
                            <i class="fas fa-file-upload"></i> นำเข้าข้อมูล Excel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="api_doc.php">
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
                    <h2><i class="fas fa-code"></i> เอกสาร API</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </div>

                <!-- Overview -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-info-circle"></i> ภาพรวม API</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Base URL:</h6>
                                <code>https://yourdomain.com/api/students.php</code>
                                
                                <h6 class="mt-3">Content-Type:</h6>
                                <code>application/json</code>
                                
                                <h6 class="mt-3">Response Format:</h6>
                                <code>JSON</code>
                            </div>
                            <div class="col-md-6">
                                <h6>การใช้งาน:</h6>
                                <ul>
                                    <li>ค้นหานักเรียนด้วยเลขประชาชน</li>
                                    <li>ค้นหานักเรียนด้วยรหัสนักเรียน</li>
                                    <li>ดึงข้อมูลนักเรียนทั้งหมด</li>
                                    <li>ค้นหาข้อมูลด้วยคำค้น</li>
                                    <li>เพิ่ม/แก้ไข/ลบข้อมูลนักเรียน</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb"></i>
                            <strong>หมายเหตุ:</strong> API นี้สามารถใช้งานได้จากระบบอื่นๆ ที่คุณพัฒนา เช่น แอพมือถือ, เว็บไซต์, หรือระบบอื่นๆ ที่ต้องการข้อมูลนักเรียน
                        </div>
                    </div>
                </div>

                <!-- Endpoints -->
                
                <!-- 1. ดึงข้อมูลนักเรียนทั้งหมด -->
                <div class="card endpoint-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><span class="badge method-badge method-get">GET</span> ดึงข้อมูลนักเรียนทั้งหมด</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="testAPI('all')">ทดสอบ API</button>
                    </div>
                    <div class="card-body">
                        <h6>Endpoint:</h6>
                        <pre><code class="language-http">GET /api/students.php?request=all&page=1&limit=50</code></pre>
                        
                        <h6>Parameters:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>request</code></td>
                                        <td>string</td>
                                        <td>Yes</td>
                                        <td>ต้องเป็น "all"</td>
                                    </tr>
                                    <tr>
                                        <td><code>page</code></td>
                                        <td>integer</td>
                                        <td>No</td>
                                        <td>หน้าที่ต้องการ (default: 1)</td>
                                    </tr>
                                    <tr>
                                        <td><code>limit</code></td>
                                        <td>integer</td>
                                        <td>No</td>
                                        <td>จำนวนข้อมูลต่อหน้า (default: 50)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <h6>Response Example:</h6>
                        <div class="response-example">
                            <pre><code class="language-json">{
    "success": true,
    "data": [
        {
            "id": "1",
            "national_id": "1101000268630",
            "student_code": "68201010001",
            "class_group": "ปวช.1/1",
            "title_name": "นางสาว",
            "first_name": "กนกวลัย",
            "last_name": "จันทะโร",
            "gender": "ญ",
            "phone": "0934715501",
            "major": "20101 - ช่างยนต์",
            "student_status": "กำลังศึกษา"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 50,
        "total": 1375,
        "pages": 28
    }
}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- 2. ค้นหาด้วยเลขประชาชน -->
                <div class="card endpoint-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><span class="badge method-badge method-get">GET</span> ค้นหาด้วยเลขประชาชน</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="testAPI('by_national_id')">ทดสอบ API</button>
                    </div>
                    <div class="card-body">
                        <h6>Endpoint:</h6>
                        <pre><code class="language-http">GET /api/students.php?request=by_national_id&national_id=1101000268630</code></pre>
                        
                        <h6>Parameters:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>request</code></td>
                                        <td>string</td>
                                        <td>Yes</td>
                                        <td>ต้องเป็น "by_national_id"</td>
                                    </tr>
                                    <tr>
                                        <td><code>national_id</code></td>
                                        <td>string</td>
                                        <td>Yes</td>
                                        <td>เลขประจำตัวประชาชน 13 หลัก</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <h6>Response Example:</h6>
                        <div class="response-example">
                            <pre><code class="language-json">{
    "success": true,
    "data": {
        "id": "1",
        "national_id": "1101000268630",
        "student_code": "68201010001",
        "title_name": "นางสาว",
        "first_name": "กนกวลัย",
        "last_name": "จันทะโร",
        "gender": "ญ",
        "nickname": "",
        "birth_date": "2009-11-17",
        "age": "15 ปี 6 เดือน 24 วัน",
        "class_group": "ปวช.1/1",
        "major": "20101 - ช่างยนต์",
        "phone": "0934715501",
        "student_status": "กำลังศึกษา"
    }
}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- 3. ค้นหาด้วยรหัสนักเรียน -->
                <div class="card endpoint-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><span class="badge method-badge method-get">GET</span> ค้นหาด้วยรหัสนักเรียน</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="testAPI('by_student_code')">ทดสอบ API</button>
                    </div>
                    <div class="card-body">
                        <h6>Endpoint:</h6>
                        <pre><code class="language-http">GET /api/students.php?request=by_student_code&student_code=68201010001</code></pre>
                        
                        <h6>Parameters:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>request</code></td>
                                        <td>string</td>
                                        <td>Yes</td>
                                        <td>ต้องเป็น "by_student_code"</td>
                                    </tr>
                                    <tr>
                                        <td><code>student_code</code></td>
                                        <td>string</td>
                                        <td>Yes</td>
                                        <td>รหัสประจำตัวนักเรียน</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <h6>Response เหมือนกับการค้นหาด้วยเลขประชาชน</h6>
                    </div>
                </div>

                <!-- 4. ค้นหาทั่วไป -->
                <div class="card endpoint-card mb-4">
                    <div class="card-header">
                        <h5><span class="badge method-badge method-get">GET</span> ค้นหาข้อมูลทั่วไป</h5>
                    </div>
                    <div class="card-body">
                        <h6>Endpoint:</h6>
                        <pre><code class="language-http">GET /api/students.php?request=search&q=กนกวลัย</code></pre>
                        
                        <p>ค้นหาในฟิลด์: เลขประชาชน, รหัสนักเรียน, ชื่อ, นามสกุล, เบอร์โทรศัพท์</p>
                    </div>
                </div>

                <!-- 5. นับจำนวนนักเรียน -->
                <div class="card endpoint-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><span class="badge method-badge method-get">GET</span> นับจำนวนนักเรียนทั้งหมด</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="testAPI('count')">ทดสอบ API</button>
                    </div>
                    <div class="card-body">
                        <h6>Endpoint:</h6>
                        <pre><code class="language-http">GET /api/students.php?request=count</code></pre>
                        
                        <h6>Response Example:</h6>
                        <div class="response-example">
                            <pre><code class="language-json">{
    "success": true,
    "total": 1375
}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Usage Examples -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-code"></i> ตัวอย่างการใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <h6>JavaScript (Fetch API):</h6>
                        <pre><code class="language-javascript">// ค้นหานักเรียนด้วยเลขประชาชน
fetch('https://yourdomain.com/api/students.php?request=by_national_id&national_id=1101000268630')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('ข้อมูลนักเรียน:', data.data);
        } else {
            console.log('ไม่พบข้อมูล');
        }
    });</code></pre>

                        <h6 class="mt-4">PHP (cURL):</h6>
                        <pre><code class="language-php">$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://yourdomain.com/api/students.php?request=by_student_code&student_code=68201010001',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);
if ($data['success']) {
    echo "ชื่อนักเรียน: " . $data['data']['first_name'] . " " . $data['data']['last_name'];
}</code></pre>

                        <h6 class="mt-4">Python (requests):</h6>
                        <pre><code class="language-python">import requests

response = requests.get('https://yourdomain.com/api/students.php', params={
    'request': 'search',
    'q': 'กนกวลัย'
})

data = response.json()
if data['success']:
    for student in data['data']:
        print(f"ชื่อ: {student['first_name']} {student['last_name']}")
</code></pre>
                    </div>
                </div>

                <!-- Error Responses -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-exclamation-triangle"></i> Error Responses</h5>
                    </div>
                    <div class="card-body">
                        <h6>ไม่พบข้อมูล:</h6>
                        <pre><code class="language-json">{
    "success": false,
    "message": "Student not found"
}</code></pre>

                        <h6>ข้อมูลไม่ครบถ้วน:</h6>
                        <pre><code class="language-json">{
    "success": false,
    "message": "National ID required"
}</code></pre>

                        <h6>Method ไม่ถูกต้อง:</h6>
                        <pre><code class="language-json">{
    "message": "Method not allowed"
}</code></pre>
                    </div>
                </div>

                <!-- Test API Section -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-play"></i> ทดสอบ API</h5>
                    </div>
                    <div class="card-body">
                        <div id="apiTest" style="display: none;">
                            <h6>Response:</h6>
                            <pre id="apiResponse" style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;"></pre>
                        </div>
                        <p class="text-muted">คลิกปุ่ม "ทดสอบ API" ในแต่ละ endpoint เพื่อดูผลลัพธ์</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/autoloader/prism-autoloader.min.js"></script>
    <script>
        function testAPI(type) {
            const apiTest = document.getElementById('apiTest');
            const apiResponse = document.getElementById('apiResponse');
            
            let url = '../api/students.php';
            
            switch(type) {
                case 'all':
                    url += '?request=all&page=1&limit=5';
                    break;
                case 'by_national_id':
                    url += '?request=by_national_id&national_id=1101000268630';
                    break;
                case 'by_student_code':
                    url += '?request=by_student_code&student_code=68201010001';
                    break;
                case 'count':
                    url += '?request=count';
                    break;
                default:
                    url += '?request=all&limit=1';
            }
            
            apiResponse.textContent = 'กำลังโหลด...';
            apiTest.style.display = 'block';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    apiResponse.textContent = JSON.stringify(data, null, 2);
                    apiTest.scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    apiResponse.textContent = 'Error: ' + error.message;
                });
        }
    </script>
</body>
</html>

<?php
// install/setup.php
require_once '../config/database.php';
require_once '../models/Student.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $student = new Student($db);
    $student->createTable();
    echo "ระบบติดตั้งเรียบร้อยแล้ว!";
} else {
    echo "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
}
?>

<?php
// composer.json สำหรับจัดการ dependencies
/*
{
    "require": {
        "phpoffice/phpspreadsheet": "^1.18"
    }
}
*/

// ไฟล์ .htaccess สำหรับ API
/*
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [QSA,L]

Header add Access-Control-Allow-Origin "*"
Header add Access-Control-Allow-Headers "origin, x-requested-with, content-type"
Header add Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"
*/
?>