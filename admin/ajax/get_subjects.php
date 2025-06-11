<?php
require '../../config.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID du semestre
$id_semester = isset($_GET['id_semester']) ? intval($_GET['id_semester']) : 0;

if ($id_semester <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Récupérer les matières pour ce semestre
$query = "SELECT s.id_subject, s.subject_name 
          FROM subjects s 
          JOIN modules m ON s.id_module = m.id_module 
          WHERE m.id_semester = ?
          ORDER BY s.subject_name";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_semester);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$subjects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects[] = [
        'id_subject' => $row['id_subject'],
        'subject_name' => $row['subject_name']
    ];
}

// Renvoyer les résultats au format JSON
header('Content-Type: application/json');
echo json_encode($subjects);