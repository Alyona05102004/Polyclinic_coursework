<?php
include 'bd.php'; 

$department_id = $_GET['id_department']?? null;

$sql = "SELECT id_cabinet, number_of_cabinet FROM cabinet WHERE id_department = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $departmentId);
$stmt->execute();
$result = $stmt->get_result();

$cabinets = [];
while ($row = $result->fetch_assoc()) {
    $cabinets[] = $row;
}

header('Content-Type: application/json');
echo json_encode($cabinets);
?>