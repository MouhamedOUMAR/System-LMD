<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage_notes.php");
    exit;
}

$id_note = mysqli_real_escape_string($conn, $_GET['id']);

// Récupérer les informations de la note
$query = "SELECT n.*, s.nom, s.prenom, s.matricule, sub.subject_name, m.module_name, sem.semester_name, sem.id_semester 
         FROM notes n 
         JOIN students s ON n.id_student = s.id_student 
         JOIN subjects sub ON n.id_subject = sub.id_subject 
         JOIN modules m ON sub.id_module = m.id_module 
         JOIN semesters sem ON m.id_semester = sem.id_semester 
         WHERE n.id_note = '$id_note'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: manage_notes.php");
    exit;
}

$note = mysqli_fetch_assoc($result);

// Fonction pour calculer la note finale et déterminer le statut
function calculateStatus($note_devoir, $note_examen) {
    $note_finale = ($note_devoir * 0.4) + ($note_examen * 0.6);
    $note_finale = round($note_finale, 2); // Arrondir à 2 décimales
    
    if ($note_finale >= 10) {
        return ['note' => $note_finale, 'status' => 'Validé'];
    } elseif ($note_finale >= 7) {
        return ['note' => $note_finale, 'status' => 'Rattrapage'];
    } else {
        return ['note' => $note_finale, 'status' => 'Non validé'];
    }
}

// Fonction pour mettre à jour les moyennes du semestre
function updateSemesterAverages($conn, $id_student, $id_semester) {
    // Vérifier si la table results existe
    $table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'results'");
    if (mysqli_num_rows($table_exists) == 0) {
        // Créer la table si elle n'existe pas
        $create_table = "CREATE TABLE IF NOT EXISTS `results` (
            `id_result` int(11) NOT NULL AUTO_INCREMENT,
            `id_student` int(11) NOT NULL,
            `id_semester` int(11) NOT NULL,
            `moyenne_semestre` decimal(5,2) DEFAULT NULL,
            `status` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id_result`),
            KEY `id_student` (`id_student`),
            KEY `id_semester` (`id_semester`),
            CONSTRAINT `results_ibfk_1` FOREIGN KEY (`id_student`) REFERENCES `students` (`id_student`),
            CONSTRAINT `results_ibfk_2` FOREIGN KEY (`id_semester`) REFERENCES `semesters` (`id_semester`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        mysqli_query($conn, $create_table);
    }
    
    // Calculer la moyenne du semestre
    $avg_query = "SELECT AVG(n.note_finale) as moyenne 
                 FROM notes n 
                 JOIN subjects sub ON n.id_subject = sub.id_subject 
                 JOIN modules m ON sub.id_module = m.id_module 
                 WHERE n.id_student = '$id_student' AND m.id_semester = '$id_semester'";
    $avg_result = mysqli_query($conn, $avg_query);
    
    if ($avg_result && mysqli_num_rows($avg_result) > 0) {
        $avg_row = mysqli_fetch_assoc($avg_result);
        $moyenne_semestre = round($avg_row['moyenne'], 2);
        
        // Déterminer le statut du semestre
        $status = $moyenne_semestre >= 10 ? 'Validé' : 'Non validé';
        
        // Vérifier si un résultat existe déjà pour ce semestre
        $check_query = "SELECT id_result FROM results WHERE id_student = '$id_student' AND id_semester = '$id_semester'";
        $check_result = mysqli_query($conn, $check_query);
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            // Mettre à jour le résultat existant
            $row = mysqli_fetch_assoc($check_result);
            $id_result = $row['id_result'];
            $update_query = "UPDATE results SET moyenne_semestre = '$moyenne_semestre', status = '$status' 
                            WHERE id_result = '$id_result'";
            mysqli_query($conn, $update_query);
        } else {
            // Ajouter un nouveau résultat
            $insert_query = "INSERT INTO results (id_student, id_semester, moyenne_semestre, status) 
                           VALUES ('$id_student', '$id_semester', '$moyenne_semestre', '$status')";
            mysqli_query($conn, $insert_query);
        }
        
        return true;
    }
    
    return false;
}

$success_message = '';
$error_message = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $note_devoir = floatval($_POST['note_devoir']);
    $note_examen = floatval($_POST['note_examen']);
    
    // Vérifier que les notes sont dans la plage valide
    if ($note_devoir < 0 || $note_devoir > 20 || $note_examen < 0 || $note_examen > 20) {
        $error_message = "Les notes doivent être comprises entre 0 et 20.";
    } else {
        // Calculer la note finale et le statut
        $result = calculateStatus($note_devoir, $note_examen);
        $note_finale = $result['note'];
        $status = $result['status'];
        
        // Mettre à jour la note
        $update_query = "UPDATE notes SET note_devoir = '$note_devoir', note_examen = '$note_examen', 
                        note_finale = '$note_finale', status = '$status' WHERE id_note = '$id_note'";
        
        if (mysqli_query($conn, $update_query)) {
            // Mettre à jour les moyennes du semestre
            $id_student = $note['id_student'];
            $id_semester = $note['id_semester'];
            
            if (updateSemesterAverages($conn, $id_student, $id_semester)) {
                $success_message = "Note mise à jour avec succès et moyennes recalculées.";
                
                // Récupérer les informations mises à jour
                $query = "SELECT n.*, s.nom, s.prenom, s.matricule, sub.subject_name, m.module_name, sem.semester_name, sem.id_semester 
                         FROM notes n 
                         JOIN students s ON n.id_student = s.id_student 
                         JOIN subjects sub ON n.id_subject = sub.id_subject 
                         JOIN modules m ON sub.id_module = m.id_module 
                         JOIN semesters sem ON m.id_semester = sem.id_semester 
                         WHERE n.id_note = '$id_note'";
                $result = mysqli_query($conn, $query);
                $note = mysqli_fetch_assoc($result);
            } else {
                $success_message = "Note mise à jour avec succès, mais erreur lors du recalcul des moyennes.";
            }
        } else {
            $error_message = "Erreur lors de la mise à jour de la note: " . mysqli_error($conn);
        }
    }
}

$title = "Modifier une Note";
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Modifier une Note</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="manage_notes.php">Gestion des Notes</a></li>
        <li class="breadcrumb-item active">Modifier une Note</li>
    </ol>
    
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Modifier la Note
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Informations de l'Étudiant</h5>
                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($note['nom'] . ' ' . $note['prenom']); ?></p>
                    <p><strong>Matricule:</strong> <?php echo htmlspecialchars($note['matricule']); ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Informations de la Matière</h5>
                    <p><strong>Semestre:</strong> <?php echo htmlspecialchars($note['semester_name']); ?></p>
                    <p><strong>Module:</strong> <?php echo htmlspecialchars($note['module_name']); ?></p>
                    <p><strong>Matière:</strong> <?php echo htmlspecialchars($note['subject_name']); ?></p>
                </div>
            </div>
            
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="note_devoir" class="form-label">Note CC (40%)</label>
                        <input type="number" name="note_devoir" id="note_devoir" class="form-control" min="0" max="20" step="0.01" value="<?php echo htmlspecialchars($note['note_devoir']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="note_examen" class="form-label">Note Examen (60%)</label>
                        <input type="number" name="note_examen" id="note_examen" class="form-control" min="0" max="20" step="0.01" value="<?php echo htmlspecialchars($note['note_examen']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="note_finale" class="form-label">Note Finale (calculée)</label>
                        <input type="text" id="note_finale" class="form-control" value="<?php echo htmlspecialchars($note['note_finale']); ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Statut</label>
                        <?php
                        $status = $note['status'];
                        $badgeClass = '';
                        if ($status === 'Validé') {
                            $badgeClass = 'bg-success';
                        } elseif ($status === 'Validé par compensation') {
                            $badgeClass = 'bg-primary';
                        } elseif ($status === 'Rattrapage') {
                            $badgeClass = 'bg-warning text-dark';
                        } else {
                            $badgeClass = 'bg-danger';
                        }
                        ?>
                        <span class="form-control border-0 fw-bold <?php echo $badgeClass; ?>" readonly style="pointer-events:none;">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="manage_notes.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const noteDevoir = document.getElementById('note_devoir');
    const noteExamen = document.getElementById('note_examen');
    const noteFinale = document.getElementById('note_finale');
    const status = document.getElementById('status');
    
    function calculateNote() {
        const devoir = parseFloat(noteDevoir.value) || 0;
        const examen = parseFloat(noteExamen.value) || 0;
        
        if (devoir < 0 || devoir > 20 || examen < 0 || examen > 20) {
            return;
        }
        
        const finale = (devoir * 0.4) + (examen * 0.6);
        const finaleRounded = Math.round(finale * 100) / 100;
        
        noteFinale.value = finaleRounded;
        
        if (finaleRounded >= 10) {
            status.value = 'Validé';
            status.className = 'form-control bg-success text-white';
        } else {
            status.value = 'Rattrapage';
            status.className = 'form-control bg-warning';
        }
    }
    
    noteDevoir.addEventListener('input', calculateNote);
    noteExamen.addEventListener('input', calculateNote);
});
</script>

<?php include 'includes/footer.php'; ?>










