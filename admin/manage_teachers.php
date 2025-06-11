<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Vérifier si le nom d'utilisateur existe déjà
    $query = "SELECT id_user FROM users WHERE username = '$username'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Ce nom d'utilisateur existe déjà.";
    } else {
        // Insérer dans users
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'teacher')";
        if (mysqli_query($conn, $query)) {
            $id_user = mysqli_insert_id($conn);
            $query = "INSERT INTO teachers (id_user, nom, prenom, email) VALUES ('$id_user', '$nom', '$prenom', '$email')";
            if (mysqli_query($conn, $query)) {
                header("Location: manage_teachers.php?success=Enseignant ajouté avec succès");
            } else {
                $error = "Erreur : " . mysqli_error($conn);
                // Supprimer l'utilisateur si l'insertion dans teachers échoue
                mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_user'");
            }
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
    $id_teacher = mysqli_real_escape_string($conn, $_POST['id_teacher']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $password = !empty($_POST['password']) ? mysqli_real_escape_string($conn, $_POST['password']) : null;
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Vérifier si le nom d'utilisateur existe déjà pour un autre utilisateur
    $query = "SELECT u.id_user FROM users u JOIN teachers t ON u.id_user = t.id_user WHERE u.username = '$username' AND t.id_teacher != '$id_teacher'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Ce nom d'utilisateur est déjà utilisé.";
    } else {
        $query = "UPDATE teachers t JOIN users u ON t.id_user = u.id_user 
                  SET t.nom = '$nom', t.prenom = '$prenom', t.email = '$email', u.username = '$username'";
        if ($password) {
            $query .= ", u.password = '$password'";
        }
        $query .= " WHERE t.id_teacher = '$id_teacher'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_teachers.php?success=Enseignant mis à jour avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'un enseignant
if (isset($_GET['delete'])) {
    $id_teacher = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "SELECT id_user FROM teachers WHERE id_teacher = '$id_teacher'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $id_user = $row['id_user'];
        $query = "DELETE FROM teachers WHERE id_teacher = '$id_teacher'; DELETE FROM users WHERE id_user = '$id_user'";
        if (mysqli_multi_query($conn, $query)) {
            header("Location: manage_teachers.php?success=Enseignant supprimé avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    } else {
        $error = "Erreur : Enseignant non trouvé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Enseignants - Système LMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3">
            <h3 class="text-center mb-4">ISCAE LMD</h3>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fas fa-home"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-university"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_schedules.php"><i class="fas fa-clock"></i> Emplois du Temps</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Enseignants</h1>
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
                    <h5 class="card-title">Ajouter un Enseignant</h5>
                    <form action="manage_teachers.php" method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <button type="submit" name="add_teacher" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Enseignants</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom d'utilisateur</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT t.id_teacher, u.username, t.nom, t.prenom, t.email 
                                      FROM teachers t 
                                      JOIN users u ON t.id_user = u.id_user";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['username']) . "</td>
                                    <td>" . htmlspecialchars($row['nom']) . "</td>
                                    <td>" . htmlspecialchars($row['prenom']) . "</td>
                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                    <td>
                                        <button class='btn btn-sm btn-warning btn-edit-teacher' data-teacher='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                        <a href='manage_teachers.php?delete={$row['id_teacher']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
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

    <!-- Modal d'édition unique pour enseignant -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="manage_teachers.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTeacherModalLabel">Modifier Enseignant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_teacher" id="edit_id_teacher">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_teacher" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
        <script>
        document.querySelectorAll('.btn-edit-teacher').forEach(btn => {
            btn.addEventListener('click', function() {
                const teacher = JSON.parse(this.getAttribute('data-teacher'));
                document.getElementById('edit_id_teacher').value = teacher.id_teacher;
                document.getElementById('edit_username').value = teacher.username;
                document.getElementById('edit_nom').value = teacher.nom;
                document.getElementById('edit_prenom').value = teacher.prenom;
                document.getElementById('edit_email').value = teacher.email;
                document.getElementById('edit_password').value = ''; // Réinitialiser le mot de passe
                
                var modal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
                modal.show();
            });
        });
        </script>
</body>
</html>