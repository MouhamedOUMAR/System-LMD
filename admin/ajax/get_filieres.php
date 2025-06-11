<?php
require '../../config.php';
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['id_department'])) {
    $id_department = mysqli_real_escape_string($conn, $_GET['id_department']);
    
    $query = "SELECT id_filiere, filiere_name 
              FROM filieres 
              WHERE id_department = '$id_department' 
              ORDER BY filiere_name";
    $result = mysqli_query($conn, $query);
    
    $filieres = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $filieres[] = $row;
    }
    
    echo json_encode($filieres);
} else {
    echo json_encode([]);
}
?>