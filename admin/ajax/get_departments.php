<?php
require '../../config.php';
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['id_faculty'])) {
    $id_faculty = mysqli_real_escape_string($conn, $_GET['id_faculty']);
    
    $query = "SELECT id_department, department_name 
              FROM departments 
              WHERE id_faculty = '$id_faculty' 
              ORDER BY department_name";
    $result = mysqli_query($conn, $query);
    
    $departments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
    
    echo json_encode($departments);
} else {
    echo json_encode([]);
}
?>