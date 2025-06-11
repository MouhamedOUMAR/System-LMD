<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'un étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash(mysqli_real_escape_string($conn, $_POST['password']), PASSWORD_DEFAULT);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $matricule = mysqli_real_escape_string($conn, $_POST['matricule']);
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $date_naissance = mysqli_real_escape_string($conn, $_POST['date_naissance']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sub_level = mysqli_real_escape_string($conn, $_POST['sub_level']);

    // Vérifier si le matricule existe déjà
    $check_query = "SELECT id_student FROM students WHERE matricule = '$matricule'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Erreur : Ce matricule existe déjà. Veuillez en choisir un autre.";
    } else {
        // Insérer l'utilisateur
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'student')";
        if (mysqli_query($conn, $query)) {
            $id_user = mysqli_insert_id($conn);
            $query = "INSERT INTO students (id_user, id_filiere, matricule, nom, prenom, date_naissance, email, sub_level) 
                      VALUES ('$id_user', '$id_filiere', '$matricule', '$nom', '$prenom', '$date_naissance', '$email', '$sub_level')";
            if (mysqli_query($conn, $query)) {
                header("Location: manage_students.php?success=Étudiant ajouté avec succès");
            } else {
                $error = "Erreur : " . mysqli_error($conn);
                // Supprimer l'utilisateur si l'insertion de l'étudiant échoue
                mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_user'");
            }
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'un étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $id_student = mysqli_real_escape_string($conn, $_POST['id_student']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $matricule = mysqli_real_escape_string($conn, $_POST['matricule']);
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $date_naissance = mysqli_real_escape_string($conn, $_POST['date_naissance']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sub_level = mysqli_real_escape_string($conn, $_POST['sub_level']);

    // Vérifier si le matricule existe déjà pour un autre étudiant
    $check_query = "SELECT id_student FROM students WHERE matricule = '$matricule' AND id_student != '$id_student'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Erreur : Ce matricule est déjà utilisé par un autre étudiant.";
    } else {
        $query = "UPDATE students SET id_filiere = '$id_filiere', matricule = '$matricule', nom = '$nom', prenom = '$prenom', 
                  date_naissance = '$date_naissance', email = '$email', sub_level = '$sub_level' WHERE id_student = '$id_student'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_students.php?success=Étudiant mis à jour avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'un étudiant
if (isset($_GET['delete'])) {
    $id_student = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Commencer une transaction pour s'assurer que toutes les opérations sont effectuées ou aucune
    mysqli_begin_transaction($conn);
    
    try {
        // Récupérer l'id_user associé à cet étudiant
        $query = "SELECT id_user FROM students WHERE id_student = '$id_student'";
        $result = mysqli_query($conn, $query);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $id_user = $row['id_user'];
            
            // Supprimer d'abord les enregistrements qui pourraient référencer cet étudiant
            // Par exemple, supprimer les notes de l'étudiant s'il en a
            $query = "DELETE FROM notes WHERE id_student = '$id_student'";
            mysqli_query($conn, $query);
            
            // Supprimer les résultats de l'étudiant s'il en a
            $query = "DELETE FROM results WHERE id_student = '$id_student'";
            mysqli_query($conn, $query);
            
            // Supprimer l'étudiant
            $query = "DELETE FROM students WHERE id_student = '$id_student'";
            if (!mysqli_query($conn, $query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Supprimer l'utilisateur associé
            $query = "DELETE FROM users WHERE id_user = '$id_user'";
            if (!mysqli_query($conn, $query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            mysqli_commit($conn);
            header("Location: manage_students.php?success=Étudiant supprimé avec succès");
            exit;
        } else {
            throw new Exception("Étudiant non trouvé");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_schedules.php"><i class="fas fa-clock"></i> Emplois du Temps</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_soutenances.php"><i class="fas fa-gavel"></i> Soutenances</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_academic_years.php"><i class="fas fa-calendar-alt"></i> Années Académiques</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Étudiants</h1>
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
                    <h5 class="card-title">Ajouter un Étudiant</h5>
                    <form action="manage_students.php" method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_faculty" class="form-label">Faculté</label>
                                <select class="form-control" id="id_faculty" name="id_faculty" onchange="loadDepartments()" required>
                                    <option value="">Sélectionner une faculté</option>
                                    <?php
                                    $query = "SELECT id_faculty, faculty_name FROM faculties";
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
                                <select class="form-control" id="id_level" name="id_level" onchange="loadFilieres(); updateSubLevelOptions();" required>
                                    <option value="">Sélectionner un niveau</option>
                                    <?php
                                    $query = "SELECT id_level, level_name FROM levels";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_level']}'>" . htmlspecialchars($row['level_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_filiere" class="form-label">Filière</label>
                                <select class="form-control" id="id_filiere" name="id_filiere" required>
                                    <option value="">Sélectionner une filière</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sub_level" class="form-label">Sous-niveau</label>
                                <select class="form-control" id="sub_level" name="sub_level" required>
                                    <option value="">Sélectionner un sous-niveau</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="matricule" class="form-label">Matricule</label>
                                <input type="text" class="form-control" id="matricule" name="matricule" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="date_naissance" class="form-label">Date de Naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Étudiants</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Sous-niveau</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT s.*, f.filiere_name, d.department_name, fac.faculty_name, l.level_name, f.id_level, f.id_department 
                                      FROM students s 
                                      LEFT JOIN filieres f ON s.id_filiere = f.id_filiere 
                                      LEFT JOIN departments d ON f.id_department = d.id_department 
                                      LEFT JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      LEFT JOIN levels l ON f.id_level = l.id_level 
                                      ORDER BY s.nom, s.prenom";
                            $result = mysqli_query($conn, $query);

                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                        <td>" . htmlspecialchars($row['matricule']) . "</td>
                                        <td>" . htmlspecialchars($row['nom']) . " " . htmlspecialchars($row['prenom']) . "</td>
                                        <td>" . htmlspecialchars($row['email']) . "</td>
                                        <td>" . htmlspecialchars($row['faculty_name'] ?? 'Non défini') . "</td>
                                        <td>" . htmlspecialchars($row['department_name'] ?? 'Non défini') . "</td>
                                        <td>" . htmlspecialchars($row['filiere_name'] ?? 'Non défini') . "</td>
                                        <td>" . htmlspecialchars($row['level_name'] ?? 'Non défini') . "</td>";
                                // Affichage du statut en PHP natif pour éviter les erreurs de syntaxe
                                echo "<td>";
                                if (isset($row['status'])) {
                                    $badgeClass = ($row['status'] === 'Validé') ? 'bg-success' : (($row['status'] === 'Rattrapage') ? 'bg-danger' : 'bg-secondary');
                                    echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row['status']) . '</span>';
                                }
                                echo "</td>";
                                echo "<td>
                                        <button class='btn btn-sm btn-primary btn-edit-student' data-student='" . json_encode($row) . "'>
                                                <i class='fas fa-edit'></i> Modifier
                                            </button>
                                            <a href='manage_students.php?delete=" . $row['id_student'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer cet étudiant ?\")'>
                                                <i class='fas fa-trash'></i> Supprimer
                                            </a>
                                        </td>
                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'édition unique -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editStudentForm" method="POST" action="manage_students.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStudentModalLabel">Modifier l'étudiant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_student" id="edit_id_student">
                        <div class="mb-3">
                            <label for="edit_matricule" class="form-label">Matricule</label>
                            <input type="text" class="form-control" name="matricule" id="edit_matricule" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" id="edit_nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" id="edit_prenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_date_naissance" class="form-label">Date de Naissance</label>
                            <input type="date" class="form-control" name="date_naissance" id="edit_date_naissance">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        <div class="mb-3">
                            <label for="edit_sub_level" class="form-label">Sous-niveau</label>
                            <input type="text" class="form-control" name="sub_level" id="edit_sub_level" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_filiere" class="form-label">Filière</label>
                            <select class="form-control" name="id_filiere" id="edit_id_filiere" required>
                                <!-- Options remplies dynamiquement -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_student" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        function loadDepartments(studentId = '') {
            const facultyId = document.getElementById(`id_faculty${studentId ? '_' + studentId : ''}`).value;
            const departmentSelect = document.getElementById(`id_department${studentId ? '_' + studentId : ''}`);
            departmentSelect.innerHTML = '<option value="">Sélectionner un département</option>';
            if (facultyId) {
                fetch(`get_departments.php?id_faculty=${facultyId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id_department;
                            option.textContent = dept.department_name;
                            departmentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erreur lors du chargement des départements:', error));
            }
        }

        function loadFilieres(studentId = '') {
            const departmentId = document.getElementById(`id_department${studentId ? '_' + studentId : ''}`).value;
            const levelId = document.getElementById(`id_level${studentId ? '_' + studentId : ''}`).value;
            const filiereSelect = document.getElementById(`id_filiere${studentId ? '_' + studentId : ''}`);
            
            filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
            
            if (departmentId && levelId) {
                fetch(`get_filieres.php?id_department=${departmentId}&id_level=${levelId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (Array.isArray(data)) {
                            data.forEach(filiere => {
                                const option = document.createElement('option');
                                option.value = filiere.id_filiere;
                                option.textContent = filiere.filiere_name;
                                filiereSelect.appendChild(option);
                            });
                        } else {
                            console.error('Données invalides:', data);
                        }
                    })
                    .catch(error => console.error('Erreur lors du chargement des filières:', error));
            }
        }

        function updateSubLevelOptions(studentId = '') {
            const levelId = document.getElementById(`id_level${studentId ? '_' + studentId : ''}`).value;
            const subLevelSelect = document.getElementById(`sub_level${studentId ? '_' + studentId : ''}`);
            subLevelSelect.innerHTML = '<option value="">Sélectionner un sous-niveau</option>';

            let options = [];
            if (levelId == 1) { // Licence
                options = ['L1', 'L2', 'L3'];
            } else if (levelId == 2) { // Master
                options = ['M1', 'M2'];
            } else if (levelId == 3) { // Doctorat
                options = ['D1', 'D2', 'D3'];
            }

            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.textContent = opt;
                subLevelSelect.appendChild(option);
            });
        }

        // Remplir dynamiquement le modal d'édition
        function openEditStudentModal(student) {
            document.getElementById('edit_id_student').value = student.id_student;
            document.getElementById('edit_matricule').value = student.matricule;
            document.getElementById('edit_nom').value = student.nom;
            document.getElementById('edit_prenom').value = student.prenom;
            document.getElementById('edit_date_naissance').value = student.date_naissance;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_sub_level').value = student.sub_level;
            // Charger dynamiquement les filières selon le département et le niveau de l'étudiant
            fetch('get_filieres.php?id_department=' + student.id_department + '&id_level=' + student.id_level)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('edit_id_filiere');
                    select.innerHTML = '';
                    data.forEach(filiere => {
                        const option = document.createElement('option');
                        option.value = filiere.id_filiere;
                        option.textContent = filiere.filiere_name;
                        if (filiere.id_filiere == student.id_filiere) option.selected = true;
                        select.appendChild(option);
                    });
                });
            var modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
            modal.show();
        }

        // Ajouter un event sur chaque bouton Modifier
        window.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-edit-student').forEach(btn => {
                btn.addEventListener('click', function() {
                    const student = JSON.parse(this.getAttribute('data-student'));
                    openEditStudentModal(student);
                });
            });
        });
    </script>
</body>
</html>
