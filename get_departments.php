<?php
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
        error_log("MySQL error: " . $conn->error);
    }
    return $data;
}

// Получаем ID поликлиники и проверяем, что это число
$polyclinic_id = isset($_GET['id_polyclinic']) ? $_GET['id_polyclinic'] : 0;
$polyclinic_id = is_numeric($polyclinic_id) ? $polyclinic_id : 0;

// Вызываем процедуру с правильным параметром
$departments = callProcedure($conn, "CALL department_philter($polyclinic_id)");

header('Content-Type: application/json');
echo json_encode($departments);
exit();
?>