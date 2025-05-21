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

$department_id = isset($_GET['id_department']) ? $_GET['id_department'] : 0;
$department_id = is_numeric($department_id) ? $department_id : 0;

// Вызываем процедуру с правильным параметром
$doctors = callProcedure($conn, "CALL doctor_philter($department_id)");

header('Content-Type: application/json');
echo json_encode($doctors);
exit();
?>