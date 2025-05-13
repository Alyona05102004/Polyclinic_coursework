<?php
include 'bd.php'; 

// Получаем id_department из GET-параметров
$department_id = isset($_GET['id_department']) ? $_GET['id_department'] : null;
if ($department_id == "all" || $department_id === null) {
    $sql_philter_doctor = "SELECT staff.id_doctor, staff.full_name FROM staff";
} else {
    $sql_philter_doctor = "SELECT staff.id_doctor, staff.full_name FROM staff WHERE staff.id_department = " . intval($department_id);
}
$sql_philter_doctor_result = $conn->query($sql_philter_doctor);
$doctors = $sql_philter_doctor_result ? $sql_philter_doctor_result->fetch_all(MYSQLI_ASSOC) : [];

header('Content-Type: application/json');
echo json_encode($doctors);
exit();
?>