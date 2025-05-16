<?php
include 'bd.php';

// Получаем данные из POST-запроса
$firstName = $_POST['firstName_patient'] ?? '';
$lastName = $_POST['lastName_patient'] ?? '';
$middleName = $_POST['middleName_patient'] ?? '';
$birthDate = $_POST['birthDate_patient'] ?? '';
$address = $_POST['patientAdress'] ?? '';
$policyNumber = $_POST['policy_number'] ?? '';
$gender = $_POST['new_patient_gender'] ?? '';

// Формируем полное имя
$fullName = trim("$lastName $firstName $middleName");

// Подготавливаем SQL-запрос
$sql = "INSERT INTO information_about_patient (full_name, birthday, policy_number, address, gender) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}

// Привязываем параметры
$stmt->bind_param("sssss", $fullName, $birthDate, $policyNumber, $address, $gender);

// Выполняем запрос
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Пациент успешно добавлен!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>