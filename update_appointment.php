<?php
header('Content-Type: application/json');
include 'bd.php';


error_log(print_r($_POST, true));

// Основные данные записи
$appointment_id = intval($_POST['id_appointment'] ?? 0);
$doctor_id = intval($_POST['id_doctor'] ?? 0);
$cabinet = intval($_POST['id_cabinet'] ?? 0);
$patient_id = intval($_POST['id_patientAppointment'] ?? 0);

$disease_id = intval($_POST['disease_id'] ?? 0);
$complaints = $_POST['complaintsAppointment'] ?? '';
$symptoms = $_POST['symptomsAppointment'] ?? '';
$diagnosis = $_POST['diagnosisAppointment'] ?? '';
$treatment = $_POST['treatmentAppointment'] ?? '';
$medications = $_POST['medicationsAppointment'] ?? '';
$id_field = intval($_POST['id_field'] ?? 0);

// Проверяем, существует ли запись
$checkAppointment = $conn->prepare("SELECT id_appointment, date, id_ranges FROM appointment WHERE id_appointment = ?");
$checkAppointment->bind_param("i", $appointment_id);
$checkAppointment->execute();
$appointment = $checkAppointment->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(['status' => 'error', 'message' => 'Запись не найдена']);
    exit;
}

$checkActiveAppointment = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM appointment a
    JOIN staff s ON a.id_doctor = s.id_doctor
    JOIN field_of_medicine fm ON s.id_field = fm.id_field
    WHERE a.id_patient = ? 
    AND a.id_appointment != ?
    AND a.date >= CURDATE()
    AND s.id_field = (SELECT id_field FROM staff WHERE id_doctor = ?)
");
$checkActiveAppointment->bind_param("iii", $patient_id, $appointment_id, $doctor_id);
$checkActiveAppointment->execute();
$result = $checkActiveAppointment->get_result()->fetch_assoc();

if ($result['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Пациент уже имеет активную запись к врачу этой специальности']);
    exit;
}

// Обновляем основные данные записи
$stmt = $conn->prepare("UPDATE appointment SET id_doctor=?, id_patient=?, id_cabinet=? WHERE id_appointment=?");
$stmt->bind_param("iiii", $doctor_id, $patient_id, $cabinet, $appointment_id);
$stmt->execute();
if ($disease_id > 0) {
    // Обновляем данные болезни
    $updateDisease = $conn->prepare("UPDATE disease SET 
        name_of_disease = ?, 
        symptoms = ?, 
        treatment_recommendations = ?, 
        medicament = ?, 
        id_field = ? 
        WHERE id_disease = ?");
    $updateDisease->bind_param("ssssii", $diagnosis, $symptoms, $treatment, $medications, $id_field, $disease_id);
    $updateDisease->execute();
} else {
    // Создаем новую болезнь
    $insertDisease = $conn->prepare("INSERT INTO disease 
        (name_of_disease, symptoms, treatment_recommendations, medicament, id_field) 
        VALUES (?, ?, ?, ?, ?)");
    $insertDisease->bind_param("ssssi", $diagnosis, $symptoms, $treatment, $medications, $id_field);
    $insertDisease->execute();
    $disease_id = $conn->insert_id;
}

// Создаем или обновляем медицинскую историю
if (!empty($appointment['id_medical_history'])) {
    $updateHistory = $conn->prepare("UPDATE medical_history SET 
        complaints = ?, 
        id_disease = ? 
        WHERE id_history = ?");
    $updateHistory->bind_param("sii", $complaints, $disease_id, $appointment['id_medical_history']);
    $updateHistory->execute();
} else {
    $insertHistory = $conn->prepare("INSERT INTO medical_history 
        (complaints, id_disease) 
        VALUES (?, ?)");
    $insertHistory->bind_param("si", $complaints, $disease_id);
    $insertHistory->execute();
    $history_id = $conn->insert_id;
    
    // Связываем с записью
    $conn->query("UPDATE appointment SET id_medical_history = $history_id WHERE id_appointment = $appointment_id");
}
echo json_encode(['status' => 'success', 'message' => 'Данные успешно обновлены']);

?>



