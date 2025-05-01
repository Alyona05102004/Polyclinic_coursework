<?php

$host = "localhost";
$user = "root";
$password = "";
$db_name = "polyclinic";

$conn = new mysqli($host, $user, $password, $db_name);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Ошибка подключения к БД: ' . $conn->connect_error]));
}
?>