<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Fonction pour déterminer la décision du module
function getModuleDecision($moduleNotes, $moduleAverage) {
    // Si la moyenne du module est >= 10, le module est validé
    if ($moduleAverage >= 10) {
        return 'Validé';
        }
    // Si la moyenne est > 9, le module est validé (peu importe les notes individuelles)
    else if ($moduleAverage > 9) {
        return 'Validé';
    }
    return 'Rattrapage';
}

// Fonction pour mettre à jour le statut de toutes les matières d'un module
function updateSubjectsStatusForModule($conn, $id_student, $id_module, $moduleAverage) {
    // Déterminer si le module est validé
    $moduleValidated = false;
    if ($moduleAverage >= 10 || $moduleAverage > 9) {
        $moduleValidated = true;
    }
    
    // Récupérer toutes les matières du module pour cet étudiant avec leurs coefficients
    $query = "SELECT n.id_note, n.note_finale, s.coefficient, s.subject_name 
                  FROM notes n 
                  JOIN subjects s ON n.id_subject = s.id_subject 
                  WHERE n.id_student = ? AND s.id_module = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $id_student, $id_module);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
    // Mettre à jour le statut de chaque matière
        while ($note = mysqli_fetch_assoc($result)) {
            $status = '';
            $validated = 0;
            
        if ($moduleValidated) {
            // Si le module est validé, toutes les matières sont validées
            if ($note['note_finale'] >= 10) {
                $status = 'Validé';
            } else {
                $status = 'Validé par compensation';
            }
            $validated = 1;
        } else {
            // Si le module n'est pas validé
            if ($note['note_finale'] >= 10) {
                $status = 'Validé';
                $validated = 1;
            } else {
                $status = 'Rattrapage';
                $validated = 0;
            }
            }
            
            // Mettre à jour le statut de la matière
            $update_query = "UPDATE notes SET status = ?, validated = ? WHERE id_note = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sii", $status, $validated, $note['id_note']);
            mysqli_stmt_execute($update_stmt);
    }
}

// Fonction pour mettre à jour les moyennes du semestre
function updateSemesterAverages($conn, $id_student, $id_semester) {
    // Récupérer tous les modules du semestre
    $query = "SELECT id_module, credits as module_credits FROM modules WHERE id_semester = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_semester);
    mysqli_stmt_execute($stmt);
    $modules_result = mysqli_stmt_get_result($stmt);
    
    $semesterPoints = 0;
    $semesterCoefficients = 0;
    $totalCredits = 0;
    $validatedCredits = 0;
    $semesterModules = [];
    
    while ($module = mysqli_fetch_assoc($modules_result)) {
        $id_module = $module['id_module'];
        $moduleCredits = $module['module_credits'];
        $totalCredits += $moduleCredits;
        
        // Récupérer toutes les notes de l'étudiant pour ce module
        $query = "SELECT n.id_note, n.note_finale, n.status, s.coefficient, s.subject_name 
                  FROM notes n 
                  JOIN subjects s ON n.id_subject = s.id_subject 
                  WHERE n.id_student = ? AND s.id_module = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $id_student, $id_module);
        mysqli_stmt_execute($stmt);
        $notes_result = mysqli_stmt_get_result($stmt);
        
        $moduleNotes = [];
        $modulePoints = 0;
        $moduleCoefficients = 0;
        
        while ($note = mysqli_fetch_assoc($notes_result)) {
            $moduleNotes[] = $note;
            $modulePoints += $note['note_finale'] * $note['coefficient'];
            $moduleCoefficients += $note['coefficient'];
        }
        
        // Calculer la moyenne du module
        $moduleAverage = ($moduleCoefficients > 0) ? $modulePoints / $moduleCoefficients : 0;

        if (count($moduleNotes) > 0) {
            // Déterminer la décision du module
            $moduleDecision = getModuleDecision($moduleNotes, $moduleAverage);
            
            // Mettre à jour le statut de toutes les matières du module
            updateSubjectsStatusForModule($conn, $id_student, $id_module, $moduleAverage);
            
            // Si le module est validé, ajouter ses crédits aux crédits validés
            if ($moduleDecision == 'Validé') {
                $validatedCredits += $moduleCredits;
            }
            
            // Stocker les informations du module pour la décision du semestre
            $semesterModules[] = [
                'id_module' => $id_module,
                'decision' => $moduleDecision,
                'average' => $moduleAverage,
                'credits' => $moduleCredits
            ];
            
            // Mettre à jour la décision du module dans la base de données
            $query = "INSERT INTO module_decisions (id_student, id_module, decision, average) 
                      VALUES (?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE decision = ?, average = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iisdsd", $id_student, $id_module, $moduleDecision, $moduleAverage, $moduleDecision, $moduleAverage);
            mysqli_stmt_execute($stmt);
        }
        
        // Ajouter les points du module à la moyenne du semestre
        $semesterPoints += $modulePoints;
        $semesterCoefficients += $moduleCoefficients;
    }
    
    // Calculer et enregistrer la moyenne du semestre
    if ($semesterCoefficients > 0) {
        $semesterAverage = $semesterPoints / $semesterCoefficients;
        
        // Déterminer la décision du semestre
        $semesterDecision = '';
        if ($validatedCredits == $totalCredits) {
            $semesterDecision = 'Validé';
        } else if ($validatedCredits >= ($totalCredits / 2)) {
            $semesterDecision = 'Validé avec dettes';
        } else {
            $semesterDecision = 'Non validé';
        }
        
        // Mettre à jour la moyenne du semestre dans la base de données
        $query = "INSERT INTO semester_averages (id_student, id_semester, average, total_credits, validated_credits, decision) 
                  VALUES (?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE average = ?, total_credits = ?, validated_credits = ?, decision = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iidiisidis", $id_student, $id_semester, $semesterAverage, $totalCredits, $validatedCredits, $semesterDecision, $semesterAverage, $totalCredits, $validatedCredits, $semesterDecision);
        mysqli_stmt_execute($stmt);
        
        // Mettre à jour la décision annuelle
        updateAnnualDecision($conn, $id_student, $id_semester);
        
        return true;
    }
    
    return false;
}

// Fonction pour mettre à jour la décision annuelle
function updateAnnualDecision($conn, $id_student, $id_semester) {
    // Récupérer l'année académique du semestre
    $query = "SELECT s.id_academic_year, s.semester_order 
              FROM semesters s 
              WHERE s.id_semester = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_semester);
    mysqli_stmt_execute($stmt);
    $semester_result = mysqli_stmt_get_result($stmt);
    
    if ($semester_row = mysqli_fetch_assoc($semester_result)) {
        $id_academic_year = $semester_row['id_academic_year'];
        $semester_order = $semester_row['semester_order'];
        
        // Déterminer l'année d'étude (1ère, 2ème, 3ème)
        $study_year = ceil($semester_order / 2);
        
        // Récupérer tous les semestres de cette année d'étude
        $query = "SELECT s.id_semester 
                  FROM semesters s 
                  WHERE s.id_academic_year = ? 
                  AND s.semester_order BETWEEN ? AND ?";
        $start_order = ($study_year - 1) * 2 + 1;
        $end_order = $start_order + 1;
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iii", $id_academic_year, $start_order, $end_order);
        mysqli_stmt_execute($stmt);
        $semesters_result = mysqli_stmt_get_result($stmt);
        
        $total_credits = 0;
        $validated_credits = 0;
        $total_points = 0;
        $total_coefficients = 0;
        $nb_semestres = 0;
        
        // Récupérer les crédits validés et moyennes pour chaque semestre
        while ($semester = mysqli_fetch_assoc($semesters_result)) {
            $query = "SELECT average, total_credits, validated_credits 
                      FROM semester_averages 
                      WHERE id_student = ? AND id_semester = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $id_student, $semester['id_semester']);
            mysqli_stmt_execute($stmt);
            $avg_result = mysqli_stmt_get_result($stmt);
            
            if ($avg_row = mysqli_fetch_assoc($avg_result)) {
                $total_credits += $avg_row['total_credits'];
                $validated_credits += $avg_row['validated_credits'];
                $total_points += $avg_row['average'];
                $nb_semestres++;
            }
        }
        
        // Calculer la moyenne annuelle (moyenne arithmétique des deux semestres)
        $annual_average = ($nb_semestres > 0) ? $total_points / $nb_semestres : 0;
        
        // Déterminer la décision annuelle
        $annual_decision = '';
        if ($annual_average >= 10 && $validated_credits == $total_credits) {
            $annual_decision = 'Admis';
        } else if ($annual_average >= 10 && $validated_credits < $total_credits && $validated_credits >= 45) {
            $annual_decision = 'Admis avec dettes';
        } else {
            $annual_decision = 'Ajourné';
        }
        
        // Mettre à jour la décision annuelle dans la base de données
        $query = "INSERT INTO annual_decisions (id_student, id_academic_year, study_year, average, total_credits, validated_credits, decision) 
                  VALUES (?, ?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE average = ?, total_credits = ?, validated_credits = ?, decision = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiidiisd", $id_student, $id_academic_year, $study_year, $annual_average, $total_credits, $validated_credits, $annual_decision, $annual_average, $total_credits, $validated_credits, $annual_decision);
        mysqli_stmt_execute($stmt);
        
        return true;
    }
    
    return false;
}

// Traitement du formulaire de saisie des notes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_notes'])) {
    $id_student = $_POST['id_student'];
    $id_subject = $_POST['id_subject'];
    $note_devoir = $_POST['note_devoir'];
    $note_examen = $_POST['note_examen'];
    
    // Calculer la note finale (moyenne pondérée)
    $note_finale = ($note_devoir * 0.4) + ($note_examen * 0.6);
    
    // Initialiser le statut (sera mis à jour plus tard par updateSubjectsStatusForModule)
    $status = 'En cours';
        $validated = 0;
    
    // Vérifier si une note existe déjà pour cet étudiant et cette matière
    $query = "SELECT id_note FROM notes WHERE id_student = ? AND id_subject = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_student, $id_subject);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Mettre à jour la note existante
        $row = mysqli_fetch_assoc($result);
        $id_note = $row['id_note'];
        
        $query = "UPDATE notes SET note_devoir = ?, note_examen = ?, note_finale = ?, validated = ?, status = ? WHERE id_note = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "dddiis", $note_devoir, $note_examen, $note_finale, $validated, $status, $id_note);
    } else {
        // Insérer une nouvelle note
        $query = "INSERT INTO notes (id_student, id_subject, note_devoir, note_examen, note_finale, validated, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iidddis", $id_student, $id_subject, $note_devoir, $note_examen, $note_finale, $validated, $status);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        // Récupérer l'ID du module pour cette matière
        $query = "SELECT id_module FROM subjects WHERE id_subject = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_subject);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $subject = mysqli_fetch_assoc($result);
        $id_module = $subject['id_module'];
        
        // Récupérer l'ID du semestre pour ce module
        $query = "SELECT id_semester FROM modules WHERE id_module = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_module);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $module = mysqli_fetch_assoc($result);
        $id_semester = $module['id_semester'];
        
        // Mettre à jour les moyennes du semestre et appliquer la validation
        if (updateSemesterAverages($conn, $id_student, $id_semester)) {
            $_SESSION['success_message'] = "Note enregistrée et moyennes mises à jour avec succès.";
        } else {
            $_SESSION['success_message'] = "Note enregistrée, mais erreur lors de la mise à jour des moyennes.";
        }
        
        // Rediriger vers la page de gestion des notes
        header("Location: manage_notes.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement de la note: " . mysqli_error($conn);
        header("Location: manage_notes.php");
        exit;
    }
}
?>





