<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer l'ID de l'étudiant connecté
$query = "SELECT s.id_student, s.id_filiere, f.filiere_name, s.sub_level 
          FROM students s 
          JOIN filieres f ON s.id_filiere = f.id_filiere 
          WHERE s.id_user = '{$_SESSION['user_id']}'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);
$id_student = $student['id_student'];
$id_filiere = $student['id_filiere'];

// Récupérer le semestre actif ou sélectionné
$id_semester = null;

// Vérifier si un semestre est sélectionné dans l'URL
if (isset($_GET['semester']) && !empty($_GET['semester'])) {
    $id_semester = mysqli_real_escape_string($conn, $_GET['semester']);
} else {
    // Récupérer le semestre actif (si disponible)
    $query = "SELECT s.id_semester FROM semesters s 
              JOIN modules m ON s.id_semester = m.id_semester 
              WHERE m.id_filiere = '$id_filiere' 
              ORDER BY s.semester_name LIMIT 1";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $semester = mysqli_fetch_assoc($result);
        $id_semester = $semester['id_semester'];
    }
}

// Si aucun semestre n'est trouvé, afficher un message d'erreur
if (!$id_semester) {
    echo '<div class="alert alert-danger">Aucun semestre trouvé pour votre filière.</div>';
    exit;
}

// Fonction pour calculer la décision du module
function getModuleDecision($moduleNotes) {
    // Calculer la moyenne pondérée du module
    $totalPoints = 0;
    $totalCoefficients = 0;
    
    foreach ($moduleNotes as $note) {
        if (isset($note['note_finale'])) {
            $totalPoints += $note['note_finale'] * $note['coefficient'];
            $totalCoefficients += $note['coefficient'];
        }
    }
    
    // Calculer la moyenne du module
    $moduleAverage = ($totalCoefficients > 0) ? $totalPoints / $totalCoefficients : 0;
    
    // Si la moyenne du module est >= 10, le module est validé
    // quelle que soit les notes individuelles (compensation)
    if ($moduleAverage >= 10) {
        return 'Validé';
    }
    // Si la moyenne est > 9 et toutes les notes sont > 5.4, le module est validé
    else if ($moduleAverage > 9) {
        $allNotesAboveThreshold = true;
        foreach ($moduleNotes as $note) {
            if (isset($note['note_finale']) && $note['note_finale'] <= 5.4) {
                $allNotesAboveThreshold = false;
                break;
            }
        }
        if ($allNotesAboveThreshold) {
            return 'Validé';
        }
    }
    
        return 'Rattrapage';
}

// Fonction pour calculer la décision annuelle
function getAnnualDecision($semesterCredits, $annualAverage) {
    $totalCredits = array_sum(array_column($semesterCredits, 'total'));
    $validatedCredits = array_sum(array_column($semesterCredits, 'validated'));
    
    if ($annualAverage >= 10 && $validatedCredits == $totalCredits) {
        return 'Admis';
    } else if ($annualAverage >= 10 && $validatedCredits >= 45) {
        return 'Admis avec dettes';
    } else {
        return 'Ajourné';
    }
}

// Récupérer le nom du semestre
$query = "SELECT semester_name FROM semesters WHERE id_semester = '$id_semester'";
$result = mysqli_query($conn, $query);
$semester_name = mysqli_fetch_assoc($result)['semester_name'];
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
    <style>
        .module-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .subject-row {
            padding-left: 20px;
        }
        .subject-name {
            padding-left: 20px;
        }
        .decision-validated {
            color: green;
            font-weight: bold;
        }
        .decision-rattrapage {
            color: orange;
            font-weight: bold;
        }
        .decision-failed {
            color: red;
            font-weight: bold;
        }
        .decision-validated-with-debts {
            color: blue;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Contenu de la page -->
    <div class="container mt-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($student['filiere_name'] . ' - ' . $student['sub_level'] . ' - ' . $semester_name); ?></h5>
                    <div>
                        <form method="get" action="" class="d-flex">
                            <select class="form-select me-2" name="semester" onchange="this.form.submit()">
                                <?php
                                $query = "SELECT DISTINCT s.id_semester, s.semester_name 
                                          FROM semesters s 
                                          JOIN modules m ON s.id_semester = m.id_semester 
                                          WHERE m.id_filiere = '$id_filiere' 
                                          ORDER BY s.semester_name";
                                $semesters_result = mysqli_query($conn, $query);
                                while ($semester = mysqli_fetch_assoc($semesters_result)) {
                                    $selected = ($semester['id_semester'] == $id_semester) ? 'selected' : '';
                                    echo "<option value='" . $semester['id_semester'] . "' $selected>" . htmlspecialchars($semester['semester_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Récupérer les modules et leurs matières pour ce semestre
                $query = "SELECT m.id_module, m.module_name, m.credits as module_credits
                          FROM modules m
                          WHERE m.id_semester = '$id_semester'
                          ORDER BY m.module_name";
                $modules_result = mysqli_query($conn, $query);

                if (mysqli_num_rows($modules_result) > 0) {
                    echo '<table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Module / Matière</th>
                                    <th>Coefficient</th>
                                    <th>Note Devoir</th>
                                    <th>Note Examen</th>
                                    <th>Note Finale</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
                    $totalPoints = 0;
                    $totalCoefficients = 0;
                    $totalCredits = 0;
                    $validatedCredits = 0;
                    $moduleDecisions = [];
                    $semesterCredits = [];
                    
                    while ($module = mysqli_fetch_assoc($modules_result)) {
                        $id_module = $module['id_module'];
                        $moduleNotes = [];
                        
                        // Récupérer les matières de ce module
                        $query = "SELECT s.id_subject, s.subject_name, s.coefficient,
                                  n.note_devoir, n.note_examen, n.note_finale, n.status
                                  FROM subjects s
                                  LEFT JOIN notes n ON s.id_subject = n.id_subject AND n.id_student = '$id_student'
                                  WHERE s.id_module = '$id_module'
                                  ORDER BY s.subject_name";
                        $subjects_result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($subjects_result) > 0) {
                            // Ligne du module
                            echo "<tr class='module-row'>
                                    <td colspan='5'>" . htmlspecialchars($module['module_name']) . " (" . $module['module_credits'] . " crédits)</td>
                                    <td id='module-decision-$id_module'></td>
                                  </tr>";
                            
                            $moduleTotal = 0;
                            $moduleCoef = 0;
                            
                            while ($subject = mysqli_fetch_assoc($subjects_result)) {
                                // Stocker les notes pour calculer la décision du module
                                $moduleNotes[] = $subject;
                                
                                // Calculer les totaux pour la moyenne
                                if (isset($subject['note_finale'])) {
                                    $moduleTotal += $subject['note_finale'] * $subject['coefficient'];
                                    $moduleCoef += $subject['coefficient'];
                                    $totalPoints += $subject['note_finale'] * $subject['coefficient'];
                                    $totalCoefficients += $subject['coefficient'];
                                }
                                
                                // Déterminer le statut
                                $status = isset($subject['status']) ? $subject['status'] : '';
                                if (empty($status) && isset($subject['note_finale'])) {
                                    if ($subject['note_finale'] >= 10) {
                                        $status = 'Validé';
                                    } else if ($moduleAverage > 9 && $subject['note_finale'] > 5.4) {
                                        // Si la moyenne du module est > 9 et la note de la matière > 5.4,
                                        // la matière est validée par compensation
                                        $status = 'Validé par compensation';
                                    } else if ($subject['note_finale'] <= 5.4) {
                                        $status = 'Rattrapage';
                                    } else {
                                        $status = 'Non validé';
                                    }
                                }
                                
                                // Classe CSS pour le statut
                                $statusClass = '';
                                if ($status == 'Validé' || $status == 'Validé par compensation') {
                                    $statusClass = 'decision-validated';
                                } else if ($status == 'Rattrapage') {
                                    $statusClass = 'decision-rattrapage';
                                } else if ($status == 'Non validé') {
                                    $statusClass = 'decision-failed';
                                }
                                
                                // Ligne de la matière
                                echo "<tr class='subject-row'>
                                        <td class='subject-name'>" . htmlspecialchars($subject['subject_name']) . "</td>
                                        <td>" . htmlspecialchars($subject['coefficient']) . "</td>
                                        <td>" . (isset($subject['note_devoir']) ? htmlspecialchars($subject['note_devoir']) : '-') . "</td>
                                        <td>" . (isset($subject['note_examen']) ? htmlspecialchars($subject['note_examen']) : '-') . "</td>
                                        <td>" . (isset($subject['note_finale']) ? htmlspecialchars($subject['note_finale']) : '-') . "</td>
                                        <td class='$statusClass'>" . htmlspecialchars($status) . "</td>
                                      </tr>";
                            }
                            
                            // Calculer la moyenne du module
                            $moduleAverage = $moduleCoef > 0 ? round($moduleTotal / $moduleCoef, 2) : 0;
                            
                            // Calculer la décision du module
                            $moduleDecision = getModuleDecision($moduleNotes);
                            
                            // Stocker la décision pour l'affichage JavaScript
                            $moduleDecisions[$id_module] = $moduleDecision;

                            // Afficher la moyenne du module
                            echo "<tr class='module-average'>
                                    <td colspan='4' class='text-end'><strong>Moyenne du module :</strong></td>
                                    <td><strong>" . number_format($moduleAverage, 2) . "</strong></td>
                                    <td></td>
                                  </tr>";

                            // Stocker les crédits du module
                            $moduleCredits = $module['module_credits'];
                            $totalCredits += $moduleCredits;

                            // Si le module est validé, ajouter ses crédits aux crédits validés
                            if ($moduleDecision == 'Validé') {
                                $validatedCredits += $moduleCredits;
                                $semesterCredits[$id_semester]['validated'] = isset($semesterCredits[$id_semester]['validated']) ? 
                                                                            $semesterCredits[$id_semester]['validated'] + $moduleCredits : 
                                                                            $moduleCredits;
                            }

                            // Ajouter les crédits totaux du module
                            $semesterCredits[$id_semester]['total'] = isset($semesterCredits[$id_semester]['total']) ? 
                                                                    $semesterCredits[$id_semester]['total'] + $moduleCredits : 
                                                                    $moduleCredits;

                            // Afficher la décision du module
                            echo "<tr class='module-decision'>
                                    <td colspan='4'><strong>Décision du module :</strong></td>
                                    <td colspan='2'><span class='decision-" . strtolower(str_replace(' ', '-', $moduleDecision)) . "'>" . $moduleDecision . "</span></td>
                                  </tr>";
                            echo "<tr class='module-credits'>
                                    <td colspan='4'><strong>Crédits :</strong></td>
                                    <td colspan='2'>" . ($moduleDecision == 'Validé' ? $moduleCredits : '0') . " / " . $moduleCredits . "</td>
                                  </tr>";
                        }
                    }
                    
                    // Calculer la moyenne du semestre
                    $semesterAverage = $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
                    
                    // Afficher la moyenne du semestre
                    echo "<tr class='table-secondary'>
                            <td colspan='5'><strong>Moyenne du semestre</strong></td>
                            <td><strong>$semesterAverage</strong></td>
                          </tr>";
                    
                    echo '</tbody>
                        </table>';
                    
                    // Afficher les décisions des modules avec JavaScript
                    echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                ";
                    
                    foreach ($moduleDecisions as $id_module => $decision) {
                        $decisionClass = '';
                        if ($decision == 'Validé') {
                            $decisionClass = 'decision-validated';
                        } else if ($decision == 'Rattrapage') {
                            $decisionClass = 'decision-rattrapage';
                        } else {
                            $decisionClass = 'decision-failed';
                        }
                        
                        echo "document.getElementById('module-decision-$id_module').innerHTML = '<span class=\"$decisionClass\">$decision</span>';";
                    }
                    
                    echo "
                            });
                          </script>";
                    
                    // Calculer la décision du semestre
                    $semesterDecision = '';
                    $validatedSemesterCredits = isset($semesterCredits[$id_semester]['validated']) ? $semesterCredits[$id_semester]['validated'] : 0;
                    $totalSemesterCredits = isset($semesterCredits[$id_semester]['total']) ? $semesterCredits[$id_semester]['total'] : 0;

                    if ($semesterAverage >= 10) {
                        $semesterDecision = 'Validé';
                        $decisionClass = 'decision-validated';
                    } else {
                        $semesterDecision = 'Rattrapage';
                        $decisionClass = 'decision-rattrapage';
                    }

                    // Afficher la décision du semestre
                    echo "<div class='semester-decision'>
                            <h4>Décision du semestre : <span class='" . $decisionClass . "'>" . $semesterDecision . "</span></h4>
                            <p>Crédits validés : " . $validatedSemesterCredits . " / " . $totalSemesterCredits . "</p>
                          </div>";
                    
                } else {
                    echo '<div class="alert alert-info">Aucune note disponible pour ce semestre.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>





<?php
// Après l'affichage de la décision du semestre, ajoutons le calcul et l'affichage des résultats annuels

// Récupérer les informations sur le semestre actuel
$query = "SELECT s.semester_order, s.id_academic_year 
          FROM semesters s 
          WHERE s.id_semester = '$id_semester'";
$semester_info_result = mysqli_query($conn, $query);
$semester_info = mysqli_fetch_assoc($semester_info_result);

if ($semester_info) {
    $semester_order = $semester_info['semester_order'];
    $id_academic_year = $semester_info['id_academic_year'];
    
    // Déterminer l'année d'étude (L1, L2, L3) basée sur l'ordre du semestre
    $study_year = ceil($semester_order / 2);
    
    // Récupérer les deux semestres de cette année d'étude
    $start_order = ($study_year - 1) * 2 + 1;
    $end_order = $start_order + 1;
    
    $query = "SELECT s.id_semester, s.semester_name 
              FROM semesters s 
              WHERE s.id_academic_year = '$id_academic_year' 
              AND s.semester_order BETWEEN $start_order AND $end_order
              ORDER BY s.semester_order";
    $year_semesters_result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($year_semesters_result) == 2) {
        $year_semesters = [];
        $total_points = 0;
        $total_coefficients = 0;
        $total_credits = 0;
        $validated_credits = 0;
        $both_semesters_have_notes = true;
        
        // Collecter les IDs des semestres de cette année
        while ($sem = mysqli_fetch_assoc($year_semesters_result)) {
            $year_semesters[] = $sem;
        }
        
        // Vérifier si les deux semestres ont des notes
        foreach ($year_semesters as $sem) {
            $query = "SELECT COUNT(*) as note_count
                      FROM notes n
                      JOIN subjects s ON n.id_subject = s.id_subject
                      JOIN modules m ON s.id_module = m.id_module
                      WHERE n.id_student = '$id_student' AND m.id_semester = '{$sem['id_semester']}'";
            $note_count_result = mysqli_query($conn, $query);
            $note_count = mysqli_fetch_assoc($note_count_result);
            
            if ($note_count['note_count'] == 0) {
                $both_semesters_have_notes = false;
                break;
            }
        }
        
        // Si les deux semestres ont des notes, calculer la moyenne annuelle
        if ($both_semesters_have_notes) {
            echo '<div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Résultat Annuel - L' . $study_year . '</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Semestre</th>
                                    <th>Moyenne</th>
                                    <th>Crédits Validés</th>
                                    <th>Total Crédits</th>
                                    <th>Décision</th>
                                </tr>
                            </thead>
                            <tbody>';
            
            // Pour chaque semestre de l'année, récupérer les moyennes et crédits
            foreach ($year_semesters as $sem) {
                // Récupérer la moyenne et les crédits du semestre
                $query = "SELECT AVG(n.note_finale) as moyenne
                          FROM notes n
                          JOIN subjects s ON n.id_subject = s.id_subject
                          JOIN modules m ON s.id_module = m.id_module
                          WHERE n.id_student = '$id_student' AND m.id_semester = '{$sem['id_semester']}'";
                $sem_avg_result = mysqli_query($conn, $query);
                $sem_avg = mysqli_fetch_assoc($sem_avg_result);
                $sem_average = $sem_avg ? round($sem_avg['moyenne'], 2) : 0;
                
                // Récupérer les crédits du semestre
                $query = "SELECT m.id_module, m.credits as module_credits
                          FROM modules m
                          WHERE m.id_semester = '{$sem['id_semester']}'";
                $sem_modules_result = mysqli_query($conn, $query);
                
                $sem_total_credits = 0;
                $sem_validated_credits = 0;
                
                while ($module = mysqli_fetch_assoc($sem_modules_result)) {
                    $sem_total_credits += $module['module_credits'];
                    
                    // Vérifier si le module est validé
                    $query = "SELECT AVG(n.note_finale) as module_avg
                              FROM notes n
                              JOIN subjects s ON n.id_subject = s.id_subject
                              WHERE n.id_student = '$id_student' AND s.id_module = '{$module['id_module']}'";
                    $module_avg_result = mysqli_query($conn, $query);
                    $module_avg = mysqli_fetch_assoc($module_avg_result);
                    
                    if ($module_avg && $module_avg['module_avg'] > 9) {
                        // Vérifier si une matière a une note ≤ 5.4
                        $query = "SELECT MIN(n.note_finale) as min_note
                                  FROM notes n
                                  JOIN subjects s ON n.id_subject = s.id_subject
                                  WHERE n.id_student = '$id_student' AND s.id_module = '{$module['id_module']}'";
                        $min_note_result = mysqli_query($conn, $query);
                        $min_note = mysqli_fetch_assoc($min_note_result);
                        
                        if ($min_note && $min_note['min_note'] > 5.4) {
                            $sem_validated_credits += $module['module_credits'];
                        }
                    }
                }
                
                // Déterminer la décision du semestre
                $sem_decision = $sem_average >= 10 ? 'Validé' : 'Rattrapage';
                $sem_decision_class = $sem_average >= 10 ? 'decision-validated' : 'decision-rattrapage';
                
                // Ajouter aux totaux annuels
                $total_points += $sem_average * $sem_total_credits;
                $total_coefficients += $sem_total_credits;
                $total_credits += $sem_total_credits;
                $validated_credits += $sem_validated_credits;
                
                echo "<tr>
                        <td>{$sem['semester_name']}</td>
                        <td>" . number_format($sem_average, 2) . "</td>
                        <td>$sem_validated_credits</td>
                        <td>$sem_total_credits</td>
                        <td class='$sem_decision_class'>$sem_decision</td>
                      </tr>";
            }
            
            // Calculer la moyenne annuelle
            $annual_average = $total_coefficients > 0 ? round($total_points / $total_coefficients, 2) : 0;
            
            // Déterminer la décision annuelle
            $annual_decision = '';
            if ($annual_average >= 10 && $validated_credits == $total_credits) {
                $annual_decision = 'Admis';
                $annual_decision_class = 'decision-validated';
            } else if ($annual_average >= 10 && $validated_credits >= 45) {
                $annual_decision = 'Admis avec dettes';
                $annual_decision_class = 'decision-validated-with-debts';
            } else {
                $annual_decision = 'Ajourné';
                $annual_decision_class = 'decision-failed';
            }
            
            echo "</tbody>
                </table>
                <div class='mt-3'>
                    <h4>Moyenne Annuelle: <strong>" . number_format($annual_average, 2) . " / 20</strong></h4>
                    <h4>Décision Annuelle: <span class='$annual_decision_class'><strong>$annual_decision</strong></span></h4>
                    <p>Crédits validés: $validated_credits / $total_credits</p>
                </div>
              </div>
            </div>";
        }
    }
}
?>





