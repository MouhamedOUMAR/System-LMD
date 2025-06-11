<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'un département
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $id_faculty = mysqli_real_escape_string($conn, $_POST['id_faculty']);
    $department_name = mysqli_real_escape_string($conn, $_POST['department_name']);

    // Vérifier si le département existe déjà dans cette faculté
    $check_query = "SELECT id_department FROM departments WHERE id_faculty = '$id_faculty' AND department_name = '$department_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Ce département existe déjà dans cette faculté.";
    } else {
        $query = "INSERT INTO departments (id_faculty, department_name) VALUES ('$id_faculty', '$department_name')";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_departements.php?success=Département ajouté avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'un département
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_department'])) {
    $id_department = mysqli_real_escape_string($conn, $_POST['id_department']);
    $id_faculty = mysqli_real_escape_string($conn, $_POST['id_faculty']);
    $department_name = mysqli_real_escape_string($conn, $_POST['department_name']);

    // Vérifier si le département existe déjà dans cette faculté (sauf le département actuel)
    $check_query = "SELECT id_department FROM departments WHERE id_faculty = '$id_faculty' AND department_name = '$department_name' AND id_department != '$id_department'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Ce département existe déjà dans cette faculté.";
    } else {
        $query = "UPDATE departments SET id_faculty = '$id_faculty', department_name = '$department_name' WHERE id_department = '$id_department'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_departements.php?success=Département mis à jour avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'un département
if (isset($_GET['delete'])) {
    $id_department = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Vérifier si le département est utilisé dans d'autres tables
    $check_queries = [
        "SELECT COUNT(*) as count FROM filieres WHERE id_department = '$id_department'",
        "SELECT COUNT(*) as count FROM students WHERE id_department = '$id_department'",
        "SELECT COUNT(*) as count FROM teachers WHERE id_department = '$id_department'"
    ];
    
    $can_delete = true;
    $error_message = "";
    
    foreach ($check_queries as $check_query) {
        $result = mysqli_query($conn, $check_query);
        $row = mysqli_fetch_assoc($result);
        if ($row['count'] > 0) {
            $can_delete = false;
            $error_message = "Impossible de supprimer ce département car il est utilisé par des filières, étudiants ou enseignants.";
            break;
        }
    }
    
    if ($can_delete) {
        $query = "DELETE FROM departments WHERE id_department = '$id_department'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_departements.php?success=Département supprimé avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    } else {
        $error = $error_message;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Départements - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white active" href="manage_departements.php"><i class="fas fa-building"></i> Départements</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
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
            <h1 class="mb-4 text-primary">Gestion des Départements</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter un Département</h5>
                    <form action="manage_departements.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_faculty" class="form-label">Faculté</label>
                                <select class="form-control" id="id_faculty" name="id_faculty" required>
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
                            <div class="col-md-6 mb-3">
                                <label for="department_name" class="form-label">Nom du Département</label>
                                <input type="text" class="form-control" id="department_name" name="department_name" required>
                            </div>
                        </div>
                        <button type="submit" name="add_department" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Départements</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Faculté</th>
                                <th>Nom du Département</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT d.id_department, d.department_name, fac.id_faculty, fac.faculty_name 
                                      FROM departments d 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      ORDER BY fac.faculty_name, d.department_name";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                        <td>" . htmlspecialchars($row['department_name']) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editDepartment{$row['id_department']}'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_departements.php?delete={$row['id_department']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";
                                    echo "<div class='modal fade' id='editDepartment{$row['id_department']}' tabindex='-1'>
                                        <div class='modal-dialog'>
                                            <div class='modal-content'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Modifier le Département</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <form action='manage_departements.php' method='POST'>
                                                        <input type='hidden' name='id_department' value='{$row['id_department']}'>
                                                        <div class='mb-3'>
                                                            <label for='id_faculty_{$row['id_department']}' class='form-label'>Faculté</label>
                                                            <select class='form-control' id='id_faculty_{$row['id_department']}' name='id_faculty' required>
                                                                <option value='{$row['id_faculty']}'>" . htmlspecialchars($row['faculty_name']) . "</option>";
                                    $fac_query = "SELECT id_faculty, faculty_name FROM faculties";
                                    $fac_result = mysqli_query($conn, $fac_query);
                                    while ($fac = mysqli_fetch_assoc($fac_result)) {
                                        echo "<option value='{$fac['id_faculty']}'>" . htmlspecialchars($fac['faculty_name']) . "</option>";
                                    }
                                    echo "</select>
                                                        </div>
                                                        <div class='mb-3'>
                                                            <label for='department_name_{$row['id_department']}' class='form-label'>Nom du Département</label>
                                                            <input type='text' class='form-control' id='department_name_{$row['id_department']}' name='department_name' value='" . htmlspecialchars($row['department_name']) . "' required>
                                                        </div>
                                                        <button type='submit' name='update_department' class='btn btn-primary'>Mettre à jour</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>Erreur : " . mysqli_error($conn) . "</td></tr>";
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
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_GET['success']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>