<?php
require '../config.php';
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if (!isset($_GET['id_student']) || !isset($_GET['id_subject'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$id_student = mysqli_real_escape_string($conn, $_GET['id_student']);
$id_subject = mysqli_real_escape_string($conn, $_GET['id_subject']);

$query = "SELECT note_devoir, note_examen FROM notes WHERE id_student = '$id_student' AND id_subject = '$id_subject'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'note_devoir' => $row['note_devoir'],
        'note_examen' => $row['note_examen']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Note non trouvée']);
}
?>