<?php
include 'bd.php'; 

$department_id = $_GET['id_department']?? null;

$sql = "SELECT id_cabinet, number_of_cabinet FROM cabinet WHERE id_department = " . intval($department_id);
$sql_result = $conn->query($sql);
$cabinets = $sql_result ? $sql_result->fetch_all(MYSQLI_ASSOC) : [];


header('Content-Type: application/json');
echo json_encode($cabinets);
?>