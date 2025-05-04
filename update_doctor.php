<?php
include 'bd.php';

header('Content-Type: application/json');

$doctor_id = intval($_POST['doctor_id'] ?? 0);
$full_name = $_POST['full_name'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$address = $_POST['address'] ?? '';
$post = $_POST['post'] ?? '';
$status = $_POST['status'] ?? '';
$id_department = intval($_POST['id_department'] ?? 0);

$stmt = $conn->prepare("UPDATE staff SET full_name=?, 	birthday=?, phone_number=?, address=?, post=?, 	status=?, id_department=? WHERE id_doctor=?");
$stmt->bind_param("ssssssii", $full_name, $birthday, $phone_number, $address, $post, $status, $id_department, $doctor_id);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>
