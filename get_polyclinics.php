<?php
include 'bd.php';

if (isset($_GET['id_department'])) {
    $id_department = intval($_GET['id_department']);
    
    // Подготовленный запрос с использованием параметра
    $sql = "SELECT info_about_polyclinic.id_polyclinic, info_about_polyclinic.name_polyclinic 
            FROM info_about_polyclinic 
            JOIN connection ON connection.id_polyclinic = info_about_polyclinic.id_polyclinic
            WHERE connection.id_department = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }

    $stmt->bind_param("i", $id_department);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result === false) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $polyclinics = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($polyclinics);
    
    $stmt->close(); 
}

$conn->close();
?>