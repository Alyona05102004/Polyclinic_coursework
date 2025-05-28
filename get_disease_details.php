<?php
include 'bd.php'; // Подключение к базе данных

// Проверяем, передан ли id_disease
if (!isset($_GET['id_disease'])) {
    echo json_encode(['error' => 'ID болезни не указан']);
    exit;
}

$diseaseId = intval($_GET['id_disease']);

// Подготовка SQL-запроса для получения данных о болезни
$sql = "SELECT id_disease, name_of_disease, symptoms, treatment_recommendations, medicament, id_field 
        FROM disease 
        WHERE id_disease = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $diseaseId);
$stmt->execute();
$result = $stmt->get_result();

// Проверяем, есть ли данные
if ($result->num_rows > 0) {
    $disease = $result->fetch_assoc();
    echo json_encode($disease); // Возвращаем данные в формате JSON
} else {
    echo json_encode(['error' => 'Болезнь не найдена']);
}

$stmt->close();
$conn->close();
?>
