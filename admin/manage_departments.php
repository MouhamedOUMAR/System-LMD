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
            header("Location: manage_departments.php?success=Département ajouté avec succès");
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
            header("Location: manage_departments.php?success=Département mis à jour avec succès");
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
            header("Location: manage_departments.php?success=Département supprimé avec succès");
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
        <?php include 'sidebar.php'; ?>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Départements</h1>
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
                    <h5 class="card-title">Ajouter un Département</h5>
                    <form action="manage_departments.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_faculty" class="form-label">Faculté</label>
                                <select class="form-control" id="id_faculty" name="id_faculty" required>
                                    <option value="">Sélectionner une faculté</option>
                                    <?php
                                    $query = "SELECT id_faculty, faculty_name FROM faculties";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id_faculty'] . "'>" . htmlspecialchars($row['faculty_name']) . "</option>";
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
                                            <button class='btn btn-sm btn-warning btn-edit-department' data-department='" . json_encode($row) . "'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_departments.php?delete=" . $row['id_department'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";
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

    <!-- Modal d'édition unique pour département -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editDepartmentForm" method="POST" action="manage_departments.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDepartmentModalLabel">Modifier le Département</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_department" id="edit_id_department">
                        <div class="mb-3">
                            <label for="edit_id_faculty" class="form-label">Faculté</label>
                            <select class="form-control" id="edit_id_faculty" name="id_faculty" required>
                                <option value="">Sélectionner une faculté</option>
                                <?php
                                $faculty_query = "SELECT id_faculty, faculty_name FROM faculties ORDER BY faculty_name";
                                $faculty_result = mysqli_query($conn, $faculty_query);
                                while ($faculty = mysqli_fetch_assoc($faculty_result)) {
                                    echo "<option value='" . $faculty['id_faculty'] . "'>" . htmlspecialchars($faculty['faculty_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_department_name" class="form-label">Nom du Département</label>
                            <input type="text" class="form-control" id="edit_department_name" name="department_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_department" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-edit-department').forEach(btn => {
            btn.addEventListener('click', function() {
                const department = JSON.parse(this.getAttribute('data-department'));
                document.getElementById('edit_id_department').value = department.id_department;
                document.getElementById('edit_department_name').value = department.department_name;
                document.getElementById('edit_id_faculty').value = department.id_faculty;
                var modal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
                modal.show();
            });
        });
    });
    </script>
</body>
</html>