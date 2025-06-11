<?php
require '../../config.php';
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['id_filiere'])) {
    $id_filiere = mysqli_real_escape_string($conn, $_GET['id_filiere']);
    
    // Récupérer les semestres associés à cette filière
    $query = "SELECT id_semester, semester_name 
              FROM semesters 
              WHERE id_filiere = '$id_filiere' 
              ORDER BY semester_order, semester_name";
    
    // Vérifier si la colonne semester_order existe
    $check_order = mysqli_query($conn, "SHOW COLUMNS FROM semesters LIKE 'semester_order'");
    if (mysqli_num_rows($check_order) == 0) {
        // Si la colonne n'existe pas, utiliser une requête alternative
        $query = "SELECT id_semester, semester_name 
                  FROM semesters 
                  WHERE id_filiere = '$id_filiere' 
                  ORDER BY semester_name";
    }
    
    $result = mysqli_query($conn, $query);
    
    $semesters = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $semesters[] = [
            'id_semester' => $row['id_semester'],
            'semester_name' => $row['semester_name']
        ];
    }
    
    echo json_encode($semesters);
} else {
    echo json_encode([]);
}
?>
