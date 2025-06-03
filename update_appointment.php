<?php
header('Content-Type: application/json');
include 'bd.php';


error_log(print_r($_POST, true));

// Основные данные записи
$appointment_id = intval($_POST['id_appointment'] ?? 0);
$cabinet = intval($_POST['id_cabinet'] ?? 0);
$patient_id = intval($_POST['id_patientAppointment'] ?? 0);
$doctor_id = intval($_POST['id_doctor'] ?? 0);

// Если врач не передан, используем значение из базы данных
if ($doctor_id <= 0) {
    $checkAppointment = $conn->prepare("SELECT id_doctor FROM appointment WHERE id_appointment = ?");
    $checkAppointment->bind_param("i", $appointment_id);
    $checkAppointment->execute();
    $appointment = $checkAppointment->get_result()->fetch_assoc();
    $doctor_id = $appointment['id_doctor'] ?? 0;
}

if (!empty($_POST['id_patientAppointment'])) {
    $patient_id = intval($_POST['id_patientAppointment']);
} elseif (!empty($_POST['existing_patient_id'])) {
    $patient_id = intval($_POST['existing_patient_id']);
} else {
    $patient_id = 0;
}

// Всегда проверяем пациента, если ID не 0
if ($patient_id > 0) {
    $checkPatient = $conn->prepare("SELECT id_patient FROM information_about_patient WHERE id_patient = ?");
    $checkPatient->bind_param("i", $patient_id);
    $checkPatient->execute();
    if (!$checkPatient->get_result()->fetch_assoc()) {
        echo json_encode(['status' => 'error', 'message' => 'Указанный пациент не существует']);
        exit;
    }
}
error_log("Doctor ID: " . $doctor_id);
error_log("Appointment ID: " . $appointment_id);
error_log("Patient ID: " . $patient_id );

$disease_id = intval($_POST['disease_id'] ?? 0);
$complaints = $_POST['complaintsAppointment'] ?? '';
$symptoms = $_POST['symptomsAppointment'] ?? '';
$diagnosis = $_POST['diagnosisAppointment'] ?? '';
$treatment = $_POST['treatmentAppointment'] ?? '';
$medications = $_POST['medicationsAppointment'] ?? '';
$id_field = intval($_POST['id_field'] ?? 0);

// Проверяем, нужно ли обновлять медицинские данные
$isUpdatingMedicalData = (
    isset($_POST['complaintsAppointment']) || 
    isset($_POST['symptomsAppointment']) || 
    isset($_POST['diagnosisAppointment']) || 
    isset($_POST['treatmentAppointment']) || 
    isset($_POST['medicationsAppointment']) ||
    isset($_POST['id_field']) ||
    isset($_POST['disease_id'])
);
$checkAppointment = $conn->prepare("SELECT id_appointment, date, id_ranges FROM appointment WHERE id_appointment = ?");
$checkAppointment->bind_param("i", $appointment_id);
$checkAppointment->execute();
$appointment = $checkAppointment->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(['status' => 'error', 'message' => 'Запись не найдена']);
    exit;
}
if ($doctor_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Не указан врач']);
    exit;
}

$checkDoctor = $conn->prepare("SELECT id_doctor FROM staff WHERE id_doctor = ?");
$checkDoctor->bind_param("i", $doctor_id);
$checkDoctor->execute();
$doctorExists = $checkDoctor->get_result()->fetch_assoc();

if (!$doctorExists) {
    echo json_encode(['status' => 'error', 'message' => 'Указанный врач не существует']);
    exit;
}
$checkActiveAppointment = $conn->prepare("
    SELECT COUNT(*) AS count 
    FROM appointment
    JOIN staff ON appointment.id_doctor = staff.id_doctor
    JOIN department ON staff.id_department = department.id_department
    WHERE appointment.id_patient = ? 
    AND appointment.id_appointment != ?
    AND (
        appointment.date > CURDATE() 
        OR (
            appointment.date = CURDATE() 
            AND EXISTS (
                SELECT 1 FROM operating_ranges 
                WHERE operating_ranges.id_ranges = appointment.id_ranges 
                AND operating_ranges.range_start > CURTIME()
            )
        )
    )
    AND staff.id_department = (SELECT id_department FROM staff WHERE id_doctor = ? )
");
$checkActiveAppointment->bind_param("iii", $patient_id, $appointment_id, $doctor_id);
$checkActiveAppointment->execute();
$result = $checkActiveAppointment->get_result()->fetch_assoc();

if ($result['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Пациент уже имеет активную запись к врачу этой специальности']);
    exit;
}
// Обновляем запись на прием
$stmt = $conn->prepare("UPDATE appointment SET id_doctor=?, id_patient=?, id_cabinet=? WHERE id_appointment=?");
$stmt->bind_param("iiii", $doctor_id, $patient_id, $cabinet, $appointment_id);
$stmt->execute();

if ($stmt->errno) {
    error_log("SQL error (update appointment): " . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных при обновлении записи: ' . $stmt->error]);
    exit;
}

// Если врач заполняет медицинские данные, обновляем disease и medical_history
// Если врач заполняет медицинские данные
if ($isUpdatingMedicalData) {
    // Проверяем, что id_field передан и корректен (только при создании новой болезни)
    if ($disease_id == 0 && $id_field <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Не выбрана область медицины']);
        exit;
    }

    if ($disease_id > 0) {
        // Используем существующую болезнь
        // Можно добавить проверку, что болезнь существует
        $checkDisease = $conn->prepare("SELECT id_disease FROM disease WHERE id_disease = ?");
        $checkDisease->bind_param("i", $disease_id);
        $checkDisease->execute();
        if (!$checkDisease->get_result()->fetch_assoc()) {
            echo json_encode(['status' => 'error', 'message' => 'Указанная болезнь не существует']);
            exit;
        }
    } else {
        // Создаем новую болезнь
        $insertDisease = $conn->prepare("INSERT INTO disease 
            (name_of_disease, symptoms, treatment_recommendations, medicament, id_field) 
            VALUES (?, ?, ?, ?, ?)");
        $insertDisease->bind_param("ssssi", $diagnosis, $symptoms, $treatment, $medications, $id_field);
        $insertDisease->execute();
        $disease_id = $conn->insert_id;
    }

    // Обновляем medical_history
    if (!empty($appointment['id_medical_history'])) {
        $updateHistory = $conn->prepare("UPDATE medical_history SET 
            complaints = ?, 
            id_disease = ? 
            WHERE id_history = ?");
        $updateHistory->bind_param("sii", $complaints, $disease_id, $appointment['id_medical_history']);
        $updateHistory->execute();
    } else {
        // Если вдруг истории нет (хотя должна быть после подтверждения)
        $insertHistory = $conn->prepare("INSERT INTO medical_history 
            (complaints, id_disease) 
            VALUES (?, ?)");
        $insertHistory->bind_param("si", $complaints, $disease_id);
        $insertHistory->execute();
        $history_id = $conn->insert_id;

        // Связываем с записью
        $conn->query("UPDATE appointment SET id_medical_history = $history_id WHERE id_appointment = $appointment_id");
    }
}

echo json_encode(['status' => 'success', 'message' => 'Данные успешно обновлены']);

?>