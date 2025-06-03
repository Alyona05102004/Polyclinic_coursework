<?php
header('Content-Type: application/json');
include 'bd.php';

function callProcedure($conn, $sql) {
    $data = [];
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        // Логируем ошибку и возвращаем сообщение об ошибке
        error_log("MySQL error: " . $conn->error);
        return ['success' => false, 'message' => 'Ошибка выполнения процедуры: ' . $conn->error];
    }
    return $data;
}

$appointment_id = intval($_POST['appointment_id'] ?? 0);
if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID записи']);
    exit;
}

// Вызов процедуры
$history_id = callProcedure($conn, "CALL createMedicalHistory($appointment_id)");

// Проверяем, есть ли ошибка
if (isset($history_id['success']) && !$history_id['success']) {
    echo json_encode($history_id);
    exit;
}

// Если процедура выполнена успешно, возвращаем ID истории болезни
echo json_encode(['success' => true, 'history_id' => $history_id[0]['history_id'] ?? null]);

?>