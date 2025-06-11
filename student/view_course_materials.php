<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer l'ID de l'étudiant connecté
$id_user = $_SESSION['user_id'];

// Récupérer les informations de l'étudiant
$query = "SELECT s.*, f.filiere_name, d.department_name, fac.faculty_name 
          FROM students s 
          JOIN filieres f ON s.id_filiere = f.id_filiere 
          JOIN departments d ON f.id_department = d.id_department 
          JOIN faculties fac ON d.id_faculty = fac.id_faculty 
          WHERE s.id_user = '$id_user'";
$result = mysqli_query($conn, $query);

// Vérifier si l'étudiant existe
if (!$result || mysqli_num_rows($result) == 0) {
    die("Erreur: Informations de l'étudiant introuvables.");
}

$student = mysqli_fetch_assoc($result);

// Récupérer l'ID de la filière de l'étudiant
$id_filiere = $student['id_filiere'];
$filiere_name = $student['filiere_name'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supports de Cours - Système LMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Supports de Cours</h1>
            
            <?php
            // Vérifier si la table course_materials existe
            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'course_materials'");
            if (mysqli_num_rows($check_table) == 0) {
                // Créer la table si elle n'existe pas
                $create_table = "CREATE TABLE course_materials (
                    id_material INT AUTO_INCREMENT PRIMARY KEY,
                    id_subject INT NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_subject) REFERENCES subjects(id_subject) ON DELETE CASCADE
                )";
                mysqli_query($conn, $create_table);
            }
            
            // Récupérer les semestres de la filière de l'étudiant
            $query = "SELECT DISTINCT s.id_semester, s.semester_name 
                      FROM semesters s 
                      JOIN modules m ON s.id_semester = m.id_semester 
                      WHERE m.id_filiere = '$id_filiere' 
                      ORDER BY s.semester_name";
            $semesters_result = mysqli_query($conn, $query);
            
            if ($semesters_result && mysqli_num_rows($semesters_result) > 0) {
                while ($semester = mysqli_fetch_assoc($semesters_result)) {
                    $id_semester = $semester['id_semester'];
                    $semester_name = $semester['semester_name'];
                    
                    echo "<h3 class='mt-4 mb-3'>" . htmlspecialchars($semester_name) . "</h3>";
                    
                    // Récupérer les modules du semestre
                    $query = "SELECT id_module, module_name 
                              FROM modules 
                              WHERE id_filiere = '$id_filiere' AND id_semester = '$id_semester' 
                              ORDER BY module_name";
                    $modules_result = mysqli_query($conn, $query);
                    
                    if ($modules_result && mysqli_num_rows($modules_result) > 0) {
                        while ($module = mysqli_fetch_assoc($modules_result)) {
                            $id_module = $module['id_module'];
                            $module_name = $module['module_name'];
                            
                            echo "<div class='card shadow-sm mb-4'>
                                    <div class='card-header bg-light'>
                                        <h5 class='mb-0'>" . htmlspecialchars($module_name) . "</h5>
                                    </div>
                                    <div class='card-body'>";
                            
                            // Récupérer les matières du module et leurs supports de cours
                            $query = "SELECT s.id_subject, s.subject_name 
                                      FROM subjects s 
                                      WHERE s.id_module = '$id_module' 
                                      ORDER BY s.subject_name";
                            $subjects_result = mysqli_query($conn, $query);
                            
                            $has_materials = false;
                            
                            if ($subjects_result && mysqli_num_rows($subjects_result) > 0) {
                                while ($subject = mysqli_fetch_assoc($subjects_result)) {
                                    $id_subject = $subject['id_subject'];
                                    $subject_name = $subject['subject_name'];
                                    
                                    // Récupérer les supports de cours pour cette matière
                                    $query = "SELECT id_material, file_name, file_path, uploaded_at 
                                              FROM course_materials 
                                              WHERE id_subject = '$id_subject' 
                                              ORDER BY uploaded_at DESC";
                                    $materials_result = mysqli_query($conn, $query);
                                    
                                    if ($materials_result && mysqli_num_rows($materials_result) > 0) {
                                        $has_materials = true;
                                        
                                        echo "<h6 class='mt-3 mb-2'>" . htmlspecialchars($subject_name) . "</h6>
                                              <div class='list-group mb-3'>";
                                        
                                        while ($material = mysqli_fetch_assoc($materials_result)) {
                                            $file_name = $material['file_name'];
                                            $file_path = $material['file_path'];
                                            $uploaded_at = date('d/m/Y H:i', strtotime($material['uploaded_at']));
                                            
                                            // Déterminer l'icône en fonction du type de fichier
                                            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                                            $icon_class = 'fa-file';
                                            
                                            switch (strtolower($file_extension)) {
                                                case 'pdf':
                                                    $icon_class = 'fa-file-pdf';
                                                    break;
                                                case 'doc':
                                                case 'docx':
                                                    $icon_class = 'fa-file-word';
                                                    break;
                                                case 'xls':
                                                case 'xlsx':
                                                    $icon_class = 'fa-file-excel';
                                                    break;
                                                case 'ppt':
                                                case 'pptx':
                                                    $icon_class = 'fa-file-powerpoint';
                                                    break;
                                                case 'jpg':
                                                case 'jpeg':
                                                case 'png':
                                                case 'gif':
                                                    $icon_class = 'fa-file-image';
                                                    break;
                                                case 'zip':
                                                case 'rar':
                                                    $icon_class = 'fa-file-archive';
                                                    break;
                                            }
                                            
                                            echo "<a href='" . $file_path . "' class='list-group-item list-group-item-action' target='_blank'>
                                                    <div class='d-flex w-100 justify-content-between'>
                                                        <h6 class='mb-1'><i class='fas " . $icon_class . " me-2'></i> " . htmlspecialchars($file_name) . "</h6>
                                                        <small>" . $uploaded_at . "</small>
                                                    </div>
                                                  </a>";
                                        }
                                        
                                        echo "</div>";
                                    }
                                }
                            }
                            
                            if (!$has_materials) {
                                echo "<div class='alert alert-info'>Aucun support de cours disponible pour ce module.</div>";
                            }
                            
                            echo "</div>
                                </div>";
                        }
                    } else {
                        echo "<div class='alert alert-info'>Aucun module trouvé pour ce semestre.</div>";
                    }
                }
            } else {
                echo "<div class='alert alert-info'>Aucun semestre trouvé pour votre filière.</div>";
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>

