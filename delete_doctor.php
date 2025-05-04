<?php
include 'bd.php';

header('Content-Type: application/json');

$doctor_id = intval($_POST['doctor_id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM connection_qualif_improve WHERE id_doctors=?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM connection_education WHERE id_doctor=?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM staff WHERE id_doctor=?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>
