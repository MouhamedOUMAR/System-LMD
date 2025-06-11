<?php
require '../config.php';

if (!isset($_GET['id_faculty'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de faculté manquant']);
    exit;
}

$id_faculty = mysqli_real_escape_string($conn, $_GET['id_faculty']);
$query = "SELECT id_department, department_name FROM departments WHERE id_faculty = '$id_faculty' ORDER BY department_name";
$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
    exit;
}

$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = [
        'id_department' => $row['id_department'],
        'department_name' => $row['department_name']
    ];
}

header('Content-Type: application/json');
echo json_encode($departments);
