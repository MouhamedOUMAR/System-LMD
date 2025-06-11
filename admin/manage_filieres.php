<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'une filière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_filiere'])) {
    $id_department = mysqli_real_escape_string($conn, $_POST['id_department']);
    $id_level = mysqli_real_escape_string($conn, $_POST['id_level']);
    $filiere_name = mysqli_real_escape_string($conn, $_POST['filiere_name']);

    // Vérifier si la filière existe déjà dans ce département et niveau
    $check_query = "SELECT id_filiere FROM filieres WHERE id_department = '$id_department' AND id_level = '$id_level' AND filiere_name = '$filiere_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Cette filière existe déjà dans ce département et niveau.";
    } else {
        $query = "INSERT INTO filieres (id_department, id_level, filiere_name) VALUES ('$id_department', '$id_level', '$filiere_name')";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_filieres.php?success=Filière ajoutée avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'une filière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_filiere'])) {
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $id_department = mysqli_real_escape_string($conn, $_POST['id_department']);
    $id_level = mysqli_real_escape_string($conn, $_POST['id_level']);
    $filiere_name = mysqli_real_escape_string($conn, $_POST['filiere_name']);

    // Vérifier si la filière existe déjà dans ce département et niveau (sauf la filière actuelle)
    $check_query = "SELECT id_filiere FROM filieres WHERE id_department = '$id_department' AND id_level = '$id_level' AND filiere_name = '$filiere_name' AND id_filiere != '$id_filiere'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Cette filière existe déjà dans ce département et niveau.";
    } else {
        $query = "UPDATE filieres SET id_department = '$id_department', id_level = '$id_level', filiere_name = '$filiere_name' WHERE id_filiere = '$id_filiere'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_filieres.php?success=Filière mise à jour avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'une filière
if (isset($_GET['delete'])) {
    $id_filiere = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Vérifier si la filière est utilisée dans d'autres tables
    $check_query = "SELECT COUNT(*) as count FROM students WHERE id_filiere = '$id_filiere'";
    $check_result = mysqli_query($conn, $check_query);
    $row = mysqli_fetch_assoc($check_result);
    
    if ($row['count'] > 0) {
        $error = "Impossible de supprimer cette filière car elle est utilisée par des étudiants.";
    } else {
        $query = "DELETE FROM filieres WHERE id_filiere = '$id_filiere'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_filieres.php?success=Filière supprimée avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Filières - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white active" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
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
            <h1 class="mb-4 text-primary">Gestion des Filières</h1>
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
                    <h5 class="card-title">Ajouter une Filière</h5>
                    <form action="manage_filieres.php" method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
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
                            <div class="col-md-4 mb-3">
                                <label for="id_department" class="form-label">Département</label>
                                <select class="form-control" id="id_department" name="id_department" onchange="loadFilieres()" required>
                                    <option value="">Sélectionner un département</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_level" class="form-label">Niveau</label>
                                <select class="form-control" id="id_level" name="id_level" required>
                                    <option value="">Sélectionner un niveau</option>
                                    <?php
                                    $query = "SELECT id_level, level_name FROM levels ORDER BY level_name";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_level']}'>" . htmlspecialchars($row['level_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="filiere_name" class="form-label">Nom de la Filière</label>
                                <input type="text" class="form-control" id="filiere_name" name="filiere_name" required>
                            </div>
                        </div>
                        <button type="submit" name="add_filiere" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Filières</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Faculté</th>
                                <th>Département</th>
                                <th>Niveau</th>
                                <th>Nom de la Filière</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT f.id_filiere, f.filiere_name, d.department_name, fac.faculty_name, l.level_name, d.id_department, fac.id_faculty, f.id_level 
                                      FROM filieres f 
                                      JOIN departments d ON f.id_department = d.id_department 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      JOIN levels l ON f.id_level = l.id_level 
                                      ORDER BY fac.faculty_name, d.department_name, l.level_name, f.filiere_name";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                        <td>" . htmlspecialchars($row['department_name']) . "</td>
                                        <td>" . htmlspecialchars($row['level_name']) . "</td>
                                        <td>" . htmlspecialchars($row['filiere_name']) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-warning btn-edit-filiere' data-filiere='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_filieres.php?delete={$row['id_filiere']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>Erreur : " . mysqli_error($conn) . "</td></tr>";
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
        function loadDepartments(filiereId = '') {
            const facultyId = document.getElementById(`id_faculty${filiereId ? '_' + filiereId : ''}`).value;
            const departmentSelect = document.getElementById(`id_department${filiereId ? '_' + filiereId : ''}`);
            departmentSelect.innerHTML = '<option value="">Sélectionner un département</option>';
            
            if (facultyId) {
                fetch(`get_departments.php?id_faculty=${facultyId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id_department;
                            option.textContent = dept.department_name;
                            departmentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        function loadFilieres(filiereId = '') {
            const departmentId = document.getElementById(`id_department${filiereId ? '_' + filiereId : ''}`).value;
            const filiereSelect = document.getElementById(`id_filiere${filiereId ? '_' + filiereId : ''}`);
            
            if (filiereSelect) {
                filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
                
                if (departmentId) {
                    fetch(`get_filieres.php?id_department=${departmentId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (Array.isArray(data)) {
                                data.forEach(fil => {
                                    const option = document.createElement('option');
                                    option.value = fil.id_filiere;
                                    option.textContent = fil.filiere_name;
                                    filiereSelect.appendChild(option);
                                });
                            } else {
                                console.error('Données invalides:', data);
                            }
                        })
                        .catch(error => console.error('Erreur lors du chargement des filières:', error));
                }
            }
        }

        // Charger les départements au chargement initial
        document.addEventListener('DOMContentLoaded', () => {
            loadDepartments();
        });
    </script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_GET['success']); ?>");
        </script>
    <?php endif; ?>

    <!-- Modal d'édition unique pour filière -->
    <div class="modal fade" id="editFiliereModal" tabindex="-1" aria-labelledby="editFiliereModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editFiliereForm" method="POST" action="manage_filieres.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editFiliereModalLabel">Modifier la Filière</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_filiere" id="edit_id_filiere">
                        <div class="mb-3">
                            <label for="edit_filiere_name" class="form-label">Nom de la Filière</label>
                            <input type="text" class="form-control" name="filiere_name" id="edit_filiere_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_department" class="form-label">Département</label>
                            <select class="form-control" name="id_department" id="edit_id_department" required>
                                <!-- Options remplies dynamiquement -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_level" class="form-label">Niveau</label>
                            <select class="form-control" name="id_level" id="edit_id_level" required>
                                <!-- Options remplies dynamiquement -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_filiere" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-edit-filiere').forEach(btn => {
            btn.addEventListener('click', function() {
                const filiere = JSON.parse(this.getAttribute('data-filiere'));
                document.getElementById('edit_id_filiere').value = filiere.id_filiere;
                document.getElementById('edit_filiere_name').value = filiere.filiere_name;
                // Charger dynamiquement les départements
                fetch('get_departments.php?id_faculty=' + filiere.id_faculty)
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('edit_id_department');
                        select.innerHTML = '';
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id_department;
                            option.textContent = dept.department_name;
                            if (dept.id_department == filiere.id_department) option.selected = true;
                            select.appendChild(option);
                        });
                    });
                // Charger dynamiquement les niveaux
                fetch('get_levels.php')
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('edit_id_level');
                        select.innerHTML = '';
                        data.forEach(level => {
                            const option = document.createElement('option');
                            option.value = level.id_level;
                            option.textContent = level.level_name;
                            if (level.id_level == filiere.id_level) option.selected = true;
                            select.appendChild(option);
                        });
                    });
                var modal = new bootstrap.Modal(document.getElementById('editFiliereModal'));
                modal.show();
            });
        });
    });
    </script>
</body>
</html>
