<?php
class Student {
    private $conn;
    private $table_name = "students";

    public function __construct($db) {
        $this->conn = $db;
    }

    // สร้างตาราง
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            national_id VARCHAR(13) UNIQUE NOT NULL,
            student_code VARCHAR(20) UNIQUE NOT NULL,
            class_group VARCHAR(50),
            title_name VARCHAR(20),
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            gender ENUM('ช', 'ญ'),
            nickname VARCHAR(50),
            birth_date DATE,
            age VARCHAR(50),
            student_type VARCHAR(50),
            subject_type VARCHAR(100),
            major VARCHAR(100),
            disability VARCHAR(100),
            nationality VARCHAR(50),
            race VARCHAR(50),
            religion VARCHAR(50),
            height INT,
            weight INT,
            disadvantaged VARCHAR(100),
            phone VARCHAR(20),
            grade_level VARCHAR(20),
            enrollment_date DATE,
            enrollment_year VARCHAR(4),
            enrollment_term VARCHAR(2),
            student_status VARCHAR(50),
            education_format VARCHAR(50),
            address_code VARCHAR(20),
            house_number VARCHAR(20),
            village_number VARCHAR(10),
            street VARCHAR(100),
            province VARCHAR(50),
            district VARCHAR(50),
            sub_district VARCHAR(50),
            postal_code VARCHAR(10),
            qualification_status VARCHAR(50),
            father_title VARCHAR(20),
            father_name VARCHAR(100),
            father_surname VARCHAR(100),
            father_status VARCHAR(50),
            father_occupation VARCHAR(100),
            mother_middle_name VARCHAR(100),
            mother_status VARCHAR(50),
            mother_occupation VARCHAR(100),
            mother_nationality VARCHAR(50),
            mother_disability VARCHAR(100),
            mother_salary VARCHAR(20),
            parent_address_code VARCHAR(20),
            parent_house_number VARCHAR(20),
            parent_village_number VARCHAR(10),
            parent_soi VARCHAR(100),
            parent_street VARCHAR(100),
            parent_province VARCHAR(50),
            parent_district VARCHAR(50),
            parent_sub_district VARCHAR(50),
            parent_postal_code VARCHAR(10),
            parent_phone VARCHAR(20),
            guardian_national_id VARCHAR(13),
            guardian_title VARCHAR(20),
            guardian_name VARCHAR(100),
            guardian_surname VARCHAR(100),
            guardian_occupation VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $this->conn->exec($query);
    }

    // เพิ่มข้อมูลนักเรียน
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET national_id=:national_id, student_code=:student_code, class_group=:class_group,
                     title_name=:title_name, first_name=:first_name, last_name=:last_name,
                     gender=:gender, nickname=:nickname, birth_date=:birth_date, age=:age,
                     student_type=:student_type, subject_type=:subject_type, major=:major,
                     disability=:disability, nationality=:nationality, race=:race,
                     religion=:religion, height=:height, weight=:weight, disadvantaged=:disadvantaged,
                     phone=:phone, grade_level=:grade_level, enrollment_date=:enrollment_date,
                     enrollment_year=:enrollment_year, enrollment_term=:enrollment_term,
                     student_status=:student_status, education_format=:education_format,
                     address_code=:address_code, house_number=:house_number, village_number=:village_number,
                     street=:street, province=:province, district=:district, sub_district=:sub_district,
                     postal_code=:postal_code, qualification_status=:qualification_status,
                     father_title=:father_title, father_name=:father_name, father_surname=:father_surname,
                     father_status=:father_status, father_occupation=:father_occupation,
                     mother_middle_name=:mother_middle_name, mother_status=:mother_status,
                     mother_occupation=:mother_occupation, mother_nationality=:mother_nationality,
                     mother_disability=:mother_disability, mother_salary=:mother_salary,
                     parent_address_code=:parent_address_code, parent_house_number=:parent_house_number,
                     parent_village_number=:parent_village_number, parent_soi=:parent_soi,
                     parent_street=:parent_street, parent_province=:parent_province,
                     parent_district=:parent_district, parent_sub_district=:parent_sub_district,
                     parent_postal_code=:parent_postal_code, parent_phone=:parent_phone,
                     guardian_national_id=:guardian_national_id, guardian_title=:guardian_title,
                     guardian_name=:guardian_name, guardian_surname=:guardian_surname,
                     guardian_occupation=:guardian_occupation";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    // อ่านข้อมูลทั้งหมด
    public function read($page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // ค้นหาข้อมูล
    public function search($term) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE national_id LIKE :term 
                    OR student_code LIKE :term 
                    OR first_name LIKE :term 
                    OR last_name LIKE :term 
                    OR phone LIKE :term
                 ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$term}%";
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    // อ่านข้อมูลตาม ID
    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // อ่านข้อมูลตามเลขประชาชน
    public function getByNationalId($national_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE national_id = :national_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':national_id', $national_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // อ่านข้อมูลตามรหัสนักเรียน
    public function getByStudentCode($student_code) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE student_code = :student_code LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_code', $student_code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // อัพเดทข้อมูล
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                 SET title_name=:title_name, first_name=:first_name, last_name=:last_name,
                     gender=:gender, nickname=:nickname, phone=:phone, grade_level=:grade_level,
                     student_status=:student_status, major=:major, class_group=:class_group
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    // ลบข้อมูล
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // นับจำนวนรายการทั้งหมด
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // นำเข้าข้อมูลจาก Excel (bulk insert)
    public function bulkInsert($students) {
        $this->conn->beginTransaction();
        try {
            // ลบข้อมูลเก่าทั้งหมด
            $this->conn->exec("DELETE FROM " . $this->table_name);
            
            $query = "INSERT INTO " . $this->table_name . " 
                     (national_id, student_code, class_group, title_name, first_name, last_name,
                      gender, nickname, birth_date, age, student_type, subject_type, major,
                      disability, nationality, race, religion, height, weight, disadvantaged,
                      phone, grade_level, enrollment_date, enrollment_year, enrollment_term,
                      student_status, education_format, address_code, house_number, village_number,
                      street, province, district, sub_district, postal_code, qualification_status,
                      father_title, father_name, father_surname, father_status, father_occupation,
                      mother_middle_name, mother_status, mother_occupation, mother_nationality,
                      mother_disability, mother_salary, parent_address_code, parent_house_number,
                      parent_village_number, parent_soi, parent_street, parent_province,
                      parent_district, parent_sub_district, parent_postal_code, parent_phone,
                      guardian_national_id, guardian_title, guardian_name, guardian_surname,
                      guardian_occupation)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($students as $student) {
                $stmt->execute($student);
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>