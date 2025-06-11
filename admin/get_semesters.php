<?php
require '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id_filiere']) || empty($_GET['id_filiere'])) {
    echo json_encode([]);
    exit;
}

$id_filiere = mysqli_real_escape_string($conn, $_GET['id_filiere']);
$query = "SELECT id_semester, semester_name FROM semesters WHERE id_filiere = '$id_filiere' ORDER BY semester_name";
$result = mysqli_query($conn, $query);
    
$semesters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $semesters[] = [
        'id_semester' => $row['id_semester'],
        'semester_name' => $row['semester_name']
    ];
}
    
echo json_encode($semesters);
?>
