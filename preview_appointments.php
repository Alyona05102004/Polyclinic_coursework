<?php
include 'bd.php';

// Получаем данные из POST-запроса
$input = json_decode(file_get_contents('php://input'), true);

$dateFrom = $input['dateFrom'];
$dateTo = $input['dateTo'];
$doctorId = $input['doctorId'];
$cabinetId = $input['cabinetId'];
$shiftHours = $input['shiftHours'];
$interval = $input['interval'];
$polyclinicId = $input['polyclinicId'];

function getPolyclinicSchedule($conn, $polyclinicId, $dayOfWeek){
    $sql = "SELECT start_time, end_time, is_working 
            FROM polyclinic_schedule 
            WHERE polyclinic_id = ? AND day_of_week = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $polyclinicId, $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Проверяем, есть ли у врача уже записи на эти даты
$sqlCheck = "SELECT appointment.date, appointment.id_ranges, operating_ranges.range_start, operating_ranges.range_end FROM appointment 
            JOIN operating_ranges ON appointment.id_ranges=operating_ranges.id_ranges
                WHERE id_doctor = ? AND appointment.date  BETWEEN ? AND ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("iss", $doctorId, $dateFrom, $dateTo);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    $busyDates = [];
    while ($row = $resultCheck->fetch_assoc()) {
        $busyDates[] = $row['date'] . ' ' . $row['range_start'] . '-' . $row['range_end'];
    }
    echo json_encode([
        'error' => 'У врача уже есть записи на следующие даты: ' . implode(', ', $busyDates)
    ]);
    exit();
}


$sqlCheckCabinet = "SELECT appointment.date, operating_ranges.range_start, operating_ranges.range_end, cabinet.number_of_cabinet
                   FROM appointment 
                   JOIN operating_ranges ON appointment.id_ranges = operating_ranges.id_ranges
                   JOIN cabinet ON cabinet.id_cabinet=appointment.id_cabinet
                   WHERE appointment.id_cabinet = ? AND appointment.date BETWEEN ? AND ?";
$stmtCheckCabinet = $conn->prepare($sqlCheckCabinet);
$stmtCheckCabinet->bind_param("iss", $doctorId, $dateFrom, $dateTo);
$stmtCheckCabinet->execute();
$resultCheckCabinet = $stmtCheckCabinet->get_result();

if ($resultCheckCabinet->num_rows > 0) {
    $busyCabinets = [];
    while ($row = $resultCheckCabinet->fetch_assoc()) {
        $busyCabinets[] = $row['date'] . ' ' . $row['range_start'] . '-' . $row['range_end'];
    }
    echo json_encode([
        'error' => 'Этот кабинет занят: ' . implode(', ', $busyCabinets)
    ]);
    exit();
}

// Получаем информацию о кабинете
$sqlCabinet = "SELECT number_of_cabinet FROM cabinet WHERE id_cabinet = ?";
$stmtCabinet = $conn->prepare($sqlCabinet);
$stmtCabinet->bind_param("i", $cabinetId);
$stmtCabinet->execute();
$cabinetResult = $stmtCabinet->get_result();
$cabinet = $cabinetResult->fetch_assoc();
$cabinetNumber = $cabinet['number_of_cabinet'];

// Генерируем список записей
$appointments = [];
$currentDate = new DateTime($dateFrom);
$endDate = new DateTime($dateTo);

// Функция для получения дня недели на русском
function getRussianDayOfWeek($date) {
    $days = [
        'воскресенье', 'понедельник', 'вторник', 
        'среда', 'четверг', 'пятница', 'суббота'
    ];
    return $days[$date->format('w')];
}

while ($currentDate <= $endDate) {
    $dayOfWeek = $currentDate->format('w'); // 0-воскресенье, 6-суббота
    $dateStr = $currentDate->format('Y-m-d');
    $russianDayOfWeek = getRussianDayOfWeek($currentDate);
    
    // Получаем график работы поликлиники на этот день
    $schedule = getPolyclinicSchedule($conn, $polyclinicId, $dayOfWeek);
    
    // Пропускаем нерабочие дни
    if (!$schedule || !$schedule['is_working']) {
        $currentDate->modify('+1 day');
        continue;
    }
    
    // Определяем рабочие часы с учетом графика поликлиники
    $workStart = strtotime($schedule['start_time']);
    $workEnd = strtotime($schedule['end_time']);
    
    // Корректируем время смены врача под график поликлиники
    $shiftStart = max($shiftHours['start'], date('H', $workStart));
    $shiftEnd = min($shiftHours['end'], date('H', $workEnd));
    
    // Генерируем временные слоты для текущего дня
    $currentHour = $shiftStart;
    
    while ($currentHour + ($interval/60) <= $shiftEnd) {
        $startHours = floor($currentHour);
        $startMinutes = ($currentHour - $startHours) * 60;
        $startTime = sprintf("%02d:%02d", $startHours, $startMinutes);
        
        $endTime = date("H:i", strtotime("+$interval minutes", strtotime($startTime)));
        
        $appointments[] = [
            'date' => $dateStr,
            'dayOfWeek' => $russianDayOfWeek,
            'timeStart' => $startTime,
            'timeEnd' => $endTime,
            'cabinetNumber' => $cabinetNumber
        ];
        
        $currentHour += $interval / 60;
    }
    
    $currentDate->modify('+1 day');
}

// Возвращаем результат
echo json_encode([
    'appointments' => $appointments
]);
?>