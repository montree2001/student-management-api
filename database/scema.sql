-- ==================== สร้างฐานข้อมูล ====================

CREATE DATABASE IF NOT EXISTS student_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE student_db;

-- ==================== ตารางข้อมูลนักเรียน ====================

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL COMMENT 'รหัสประจำตัวนักเรียน',
    citizen_id VARCHAR(13) UNIQUE COMMENT 'เลขประจำตัวประชาชน',
    class_group VARCHAR(50) COMMENT 'กลุ่มเรียน',
    title_name VARCHAR(20) COMMENT 'คำนำหน้าชื่อ',
    first_name VARCHAR(100) NOT NULL COMMENT 'ชื่อ',
    last_name VARCHAR(100) NOT NULL COMMENT 'นามสกุล',
    nickname VARCHAR(50) COMMENT 'ชื่อเล่น',
    gender ENUM('ช', 'ญ') COMMENT 'เพศ',
    birth_date DATE COMMENT 'วันเกิด',
    age VARCHAR(50) COMMENT 'อายุ',
    student_type VARCHAR(50) COMMENT 'ประเภทนักเรียน',
    subject_type VARCHAR(100) COMMENT 'ประเภทวิชา',
    major VARCHAR(200) COMMENT 'สาขาวิชา',
    disability_type VARCHAR(100) COMMENT 'ประเภทความพิการ',
    nationality VARCHAR(50) COMMENT 'สัญชาติ',
    ethnicity VARCHAR(50) COMMENT 'เชื้อชาติ',
    religion VARCHAR(50) COMMENT 'ศาสนา',
    height DECIMAL(5,2) COMMENT 'ความสูง (ซม.)',
    weight DECIMAL(5,2) COMMENT 'น้ำหนัก (กก.)',
    disadvantaged_status VARCHAR(50) COMMENT 'ความด้อยโอกาส',
    phone_number VARCHAR(20) COMMENT 'โทรศัพท์',
    class_level VARCHAR(50) COMMENT 'ระดับชั้น',
    enrollment_date DATE COMMENT 'วันที่เข้าเรียน',
    enrollment_year INT COMMENT 'ปีที่เข้าเรียน',
    enrollment_term INT COMMENT 'เทอมที่เข้าเรียน',
    student_status VARCHAR(50) COMMENT 'สถานะนักเรียน',
    education_format VARCHAR(50) COMMENT 'รูปแบบการศึกษา',
    verification_status VARCHAR(50) COMMENT 'สถานะการตรวจสอบวุฒิ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_student_id (student_id),
    INDEX idx_citizen_id (citizen_id),
    INDEX idx_class_group (class_group),
    INDEX idx_major (major),
    INDEX idx_student_status (student_status),
    INDEX idx_name (first_name, last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== ตารางที่อยู่นักเรียน ====================

CREATE TABLE student_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    house_code VARCHAR(20) COMMENT 'รหัสประจำบ้าน',
    house_number VARCHAR(20) COMMENT 'บ้านเลขที่',
    village_no VARCHAR(10) COMMENT 'หมู่ที่',
    road VARCHAR(100) COMMENT 'ถนน',
    province VARCHAR(100) COMMENT 'จังหวัด',
    district VARCHAR(100) COMMENT 'อำเภอ/เขต',
    subdistrict VARCHAR(100) COMMENT 'ตำบล/แขวง',
    postal_code VARCHAR(10) COMMENT 'รหัสไปรษณีย์',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_province (province),
    INDEX idx_district (district)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== ตารางข้อมูลผู้ปกครอง ====================

CREATE TABLE student_parents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    parent_type ENUM('father', 'mother', 'guardian') NOT NULL COMMENT 'ประเภทผู้ปกครอง',
    citizen_id VARCHAR(13) COMMENT 'เลขประจำตัวประชาชน',
    title_name VARCHAR(20) COMMENT 'คำนำหน้าชื่อ',
    first_name VARCHAR(100) COMMENT 'ชื่อ',
    last_name VARCHAR(100) COMMENT 'นามสกุล',
    status VARCHAR(50) COMMENT 'สถานภาพ',
    occupation VARCHAR(100) COMMENT 'อาชีพ',
    nationality VARCHAR(50) COMMENT 'สัญชาติ',
    disability_type VARCHAR(100) COMMENT 'ประเภทความพิการ',
    salary DECIMAL(10,2) COMMENT 'เงินเดือน',
    phone_number VARCHAR(20) COMMENT 'โทรศัพท์',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_parent_type (parent_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== ตารางที่อยู่ผู้ปกครอง ====================

CREATE TABLE parent_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NOT NULL,
    house_code VARCHAR(20) COMMENT 'รหัสประจำบ้าน',
    house_number VARCHAR(20) COMMENT 'บ้านเลขที่',
    village_no VARCHAR(10) COMMENT 'หมู่ที่',
    soi VARCHAR(100) COMMENT 'ซอย',
    road VARCHAR(100) COMMENT 'ถนน',
    province VARCHAR(100) COMMENT 'จังหวัด',
    district VARCHAR(100) COMMENT 'อำเภอ/เขต',
    subdistrict VARCHAR(100) COMMENT 'ตำบล/แขวง',
    postal_code VARCHAR(10) COMMENT 'รหัสไปรษณีย์',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES student_parents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== ตารางบันทึกการนำเข้าข้อมูล ====================

CREATE TABLE import_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL COMMENT 'ชื่อไฟล์',
    total_records INT NOT NULL DEFAULT 0 COMMENT 'จำนวนข้อมูลทั้งหมด',
    success_records INT NOT NULL DEFAULT 0 COMMENT 'จำนวนข้อมูลที่สำเร็จ',
    failed_records INT NOT NULL DEFAULT 0 COMMENT 'จำนวนข้อมูลที่ล้มเหลว',
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่นำเข้า',
    import_by VARCHAR(100) COMMENT 'ผู้นำเข้าข้อมูล',
    notes TEXT COMMENT 'หมายเหตุ',
    
    INDEX idx_import_date (import_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== ตารางผู้ใช้งาน (ถ้าต้องการระบบ Login) ====================

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================== ข้อมูลตัวอย่าง ====================

-- เพิ่มผู้ใช้ admin (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES 
('admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin');

-- ข้อมูลนักเรียนตัวอย่าง
INSERT INTO students (
    student_id, citizen_id, class_group, title_name, first_name, last_name, 
    gender, birth_date, age, student_type, subject_type, major, nationality, 
    ethnicity, religion, phone_number, class_level, student_status, education_format
) VALUES 
('68201010001', '1101000268630', 'ปวช.1/1', 'นางสาว', 'กนกวลัย', 'จันทะโร', 
 'ญ', '2009-11-17', '15 ปี 6 เดือน 24 วัน', 'ปกติ', 'อุตสาหกรรม', '20101 - ช่างยนต์', 
 'ไทย', 'ไทย', 'พุทธ', '0934715501', 'ปวช.1', 'กำลังศึกษา', 'ในระบบ'),

('68201010002', '1101000268631', 'ปวช.2/1', 'นาย', 'สมชาย', 'ใจดี', 
 'ช', '2008-05-15', '16 ปี 2 เดือน 10 วัน', 'ปกติ', 'อุตสาหกรรม', '20102 - ช่างไฟฟ้า', 
 'ไทย', 'ไทย', 'พุทธ', '0812345678', 'ปวช.2', 'กำลังศึกษา', 'ในระบบ'),

('68201010003', '1101000268632', 'ปวช.3/2', 'นางสาว', 'สมหญิง', 'รักเรียน', 
 'ญ', '2007-08-20', '17 ปี 1 เดือน 5 วัน', 'ปกติ', 'เทคโนโลยี', '20103 - คอมพิวเตอร์', 
 'ไทย', 'ไทย', 'พุทธ', '0987654321', 'ปวช.3', 'กำลังศึกษา', 'ในระบบ');

-- ข้อมูลที่อยู่ตัวอย่าง
INSERT INTO student_addresses (student_id, house_number, village_no, province, district, subdistrict, postal_code) VALUES 
(1, '185', '1', 'สุรินทร์', 'ปราสาท', 'ตานี', '32140'),
(2, '123', '2', 'กรุงเทพฯ', 'บางกะปิ', 'ลาดพร้าว', '10230'),
(3, '456', '3', 'เชียงใหม่', 'เมือง', 'ช้างเผือก', '50300');

-- ==================== Views สำหรับการใช้งาน ====================

-- View สำหรับรายการนักเรียนพร้อมที่อยู่
CREATE VIEW student_list AS
SELECT 
    s.id,
    s.student_id,
    s.citizen_id,
    CONCAT(s.title_name, s.first_name, ' ', s.last_name) AS full_name,
    s.first_name,
    s.last_name,
    s.gender,
    s.class_group,
    s.major,
    s.phone_number,
    s.student_status,
    a.province,
    a.district,
    s.created_at
FROM students s
LEFT JOIN student_addresses a ON s.id = a.student_id;

-- View สำหรับสถิติ
CREATE VIEW student_statistics AS
SELECT 
    COUNT(*) AS total_students,
    SUM(CASE WHEN student_status = 'กำลังศึกษา' THEN 1 ELSE 0 END) AS active_students,
    SUM(CASE WHEN gender = 'ช' THEN 1 ELSE 0 END) AS male_count,
    SUM(CASE WHEN gender = 'ญ' THEN 1 ELSE 0 END) AS female_count
FROM students;

-- ==================== Stored Procedures ====================

-- Procedure สำหรับค้นหานักเรียน
DELIMITER //
CREATE PROCEDURE SearchStudents(
    IN search_term VARCHAR(255),
    IN class_filter VARCHAR(50),
    IN major_filter VARCHAR(200),
    IN status_filter VARCHAR(50),
    IN page_num INT,
    IN page_size INT
)
BEGIN
    DECLARE offset_val INT DEFAULT 0;
    SET offset_val = (page_num - 1) * page_size;
    
    SELECT SQL_CALC_FOUND_ROWS
        s.*,
        a.province,
        a.district
    FROM students s
    LEFT JOIN student_addresses a ON s.id = a.student_id
    WHERE 
        (search_term IS NULL OR search_term = '' OR 
         s.first_name LIKE CONCAT('%', search_term, '%') OR
         s.last_name LIKE CONCAT('%', search_term, '%') OR
         s.student_id LIKE CONCAT('%', search_term, '%') OR
         s.citizen_id LIKE CONCAT('%', search_term, '%'))
    AND (class_filter IS NULL OR class_filter = '' OR s.class_group = class_filter)
    AND (major_filter IS NULL OR major_filter = '' OR s.major LIKE CONCAT('%', major_filter, '%'))
    AND (status_filter IS NULL OR status_filter = '' OR s.student_status = status_filter)
    ORDER BY s.first_name, s.last_name
    LIMIT page_size OFFSET offset_val;
    
    SELECT FOUND_ROWS() AS total_records;
END //
DELIMITER ;

-- ==================== Triggers ====================

-- Trigger สำหรับ log การเปลี่ยนแปลงข้อมูลนักเรียน
CREATE TABLE student_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_data JSON,
    new_data JSON,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELIMITER //
CREATE TRIGGER student_audit_update 
AFTER UPDATE ON students
FOR EACH ROW
BEGIN
    INSERT INTO student_audit_log (student_id, action, old_data, new_data, changed_by)
    VALUES (NEW.id, 'UPDATE', 
            JSON_OBJECT(
                'first_name', OLD.first_name,
                'last_name', OLD.last_name,
                'phone_number', OLD.phone_number,
                'student_status', OLD.student_status
            ),
            JSON_OBJECT(
                'first_name', NEW.first_name,
                'last_name', NEW.last_name,
                'phone_number', NEW.phone_number,
                'student_status', NEW.student_status
            ),
            USER());
END //
DELIMITER ;