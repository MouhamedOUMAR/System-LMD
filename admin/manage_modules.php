<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_module'])) {
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $id_semester = mysqli_real_escape_string($conn, $_POST['id_semester']);
    $module_name = mysqli_real_escape_string($conn, $_POST['module_name']);
    $credits = mysqli_real_escape_string($conn, $_POST['credits']);

    $query = "INSERT INTO modules (id_filiere, id_semester, module_name, credits) VALUES ('$id_filiere', '$id_semester', '$module_name', '$credits')";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_modules.php?success=Module ajouté avec succès");
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

// Mise à jour d'un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_module'])) {
    $id_module = mysqli_real_escape_string($conn, $_POST['id_module']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $id_semester = mysqli_real_escape_string($conn, $_POST['id_semester']);
    $module_name = mysqli_real_escape_string($conn, $_POST['module_name']);
    $credits = mysqli_real_escape_string($conn, $_POST['credits']);

    $query = "UPDATE modules SET id_filiere = '$id_filiere', id_semester = '$id_semester', module_name = '$module_name', credits = '$credits' WHERE id_module = '$id_module'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_modules.php?success=Module mis à jour avec succès");
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

// Suppression d'un module
if (isset($_GET['delete'])) {
    $id_module = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM modules WHERE id_module = '$id_module'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_modules.php?success=Module supprimé avec succès");
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Modules - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3">
            <h3 class="text-center mb-4"><?php echo SYSTEM_NAME; ?></h3>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fas fa-home"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_faculties.php"><i class="fas fa-university"></i> Facultés</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_departements.php"><i class="fas fa-building"></i> Départements</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_schedules.php"><i class="fas fa-clock"></i> Emplois du Temps</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_soutenances.php"><i class="fas fa-gavel"></i> Soutenances</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_semesters.php"><i class="fas fa-calendar"></i> Semestres</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_academic_years.php"><i class="fas fa-calendar-alt"></i> Années Académiques</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Modules</h1>
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
            <?php if (isset($_GET['success'])) : ?>
                <script>
                    window.addEventListener('DOMContentLoaded', function() {
                        var toastEl = document.getElementById('mainToast');
                        toastEl.querySelector('.toast-header i').className = 'fas fa-check-circle text-success me-2';
                        toastEl.querySelector('.me-auto').textContent = 'Succès';
                        toastEl.querySelector('.toast-body').textContent = <?php echo json_encode($_GET['success']); ?>;
                        var toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    });
                </script>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter un Module</h5>
                    <form action="manage_modules.php" method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="id_faculty" class="form-label">Faculté</label>
                                <select class="form-control" id="id_faculty" name="id_faculty" onchange="loadDepartments()" required>
                                    <option value="">Sélectionner une faculté</option>
                                    <?php
                                    $query = "SELECT id_faculty, faculty_name FROM faculties ORDER BY faculty_name";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_faculty']}'>" . htmlspecialchars($row['faculty_name']) . "</option>";
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
                                <select class="form-control" id="id_filiere" name="id_filiere" onchange="loadSemesters()" required>
                                    <option value="">Sélectionner une filière</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_semester" class="form-label">Semestre</label>
                                <select class="form-control" id="id_semester" name="id_semester" required>
                                    <option value="">Sélectionner un semestre</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="module_name" class="form-label">Nom du Module</label>
                                <input type="text" class="form-control" id="module_name" name="module_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="credits" class="form-label">Crédits</label>
                                <input type="number" class="form-control" id="credits" name="credits" min="1" max="12" value="6" required>
                            </div>
                        </div>
                        <button type="submit" name="add_module" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Modules</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Faculté</th>
                                <th>Département</th>
                                <th>Filière</th>
                                <th>Semestre</th>
                                <th>Nom du Module</th>
                                <th>Crédits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT m.id_module, m.module_name, m.credits, m.id_filiere, m.id_semester, f.filiere_name, s.semester_name, d.id_department, d.department_name, fac.id_faculty, fac.faculty_name 
                                      FROM modules m 
                                      JOIN filieres f ON m.id_filiere = f.id_filiere 
                                      JOIN semesters s ON m.id_semester = s.id_semester 
                                      JOIN departments d ON f.id_department = d.id_department 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      ORDER BY fac.faculty_name, d.department_name, f.filiere_name, s.semester_name, m.module_name";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                        <td>" . htmlspecialchars($row['department_name']) . "</td>
                                        <td>" . htmlspecialchars($row['filiere_name']) . "</td>
                                        <td>" . htmlspecialchars($row['semester_name']) . "</td>
                                        <td>" . htmlspecialchars($row['module_name']) . "</td>
                                        <td>" . htmlspecialchars($row['credits']) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-warning btn-edit-module' data-module='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_modules.php?delete=" . $row['id_module'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>Erreur : " . mysqli_error($conn) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong class="me-auto">Succès</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
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
            document.getElementById('id_semester' + suffix).innerHTML = '<option value="">Sélectionner un semestre</option>';
            
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
            document.getElementById('id_semester' + suffix).innerHTML = '<option value="">Sélectionner un semestre</option>';
            
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

        function loadSemesters(suffix = '') {
            const filiereId = document.getElementById('id_filiere' + suffix).value;
            const semesterSelect = document.getElementById('id_semester' + suffix);
            
            // Réinitialiser le sélecteur
            semesterSelect.innerHTML = '<option value="">Sélectionner un semestre</option>';
            
            if (filiereId) {
                fetch('get_semesters.php?id_filiere=' + filiereId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(semester => {
                                const option = document.createElement('option');
                                option.value = semester.id_semester;
                                option.textContent = semester.semester_name;
                                semesterSelect.appendChild(option);
                            });
                        } else {
                            // Ajouter un message si aucun semestre n'est trouvé
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "Aucun semestre disponible";
                            semesterSelect.appendChild(option);
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Initialiser les sélecteurs pour les modals d'édition
        document.addEventListener('DOMContentLoaded', function() {
            // Pour chaque modal d'édition, initialiser les sélecteurs
            const editModals = document.querySelectorAll('[id^="editModuleModal"]');
            editModals.forEach(modal => {
                const modalId = modal.id.replace('editModuleModal', '');
                if (modalId) {
                    // Charger les départements, filières et semestres pour ce modal
                    loadDepartments('_' + modalId);
                    setTimeout(() => {
                        loadFilieres('_' + modalId);
                        setTimeout(() => {
                            loadSemesters('_' + modalId);
                        }, 300);
                    }, 300);
                }
            });
        });
    </script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_GET['success']); ?>");
        </script>
    <?php endif; ?>

    <!-- Modal d'édition unique pour module -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" aria-labelledby="editModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editModuleForm" method="POST" action="manage_modules.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModuleModalLabel">Modifier le Module</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_module" id="edit_id_module">
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
                            <label for="edit_id_semester" class="form-label">Semestre</label>
                            <select class="form-control" id="edit_id_semester" name="id_semester" required></select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_module_name" class="form-label">Nom du Module</label>
                            <input type="text" class="form-control" id="edit_module_name" name="module_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_credits" class="form-label">Crédits</label>
                            <input type="number" class="form-control" id="edit_credits" name="credits" min="1" max="12" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_module" class="btn btn-primary">Mettre à jour</button>
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
        // Charger les semestres selon la filière
        function loadSemesters(selectedFiliere, selectedSemester) {
            fetch('get_semesters.php?id_filiere=' + selectedFiliere)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('edit_id_semester');
                    select.innerHTML = '<option value="">Sélectionner un semestre</option>';
                    data.forEach(sem => {
                        const option = document.createElement('option');
                        option.value = sem.id_semester;
                        option.textContent = sem.semester_name;
                        if (sem.id_semester == selectedSemester) option.selected = true;
                        select.appendChild(option);
                    });
                });
        }
        document.querySelectorAll('.btn-edit-module').forEach(btn => {
            btn.addEventListener('click', function() {
                const module = JSON.parse(this.getAttribute('data-module'));
                document.getElementById('edit_id_module').value = module.id_module;
                document.getElementById('edit_module_name').value = module.module_name;
                document.getElementById('edit_credits').value = module.credits;
                // Faculté
                loadFaculties(module.id_faculty);
                // Départements
                setTimeout(() => loadDepartments(module.id_faculty, module.id_department), 200);
                // Filières
                setTimeout(() => loadFilieres(module.id_department, module.id_filiere), 400);
                // Semestres
                setTimeout(() => loadSemesters(module.id_filiere, module.id_semester), 600);
                var modal = new bootstrap.Modal(document.getElementById('editModuleModal'));
                modal.show();
            });
        });
    });
    </script>
</body>
</html>
