<?php
header('Content-Type: application/json');
include 'bd.php';

$appointment_id = intval($_POST['appointment_id'] ?? 0);
if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID записи']);
    exit;
}

// Получаем данные о записи
$stmt = $conn->prepare("SELECT id_patient, id_medical_history, id_doctor FROM appointment WHERE id_appointment = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Запись не найдена']);
    exit;
}

if ($appointment['id_medical_history'] != 0) {
    echo json_encode(['success' => false, 'message' => 'История болезни уже создана']);
    exit;
}

// Проверяем существование врача
$checkDoctor = $conn->prepare("SELECT id_doctor FROM staff WHERE id_doctor = ?");
$checkDoctor->bind_param("i", $appointment['id_doctor']);
$checkDoctor->execute();
if (!$checkDoctor->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Врач не существует']);
    exit;
}

// Проверяем пациента, если он есть
if ($appointment['id_patient'] > 0) {
    $checkPatient = $conn->prepare("SELECT id_patient FROM information_about_patient WHERE id_patient = ?");
    $checkPatient->bind_param("i", $appointment['id_patient']);
    $checkPatient->execute();
    if (!$checkPatient->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Пациент не существует']);
        exit;
    }
}

// Начинаем транзакцию
$conn->begin_transaction();

try {
    // Создаем медицинскую историю без болезни (id_disease = NULL)
    $stmt = $conn->prepare("INSERT INTO medical_history (complaints, id_disease) VALUES ('', NULL)");
    $stmt->execute();
    $history_id = $conn->insert_id;

    // Обновляем запись с медицинской историей
    $stmt = $conn->prepare("UPDATE appointment SET id_medical_history = ? WHERE id_appointment = ?");
    $stmt->bind_param("ii", $history_id, $appointment_id);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'История болезни создана', 
        'history_id' => $history_id
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при создании истории болезни: ' . $e->getMessage()
    ]);
}
?>