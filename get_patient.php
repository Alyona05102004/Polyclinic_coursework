<?php
include 'bd.php'; 

if (isset($_GET['id'])) {
    $patient_id = intval($_GET['id']);
    
    // Основная информация о пациенте
    $sql = "SELECT * FROM `information_about_patient` WHERE id_patient = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_data = $result->fetch_assoc();
    
    // Информация о записях пациента
    $sql_appointment = "SELECT appointment.id_appointment, appointment.date, 
                        staff.full_name AS doctor_name, staff.post AS doctor_post, 
                        cabinet.number_of_cabinet,
                        operating_ranges.range_start, operating_ranges.range_end
                        FROM appointment 
                        JOIN staff ON staff.id_doctor = appointment.id_doctor
                        JOIN cabinet ON cabinet.id_cabinet = appointment.id_cabinet
                        JOIN operating_ranges ON operating_ranges.id_ranges = appointment.id_ranges
                        WHERE appointment.id_patient = ?";
    
    $stmt = $conn->prepare($sql_appointment);
    if ($stmt === false) {
        die("Error preparing appointment statement: " . $conn->error);
    }
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Направления пациента
    $sql_referral = "SELECT referral.id_referral, referral.date_of_start, DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) AS date_of_end, referral.id_patient, referral.id_doctor, 
                    staff_doctor.full_name AS doctorName,staff_doctor.post AS DoctorPost, referral.refrerral_doctor, staff_referral.full_name AS referralDoctorName , staff_referral.post AS referralDoctorPost
                    FROM `referral`
                    JOIN staff AS staff_doctor ON staff_doctor.id_doctor = referral.id_doctor
                    JOIN staff AS staff_referral ON staff_referral.id_doctor = referral.refrerral_doctor
                    WHERE referral.id_patient = ?";
    
    $stmt = $conn->prepare($sql_referral);
    if ($stmt === false) {
        die("Error preparing referral statement: " . $conn->error);
    }
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Формируем HTML для отображения
    $html = '<div class="card mb-4">
                <div class="card-header">
                    <h3>Личная информация</h3>
                </div>
                <div class="card-body">
                    <form id="patientForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ФИО</label>
                                    <input type="text" class="form-control" name="full_name" value="'.htmlspecialchars($patient_data['full_name']).'">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Дата рождения</label>
                                    <input type="date" class="form-control" name="birthday" value="'.$patient_data['birthday'].'">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Номер полиса</label>
                                    <input type="text" class="form-control" name="policy_number" value="'.htmlspecialchars($patient_data['policy_number']).'">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Пол</label>
                                    <select class="form-select" name="gender">
                                        <option value="М" '.($patient_data['gender'] == 'М' ? 'selected' : '').'>Мужской</option>
                                        <option value="Ж" '.($patient_data['gender'] == 'Ж' ? 'selected' : '').'>Женский</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Адрес</label>
                            <input type="text" class="form-control" name="address" value="'.htmlspecialchars($patient_data['address']).'">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="savePatientInfo('.$patient_id.')">Сохранить изменения</button>
                    </form>
                </div>
            </div>';
    
    // Таблица записей/направлений
    $html .= '<div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Медицинские данные</h3>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="dataType" id="appointmentsRadio" autocomplete="off" checked>
                            <label class="btn btn-outline-primary" for="appointmentsRadio">Записи</label>
                            
                            <input type="radio" class="btn-check" name="dataType" id="referralsRadio" autocomplete="off">
                            <label class="btn btn-outline-primary" for="referralsRadio">Направления</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="appointmentsTable">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Врач</th>
                                    <th>Должность</th>
                                    <th>Кабинет</th>
                                    <th>Время</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    foreach ($appointments as $appointment) {
        $html .= '<tr style="cursor: pointer;" class="patient-appointment" data-id="' . htmlspecialchars($appointment['id_appointment']) . '">
                    <td>'.$appointment['date'].'</td>
                    <td>'.htmlspecialchars($appointment['doctor_name']).'</td>
                    <td>'.htmlspecialchars($appointment['doctor_post']).'</td>
                    <td>'.$appointment['number_of_cabinet'].'</td>
                    <td>'.$appointment['range_start'].' - '.$appointment['range_end'].'</td>
                  </tr>';
    }
    
    $html .= '</tbody></table></div>
              <div id="referralsTable" style="display: none;">
                  <table class="table">
                      <thead>
                          <tr>
                              <th>Дата начала</th>
                              <th>Дата окончания</th>
                              <th>Врач по направлению</th>
                              <th>Должность</th>
                              <th>Врач, выдавший направление</th>
                              <th>Должность</th>
                          </tr>
                      </thead>
                      <tbody>';
    
    foreach ($referrals as $referral) {
        $html .= '<tr style="cursor: pointer;" class="patient-referral" data-id="' . htmlspecialchars($referral['id_referral']) . '">
                    <td>'.$referral['date_of_start'].'</td>
                    <td>'.$referral['date_of_end'].'</td>
                    <td>'.htmlspecialchars($referral['referralDoctorName']).'</td>
                    <td>'.htmlspecialchars($referral['referralDoctorPost']).'</td>
                    <td>'.htmlspecialchars($referral['doctorName']).'</td>
                    <td>'.htmlspecialchars($referral['DoctorPost']).'</td>
                  </tr>';
    }
    
    $html .= '</tbody></table></div></div></div>';


    echo $html;
}

$conn->close();
?>

<style>
    .btn-group .btn-outline-primary {
        color: white;
        border-color: #11999e;
    }
    
    .btn-group .btn-outline-primary:hover {
        background-color:white;
        border-color: #11999e;
        color:  #11999e ;
    }

    .btn-check:checked + .btn-outline-primary {
        background-color: #11999e;
        border-color: #11999e;
        color: white;
    }
</style>

