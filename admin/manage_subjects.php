<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Vérifier si la table subjects a la colonne coefficient
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'coefficient'");
if (mysqli_num_rows($check_column) == 0) {
    // Ajouter la colonne manquante
    mysqli_query($conn, "ALTER TABLE subjects ADD COLUMN coefficient INT DEFAULT 1");
}

// Ajout d'une matière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $id_module = mysqli_real_escape_string($conn, $_POST['id_module']);
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $coefficient = mysqli_real_escape_string($conn, $_POST['coefficient']);
    $id_teacher = mysqli_real_escape_string($conn, $_POST['id_teacher']); // Ajout de l'enseignant

    // Vérifier si la matière existe déjà dans ce module
    $check_query = "SELECT id_subject FROM subjects WHERE id_module = '$id_module' AND subject_name = '$subject_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Erreur : Cette matière existe déjà dans ce module.";
    } else {
        $query = "INSERT INTO subjects (id_module, subject_name, coefficient, id_teacher) VALUES ('$id_module', '$subject_name', '$coefficient', '$id_teacher')";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_subjects.php?success=Matière ajoutée avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'une matière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    $id_subject = mysqli_real_escape_string($conn, $_POST['id_subject']);
    $id_module = mysqli_real_escape_string($conn, $_POST['id_module']);
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $coefficient = mysqli_real_escape_string($conn, $_POST['coefficient']);
    $id_teacher = mysqli_real_escape_string($conn, $_POST['id_teacher']); // Ajout de l'enseignant

    // Vérifier si la matière existe déjà dans ce module (sauf elle-même)
    $check_query = "SELECT id_subject FROM subjects WHERE id_module = '$id_module' AND subject_name = '$subject_name' AND id_subject != '$id_subject'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Erreur : Une matière avec ce nom existe déjà dans ce module.";
    } else {
        $query = "UPDATE subjects SET id_module = '$id_module', subject_name = '$subject_name', coefficient = '$coefficient', id_teacher = '$id_teacher' WHERE id_subject = '$id_subject'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_subjects.php?success=Matière mise à jour avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'une matière
if (isset($_GET['delete'])) {
    $id_subject = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Vérifier si la matière est utilisée dans des notes
    $check_query = "SELECT COUNT(*) as count FROM notes WHERE id_subject = '$id_subject'";
    $check_result = mysqli_query($conn, $check_query);
    $row = mysqli_fetch_assoc($check_result);
    
    if ($row['count'] > 0) {
        $error = "Impossible de supprimer cette matière car elle est utilisée dans des notes.";
    } else {
        $query = "DELETE FROM subjects WHERE id_subject = '$id_subject'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_subjects.php?success=Matière supprimée avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Afficher un message de succès si présent dans l'URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matières - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Matières</h1>
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
            <?php if (isset($error)) : ?>
                <script>
                    window.addEventListener('DOMContentLoaded', function() {
                        var toastEl = document.getElementById('mainToast');
                        toastEl.querySelector('.toast-header i').className = 'fas fa-times-circle text-danger me-2';
                        toastEl.querySelector('.me-auto').textContent = 'Erreur';
                        toastEl.querySelector('.toast-body').textContent = <?php echo json_encode($error); ?>;
                        var toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    });
                </script>
            <?php endif; ?>
            <?php if (isset($success) || isset($_GET['success'])) : ?>
                <script>
                    window.addEventListener('DOMContentLoaded', function() {
                        var toastEl = document.getElementById('mainToast');
                        toastEl.querySelector('.toast-header i').className = 'fas fa-check-circle text-success me-2';
                        toastEl.querySelector('.me-auto').textContent = 'Succès';
                        toastEl.querySelector('.toast-body').textContent = <?php echo json_encode(isset($success) ? $success : $_GET['success']); ?>;
                        var toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    });
                </script>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter une Matière</h5>
                    <form action="manage_subjects.php" method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="id_faculty" class="form-label">Faculté</label>
                                <select class="form-control" id="id_faculty" name="id_faculty" onchange="loadDepartments()" required>
                                    <option value="">Sélectionner une faculté</option>
                                    <?php
                                    $query = "SELECT id_faculty, faculty_name FROM faculties ORDER BY faculty_name";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id_faculty'] . "'>" . htmlspecialchars($row['faculty_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_department" class="form-label">Département</label>
                                <select class="form-control" id="id_department" name="id_department" onchange="loadFilieres()" required>
                                    <option value="">Sélectionner un département</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_filiere" class="form-label">Filière</label>
                                <select class="form-control" id="id_filiere" name="id_filiere" onchange="loadModules()" required>
                                    <option value="">Sélectionner une filière</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_module" class="form-label">Module</label>
                                <select class="form-control" id="id_module" name="id_module" required>
                                    <option value="">Sélectionner un module</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="subject_name" class="form-label">Nom de la Matière</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="coefficient" class="form-label">Coefficient</label>
                                <input type="number" class="form-control" id="coefficient" name="coefficient" min="1" max="5" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_teacher" class="form-label">Enseignant</label>
                                <select class="form-control" id="id_teacher" name="id_teacher" required>
                                    <option value="">Sélectionner un enseignant</option>
                                    <?php
                                    $query = "SELECT t.id_teacher, CONCAT(t.nom, ' ', t.prenom) as teacher_name 
                                              FROM teachers t 
                                              JOIN users u ON t.id_user = u.id_user 
                                              ORDER BY t.nom, t.prenom";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id_teacher'] . "'>" . htmlspecialchars($row['teacher_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_subject" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Matières</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Faculté</th>
                                <th>Département</th>
                                <th>Filière</th>
                                <th>Module</th>
                                <th>Nom de la Matière</th>
                                <th>Coefficient</th>
                                <th>Enseignant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT s.id_subject, s.subject_name, s.coefficient, s.id_teacher, m.module_name, m.id_module, f.filiere_name, f.id_filiere, d.department_name, d.id_department, fac.faculty_name, fac.id_faculty, CONCAT(t.nom, ' ', t.prenom) as teacher_name
                                      FROM subjects s 
                                      JOIN modules m ON s.id_module = m.id_module 
                                      JOIN filieres f ON m.id_filiere = f.id_filiere 
                                      JOIN departments d ON f.id_department = d.id_department 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      JOIN teachers t ON s.id_teacher = t.id_teacher
                                      ORDER BY fac.faculty_name, d.department_name, f.filiere_name, m.module_name, s.subject_name";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                        <td>" . htmlspecialchars($row['department_name']) . "</td>
                                        <td>" . htmlspecialchars($row['filiere_name']) . "</td>
                                        <td>" . htmlspecialchars($row['module_name']) . "</td>
                                        <td>" . htmlspecialchars($row['subject_name']) . "</td>
                                        <td>" . htmlspecialchars($row['coefficient']) . "</td>
                                        <td>" . htmlspecialchars($row['teacher_name']) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-warning btn-edit-subject' data-subject='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_subjects.php?delete=" . $row['id_subject'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>Erreur : " . mysqli_error($conn) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        function loadDepartments(suffix = '') {
            const facultyId = document.getElementById('id_faculty' + suffix).value;
            const departmentSelect = document.getElementById('id_department' + suffix);
            
            // Réinitialiser les sélecteurs dépendants
            departmentSelect.innerHTML = '<option value="">Sélectionner un département</option>';
            document.getElementById('id_filiere' + suffix).innerHTML = '<option value="">Sélectionner une filière</option>';
            document.getElementById('id_module' + suffix).innerHTML = '<option value="">Sélectionner un module</option>';
            
            if (facultyId) {
                fetch('get_departments.php?id_faculty=' + facultyId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(dept => {
                                const option = document.createElement('option');
                                option.value = dept.id_department;
                                option.textContent = dept.department_name;
                                departmentSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        function loadFilieres(suffix = '') {
            const departmentId = document.getElementById('id_department' + suffix).value;
            const filiereSelect = document.getElementById('id_filiere' + suffix);
            
            // Réinitialiser les sélecteurs dépendants
            filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
            document.getElementById('id_module' + suffix).innerHTML = '<option value="">Sélectionner un module</option>';
            
            if (departmentId) {
                fetch('get_filieres.php?id_department=' + departmentId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(filiere => {
                                const option = document.createElement('option');
                                option.value = filiere.id_filiere;
                                option.textContent = filiere.filiere_name;
                                filiereSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        function loadModules(suffix = '') {
            const filiereId = document.getElementById('id_filiere' + suffix).value;
            const moduleSelect = document.getElementById('id_module' + suffix);
            
            // Réinitialiser le sélecteur
            moduleSelect.innerHTML = '<option value="">Sélectionner un module</option>';
            
            if (filiereId) {
                fetch('get_modules.php?id_filiere=' + filiereId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(module => {
                                const option = document.createElement('option');
                                option.value = module.id_module;
                                option.textContent = module.module_name;
                                moduleSelect.appendChild(option);
                            });
                        } else {
                            // Ajouter un message si aucun module n'est trouvé
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "Aucun module disponible";
                            moduleSelect.appendChild(option);
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Initialiser les sélecteurs pour les modals d'édition
        document.addEventListener('DOMContentLoaded', function() {
            // Pour chaque modal d'édition, initialiser les sélecteurs
            const editModals = document.querySelectorAll('[id^="editSubjectModal"]');
            editModals.forEach(modal => {
                const modalId = modal.id.replace('editSubjectModal', '');
                if (modalId) {
                    // Charger les départements, filières et modules pour ce modal
                    loadDepartments('_' + modalId);
                    setTimeout(() => {
                        loadFilieres('_' + modalId);
                        setTimeout(() => {
                            loadModules('_' + modalId);
                        }, 300);
                    }, 300);
                }
            });
        });
    </script>
    <!-- Modal d'édition unique pour matière -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="editSubjectForm" method="POST" action="manage_subjects.php">
            <div class="modal-header">
              <h5 class="modal-title" id="editSubjectModalLabel">Modifier la Matière</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_subject" id="edit_id_subject">
              <div class="mb-3">
                <label for="edit_id_faculty" class="form-label">Faculté</label>
                <select class="form-control" id="edit_id_faculty" name="id_faculty" required></select>
              </div>
              <div class="mb-3">
                <label for="edit_id_department" class="form-label">Département</label>
                <select class="form-control" id="edit_id_department" name="id_department" required></select>
              </div>
              <div class="mb-3">
                <label for="edit_id_filiere" class="form-label">Filière</label>
                <select class="form-control" id="edit_id_filiere" name="id_filiere" required></select>
              </div>
              <div class="mb-3">
                <label for="edit_id_module" class="form-label">Module</label>
                <select class="form-control" id="edit_id_module" name="id_module" required></select>
              </div>
              <div class="mb-3">
                <label for="edit_subject_name" class="form-label">Nom de la Matière</label>
                <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
              </div>
              <div class="mb-3">
                <label for="edit_coefficient" class="form-label">Coefficient</label>
                <input type="number" class="form-control" id="edit_coefficient" name="coefficient" min="1" max="5" required>
              </div>
              <div class="mb-3">
                <label for="edit_id_teacher" class="form-label">Enseignant</label>
                <select class="form-control" id="edit_id_teacher" name="id_teacher" required></select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" name="update_subject" class="btn btn-primary">Mettre à jour</button>
            </div>
          </form>
        </div>
      </div>
    </div>
        <script>
    window.addEventListener('DOMContentLoaded', function() {
      // Charger toutes les facultés dans le modal d'édition
      function loadFaculties(selectedId) {
        fetch('get_faculties.php')
          .then(response => response.json())
          .then(data => {
            const select = document.getElementById('edit_id_faculty');
            select.innerHTML = '<option value="">Sélectionner une faculté</option>';
            data.forEach(fac => {
              const option = document.createElement('option');
              option.value = fac.id_faculty;
              option.textContent = fac.faculty_name;
              if (fac.id_faculty == selectedId) option.selected = true;
              select.appendChild(option);
            });
          });
      }
      // Charger les départements selon la faculté
      function loadDepartments(selectedFaculty, selectedDept) {
        fetch('get_departments.php?id_faculty=' + selectedFaculty)
          .then(response => response.json())
          .then(data => {
            const select = document.getElementById('edit_id_department');
            select.innerHTML = '<option value="">Sélectionner un département</option>';
            data.forEach(dept => {
              const option = document.createElement('option');
              option.value = dept.id_department;
              option.textContent = dept.department_name;
              if (dept.id_department == selectedDept) option.selected = true;
              select.appendChild(option);
            });
          });
      }
      // Charger les filières selon le département
      function loadFilieres(selectedDept, selectedFiliere) {
        fetch('get_filieres.php?id_department=' + selectedDept)
          .then(response => response.json())
          .then(data => {
            const select = document.getElementById('edit_id_filiere');
            select.innerHTML = '<option value="">Sélectionner une filière</option>';
            data.forEach(filiere => {
              const option = document.createElement('option');
              option.value = filiere.id_filiere;
              option.textContent = filiere.filiere_name;
              if (filiere.id_filiere == selectedFiliere) option.selected = true;
              select.appendChild(option);
            });
          });
      }
      // Charger les modules selon la filière
      function loadModules(selectedFiliere, selectedModule) {
        fetch('get_modules.php?id_filiere=' + selectedFiliere)
          .then(response => response.json())
          .then(data => {
            const select = document.getElementById('edit_id_module');
            select.innerHTML = '<option value="">Sélectionner un module</option>';
            data.forEach(module => {
              const option = document.createElement('option');
              option.value = module.id_module;
              option.textContent = module.module_name;
              if (module.id_module == selectedModule) option.selected = true;
              select.appendChild(option);
            });
          });
      }
      // Charger les enseignants
      function loadTeachers(selectedTeacher) {
        fetch('get_teachers.php')
          .then(response => response.json())
          .then(data => {
            const select = document.getElementById('edit_id_teacher');
            select.innerHTML = '<option value="">Sélectionner un enseignant</option>';
            data.forEach(teacher => {
              const option = document.createElement('option');
              option.value = teacher.id_teacher;
              option.textContent = teacher.teacher_name;
              if (teacher.id_teacher == selectedTeacher) option.selected = true;
              select.appendChild(option);
            });
          });
      }
      document.querySelectorAll('.btn-edit-subject').forEach(btn => {
        btn.addEventListener('click', function() {
          const subject = JSON.parse(this.getAttribute('data-subject'));
          document.getElementById('edit_id_subject').value = subject.id_subject;
          document.getElementById('edit_subject_name').value = subject.subject_name;
          document.getElementById('edit_coefficient').value = subject.coefficient;
          // Faculté
          loadFaculties(subject.id_faculty);
          // Départements
          setTimeout(() => loadDepartments(subject.id_faculty, subject.id_department), 200);
          // Filières
          setTimeout(() => loadFilieres(subject.id_department, subject.id_filiere), 400);
          // Modules
          setTimeout(() => loadModules(subject.id_filiere, subject.id_module), 600);
          // Enseignants
          loadTeachers(subject.id_teacher);
          var modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
          modal.show();
        });
      });
    });
        </script>
</body>
</html>
