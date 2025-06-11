<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'une année académique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_academic_year'])) {
    $year_name = mysqli_real_escape_string($conn, $_POST['year_name']);
    $start_date = !empty($_POST['start_date']) ? mysqli_real_escape_string($conn, $_POST['start_date']) : null;
    $end_date = !empty($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : null;

    $query = "SELECT id_academic_year FROM academic_years WHERE year_name = '$year_name'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Cette année académique existe déjà.";
    } else {
        $query = "INSERT INTO academic_years (year_name, start_date, end_date) VALUES ('$year_name', " . ($start_date ? "'$start_date'" : "NULL") . ", " . ($end_date ? "'$end_date'" : "NULL") . ")";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_academic_years.php?success=Année académique ajoutée avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'une année académique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_academic_year'])) {
    $id_academic_year = mysqli_real_escape_string($conn, $_POST['id_academic_year']);
    $year_name = mysqli_real_escape_string($conn, $_POST['year_name']);
    $start_date = !empty($_POST['start_date']) ? mysqli_real_escape_string($conn, $_POST['start_date']) : null;
    $end_date = !empty($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : null;

    $query = "SELECT id_academic_year FROM academic_years WHERE year_name = '$year_name' AND id_academic_year != '$id_academic_year'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Ce nom d'année académique est déjà utilisé.";
    } else {
        $query = "UPDATE academic_years SET year_name = '$year_name', start_date = " . ($start_date ? "'$start_date'" : "NULL") . ", end_date = " . ($end_date ? "'$end_date'" : "NULL") . " WHERE id_academic_year = '$id_academic_year'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_academic_years.php?success=Année académique mise à jour avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'une année académique
if (isset($_GET['delete'])) {
    $id_academic_year = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM academic_years WHERE id_academic_year = '$id_academic_year'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_academic_years.php?success=Année académique supprimée avec succès");
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
    <title>Gestion des Années Académiques - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_schedules.php"><i class="fas fa-clock"></i> Emplois du Temps</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="manage_academic_years.php"><i class="fas fa-calendar-alt"></i> Années Académiques</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Années Académiques</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter une Année Académique</h5>
                    <form action="manage_academic_years.php" method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="year_name" class="form-label">Année (ex. 2023-2024)</label>
                                <input type="text" class="form-control" id="year_name" name="year_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="start_date" class="form-label">Date de Début</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_date" class="form-label">Date de Fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        <button type="submit" name="add_academic_year" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Années Académiques</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Année</th>
                                <th>Date de Début</th>
                                <th>Date de Fin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT id_academic_year, year_name, start_date, end_date FROM academic_years ORDER BY start_date DESC";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['year_name']) . "</td>
                                    <td>" . ($row['start_date'] ? htmlspecialchars($row['start_date']) : '-') . "</td>
                                    <td>" . ($row['end_date'] ? htmlspecialchars($row['end_date']) : '-') . "</td>
                                    <td>
                                        <button class='btn btn-sm btn-warning btn-edit-year' data-year='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                        <a href='manage_academic_years.php?delete={$row['id_academic_year']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
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

    <!-- Modal d'édition unique pour année académique -->
    <div class="modal fade" id="editAcademicYearModal" tabindex="-1" aria-labelledby="editAcademicYearModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="manage_academic_years.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAcademicYearModalLabel">Modifier Année Académique</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_academic_year" id="edit_id_academic_year">
                        <div class="mb-3">
                            <label for="edit_year_name" class="form-label">Année</label>
                            <input type="text" class="form-control" id="edit_year_name" name="year_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_start_date" class="form-label">Date de Début</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date">
                        </div>
                        <div class="mb-3">
                            <label for="edit_end_date" class="form-label">Date de Fin</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_academic_year" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        document.querySelectorAll('.btn-edit-year').forEach(btn => {
            btn.addEventListener('click', function() {
                const year = JSON.parse(this.getAttribute('data-year'));
                document.getElementById('edit_id_academic_year').value = year.id_academic_year;
                document.getElementById('edit_year_name').value = year.year_name;
                document.getElementById('edit_start_date').value = year.start_date || '';
                document.getElementById('edit_end_date').value = year.end_date || '';
                
                var modal = new bootstrap.Modal(document.getElementById('editAcademicYearModal'));
                modal.show();
            });
        });
    </script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_GET['success']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>