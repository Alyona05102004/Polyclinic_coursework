<?php
header('Content-Type: application/json'); // Важно: установить заголовок JSON

include 'bd.php'; 

// Проверка обязательных полей
$required_fields = ['firstName', 'lastName', 'birthDate', 'phoneNumber', 'doctorAdress', 
                   'position', 'department', 'polyclinic', 'status', 'experience'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Не все обязательные поля заполнены: ' . $field]);
        exit;
    }
}

// Обработка данных
try {
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $lastName = $conn->real_escape_string($_POST['lastName']);
    $middleName = $conn->real_escape_string($_POST['middleName'] ?? '');
    $fullName = $lastName . ' ' . $firstName . ' ' . $middleName;
    
    // Обработка даты
    $birthDate = DateTime::createFromFormat('Y-m-d', $_POST['birthDate']);
    if (!$birthDate) {
        throw new Exception("Неверный формат даты рождения");
    }
    $birthDateFormatted = $birthDate->format('Y-m-d');

    // Остальные поля
    $phoneNumber = $conn->real_escape_string($_POST['phoneNumber']);
    $doctorAdress = $conn->real_escape_string($_POST['doctorAdress']);
    $position = $conn->real_escape_string($_POST['position']);
    $id_department = intval($_POST['department']);
    $statusValue = ($_POST['status'] === 'byAppointment') ? 1 : 0;
    $experience = $conn->real_escape_string($_POST['experience']);
    
    // Начало транзакции
    $conn->begin_transaction();

    // 1. Добавление врача
    $sql_doctor = "INSERT INTO `staff` (`full_name`,`birthday`,`post`,`status`,`address`,`phone_number`,`id_department`)
                   VALUES ('$fullName', '$birthDateFormatted', '$position', '$statusValue', '$doctorAdress', '$phoneNumber', $id_department)";
    if (!$conn->query($sql_doctor)) {
        throw new Exception("Ошибка при добавлении врача: " . $conn->error);
    }
    $lastDoctorId = $conn->insert_id;

    // 2. Добавление образования (если есть данные)
    if (isset($_POST['educationType'], $_POST['university'], $_POST['startYear'], $_POST['endYear'], $_POST['medicalField'])) {
        $educationType = $conn->real_escape_string($_POST['educationType']);
        $university = $conn->real_escape_string($_POST['university']);
        $startYear = $conn->real_escape_string($_POST['startYear']);
        $endYear = $conn->real_escape_string($_POST['endYear']);
        $id_medicalField = intval($_POST['medicalField']);
        
        $sql_education = "INSERT INTO `education` (`work_experience`, `type_of_education`, `educational_institution`, `year_of_start`, `year_of_end`, `id_field`)
                          VALUES ('$experience', '$educationType', '$university', '$startYear', '$endYear', $id_medicalField)";
        if (!$conn->query($sql_education)) {
            throw new Exception("Ошибка при добавлении образования: " . $conn->error);
        }
        $lastEducationId = $conn->insert_id;
        
        // Связь врач-образование
        $sql_connection_education = "INSERT INTO `connection_education` (`id_doctor`, `id_education`)
                                    VALUES ($lastDoctorId, $lastEducationId)";
        if (!$conn->query($sql_connection_education)) {
            throw new Exception("Ошибка при связывании врача и образования: " . $conn->error);
        }
    }

    // 3. Добавление квалификации (если есть данные)
    if (isset($_POST['qualif_improv_date'], $_POST['qualif_improv_name'], $_POST['qualif_improv_type'], 
              $_POST['qualif_improv_nameOrganization'], $_POST['medicalField_qe'])) {
        $qualif_improv_date = DateTime::createFromFormat('Y-m-d', $_POST['qualif_improv_date']);
        if (!$qualif_improv_date) {
            throw new Exception("Неверный формат даты квалификации");
        }
        $qualif_improv_date_formatted = $qualif_improv_date->format('Y-m-d');
        
        $qualif_improv_name = $conn->real_escape_string($_POST['qualif_improv_name']);
        $qualif_improv_type = $conn->real_escape_string($_POST['qualif_improv_type']);
        $qualif_improv_nameOrganization = $conn->real_escape_string($_POST['qualif_improv_nameOrganization']);
        $id_medicalField_qe = intval($_POST['medicalField_qe']);
        
        $sql_qe = "INSERT INTO `qualification_improvement` (`name`, `type`, `name_of_organizator`, `date`, `id_field`)
                   VALUES ('$qualif_improv_name', '$qualif_improv_type', '$qualif_improv_nameOrganization', 
                           '$qualif_improv_date_formatted', $id_medicalField_qe)";
        if (!$conn->query($sql_qe)) {
            throw new Exception("Ошибка при добавлении квалификации: " . $conn->error);
        }
        $lastQeId = $conn->insert_id;
        
        // Связь врач-квалификация
        $sql_connection_qualif_improve = "INSERT INTO `connection_qualif_improve` (`id_doctors`, `id_qualif_improve`)
                                         VALUES ($lastDoctorId, $lastQeId)";
        if (!$conn->query($sql_connection_qualif_improve)) {
            throw new Exception("Ошибка при связывании врача и квалификации: " . $conn->error);
        }
    }

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Врач успешно добавлен',
        'doctor_id' => $lastDoctorId
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}