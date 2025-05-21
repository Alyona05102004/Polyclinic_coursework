<?php
include 'bd.php'; 

if (isset($_GET['id'])) {
    $doctor_id = intval($_GET['id']);
    
    // Основная информация о враче
    $sql = "SELECT DISTINCT staff.id_doctor, staff.full_name, staff.birthday, staff.post, staff.status, 
                   staff.address, staff.phone_number, staff.id_department, department.name_department, 
                   info_about_polyclinic.name_polyclinic, info_about_polyclinic.id_polyclinic 
            FROM staff 
            JOIN department ON department.id_department = staff.id_department
            JOIN connection ON connection.id_department = department.id_department
            JOIN info_about_polyclinic ON info_about_polyclinic.id_polyclinic = connection.id_polyclinic
            WHERE staff.id_doctor = ?";
   
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor_data = $result->fetch_assoc();
    
    // Образование
    $sql_education = "SELECT education.id_education, education.work_experience, education.type_of_education,  
                              education.educational_institution, education.year_of_start, education.year_of_end,
                              education.id_field as ed_id_field, ed_field.name_of_field AS ed_name_of_field
                       FROM staff 
                       LEFT JOIN connection_education ON connection_education.id_doctor = staff.id_doctor
                       LEFT JOIN education ON education.id_education = connection_education.id_education
                       LEFT JOIN field_of_medicine as ed_field ON education.id_field = ed_field.id_field
                       WHERE staff.id_doctor = ?";
    
    $stmt = $conn->prepare($sql_education);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $educations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Повышение квалификации
    $sql_qe = "SELECT qualification_improvement.id_qualif_improv, qualification_improvement.name as qe_name, 
                      qualification_improvement.type as qe_type, qualification_improvement.name_of_organizator, 
                      qualification_improvement.date, qualification_improvement.id_field as qe_id_field, 
                      qe_field.name_of_field AS qe_name_of_field
               FROM staff 
               LEFT JOIN connection_qualif_improve ON connection_qualif_improve.id_doctors = staff.id_doctor
               LEFT JOIN qualification_improvement ON connection_qualif_improve.id_qualif_improve = qualification_improvement.id_qualif_improv
               LEFT JOIN field_of_medicine as qe_field ON qualification_improvement.id_field = qe_field.id_field
               WHERE staff.id_doctor = ?";
    
    $stmt = $conn->prepare($sql_qe);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $qualifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    

    // Собираем все данные в один массив
    $response = [
        'doctor' => $doctor_data,
        'educations' => $educations,
        'qualifications' => $qualifications,
        //'work_exp' => $total_experience
    ];
    
    echo json_encode($response);
    
    $stmt->close();
}
$conn->close();
?>