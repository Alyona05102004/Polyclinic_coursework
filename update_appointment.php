<?php
include 'bd.php';

header('Content-Type: application/json');
error_log(print_r($_POST, true));

// Основные данные записи
$appointment_id = intval($_POST['id_appointment'] ?? 0);
$doctor_id = intval($_POST['id_doctor'] ?? 0);
$cabinet = intval($_POST['id_cabinet'] ?? 0);
$patient_id = intval($_POST['id_patientAppointment'] ?? 0);

/* Данные медицинской истории
$medical_history_id = intval($_POST['id_medical_history'] ?? 0);
$complaints = $_POST['complaintsAppointment'] ?? '';
$symptoms = $_POST['symptomsAppointment'] ?? '';
$diagnosis = $_POST['diagnosisAppointment'] ?? '';
$treatment = $_POST['treatmentAppointment'] ?? '';
$medications = $_POST['medicationsAppointment'] ?? '';
$id_field = intval($_POST['id_field'] ?? 0);
*/
// Проверяем, существует ли запись
$checkAppointment = $conn->prepare("SELECT id_appointment, date, id_ranges FROM appointment WHERE id_appointment = ?");
$checkAppointment->bind_param("i", $appointment_id);
$checkAppointment->execute();
$appointment = $checkAppointment->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(['status' => 'error', 'message' => 'Запись не найдена']);
    exit;
}

/* Проверяем, наступила ли дата записи
$currentDate = date('Y-m-d');
$appointmentDate = $appointment['date'];

// Получаем текущее время
$currentTime = date('H:i:s');
$rangeSql = "SELECT range_start FROM operating_ranges WHERE id_ranges = ?";
$rangeStmt = $conn->prepare($rangeSql);
$rangeStmt->bind_param("i", $appointment['id_ranges']);
$rangeStmt->execute();
$rangeResult = $rangeStmt->get_result();
$rangeData = $rangeResult->fetch_assoc();
$appointmentTime = $rangeData['range_start'] ?? '00:00:00';

$canEditHistory = ($appointmentDate < $currentDate) || 
                 ($appointmentDate == $currentDate && $appointmentTime <= $currentTime);
*/
// Обновляем основные данные записи
$stmt = $conn->prepare("UPDATE appointment SET id_doctor=?, id_patient=?, id_cabinet=? WHERE id_appointment=?");
$stmt->bind_param("iiii", $doctor_id, $patient_id, $cabinet, $appointment_id);
$stmt->execute();
/*
// Если можно редактировать историю и есть данные
if ($canEditHistory && (!empty($complaints) || !empty($symptoms) || !empty($diagnosis))) {
    // Проверяем, существует ли медицинская история
    if ($medical_history_id == 0) {
        // Создаем новую болезнь
        $diseaseStmt = $conn->prepare("INSERT INTO disease (name_of_disease, symptoms, treatment_recommendations, medicament, id_field) VALUES (?, ?, ?, ?, ?)");
        $diseaseStmt->bind_param("ssssi", $diagnosis, $symptoms, $treatment, $medications, $id_field);
        $diseaseStmt->execute();
        $disease_id = $conn->insert_id;
        
        // Создаем медицинскую историю
        $historyStmt = $conn->prepare("INSERT INTO medical_history (complaints, id_disease) VALUES (?, ?)");
        $historyStmt->bind_param("si", $complaints, $disease_id);
        $historyStmt->execute();
        $medical_history_id = $conn->insert_id;
        
        // Связываем с записью
        $updateAppStmt = $conn->prepare("UPDATE appointment SET id_medical_history = ? WHERE id_appointment = ?");
        $updateAppStmt->bind_param("ii", $medical_history_id, $appointment_id);
        $updateAppStmt->execute();
    } else {
        // Обновляем существующую историю
        // Сначала получаем id_disease из медицинской истории
        $getDiseaseStmt = $conn->prepare("SELECT id_disease FROM medical_history WHERE id_history = ?");
        $getDiseaseStmt->bind_param("i", $medical_history_id);
        $getDiseaseStmt->execute();
        $diseaseResult = $getDiseaseStmt->get_result();
        $diseaseData = $diseaseResult->fetch_assoc();
        $disease_id = $diseaseData['id_disease'] ?? 0;
        
        if ($disease_id > 0) {
            // Обновляем болезнь
            $updateDiseaseStmt = $conn->prepare("UPDATE disease SET name_of_disease=?, symptoms=?, treatment_recommendations=?, medicament=?, id_field=? WHERE id_disease=?");
            $updateDiseaseStmt->bind_param("ssssii", $diagnosis, $symptoms, $treatment, $medications, $id_field, $disease_id);
            $updateDiseaseStmt->execute();
            
            // Обновляем медицинскую историю
            $updateHistoryStmt = $conn->prepare("UPDATE medical_history SET complaints=?, id_disease=? WHERE id_history=?");
            $updateHistoryStmt->bind_param("sii", $complaints, $disease_id, $medical_history_id);
            $updateHistoryStmt->execute();
        }
    }
}
*/
echo json_encode(['status' => 'success', 'message' => 'Данные успешно обновлены']);
?>

