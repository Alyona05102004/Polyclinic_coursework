<?php
include 'bd.php';

if (!isset($_GET['id'])) {
    die("ID записи не указан");
}

$appointmentId = intval($_GET['id']);


// Получаем данные о записи
$sql = "SELECT appointment.id_appointment, appointment.date, appointment.id_doctor, staff.full_name as doctorName, staff.post, staff.status,
    appointment.id_ranges, operating_ranges.range_start, operating_ranges.range_end, appointment.id_patient, 
    information_about_patient.full_name as patientName, appointment.id_cabinet, cabinet.number_of_cabinet, appointment.id_referral, 
    appointment.id_medical_history, department.id_department, department.name_department, info_about_polyclinic.id_polyclinic, 
    info_about_polyclinic.name_polyclinic, info_about_polyclinic.address
    FROM appointment
    LEFT JOIN staff ON staff.id_doctor=appointment.id_doctor
    JOIN operating_ranges ON operating_ranges.id_ranges=appointment.id_ranges
    LEFT JOIN information_about_patient ON information_about_patient.id_patient=appointment.id_patient
    JOIN cabinet ON cabinet.id_cabinet=appointment.id_cabinet
    JOIN department ON cabinet.id_department=department.id_department
    JOIN connection ON connection.id_department=department.id_department
    JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic=connection.id_polyclinic
    WHERE appointment.id_appointment=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if (!$appointment) {
    die("Запись не найдена");
}

// Получаем список свободных кабинетов в этом отделении на это время
$freeCabinetsSql = "SELECT c.id_cabinet, c.number_of_cabinet 
                   FROM cabinet c
                   WHERE c.id_department = ? 
                   AND c.id_cabinet NOT IN (
                       SELECT a.id_cabinet 
                       FROM appointment a
                       WHERE a.date = ? 
                       AND a.id_ranges = ?
                       AND a.id_appointment != ?
                   )";

$stmt = $conn->prepare($freeCabinetsSql);
$stmt->bind_param("isii", $appointment['id_department'], $appointment['date'], $appointment['id_ranges'], $appointmentId);
$stmt->execute();
$freeCabinets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Проверяем, есть ли уже медицинская история
$hasMedicalHistory = !empty($appointment['id_medical_history']);


$sql_history="SELECT complaints, name_of_disease, 	symptoms, 	treatment_recommendations, medicament, name_of_field 
                    FROM `appointment`
                    JOIN medical_history ON medical_history.id_history=appointment.id_medical_history
                    JOIN disease ON disease.id_disease=medical_history.id_disease
                    JOIN field_of_medicine ON field_of_medicine.id_field=disease.id_field
                    WHERE appointment.id_appointment=?";

    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bind_param("i", $appointmentId);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    $history_data = $result_history->fetch_assoc();


// Получаем список пациентов в зависимости от статуса врача
if ($appointment['status'] == 0) { // Если врач принимает только по направлению
    // Получаем пациентов с направлением к этому врачу, но без активной записи
    $sql_pacients = "SELECT DISTINCT information_about_patient.id_patient, information_about_patient.full_name 
                    FROM information_about_patient
                    JOIN referral ON referral.id_patient = information_about_patient.id_patient
                    WHERE referral.refrerral_doctor = ?
                    AND NOT EXISTS(
                        SELECT 1
                        FROM appointment
                        WHERE appointment.id_patient = information_about_patient.id_patient
                        AND appointment.id_doctor = ?
                    )";
    $stmt_pacients = $conn->prepare($sql_pacients);
    $stmt_pacients->bind_param("ii", $appointment['id_doctor'], $appointment['id_doctor']);
    $stmt_pacients->execute();
    $pacients = $stmt_pacients->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Получаем всех пациентов
    $sql_pacients = "SELECT id_patient, full_name FROM information_about_patient";
    $result_pacients = $conn->query($sql_pacients);
    $pacients = $result_pacients ? $result_pacients->fetch_all(MYSQLI_ASSOC) : [];
}

?>

<div class="container-fluid">
    <form id="editAppointmentForm">
        <input type="hidden" name="id_appointment" value="<?= htmlspecialchars($appointment['id_appointment']) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($appointment['date']) ?>">
        <input type="hidden" name="id_ranges" value="<?= htmlspecialchars($appointment['id_ranges']) ?>">
        <input type="hidden" name="id_doctor" value="<?= htmlspecialchars($appointment['id_doctor']) ?>">
        
        <p><strong>ID записи:</strong> <?= htmlspecialchars($appointment['id_appointment']) ?></p>
        <p><strong>Дата:</strong> <?= htmlspecialchars($appointment['date']) ?></p>
        <p><strong>Время:</strong> <?= $appointment['range_start'] ?> - <?= $appointment['range_end'] ?></p>
        <p><strong>Врач:</strong> <?= htmlspecialchars($appointment['doctorName'] ?? 'Не указан') ?></p>
        <p><strong>Должность:</strong> <?= htmlspecialchars($appointment['post'] ?? '') ?></p>
        <?php if ($appointment['id_patient'] != 0 ): ?>
            <p><strong>Пациент:</strong> <?= htmlspecialchars($appointment['patientName'] ?? 'Не указан') ?></p>
            <?php else: ?>
            <div class="mb-3">
                <label for="id_patientAppointment" class="form-label"><strong>Выбрать пациента:</strong></label>
                <select class="form-select" id="id_patientAppointment" name="id_patientAppointment" required>
                    <option value="">Выберите пациента</option>
                    <?php foreach ($pacients as $pacient): ?>
                        <option value="<?= $pacient['id_patient'] ?>">
                            <?= htmlspecialchars($pacient['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($appointment['status'] == 0 && empty($pacients)): ?>
                    <div class="alert alert-warning mt-2">Нет пациентов с направлением к этому врачу</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-3">
            <label for="id_cabinet" class="form-label"><strong>Кабинет:</strong></label>
            <select class="form-select" id="id_cabinet" name="id_cabinet" required>
                <?php foreach ($freeCabinets as $cabinet): ?>
                    <option value="<?= $cabinet['id_cabinet'] ?>" <?= $cabinet['id_cabinet'] == $appointment['id_cabinet'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cabinet['number_of_cabinet']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <p><strong>Отделение:</strong> <?= htmlspecialchars($appointment['name_department']) ?></p>  
        <p><strong>Поликлиника:</strong> <?= htmlspecialchars($appointment['name_polyclinic']) ?></p>
        <input type="hidden" name="existing_patient_id" value="<?= htmlspecialchars($appointment['id_patient']) ?>">
        <p><strong>Адрес:</strong> <?= htmlspecialchars($appointment['address']) ?></p>
        <p><strong>ID направление:</strong> <?= htmlspecialchars($appointment['id_referral']) ?></p>
        
        <?php if ($appointment['id_patient'] != 0 && $appointment['id_medical_history'] != 0): ?>
            <h5>Результаты приема</h5>
            <div class="mb-3">
                <label class="form-label">Область медицины</label>
                <select class="form-select" name="id_field" required>
                    <option value="">Выберите область</option>
                    <?php 
                    $fields = $conn->query("SELECT id_field, name_of_field FROM field_of_medicine");
                    while ($field = $fields->fetch_assoc()): ?>
                        <option value="<?= $field['id_field'] ?>" <?= ($field['id_field'] == ($history_data['id_field'] ?? 0)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($field['name_of_field']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Жалобы</label>
                <textarea class="form-control" id="complaintsAppointment" name="complaintsAppointment" rows="2"><?= htmlspecialchars($history_data['complaints'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Симптомы</label>
                <input type="text" class="form-control" id="symptomsAppointment" name="symptomsAppointment" value="<?=htmlspecialchars($history_data['symptoms'] ?? '')?>">
            </div>
            <input type="hidden" name="id_medical_history" value="<?= htmlspecialchars($appointment['id_medical_history'] ?? 0) ?>">
            <div class="mb-3">
                <label class="form-label">Диагноз</label>
                <input type="text" class="form-control" id="diagnosisAppointment" name="diagnosisAppointment" value="<?=htmlspecialchars($history_data['name_of_disease'] ?? '')?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Рекомендации по лечению</label>
                <textarea class="form-control" id="treatmentAppointment" name="treatmentAppointment" rows="3"><?= htmlspecialchars($history_data['treatment_recommendations'] ?? '')?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Назначенные препараты</label>
                <textarea class="form-control" id="medicationsAppointment"  name="medicationsAppointment" rows="2"><?= htmlspecialchars($history_data['medicament'] ?? '')?></textarea>
            </div>
        <?php endif; ?> 


        <div class="d-flex justify-content-between mt-4">
            <div>
                <?php 
                $canCancel = ($appointment['id_patient'] != 0) && 
                            (($appointment['date'] <= $currentDate) || 
                            ($appointment['date'] == $currentDate && $appointment['range_start'] <= $currentTime));
                if ($canCancel): ?>
                    <button type="button" class="btn btn-danger me-2 fixed-height-btn" onclick="cancelAppointment(<?= $appointment['id_appointment'] ?>)">
                        Отменить запись
                    </button>
                <?php endif; ?> 
            </div>
            <div>
                <?php if ($canCancel && $appointment['id_medical_history'] == 0 ): ?>
                    <button type="button" class="btn btn-success me-2 fixed-height-btn" onclick="confirmAppointment(<?= $appointment['id_appointment'] ?>)">
                        Подтвердить прием
                    </button>
                <?php endif; ?>
            </div>
            <div>
                <button type="button" class="btn btn-primary me-2 fixed-height-btn" onclick="updateAppointment(<?= $appointment['id_appointment'] ?>)">
                    Сохранить изменения
                </button>
            </div>
        </div>
    </form>
</div>
