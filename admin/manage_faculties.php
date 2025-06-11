<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'une faculté
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
    $faculty_name = mysqli_real_escape_string($conn, $_POST['faculty_name']);
    
    $query = "SELECT id_faculty FROM faculties WHERE faculty_name = '$faculty_name'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Cette faculté existe déjà.";
    } else {
        $query = "INSERT INTO faculties (faculty_name) VALUES ('$faculty_name')";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_faculties.php?success=Faculté ajoutée avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'une faculté
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faculty'])) {
    $id_faculty = mysqli_real_escape_string($conn, $_POST['id_faculty']);
    $faculty_name = mysqli_real_escape_string($conn, $_POST['faculty_name']);
    
    $query = "SELECT id_faculty FROM faculties WHERE faculty_name = '$faculty_name' AND id_faculty != '$id_faculty'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Ce nom de faculté est déjà utilisé.";
    } else {
        $query = "UPDATE faculties SET faculty_name = '$faculty_name' WHERE id_faculty = '$id_faculty'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_faculties.php?success=Faculté mise à jour avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'une faculté
if (isset($_GET['delete'])) {
    $id_faculty = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM faculties WHERE id_faculty = '$id_faculty'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_faculties.php?success=Faculté supprimée avec succès");
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
    <title>Gestion des Facultés - Système LMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3">
            <h3 class="text-center mb-4">Système LMD</h3>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fas fa-home"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_faculties.php"><i class="fas fa-university"></i> Facultés</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
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
            <h1 class="mb-4 text-primary">Gestion des Facultés</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter une Faculté</h5>
                    <form action="manage_faculties.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="faculty_name" class="form-label">Nom de la Faculté</label>
                                <input type="text" class="form-control" id="faculty_name" name="faculty_name" required>
                            </div>
                        </div>
                        <button type="submit" name="add_faculty" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Facultés</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom de la Faculté</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT id_faculty, faculty_name FROM faculties";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                    <td>
                                        <button class='btn btn-sm btn-warning btn-edit-faculty' data-faculty='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                        <a href='manage_faculties.php?delete={$row['id_faculty']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
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

    <!-- Toast Container -->
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

    <!-- Modal d'édition unique pour faculté -->
    <div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editFacultyForm" method="POST" action="manage_faculties.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editFacultyModalLabel">Modifier la Faculté</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_faculty" id="edit_id_faculty">
                        <div class="mb-3">
                            <label for="edit_faculty_name" class="form-label">Nom de la Faculté</label>
                            <input type="text" class="form-control" name="faculty_name" id="edit_faculty_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_faculty" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_GET['success']); ?>");
        </script>
    <?php endif; ?>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-edit-faculty').forEach(btn => {
            btn.addEventListener('click', function() {
                const faculty = JSON.parse(this.getAttribute('data-faculty'));
                document.getElementById('edit_id_faculty').value = faculty.id_faculty;
                document.getElementById('edit_faculty_name').value = faculty.faculty_name;
                var modal = new bootstrap.Modal(document.getElementById('editFacultyModal'));
                modal.show();
            });
        });
    });
    </script>
</body>
</html>