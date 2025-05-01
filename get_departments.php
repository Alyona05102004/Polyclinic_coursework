<?php
include 'bd.php'; 

$polyclinic_id = $_GET['id_polyclinic'];
if ($polyclinic_id == "all") {
    $sql_philter_department = "SELECT department.id_department, department.name_department FROM department";
} else {
    $sql_philter_department = "SELECT department.id_department, department.name_department FROM department JOIN connection ON connection.id_department = department.id_department WHERE connection.id_polyclinic = " . intval($polyclinic_id);
}
$sql_philter_department_result = $conn->query($sql_philter_department);
$departments = $sql_philter_department_result ? $sql_philter_department_result->fetch_all(MYSQLI_ASSOC) : [];

header('Content-Type: application/json');
echo json_encode($departments);
exit();
?>
