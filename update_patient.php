<?php
include 'bd.php';

if (isset($_GET['id'])) {
    $patient_id = intval($_GET['id']);
    
    // Подготовка данных
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $birthday = $conn->real_escape_string($_POST['birthday']);
    $policy_number = $conn->real_escape_string($_POST['policy_number']);
    $address = $conn->real_escape_string($_POST['address']);
    $gender = $conn->real_escape_string($_POST['gender']);
    
    // SQL запрос для обновления
    $sql = "UPDATE information_about_patient SET 
            full_name = '$full_name',
            birthday = '$birthday',
            policy_number = '$policy_number',
            address = '$address',
            gender = '$gender'
            WHERE id_patient = $patient_id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Не указан ID пациента']);
}

$conn->close();
?>