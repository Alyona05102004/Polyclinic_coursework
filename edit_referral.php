<?php

include 'bd.php';


if (isset($_GET['id'])) {
    $referral_id = intval($_GET['id']);
    $patient_id = intval($_GET['patient_id']);
    
    $sql = "SELECT referral.id_referral, referral.date_of_start, 
               DATE_ADD(referral.date_of_start, INTERVAL referral.duration DAY) AS date_of_end, 
               referral.id_patient, referral.id_doctor, 
               staff_doctor.full_name AS doctorName, staff_doctor.post AS DoctorPost, 
               referral.refrerral_doctor, staff_referral.full_name AS referralDoctorName, 
               staff_referral.post AS referralDoctorPost
        FROM `referral`
        JOIN staff AS staff_doctor ON staff_doctor.id_doctor = referral.id_doctor
        JOIN staff AS staff_referral ON staff_referral.id_doctor = referral.refrerral_doctor
        WHERE referral.id_referral = ? AND referral.id_patient = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $referral_id, $patient_id);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $referral = $result->fetch_assoc();
    
    $sql_patient = "SELECT full_name FROM information_about_patient WHERE id_patient = ?";
    $stmt_patient = $conn->prepare($sql_patient);
    $stmt_patient->bind_param("i", $patient_id);
    $stmt_patient->execute();
    $result_patient = $stmt_patient->get_result();
    $patient_data = $result_patient->fetch_assoc();


    if ($referral) {
        echo '
        <div class="referral-details">
            <p><strong>Пациент:</strong> '.htmlspecialchars($patient_data['full_name']).'</p>
            <p><strong>Срок действия направления:</strong> '.$referral['date_of_start'].' - '.$referral['date_of_end'].'</p>
            <p><strong>Врач, выдавший направление:</strong> '.htmlspecialchars($referral['doctorName']).'</p>
            <p><strong>Должность врача, выдавшего направление:</strong> '.htmlspecialchars($referral['DoctorPost']).'</p>
            <p><strong>Врач по направлению:</strong> '.$referral['referralDoctorName'].'</p>
            <p><strong>Должность:</strong> '.htmlspecialchars($referral['referralDoctorPost']).'</p>
        </div>';
    } else {
        echo 'Запись не найдена';
    }
} else {
    echo 'Не указан ID записи';
}
    
$conn->close();


?>