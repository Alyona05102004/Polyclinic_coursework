<?php
include 'bd.php';

header('Content-Type: application/json');

$operation = $_POST['operation'] ?? '';
$doctor_id = intval($_POST['doctor_id'] ?? 0);

switch ($operation) {
    case 'add':
        // Добавление новой квалификации
        $name = $_POST['name'];
        $type = $_POST['type'];
        $organizator = $_POST['organizator'];
        $date = $_POST['date'];
        $field_id = intval($_POST['field_id']);
        
        $stmt = $conn->prepare("INSERT INTO qualification_improvement (name, type, name_of_organizator, date, id_field) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $type, $organizator, $date, $field_id);
        $stmt->execute();
        $qualif_id = $stmt->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO connection_qualif_improve (id_doctors, id_qualif_improve) VALUES (?, ?)");
        $stmt->bind_param("ii", $doctor_id, $qualif_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success', 'id' => $qualif_id]);
        break;
        
    case 'update':
        // Обновление квалификации
        $qualif_id = intval($_POST['qualif_id']);
        $name = $_POST['name'];
        $type = $_POST['type'];
        $organizator = $_POST['organizator'];
        $date = $_POST['date'];
        $field_id = intval($_POST['field_id']);
        
        $stmt = $conn->prepare("UPDATE qualification_improvement SET name=?, type=?, name_of_organizator=?, date=?, id_field=? WHERE id_qualif_improv=?");
        $stmt->bind_param("ssssii", $name, $type, $organizator, $date, $field_id, $qualif_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success']);
        break;
        
    case 'delete':
        // Удаление квалификации
        $qualif_id = intval($_POST['qualif_id']);
        
        $stmt = $conn->prepare("DELETE FROM connection_qualif_improve WHERE id_qualif_improve=?");
        $stmt->bind_param("i", $qualif_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM qualification_improvement WHERE id_qualif_improv=?");
        $stmt->bind_param("i", $qualif_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid operation']);
}
?>