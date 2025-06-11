<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Gestion des filtres
$where_clause = "";

if (isset($_GET['semester']) && !empty($_GET['semester'])) {
    $semester_id = mysqli_real_escape_string($conn, $_GET['semester']);
    $where_clause .= " AND sem.id_semester = '$semester_id'";
}

if (isset($_GET['module']) && !empty($_GET['module'])) {
    $module_id = mysqli_real_escape_string($conn, $_GET['module']);
    $where_clause .= " AND m.id_module = '$module_id'";
}

if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $subject_id = mysqli_real_escape_string($conn, $_GET['subject']);
    $where_clause .= " AND sub.id_subject = '$subject_id'";
}

if (isset($_GET['student']) && !empty($_GET['student'])) {
    $student_search = mysqli_real_escape_string($conn, $_GET['student']);
    $where_clause .= " AND (s.nom LIKE '%$student_search%' OR s.prenom LIKE '%$student_search%' OR s.matricule LIKE '%$student_search%')";
}

// Récupérer les semestres pour le filtre
$semesters_query = "SELECT * FROM semesters ORDER BY semester_name";
$semesters_result = mysqli_query($conn, $semesters_query);

// Récupérer les modules pour le filtre
$modules_query = "SELECT * FROM modules ORDER BY module_name";
$modules_result = mysqli_query($conn, $modules_query);

// Récupérer les matières pour le filtre
$subjects_query = "SELECT * FROM subjects ORDER BY subject_name";
$subjects_result = mysqli_query($conn, $subjects_query);

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Requête pour compter le nombre total d'enregistrements
$count_query = "SELECT COUNT(*) as total FROM notes n
               JOIN students s ON n.id_student = s.id_student
               JOIN subjects sub ON n.id_subject = sub.id_subject
               JOIN modules m ON sub.id_module = m.id_module
               JOIN semesters sem ON m.id_semester = sem.id_semester
               WHERE 1=1" . $where_clause;
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Requête pour récupérer les notes avec pagination
$query = "SELECT n.*, s.nom, s.prenom, s.matricule, sub.subject_name, m.module_name, sem.semester_name
         FROM notes n
         JOIN students s ON n.id_student = s.id_student
         JOIN subjects sub ON n.id_subject = sub.id_subject
         JOIN modules m ON sub.id_module = m.id_module
         JOIN semesters sem ON m.id_semester = sem.id_semester
         WHERE 1=1" . $where_clause . "
         ORDER BY sem.semester_name, m.module_name, sub.subject_name, s.nom, s.prenom
         LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $query);

// Traitement de la suppression
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id_note = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Récupérer les informations de la note avant suppression
    $info_query = "SELECT n.id_student, m.id_semester
                  FROM notes n
                  JOIN subjects sub ON n.id_subject = sub.id_subject
                  JOIN modules m ON sub.id_module = m.id_module
                  WHERE n.id_note = '$id_note'";
    $info_result = mysqli_query($conn, $info_query);
    
    if ($info_result && mysqli_num_rows($info_result) > 0) {
        $info = mysqli_fetch_assoc($info_result);
        $id_student = $info['id_student'];
        $id_semester = $info['id_semester'];
        
        // Supprimer la note
        $delete_query = "DELETE FROM notes WHERE id_note = '$id_note'";
        if (mysqli_query($conn, $delete_query)) {
            // Mettre à jour les moyennes du semestre
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
            }
            
            // Rediriger pour éviter les soumissions multiples
            header("Location: manage_notes.php?success=1");
            exit;
        } else {
            $error_message = "Erreur lors de la suppression de la note: " . mysqli_error($conn);
        }
    }
}

// Traitement de l'ajout d'une note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $id_student = mysqli_real_escape_string($conn, $_POST['id_student']);
    $id_subject = mysqli_real_escape_string($conn, $_POST['id_subject']);
    $note_devoir = floatval($_POST['note_devoir']);
    $note_examen = floatval($_POST['note_examen']);
    
    // Vérifier que les notes sont dans la plage valide
    if ($note_devoir < 0 || $note_devoir > 20 || $note_examen < 0 || $note_examen > 20) {
        $error_message = "Les notes doivent être comprises entre 0 et 20.";
    } else {
        // Vérifier si une note existe déjà pour cet étudiant et cette matière
        $check_query = "SELECT id_note FROM notes WHERE id_student = '$id_student' AND id_subject = '$id_subject'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Une note existe déjà pour cet étudiant dans cette matière.";
        } else {
            // Calculer la note finale et le statut
            $note_finale = ($note_devoir * 0.4) + ($note_examen * 0.6);
            $note_finale = round($note_finale, 2);

            // Déterminer le statut en fonction de la note finale
            if ($note_finale >= 10) {
                $status = 'Validé';
            } elseif ($note_finale >= 7) {
                $status = 'Rattrapage';
            } else {
                $status = 'Non validé';
            }
            
            // Insérer la note
            $insert_query = "INSERT INTO notes (id_student, id_subject, note_devoir, note_examen, note_finale, status) 
                           VALUES ('$id_student', '$id_subject', '$note_devoir', '$note_examen', '$note_finale', '$status')";
            
            if (mysqli_query($conn, $insert_query)) {
                // Récupérer l'ID du semestre pour mettre à jour les moyennes
                $semester_query = "SELECT m.id_semester 
                                  FROM subjects sub 
                                  JOIN modules m ON sub.id_module = m.id_module 
                                  WHERE sub.id_subject = '$id_subject'";
                $semester_result = mysqli_query($conn, $semester_query);
                
                if ($semester_result && mysqli_num_rows($semester_result) > 0) {
                    $semester_row = mysqli_fetch_assoc($semester_result);
                    $id_semester = $semester_row['id_semester'];
                    
                    // Mettre à jour les moyennes du semestre
                    include_once 'edit_note.php'; // Inclure le fichier pour utiliser la fonction updateSemesterAverages
                    if (function_exists('updateSemesterAverages')) {
                        updateSemesterAverages($conn, $id_student, $id_semester);
                    }
                }
                
                $success_message = "Note ajoutée avec succès.";
            } else {
                $error_message = "Erreur lors de l'ajout de la note: " . mysqli_error($conn);
            }
        }
    }
}

// Message de succès
$success_message = '';
if (isset($_GET['success'])) {
    $success_message = "Opération réussie.";
}

$title = "Gestion des Notes";
include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Gestion des Notes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Gestion des Notes</li>
    </ol>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="mainToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>
    
    <?php if (isset($error_message)) : ?>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.getElementById('mainToast');
                toastEl.querySelector('.toast-header i').className = 'fas fa-times-circle text-danger me-2';
                toastEl.querySelector('.me-auto').textContent = 'Erreur';
                toastEl.querySelector('.toast-body').textContent = <?php echo json_encode($error_message); ?>;
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        </script>
    <?php endif; ?>
    <?php if (isset($success_message)) : ?>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.getElementById('mainToast');
                toastEl.querySelector('.toast-header i').className = 'fas fa-check-circle text-success me-2';
                toastEl.querySelector('.me-auto').textContent = 'Succès';
                toastEl.querySelector('.toast-body').textContent = <?php echo json_encode($success_message); ?>;
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        </script>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtres
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="semester" class="form-label">Semestre</label>
                    <select name="semester" id="semester" class="form-select">
                        <option value="">Tous les semestres</option>
                        <?php while ($semester = mysqli_fetch_assoc($semesters_result)): ?>
                        <option value="<?php echo $semester['id_semester']; ?>" <?php echo (isset($_GET['semester']) && $_GET['semester'] == $semester['id_semester']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($semester['semester_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="module" class="form-label">Module</label>
                    <select name="module" id="module" class="form-select">
                        <option value="">Tous les modules</option>
                        <?php while ($module = mysqli_fetch_assoc($modules_result)): ?>
                        <option value="<?php echo $module['id_module']; ?>" <?php echo (isset($_GET['module']) && $_GET['module'] == $module['id_module']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($module['module_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subject" class="form-label">Matière</label>
                    <select name="subject" id="subject" class="form-select">
                        <option value="">Toutes les matières</option>
                        <?php while ($subject = mysqli_fetch_assoc($subjects_result)): ?>
                        <option value="<?php echo $subject['id_subject']; ?>" <?php echo (isset($_GET['subject']) && $_GET['subject'] == $subject['id_subject']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="student" class="form-label">Étudiant (nom, prénom ou matricule)</label>
                    <input type="text" name="student" id="student" class="form-control" value="<?php echo isset($_GET['student']) ? htmlspecialchars($_GET['student']) : ''; ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="manage_notes.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Liste des Notes
            </div>
    
            
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Semestre</th>
                            <th>Module</th>
                            <th>Matière</th>
                            <th>Étudiant</th>
                            <th>Matricule</th>
                            <th>Note CC (40%)</th>
                            <th>Note Examen (60%)</th>
                            <th>Note Finale</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['semester_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nom'] . ' ' . $row['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($row['matricule']); ?></td>
                                    <td><?php echo htmlspecialchars($row['note_devoir']); ?></td>
                                    <td><?php echo htmlspecialchars($row['note_examen']); ?></td>
                                    <td><?php echo htmlspecialchars($row['note_finale']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_icon = '';
                                        switch($row['status']) {
                                            case 'Validé':
                                                $status_class = 'success';
                                                $status_icon = 'check-circle';
                                                break;
                                            case 'Rattrapage':
                                                $status_class = 'warning';
                                                $status_icon = 'exclamation-circle';
                                                break;
                                            case 'Non validé':
                                                $status_class = 'danger';
                                                $status_icon = 'times-circle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_note.php?id=<?php echo $row['id_note']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage_notes.php?delete=<?php echo $row['id_note']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette note?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">Aucune note trouvée</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['semester']) ? '&semester=' . $_GET['semester'] : ''; ?><?php echo isset($_GET['module']) ? '&module=' . $_GET['module'] : ''; ?><?php echo isset($_GET['subject']) ? '&subject=' . $_GET['subject'] : ''; ?><?php echo isset($_GET['student']) ? '&student=' . $_GET['student'] : ''; ?>">Précédent</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['semester']) ? '&semester=' . $_GET['semester'] : ''; ?><?php echo isset($_GET['module']) ? '&module=' . $_GET['module'] : ''; ?><?php echo isset($_GET['subject']) ? '&subject=' . $_GET['subject'] : ''; ?><?php echo isset($_GET['student']) ? '&student=' . $_GET['student'] : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['semester']) ? '&semester=' . $_GET['semester'] : ''; ?><?php echo isset($_GET['module']) ? '&module=' . $_GET['module'] : ''; ?><?php echo isset($_GET['subject']) ? '&subject=' . $_GET['subject'] : ''; ?><?php echo isset($_GET['student']) ? '&student=' . $_GET['student'] : ''; ?>">Suivant</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrage dynamique des modules en fonction du semestre sélectionné
    const semesterSelect = document.getElementById('semester');
    const moduleSelect = document.getElementById('module');
    const subjectSelect = document.getElementById('subject');
    
    semesterSelect.addEventListener('change', function() {
        const semesterId = this.value;
        
        // Réinitialiser les sélections de module et matière
        moduleSelect.innerHTML = '<option value="">Tous les modules</option>';
        subjectSelect.innerHTML = '<option value="">Toutes les matières</option>';
        
        if (semesterId) {
            // Charger les modules du semestre sélectionné via AJAX
            fetch(`get_modules.php?semester_id=${semesterId}`)
                .then(response => response.json())
                .then(modules => {
                    modules.forEach(module => {
                        const option = document.createElement('option');
                        option.value = module.id_module;
                        option.textContent = module.module_name;
                        moduleSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Erreur:', error));
        }
    });
    
    // Filtrage dynamique des matières en fonction du module sélectionné
    moduleSelect.addEventListener('change', function() {
        const moduleId = this.value;
        
        // Réinitialiser la sélection de matière
        subjectSelect.innerHTML = '<option value="">Toutes les matières</option>';
        
        if (moduleId) {
            // Charger les matières du module sélectionné via AJAX
            fetch(`get_subjects.php?module_id=${moduleId}`)
                .then(response => response.json())
                .then(subjects => {
                    subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id_subject;
                        option.textContent = subject.subject_name;
                        subjectSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Erreur:', error));
        }
    });
});
</script>

<!-- Ajouter un modal pour ajouter une note -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">Ajouter une Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="manage_notes.php">
                    <!-- Sélection hiérarchique -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="add_faculty" class="form-label">Faculté</label>
                            <select class="form-select" id="add_faculty" onchange="loadDepartmentsForAdd()">
                                <option value="">Sélectionner une faculté</option>
                                <?php
                                $faculties_query = "SELECT id_faculty, faculty_name FROM faculties ORDER BY faculty_name";
                                $faculties_result = mysqli_query($conn, $faculties_query);
                                while ($faculty = mysqli_fetch_assoc($faculties_result)) {
                                    echo "<option value='" . $faculty['id_faculty'] . "'>" . htmlspecialchars($faculty['faculty_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_department" class="form-label">Département</label>
                            <select class="form-select" id="add_department" onchange="loadFilieresForAdd()">
                                <option value="">Sélectionner un département</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="add_filiere" class="form-label">Filière</label>
                            <select class="form-select" id="add_filiere" onchange="loadSemestersForAdd()">
                                <option value="">Sélectionner une filière</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_semester" class="form-label">Semestre</label>
                            <select class="form-select" id="add_semester" onchange="loadModulesForAdd()">
                                <option value="">Sélectionner un semestre</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="add_module" class="form-label">Module</label>
                            <select class="form-select" id="add_module" onchange="loadSubjectsForAdd()">
                                <option value="">Sélectionner un module</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="id_subject" class="form-label">Matière</label>
                            <select class="form-select" id="id_subject" name="id_subject" required>
                                <option value="">Sélectionner une matière</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="add_student_filter" class="form-label">Filtrer les étudiants</label>
                            <input type="text" class="form-control" id="add_student_filter" placeholder="Nom, prénom ou matricule">
                        </div>
                        <div class="col-md-6">
                            <label for="id_student" class="form-label">Étudiant</label>
                            <select class="form-select" id="id_student" name="id_student" required>
                                <option value="">Sélectionner un étudiant</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="note_devoir" class="form-label">Note CC (40%)</label>
                            <input type="number" class="form-control" id="note_devoir" name="note_devoir" min="0" max="20" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="note_examen" class="form-label">Note Examen (60%)</label>
                            <input type="number" class="form-control" id="note_examen" name="note_examen" min="0" max="20" step="0.01" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="note_finale_preview" class="form-label">Note Finale (calculée)</label>
                            <input type="text" class="form-control" id="note_finale_preview" readonly>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_note" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculer la note finale en temps réel dans le modal d'ajout
    const noteDevoir = document.getElementById('note_devoir');
    const noteExamen = document.getElementById('note_examen');
    const noteFinalePreview = document.getElementById('note_finale_preview');
    
    if (noteDevoir && noteExamen) {
        noteDevoir.addEventListener('input', calculateFinalNote);
        noteExamen.addEventListener('input', calculateFinalNote);
    }
    
    function calculateFinalNote() {
        const devoir = parseFloat(noteDevoir.value) || 0;
        const examen = parseFloat(noteExamen.value) || 0;
        
        if (devoir < 0 || devoir > 20 || examen < 0 || examen > 20) {
            return;
        }
        
        const finale = (devoir * 0.4) + (examen * 0.6);
        const finaleRounded = Math.round(finale * 100) / 100;
        
        if (noteFinalePreview) {
            noteFinalePreview.value = finaleRounded;
        }
    }
    
    // Filtrer les étudiants en fonction du texte saisi
    const studentFilter = document.getElementById('add_student_filter');
    if (studentFilter) {
        studentFilter.addEventListener('input', function() {
            const filterText = this.value.toLowerCase();
            const studentSelect = document.getElementById('id_student');
            const options = studentSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') return; // Ignorer l'option par défaut
                
                const text = option.textContent.toLowerCase();
                if (text.includes(filterText)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    }
});

// Fonctions pour charger les données hiérarchiques
function loadDepartmentsForAdd() {
    const facultyId = document.getElementById('add_faculty').value;
    const departmentSelect = document.getElementById('add_department');
    
    // Réinitialiser les sélections dépendantes
    departmentSelect.innerHTML = '<option value="">Sélectionner un département</option>';
    document.getElementById('add_filiere').innerHTML = '<option value="">Sélectionner une filière</option>';
    document.getElementById('add_semester').innerHTML = '<option value="">Sélectionner un semestre</option>';
    document.getElementById('add_module').innerHTML = '<option value="">Sélectionner un module</option>';
    document.getElementById('id_subject').innerHTML = '<option value="">Sélectionner une matière</option>';
    
    if (facultyId) {
        fetch(`get_departments.php?id_faculty=${facultyId}`)
            .then(response => response.json())
            .then(departments => {
                departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id_department;
                    option.textContent = dept.department_name;
                    departmentSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur:', error));
    }
}

function loadFilieresForAdd() {
    const departmentId = document.getElementById('add_department').value;
    const filiereSelect = document.getElementById('add_filiere');
    
    // Réinitialiser les sélections dépendantes
    filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
    document.getElementById('add_semester').innerHTML = '<option value="">Sélectionner un semestre</option>';
    document.getElementById('add_module').innerHTML = '<option value="">Sélectionner un module</option>';
    document.getElementById('id_subject').innerHTML = '<option value="">Sélectionner une matière</option>';
    
    if (departmentId) {
        fetch(`get_filieres.php?id_department=${departmentId}`)
            .then(response => response.json())
            .then(filieres => {
                filieres.forEach(filiere => {
                    const option = document.createElement('option');
                    option.value = filiere.id_filiere;
                    option.textContent = filiere.filiere_name;
                    filiereSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur:', error));
    }
}

function loadSemestersForAdd() {
    const filiereId = document.getElementById('add_filiere').value;
    const semesterSelect = document.getElementById('add_semester');
    
    // Réinitialiser les sélections dépendantes
    semesterSelect.innerHTML = '<option value="">Sélectionner un semestre</option>';
    document.getElementById('add_module').innerHTML = '<option value="">Sélectionner un module</option>';
    document.getElementById('id_subject').innerHTML = '<option value="">Sélectionner une matière</option>';
    
    if (filiereId) {
        fetch(`get_semesters.php?id_filiere=${filiereId}`)
            .then(response => response.json())
            .then(semesters => {
                semesters.forEach(semester => {
                    const option = document.createElement('option');
                    option.value = semester.id_semester;
                    option.textContent = semester.semester_name;
                    semesterSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur:', error));
            
        // Charger les étudiants de cette filière
        loadStudentsForAdd(filiereId);
    }
}

function loadModulesForAdd() {
    const semesterId = document.getElementById('add_semester').value;
    const moduleSelect = document.getElementById('add_module');
    
    // Réinitialiser les sélections dépendantes
    moduleSelect.innerHTML = '<option value="">Sélectionner un module</option>';
    document.getElementById('id_subject').innerHTML = '<option value="">Sélectionner une matière</option>';
    
    if (semesterId) {
        fetch(`get_modules.php?semester_id=${semesterId}`)
            .then(response => response.json())
            .then(modules => {
                modules.forEach(module => {
                    const option = document.createElement('option');
                    option.value = module.id_module;
                    option.textContent = module.module_name;
                    moduleSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur:', error));
    }
}

function loadSubjectsForAdd() {
    const moduleId = document.getElementById('add_module').value;
    const subjectSelect = document.getElementById('id_subject');
    
    // Réinitialiser la sélection de matière
    subjectSelect.innerHTML = '<option value="">Sélectionner une matière</option>';
    
    if (moduleId) {
        fetch(`get_subjects.php?module_id=${moduleId}`)
            .then(response => response.json())
            .then(subjects => {
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id_subject;
                    option.textContent = subject.subject_name;
                    subjectSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur:', error));
    }
}

function loadStudentsForAdd(filiereId) {
    const studentSelect = document.getElementById('id_student');
    
    // Réinitialiser la sélection d'étudiant
    studentSelect.innerHTML = '<option value="">Sélectionner un étudiant</option>';
    
    if (filiereId) {
        fetch(`get_students.php?id_filiere=${filiereId}`)
            .then(response => response.json())
            .then(students => {
                students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id_student;
                    option.textContent = `${student.nom} ${student.prenom} (${student.matricule})`;
                    studentSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur:', error));
    }
}
</script>

<!-- Modal d'édition de note -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editNoteForm" method="POST" action="manage_notes.php">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editNoteModalLabel">
                        <i class="fas fa-edit me-2"></i>Modifier la Note
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_note" id="edit_id_note">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_id_student" class="form-label">Étudiant</label>
                            <select class="form-select" id="edit_id_student" name="id_student" required>
                                <option value="">Sélectionner un étudiant</option>
                                <?php
                                $students_query = "SELECT s.id_student, s.nom, s.prenom, s.matricule 
                                                 FROM students s 
                                                 ORDER BY s.nom, s.prenom";
                                $students_result = mysqli_query($conn, $students_query);
                                while ($student = mysqli_fetch_assoc($students_result)) {
                                    echo "<option value='" . $student['id_student'] . "'>" . 
                                         htmlspecialchars($student['nom'] . ' ' . $student['prenom'] . ' (' . $student['matricule'] . ')') . 
                                         "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_id_subject" class="form-label">Matière</label>
                            <select class="form-select" id="edit_id_subject" name="id_subject" required>
                                <option value="">Sélectionner une matière</option>
                                <?php
                                $subjects_query = "SELECT s.id_subject, s.subject_name, m.module_name 
                                                 FROM subjects s 
                                                 JOIN modules m ON s.id_module = m.id_module 
                                                 ORDER BY m.module_name, s.subject_name";
                                $subjects_result = mysqli_query($conn, $subjects_query);
                                while ($subject = mysqli_fetch_assoc($subjects_result)) {
                                    echo "<option value='" . $subject['id_subject'] . "'>" . 
                                         htmlspecialchars($subject['module_name'] . ' - ' . $subject['subject_name']) . 
                                         "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_note_devoir" class="form-label">
                                <i class="fas fa-pencil-alt me-1"></i>Note Devoir
                            </label>
                            <input type="number" class="form-control" id="edit_note_devoir" name="note_devoir" 
                                   min="0" max="20" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_note_examen" class="form-label">
                                <i class="fas fa-file-alt me-1"></i>Note Examen
                            </label>
                            <input type="number" class="form-control" id="edit_note_examen" name="note_examen" 
                                   min="0" max="20" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        La note finale sera calculée automatiquement (40% devoir + 60% examen)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" name="update_note" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du modal d'édition
    const editNoteModal = document.getElementById('editNoteModal');
    if (editNoteModal) {
        editNoteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const noteData = JSON.parse(button.getAttribute('data-note'));
            
            // Remplir les champs du formulaire
            document.getElementById('edit_id_note').value = noteData.id_note;
            document.getElementById('edit_id_student').value = noteData.id_student;
            document.getElementById('edit_id_subject').value = noteData.id_subject;
            document.getElementById('edit_note_devoir').value = noteData.note_devoir;
            document.getElementById('edit_note_examen').value = noteData.note_examen;
            
            // Focus sur le premier champ
            document.getElementById('edit_note_devoir').focus();
        });
    }
    
    // Calcul automatique de la note finale
    const noteDevoirInput = document.getElementById('edit_note_devoir');
    const noteExamenInput = document.getElementById('edit_note_examen');
    
    function updateNoteFinale() {
        const noteDevoir = parseFloat(noteDevoirInput.value) || 0;
        const noteExamen = parseFloat(noteExamenInput.value) || 0;
        const noteFinale = (noteDevoir * 0.4) + (noteExamen * 0.6);
        
        // Mettre à jour l'affichage de la note finale
        const noteFinaleDisplay = document.getElementById('note_finale_display');
        if (noteFinaleDisplay) {
            noteFinaleDisplay.textContent = noteFinale.toFixed(2);
            
            // Mettre à jour la couleur en fonction de la note
            if (noteFinale >= 10) {
                noteFinaleDisplay.className = 'text-success';
            } else if (noteFinale >= 7) {
                noteFinaleDisplay.className = 'text-warning';
            } else {
                noteFinaleDisplay.className = 'text-danger';
            }
        }
    }
    
    noteDevoirInput.addEventListener('input', updateNoteFinale);
    noteExamenInput.addEventListener('input', updateNoteFinale);
});
</script>

</html>
