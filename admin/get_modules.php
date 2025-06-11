<?php
require '../config.php';
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id_filiere']) || empty($_GET['id_filiere'])) {
    echo json_encode([]);
    exit;
}

$id_filiere = mysqli_real_escape_string($conn, $_GET['id_filiere']);

$query = "SELECT id_module, module_name FROM modules WHERE id_filiere = '$id_filiere' ORDER BY module_name";
$result = mysqli_query($conn, $query);

$modules = [];
while ($row = mysqli_fetch_assoc($result)) {
    $modules[] = [
        'id_module' => $row['id_module'],
        'module_name' => $row['module_name']
    ];
}

echo json_encode($modules);
?>
