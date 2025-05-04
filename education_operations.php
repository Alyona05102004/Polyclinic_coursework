<?php
include 'bd.php';

header('Content-Type: application/json');

$operation = $_POST['operation'] ?? '';
$doctor_id = intval($_POST['doctor_id'] ?? 0);

switch ($operation) {
    case 'add':
        // Добавление нового образования
        $type = $_POST['type'];
        $institution = $_POST['institution'];
        $start_year = $_POST['start_year'];
        $end_year = $_POST['end_year'];
        $work_experience=intval($_POST['work_experience']);
        $field_id = intval($_POST['field_id']);
        
        $stmt = $conn->prepare("INSERT INTO education (work_experience, type_of_education, educational_institution, year_of_start, year_of_end, id_field) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $work_experience, $type, $institution, $start_year, $end_year, $field_id);
        $stmt->execute();
        $education_id = $stmt->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO connection_education (id_doctor, id_education) VALUES (?, ?)");
        $stmt->bind_param("ii", $doctor_id, $education_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success', 'id' => $education_id]);
        break;
        
    case 'update':
        // Обновление существующего образования
        $education_id = intval($_POST['education_id']);
        $type = $_POST['type'];
        $institution = $_POST['institution'];
        $start_year = $_POST['start_year'];
        $end_year = $_POST['end_year'];
        $field_id = intval($_POST['field_id']);
        $work_experience=intval($_POST['work_experience']);
        
        $stmt = $conn->prepare("UPDATE education SET work_experience=?, type_of_education=?, educational_institution=?, year_of_start=?, year_of_end=?, id_field=? WHERE id_education=?");
        $stmt->bind_param("ssssii", $work_experience, $type, $institution, $start_year, $end_year, $field_id, $education_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success']);
        break;
        
    case 'delete':
        // Удаление образования
        $education_id = intval($_POST['education_id']);
        
        $stmt = $conn->prepare("DELETE FROM connection_education WHERE id_education=?");
        $stmt->bind_param("i", $education_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM education WHERE id_education=?");
        $stmt->bind_param("i", $education_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid operation']);
}
?>