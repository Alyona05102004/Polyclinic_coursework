<?php
header('Content-Type: application/json');
require_once 'db_connect.php'; // Подключение к БД

$polyclinic_id = isset($_GET['polyclinic_id']) ? intval($_GET['polyclinic_id']) : null;
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : null;

if (!$polyclinic_id || !$doctor_id) {
    echo json_encode(['error' => 'Требуются polyclinic_id и doctor_id']);
    exit;
}

// Функция для парсинга расписания из строки в массив
function parseSchedule($scheduleStr) {
    $result = [
        'Пн' => 'Выходной',
        'Вт' => 'Выходной',
        'Ср' => 'Выходной',
        'Чт' => 'Выходной',
        'Пт' => 'Выходной',
        'Сб' => 'Выходной',
        'Вс' => 'Выходной'
    ];

    // Обрабатываем диапазоны дней (например, "Пн-Пт")
    if (preg_match_all('/([А-я]{2})-([А-я]{2}):\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $scheduleStr, $matches)) {
        foreach ($matches[0] as $key => $fullMatch) {
            $startDay = $matches[1][$key];
            $endDay = $matches[2][$key];
            $time = $matches[3][$key] . '-' . $matches[4][$key];
            
            $days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            $startIndex = array_search($startDay, $days);
            $endIndex = array_search($endDay, $days);
            
            for ($i = $startIndex; $i <= $endIndex; $i++) {
                $result[$days[$i]] = $time;
            }
        }
    }

    // Обрабатываем отдельные дни (например, "Сб: 09:00 - 13:00")
    if (preg_match_all('/([А-я]{2}):\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $scheduleStr, $matches)) {
        foreach ($matches[1] as $key => $day) {
            $result[$day] = $matches[2][$key] . '-' . $matches[3][$key];
        }
    }

    return $result;
}

// 1. Получаем расписание поликлиники
$query = "SELECT work_schedule FROM polyclinics WHERE id_polyclinic = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$polyclinic_id]);
$polyclinic = $stmt->fetch(PDO::FETCH_ASSOC);

$polyclinic_schedule = [];
if ($polyclinic) {
    $polyclinic_schedule = parseSchedule($polyclinic['work_schedule']);
}



$doctor_schedule = [];
$doctor_schedule = $polyclinic_schedule;


echo json_encode([
    'polyclinic' => $polyclinic_schedule,
    'doctor' => $doctor_schedule
]);
?>