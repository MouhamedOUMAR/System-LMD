<?php
require '../config.php';
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id_filiere'])) {
    $id_filiere = mysqli_real_escape_string($conn, $_GET['id_filiere']);
    
    $query = "SELECT id_student, nom, prenom, matricule 
              FROM students 
              WHERE id_filiere = '$id_filiere' 
              ORDER BY nom, prenom";
    $result = mysqli_query($conn, $query);
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($students);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>