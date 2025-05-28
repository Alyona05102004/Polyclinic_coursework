<?php
include 'bd.php';

if (!isset($_POST['id'])) {
    die(json_encode(['success' => false, 'message' => 'ID записи не указан']));
}

$appointmentId = intval($_POST['id']);

// Проверяем, есть ли медицинская история у этой записи
$checkSql = "SELECT id_medical_history FROM appointment WHERE id_appointment = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if ($appointment['id_medical_history'] != 0) {
    die(json_encode(['success' => false, 'message' => 'Нельзя отменить подтвержденную запись']));
}

// Обновляем запись, устанавливая id_patient в NULL
$updateSql = "UPDATE appointment SET id_patient = NULL WHERE id_appointment = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("i", $appointmentId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $conn->error]);
}