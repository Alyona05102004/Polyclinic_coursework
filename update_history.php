<?php
include 'bd.php';

header('Content-Type: application/json');

try {
    // Проверяем метод запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Только POST-запросы разрешены");
    }

    // Получаем и проверяем данные
    $appointment_id = intval($_POST['id'] ?? 0);
    if ($appointment_id <= 0) {
        throw new Exception("Неверный ID приёма");
    }

    $complaints = $_POST['complaints'] ?? '';
    $symptoms = $_POST['symptoms'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment = $_POST['treatment'] ?? '';
    $medications = $_POST['medications'] ?? '';

    // Начинаем транзакцию
    $conn->begin_transaction();

    // Ваш первый запрос без изменений
    $stmt = $conn->prepare("SELECT id_medical_history FROM appointment WHERE id_appointment = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Запись о приеме не найдена");
    }
    
    $row = $result->fetch_assoc();
    $id_history = $row['id_medical_history'];

    // Ваш второй запрос без изменений
    $stmt = $conn->prepare("UPDATE medical_history SET complaints=? WHERE id_history=?");
    $stmt->bind_param("si", $complaints, $id_history);
    $stmt->execute();

    // Ваш третий запрос без изменений
    $stmt = $conn->prepare("SELECT id_disease FROM medical_history WHERE id_history = ?");
    $stmt->bind_param("i", $id_history);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Медицинская история не найдена");
    }
    
    $row = $result->fetch_assoc();
    $id_disease = $row['id_disease'];

    // Ваш четвертый запрос без изменений (исправлена только привязка параметров)
    $stmt = $conn->prepare("UPDATE disease SET name_of_disease=?, symptoms=?, treatment_recommendations=?, medicament=? WHERE id_disease=?");
    $stmt->bind_param("ssssi", $diagnosis, $symptoms, $treatment, $medications, $id_disease); // Исправлено: используем $id_disease вместо $id_history
    $stmt->execute();

    // Фиксируем транзакцию
    $conn->commit();

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>