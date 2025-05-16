<?php
include 'bd.php';

// Получаем данные из POST-запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Неверный формат данных']);
    exit();
}

$appointments = $input['appointments'];
$doctorId = $input['doctorId'];
$cabinetId = $input['cabinetId'];

// Включаем отчет об ошибках
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Начинаем транзакцию
    $conn->begin_transaction();

    // Подготавливаем запрос для поиска диапазона времени
    $sqlFindRange = "SELECT id_ranges FROM operating_ranges WHERE range_start = ? AND range_end = ?";
    $stmtFindRange = $conn->prepare($sqlFindRange);
    
    // Подготавливаем запрос для добавления диапазона времени
    $sqlInsertRange = "INSERT INTO operating_ranges (range_start, range_end) VALUES (?, ?)";
    $stmtInsertRange = $conn->prepare($sqlInsertRange);
    
    // Подготавливаем запрос для добавления записи
    $sqlInsertAppointment = "INSERT INTO appointment 
                            (date, id_doctor, id_ranges, id_cabinet, id_patient, id_referral, id_medical_history) 
                            VALUES (?, ?, ?, ?, NULL, NULL, NULL)";
    $stmtInsertAppointment = $conn->prepare($sqlInsertAppointment);

    foreach ($appointments as $appointment) {
        // 1. Проверяем существование диапазона времени
        $stmtFindRange->bind_param("ss", $appointment['timeStart'], $appointment['timeEnd']);
        $stmtFindRange->execute();
        $result = $stmtFindRange->get_result();
        
        if ($result->num_rows > 0) {
            $range = $result->fetch_assoc();
            $rangeId = $range['id_ranges'];
        } else {
            // 2. Если диапазона нет - создаем новый
            $stmtInsertRange->bind_param("ss", $appointment['timeStart'], $appointment['timeEnd']);
            $stmtInsertRange->execute();
            $rangeId = $conn->insert_id;
        }
        
        // 3. Создаем запись на прием
        $stmtInsertAppointment->bind_param("siii", 
            $appointment['date'], 
            $doctorId, 
            $rangeId, 
            $cabinetId
        );
        $stmtInsertAppointment->execute();
    }

    // Фиксируем транзакцию
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Успешно создано ' . count($appointments) . ' записей',
        'created' => count($appointments)
    ]);
    
} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    
    echo json_encode([
        'error' => 'Ошибка при сохранении: ' . $e->getMessage(),
        'details' => $conn->error
    ]);
} finally {
    // Закрываем соединения
    if (isset($stmtFindRange)) $stmtFindRange->close();
    if (isset($stmtInsertRange)) $stmtInsertRange->close();
    if (isset($stmtInsertAppointment)) $stmtInsertAppointment->close();
}
?>