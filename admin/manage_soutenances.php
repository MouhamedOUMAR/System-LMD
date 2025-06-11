<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'une soutenance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_soutenance'])) {
    $id_student = mysqli_real_escape_string($conn, $_POST['id_student']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $defense_date = mysqli_real_escape_string($conn, $_POST['defense_date']);
    $defense_time = mysqli_real_escape_string($conn, $_POST['defense_time']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $jury_members = mysqli_real_escape_string($conn, $_POST['jury_members']);

    $query = "INSERT INTO soutenances (id_student, title, defense_date, defense_time, room, jury_members) 
              VALUES ('$id_student', '$title', '$defense_date', '$defense_time', '$room', '$jury_members')";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_soutenances.php?success=Soutenance ajoutée avec succès");
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

// Mise à jour d'une soutenance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_soutenance'])) {
    $id_soutenance = mysqli_real_escape_string($conn, $_POST['id_soutenance']);
    $id_student = mysqli_real_escape_string($conn, $_POST['id_student']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $defense_date = mysqli_real_escape_string($conn, $_POST['defense_date']);
    $defense_time = mysqli_real_escape_string($conn, $_POST['defense_time']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $jury_members = mysqli_real_escape_string($conn, $_POST['jury_members']);

    $query = "UPDATE soutenances SET id_student = '$id_student', title = '$title', defense_date = '$defense_date', 
              defense_time = '$defense_time', room = '$room', jury_members = '$jury_members' WHERE id_soutenance = '$id_soutenance'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_soutenances.php?success=Soutenance mise à jour avec succès");
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

// Suppression d'une soutenance
if (isset($_GET['delete'])) {
    $id_soutenance = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM soutenances WHERE id_soutenance = '$id_soutenance'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_soutenances.php?success=Soutenance supprimée avec succès");
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
    <title>Gestion des Soutenances - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">
                <i class="fas fa-gavel me-2"></i>Gestion des Soutenances
            </h1>
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
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter une Soutenance
                    </h5>
                </div>
                <div class="card-body">
                    <form action="manage_soutenances.php" method="POST" id="addSoutenanceForm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="id_student" class="form-label">
                                    <i class="fas fa-user-graduate me-1"></i>Doctorant
                                </label>
                                <select class="form-select" id="id_student" name="id_student" required>
                                    <option value="">Sélectionner un doctorant</option>
                                    <?php
                                    $query = "SELECT s.id_student, s.nom, s.prenom, f.filiere_name 
                                              FROM students s 
                                              JOIN filieres f ON s.id_filiere = f.id_filiere 
                                              JOIN levels l ON f.id_level = l.id_level 
                                              WHERE l.level_name = 'Doctorat' 
                                              ORDER BY s.nom, s.prenom";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_student']}'>" . htmlspecialchars($row['nom'] . ' ' . $row['prenom'] . ' (' . $row['filiere_name'] . ')') . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-book me-1"></i>Titre de la Thèse
                                </label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="defense_date" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date de Soutenance
                                </label>
                                <input type="date" class="form-control" id="defense_date" name="defense_date" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="defense_time" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Heure de Soutenance
                                </label>
                                <input type="time" class="form-control" id="defense_time" name="defense_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="room" class="form-label">
                                    <i class="fas fa-door-open me-1"></i>Salle
                                </label>
                                <input type="text" class="form-control" id="room" name="room" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="jury_members" class="form-label">
                                    <i class="fas fa-users me-1"></i>Membres du Jury
                                </label>
                                <textarea class="form-control" id="jury_members" name="jury_members" rows="3" required 
                                          placeholder="Entrez les noms des membres du jury, un par ligne"></textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="add_soutenance" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Ajouter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Liste des Soutenances
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Doctorant</th>
                                <th>Filière</th>
                                <th>Titre</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Salle</th>
                                <th>Jury</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT sout.id_soutenance, s.nom, s.prenom, f.filiere_name, sout.title, sout.defense_date, sout.defense_time, sout.room, sout.jury_members 
                                      FROM soutenances sout 
                                      JOIN students s ON sout.id_student = s.id_student 
                                      JOIN filieres f ON s.id_filiere = f.id_filiere 
                                      ORDER BY sout.defense_date, sout.defense_time";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['nom'] . ' ' . $row['prenom']) . "</td>
                                        <td>" . htmlspecialchars($row['filiere_name']) . "</td>
                                        <td>" . htmlspecialchars($row['title']) . "</td>
                                            <td>" . date('d/m/Y', strtotime($row['defense_date'])) . "</td>
                                            <td>" . date('H:i', strtotime($row['defense_time'])) . "</td>
                                        <td>" . htmlspecialchars($row['room']) . "</td>
                                            <td>" . nl2br(htmlspecialchars($row['jury_members'])) . "</td>
                                        <td>
                                                <button class='btn btn-sm btn-warning me-1' data-bs-toggle='modal' data-bs-target='#editSoutenance{$row['id_soutenance']}' title='Modifier'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <a href='manage_soutenances.php?delete={$row['id_soutenance']}' 
                                                   class='btn btn-sm btn-danger' 
                                                   onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer cette soutenance ?\")'
                                                   title='Supprimer'>
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                        </td>
                                    </tr>";
                                    echo "<div class='modal fade' id='editSoutenance{$row['id_soutenance']}' tabindex='-1'>
                                            <div class='modal-dialog modal-lg'>
                                            <div class='modal-content'>
                                                    <div class='modal-header bg-warning text-dark'>
                                                        <h5 class='modal-title'>
                                                            <i class='fas fa-edit me-2'></i>Modifier la Soutenance
                                                        </h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <form action='manage_soutenances.php' method='POST'>
                                                        <input type='hidden' name='id_soutenance' value='{$row['id_soutenance']}'>
                                                            <div class='row'>
                                                                <div class='col-md-6 mb-3'>
                                                                    <label for='id_student_{$row['id_soutenance']}' class='form-label'>
                                                                        <i class='fas fa-user-graduate me-1'></i>Doctorant
                                                                    </label>
                                                                    <select class='form-select' id='id_student_{$row['id_soutenance']}' name='id_student' required>
                                                                <option value='{$row['id_student']}'>" . htmlspecialchars($row['nom'] . ' ' . $row['prenom']) . "</option>";
                                    $stud_query = "SELECT s.id_student, s.nom, s.prenom, f.filiere_name 
                                                   FROM students s 
                                                   JOIN filieres f ON s.id_filiere = f.id_filiere 
                                                   JOIN levels l ON f.id_level = l.id_level 
                                                   WHERE l.level_name = 'Doctorat' 
                                                       AND s.id_student != '{$row['id_student']}'
                                                   ORDER BY s.nom, s.prenom";
                                    $stud_result = mysqli_query($conn, $stud_query);
                                        while ($stud_row = mysqli_fetch_assoc($stud_result)) {
                                            echo "<option value='{$stud_row['id_student']}'>" . htmlspecialchars($stud_row['nom'] . ' ' . $stud_row['prenom'] . ' (' . $stud_row['filiere_name'] . ')') . "</option>";
                                    }
                                    echo "</select>
                                                        </div>
                                                                <div class='col-md-6 mb-3'>
                                                                    <label for='title_{$row['id_soutenance']}' class='form-label'>
                                                                        <i class='fas fa-book me-1'></i>Titre de la Thèse
                                                                    </label>
                                                            <input type='text' class='form-control' id='title_{$row['id_soutenance']}' name='title' value='" . htmlspecialchars($row['title']) . "' required>
                                                        </div>
                                                                <div class='col-md-4 mb-3'>
                                                                    <label for='defense_date_{$row['id_soutenance']}' class='form-label'>
                                                                        <i class='fas fa-calendar me-1'></i>Date de Soutenance
                                                                    </label>
                                                                    <input type='date' class='form-control' id='defense_date_{$row['id_soutenance']}' name='defense_date' value='" . $row['defense_date'] . "' required>
                                                                </div>
                                                                <div class='col-md-4 mb-3'>
                                                                    <label for='defense_time_{$row['id_soutenance']}' class='form-label'>
                                                                        <i class='fas fa-clock me-1'></i>Heure de Soutenance
                                                                    </label>
                                                                    <input type='time' class='form-control' id='defense_time_{$row['id_soutenance']}' name='defense_time' value='" . $row['defense_time'] . "' required>
                                                                </div>
                                                                <div class='col-md-4 mb-3'>
                                                                    <label for='room_{$row['id_soutenance']}' class='form-label'>
                                                                        <i class='fas fa-door-open me-1'></i>Salle
                                                                    </label>
                                                                    <input type='text' class='form-control' id='room_{$row['id_soutenance']}' name='room' value='" . htmlspecialchars($row['room']) . "' required>
                                                        </div>
                                                                <div class='col-12 mb-3'>
                                                                    <label for='jury_members_{$row['id_soutenance']}' class='form-label'>
                                                                        <i class='fas fa-users me-1'></i>Membres du Jury
                                                                    </label>
                                                                    <textarea class='form-control' id='jury_members_{$row['id_soutenance']}' name='jury_members' rows='3' required>" . htmlspecialchars($row['jury_members']) . "</textarea>
                                                        </div>
                                                        </div>
                                                            <div class='text-end'>
                                                                <button type='submit' name='update_soutenance' class='btn btn-warning'>
                                                                    <i class='fas fa-save me-1'></i>Enregistrer
                                                                </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>