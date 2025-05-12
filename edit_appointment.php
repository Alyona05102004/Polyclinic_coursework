<?php

include 'bd.php';


if (isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $patient_id = intval($_GET['patient_id']);
    
    $sql = "SELECT appointment.id_appointment, appointment.date, 
                        staff.full_name AS doctor_name, staff.post AS doctor_post, 
                        cabinet.number_of_cabinet,
                        operating_ranges.range_start, operating_ranges.range_end, info_about_polyclinic.address as address
                        FROM appointment 
                        JOIN staff ON staff.id_doctor = appointment.id_doctor
                        JOIN cabinet ON cabinet.id_cabinet = appointment.id_cabinet
                        JOIN operating_ranges ON operating_ranges.id_ranges = appointment.id_ranges
                        JOIN department ON department.id_department=cabinet.id_department
                        JOIN connection ON connection.id_department=department.id_department
                        JOIN info_about_polyclinic ON connection.id_polyclinic=info_about_polyclinic.id_polyclinic
                        WHERE appointment.id_appointment = ?";
    

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    
    $sql_patient = "SELECT full_name FROM information_about_patient WHERE id_patient = ?";
    $stmt_patient = $conn->prepare($sql_patient);
    $stmt_patient->bind_param("i", $patient_id);
    $stmt_patient->execute();
    $result_patient = $stmt_patient->get_result();
    $patient_data = $result_patient->fetch_assoc();

    $sql_history="SELECT complaints, name_of_disease, 	symptoms, 	treatment_recommendations, medicament, name_of_field 
                    FROM `appointment`
                    JOIN medical_history ON medical_history.id_history=appointment.id_medical_history
                    JOIN disease ON disease.id_disease=medical_history.id_disease
                    JOIN field_of_medicine ON field_of_medicine.id_field=disease.id_field
                    WHERE appointment.id_appointment=?";

    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bind_param("i", $appointment_id);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    $history_data = $result_history->fetch_assoc();

    if ($appointment) {
        echo '
        <div class="appointment-details">
            <p><strong>Пациент:</strong> '.htmlspecialchars($patient_data['full_name']).'</p>
            <p><strong>Дата:</strong> '.$appointment['date'].'</p>
            <p><strong>Время:</strong> '.$appointment['range_start'].' - '.$appointment['range_end'].'</p>
            <p><strong>Врач:</strong> '.htmlspecialchars($appointment['doctor_name']).'</p>
            <p><strong>Должность:</strong> '.htmlspecialchars($appointment['doctor_post']).'</p>
            <p><strong>Кабинет:</strong> '.$appointment['number_of_cabinet'].'</p>
            <p><strong>Адрес:</strong> '.htmlspecialchars($appointment['address']).'</p>
        </div>';
    } else {
        echo 'Запись не найдена';
    }
} else {
    echo 'Не указан ID записи';
}
if ($history_data) {
    echo '
    <form id="editHistoryForm">
            <h5>Результаты приема</h5>
            <div class="mb-3">
                <label class="form-label">Жалобы</label>
                <textarea class="form-control" id="complaints" name="complaints" rows="2">'.htmlspecialchars($history_data['complaints'] ?? '').'</textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Симптомы</label>
                <textarea class="form-control" id="symptoms" name="symptoms" rows="2">'.htmlspecialchars($history_data['symptoms'] ?? '').'</textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Диагноз</label>
                <input type="text" class="form-control" id="diagnosis" name="diagnosis" value="'.htmlspecialchars($history_data['name_of_disease'] ?? '').'">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Рекомендации по лечению</label>
                <textarea class="form-control" id="treatment" name="treatment" rows="3">'.htmlspecialchars($history_data['treatment_recommendations'] ?? '').'</textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Назначенные препараты</label>
                <textarea class="form-control" id="medications"  name="medications" rows="2">'.htmlspecialchars($history_data['medicament'] ?? '').'</textarea>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="saveAppointmentData('.$appointment_id.')">Сохранить изменения</button>
        </form>
    </div>';
}
    
$conn->close();


?>