<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer l'ID de l'étudiant connecté
$id_student = $_SESSION['user_id'];

// Récupérer les informations de l'étudiant
$query = "SELECT s.*, f.filiere_name, d.department_name, fac.faculty_name 
          FROM students s 
          JOIN filieres f ON s.id_filiere = f.id_filiere 
          JOIN departments d ON f.id_department = d.id_department 
          JOIN faculties fac ON d.id_faculty = fac.id_faculty 
          WHERE s.id_student = '$id_student'";
$result = mysqli_query($conn, $query);
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
    <title>Mes Résultats - Système LMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Mes Résultats</h1>
            
            <?php
            // Récupérer les semestres de la filière de l'étudiant
            $query = "SELECT DISTINCT s.id_semester, s.semester_name 
                      FROM semesters s 
                      JOIN modules m ON s.id_semester = m.id_semester 
                      WHERE m.id_filiere = '$id_filiere' 
                      ORDER BY s.semester_name";
            $semesters_result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($semesters_result) > 0) {
                while ($semester = mysqli_fetch_assoc($semesters_result)) {
                    $id_semester = $semester['id_semester'];
                    $semester_name = $semester['semester_name'];
                    
                    echo "<h3 class='mt-4 mb-3'>" . htmlspecialchars($semester_name) . "</h3>";
                    
                    // Récupérer les modules du semestre
                    $query = "SELECT id_module, module_name, credits 
                              FROM modules 
                              WHERE id_filiere = '$id_filiere' AND id_semester = '$id_semester' 
                              ORDER BY module_name";
                    $modules_result = mysqli_query($conn, $query);
                    
                    if (mysqli_num_rows($modules_result) > 0) {
                        while ($module = mysqli_fetch_assoc($modules_result)) {
                            $id_module = $module['id_module'];
                            $module_name = $module['module_name'];
                            $credits = $module['credits'];
                            
                            echo "<div class='card shadow-sm mb-4'>
                                    <div class='card-header bg-light'>
                                        <h5 class='mb-0'>" . htmlspecialchars($module_name) . " (" . htmlspecialchars($credits) . " crédits)</h5>
                                    </div>
                                    <div class='card-body'>
                                        <div class='table-responsive'>
                                            <table class='table table-hover'>
                                                <thead>
                                                    <tr>
                                                        <th>Matière</th>
                                                        <th>Coefficient</th>
                                                        <th>Note Devoir</th>
                                                        <th>Note Examen</th>
                                                        <th>Note Finale</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>";
                            
                            // Récupérer les matières du module et les notes de l'étudiant
                            $query = "SELECT s.id_subject, s.subject_name, s.coefficient, 
                                             n.note_devoir, n.note_examen, n.note_finale 
                                      FROM subjects s 
                                      LEFT JOIN notes n ON s.id_subject = n.id_subject AND n.id_student = '$id_student' 
                                      WHERE s.id_module = '$id_module' 
                                      ORDER BY s.subject_name";
                            $subjects_result = mysqli_query($conn, $query);
                            
                            $module_total = 0;
                            $module_coef_total = 0;
                            
                            while ($subject = mysqli_fetch_assoc($subjects_result)) {
                                $subject_name = $subject['subject_name'];
                                $coefficient = $subject['coefficient'];
                                $note_devoir = $subject['note_devoir'] ? $subject['note_devoir'] : '-';
                                $note_examen = $subject['note_examen'] ? $subject['note_examen'] : '-';
                                $note_finale = $subject['note_finale'] ? $subject['note_finale'] : '-';
                                
                                // Calculer le statut
                                $status = '-';
                                $status_class = '';
                                
                                if ($note_finale !== '-') {
                                    if ($note_finale >= 10) {
                                        $status = 'Validé';
                                        $status_class = 'text-success';
                                    } else {
                                        $status = 'Non validé';
                                        $status_class = 'text-danger';
                                    }
                                    
                                    // Ajouter à la moyenne du module
                                    $module_total += $note_finale * $coefficient;
                                    $module_coef_total += $coefficient;
                                }
                                
                                echo "<tr>
                                        <td>" . htmlspecialchars($subject_name) . "</td>
                                        <td>" . htmlspecialchars($coefficient) . "</td>
                                        <td>" . htmlspecialchars($note_devoir) . "</td>
                                        <td>" . htmlspecialchars($note_examen) . "</td>
                                        <td>" . htmlspecialchars($note_finale) . "</td>
                                        <td class='" . $status_class . "'>" . htmlspecialchars($status) . "</td>
                                      </tr>";
                            }
                            
                            // Calculer la moyenne du module
                            $module_average = $module_coef_total > 0 ? round($module_total / $module_coef_total, 2) : '-';
                            $module_status = '-';
                            $module_status_class = '';

                            if ($module_average !== '-') {
                                // Vérifier si une matière a une note ≤ 5.4
                                $has_low_grade = false;
                                foreach ($subjects_result as $subject) {
                                    if (isset($subject['note_finale']) && $subject['note_finale'] <= 5.4) {
                                        $has_low_grade = true;
                                        break;
                                    }
                                }
                                
                                if ($module_average > 9) {
                                    if ($has_low_grade) {
                                        $module_status = 'Rattrapage';
                                        $module_status_class = 'text-warning';
                                    } else {
                                        $module_status = 'Validé';
                                        $module_status_class = 'text-success';
                                    }
                                } else {
                                    $module_status = 'Non validé';
                                    $module_status_class = 'text-danger';
                                }
                            }

                            // Afficher les crédits validés
                            $module_credits = $credits;
                            $validated_credits = ($module_status == 'Validé') ? $module_credits : 0;

                            echo "</tbody>
                                </table>
                            </div>
                            <div class='mt-3'>
                                <strong>Moyenne du module: </strong> " . $module_average . " / 20
                                <span class='ms-3 " . $module_status_class . "'><strong>Statut: </strong> " . $module_status . "</span>
                                <span class='ms-3'><strong>Crédits validés: </strong> " . $validated_credits . " / " . $module_credits . "</span>
                            </div>
                          </div>
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

