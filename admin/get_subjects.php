<?php
require '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id_module']) || empty($_GET['id_module'])) {
    echo json_encode([]);
    exit;
}

$id_module = mysqli_real_escape_string($conn, $_GET['id_module']);

$query = "SELECT id_subject, subject_name FROM subjects WHERE id_module = '$id_module' ORDER BY subject_name";
$result = mysqli_query($conn, $query);

$subjects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects[] = [
        'id_subject' => $row['id_subject'],
        'subject_name' => $row['subject_name']
    ];
}

echo json_encode($subjects);
?>
