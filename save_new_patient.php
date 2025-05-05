<?php
header('Content-Type: application/json'); // Важно: установить заголовок JSON

include 'bd.php'; 

// Проверка обязательных полей
$required_fields = ['firstName_patient', 'lastName_patient', 'birthDate_patient', 'patientAdress', 
                   'policy_number'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Не все обязательные поля заполнены: ' . $field]);
        exit;
    }
}
try {
    $firstName_patient = $conn->real_escape_string($_POST['firstName_patient']);
    $lastName_patient = $conn->real_escape_string($_POST['lastName_patient']);
    $middleName_patient = $conn->real_escape_string($_POST['middleName_patient'] ?? '');
    $fullName_patient = $lastName_patient . ' ' . $firstName_patient . ' ' . $middleName_patient;
    
    // Обработка даты
    $birthDate = DateTime::createFromFormat('Y-m-d', $_POST['birthDate_patient']);
    if (!$birthDate) {
        throw new Exception("Неверный формат даты рождения");
    }
    $birthDateFormatted = $birthDate->format('Y-m-d');


    $patientAdress = $conn->real_escape_string($_POST['patientAdress']);
    $policy_number = intval($_POST['policy_number']);
    $new_patient_gender=$conn->real_escape_string($_POST['new_patient_gender']);
    
    $check_policy_sql = "SELECT id_patient FROM information_about_patient WHERE policy_number = '$policy_number'";
    $check_result = $conn->query($check_policy_sql);
    if ($check_result && $check_result->num_rows > 0) {
        throw new Exception("Пациент с таким номером полиса уже существует");
    }
    
    // Начало транзакции
    $conn->begin_transaction();

    // 1. Добавление врача
    $sql_patient = "INSERT INTO `information_about_patient` (`full_name`, `birthday`, `policy_number`, `address`, `gender`)
                   VALUES ('$fullName_patient', '$birthDateFormatted', '$policy_number', '$patientAdress', '$new_patient_gender' )";
    if (!$conn->query($sql_patient)) {
        throw new Exception("Ошибка при добавлении врача: " . $conn->error);
    }
    $lastPatientId = $conn->insert_id;

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Пациент успешно добавлен',
        'patient_id' => $lastPatientId
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