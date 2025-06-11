<?php
require '../config.php';

// Récupération des filières par département
if (isset($_GET['id_department'])) {
    $id_department = mysqli_real_escape_string($conn, $_GET['id_department']);
    
    // Si un niveau est spécifié, filtrer par niveau également
    if (isset($_GET['id_level'])) {
        $id_level = mysqli_real_escape_string($conn, $_GET['id_level']);
        $query = "SELECT id_filiere, filiere_name FROM filieres 
                  WHERE id_department = '$id_department' AND id_level = '$id_level' 
                  ORDER BY filiere_name";
    } else {
        $query = "SELECT id_filiere, filiere_name FROM filieres 
                  WHERE id_department = '$id_department' 
                  ORDER BY filiere_name";
    }
    
    $result = mysqli_query($conn, $query);

    $filieres = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $filieres[] = [
                'id_filiere' => $row['id_filiere'],
                'filiere_name' => $row['filiere_name']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($filieres);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
