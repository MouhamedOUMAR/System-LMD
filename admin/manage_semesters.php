<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Vérifier si la table semesters a la colonne id_academic_year
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM semesters LIKE 'id_academic_year'");
if (mysqli_num_rows($check_column) == 0) {
    // Ajouter la colonne manquante
    mysqli_query($conn, "ALTER TABLE semesters ADD COLUMN id_academic_year INT NULL");
    mysqli_query($conn, "ALTER TABLE semesters ADD FOREIGN KEY (id_academic_year) REFERENCES academic_years(id_academic_year)");
}

// Vérifier si la table semesters a les colonnes start_date et end_date
$check_start_date = mysqli_query($conn, "SHOW COLUMNS FROM semesters LIKE 'start_date'");
if (mysqli_num_rows($check_start_date) == 0) {
    mysqli_query($conn, "ALTER TABLE semesters ADD COLUMN start_date DATE NULL");
}

$check_end_date = mysqli_query($conn, "SHOW COLUMNS FROM semesters LIKE 'end_date'");
if (mysqli_num_rows($check_end_date) == 0) {
    mysqli_query($conn, "ALTER TABLE semesters ADD COLUMN end_date DATE NULL");
}

// Ajouter un semestre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_semester'])) {
    $id_academic_year = mysqli_real_escape_string($conn, $_POST['id_academic_year']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $semester_name = mysqli_real_escape_string($conn, $_POST['semester_name']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    
    $query = "INSERT INTO semesters (id_filiere, semester_name, start_date, end_date, id_academic_year) 
              VALUES ('$id_filiere', '$semester_name', '$start_date', '$end_date', '$id_academic_year')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: manage_semesters.php?success=Semestre ajouté avec succès");
        exit;
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

// Modifier un semestre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_semester'])) {
    $id_semester = mysqli_real_escape_string($conn, $_POST['id_semester']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere_' . $id_semester]);
    $semester_name = mysqli_real_escape_string($conn, $_POST['semester_name_' . $id_semester]);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date_' . $id_semester]);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date_' . $id_semester]);
    $id_academic_year = mysqli_real_escape_string($conn, $_POST['id_academic_year_' . $id_semester]);
    
    $query = "UPDATE semesters SET id_filiere = '$id_filiere', semester_name = '$semester_name', 
              start_date = '$start_date', end_date = '$end_date', id_academic_year = '$id_academic_year' 
              WHERE id_semester = '$id_semester'";
    
    if (mysqli_query($conn, $query)) {
        header("Location: manage_semesters.php?success=Semestre modifié avec succès");
        exit;
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

// Supprimer un semestre
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id_semester = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Vérifier si le semestre est utilisé dans des modules
    $query = "SELECT COUNT(*) as count FROM modules WHERE id_semester = '$id_semester'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        $error = "Impossible de supprimer ce semestre car il est utilisé dans des modules";
    } else {
        $query = "DELETE FROM semesters WHERE id_semester = '$id_semester'";
        
        if (mysqli_query($conn, $query)) {
            header("Location: manage_semesters.php?success=Semestre supprimé avec succès");
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
    <title>Gestion des Semestres - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Semestres</h1>
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
            
            <!-- Formulaire d'ajout de semestre -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ajouter un Semestre</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="id_academic_year" class="form-label">Année Académique</label>
                                <select class="form-select" id="id_academic_year" name="id_academic_year" required>
                                    <option value="">Sélectionner une année</option>
                                    <?php
                                    $query = "SELECT id_academic_year, year_name FROM academic_years ORDER BY year_name DESC";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id_academic_year'] . "'>" . htmlspecialchars($row['year_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="id_filiere" class="form-label">Filière</label>
                                <select class="form-select" id="id_filiere" name="id_filiere" required onchange="loadDepartments()">
                                    <option value="">Sélectionner une filière</option>
                                    <?php
                                    $query = "SELECT f.id_filiere, f.filiere_name, d.department_name, fac.faculty_name 
                                              FROM filieres f 
                                              JOIN departments d ON f.id_department = d.id_department 
                                              JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                              ORDER BY fac.faculty_name, d.department_name, f.filiere_name";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id_filiere'] . "'>" . htmlspecialchars($row['faculty_name'] . ' - ' . $row['department_name'] . ' - ' . $row['filiere_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="semester_name" class="form-label">Nom du Semestre</label>
                                <input type="text" class="form-control" id="semester_name" name="semester_name" placeholder="Ex: Semestre 1" required>
                            </div>
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="add_semester" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Ajouter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des semestres -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Liste des Semestres</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Année Académique</th>
                                    <th>Filière</th>
                                    <th>Semestre</th>
                                    <th>Date de début</th>
                                    <th>Date de fin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT s.id_semester, s.semester_name, s.start_date, s.end_date, s.id_filiere, s.id_academic_year, f.filiere_name, d.department_name, fac.faculty_name, ay.year_name
                                          FROM semesters s
                                          JOIN filieres f ON s.id_filiere = f.id_filiere
                                          JOIN departments d ON f.id_department = d.id_department
                                          JOIN faculties fac ON d.id_faculty = fac.id_faculty
                                          LEFT JOIN academic_years ay ON s.id_academic_year = ay.id_academic_year
                                          ORDER BY ay.year_name DESC, fac.faculty_name, d.department_name, f.filiere_name, s.semester_name";
                                $result = mysqli_query($conn, $query);
                                
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>
                                                <td>" . (isset($row['year_name']) ? htmlspecialchars($row['year_name']) : 'Non défini') . "</td>
                                                <td>" . htmlspecialchars($row['faculty_name'] . ' - ' . $row['department_name'] . ' - ' . $row['filiere_name']) . "</td>
                                                <td>" . htmlspecialchars($row['semester_name']) . "</td>
                                                <td>" . htmlspecialchars($row['start_date']) . "</td>
                                                <td>" . htmlspecialchars($row['end_date']) . "</td>
                                                <td>
                                                    <button class='btn btn-sm btn-primary btn-edit-semester' data-semester='" . json_encode($row) . "'>
                                                        <i class='fas fa-edit'></i>
                                                    </button>
                                                    <a href='manage_semesters.php?delete=" . $row['id_semester'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce semestre ?\")'>
                                                        <i class='fas fa-trash'></i>
                                                    </a>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Aucun semestre trouvé</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'édition unique pour semestre -->
    <div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="editSemesterForm" method="POST" action="manage_semesters.php">
            <div class="modal-header">
              <h5 class="modal-title" id="editSemesterModalLabel">Modifier le Semestre</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_semester" id="edit_id_semester">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="edit_id_academic_year" class="form-label">Année Académique</label>
                  <select class="form-select" id="edit_id_academic_year" name="id_academic_year" required>
                    <option value="">Sélectionner une année</option>
                    <?php
                    $years_query = "SELECT id_academic_year, year_name FROM academic_years ORDER BY year_name DESC";
                    $years_result = mysqli_query($conn, $years_query);
                    while ($year = mysqli_fetch_assoc($years_result)) {
                        echo "<option value='" . $year['id_academic_year'] . "'>" . htmlspecialchars($year['year_name']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="edit_id_filiere" class="form-label">Filière</label>
                  <select class="form-select" id="edit_id_filiere" name="id_filiere" required>
                    <option value="">Sélectionner une filière</option>
                    <?php
                    $filieres_query = "SELECT f.id_filiere, f.filiere_name, d.department_name, fac.faculty_name 
                                      FROM filieres f 
                                      JOIN departments d ON f.id_department = d.id_department 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      ORDER BY fac.faculty_name, d.department_name, f.filiere_name";
                    $filieres_result = mysqli_query($conn, $filieres_query);
                    while ($filiere = mysqli_fetch_assoc($filieres_result)) {
                        echo "<option value='" . $filiere['id_filiere'] . "'>" . htmlspecialchars($filiere['faculty_name'] . ' - ' . $filiere['department_name'] . ' - ' . $filiere['filiere_name']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label for="edit_semester_name" class="form-label">Nom du Semestre</label>
                  <input type="text" class="form-control" id="edit_semester_name" name="semester_name" required>
                </div>
                <div class="col-md-4">
                  <label for="edit_start_date" class="form-label">Date de début</label>
                  <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                </div>
                <div class="col-md-4">
                  <label for="edit_end_date" class="form-label">Date de fin</label>
                  <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" name="edit_semester" class="btn btn-primary">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.btn-edit-semester').forEach(btn => {
        btn.addEventListener('click', function() {
          const semester = JSON.parse(this.getAttribute('data-semester'));
          document.getElementById('edit_id_semester').value = semester.id_semester;
          document.getElementById('edit_semester_name').value = semester.semester_name;
          document.getElementById('edit_start_date').value = semester.start_date;
          document.getElementById('edit_end_date').value = semester.end_date;
          document.getElementById('edit_id_filiere').value = semester.id_filiere;
          document.getElementById('edit_id_academic_year').value = semester.id_academic_year;
          var modal = new bootstrap.Modal(document.getElementById('editSemesterModal'));
          modal.show();
        });
      });
    });
    </script>
</body>
</html>
