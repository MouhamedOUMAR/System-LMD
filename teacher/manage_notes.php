<?php
require '../config.php';
if (!isTeacher()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer l'ID de l'enseignant connecté
$query = "SELECT id_teacher FROM teachers WHERE id_user = '{$_SESSION['user_id']}'";
$result = mysqli_query($conn, $query);
$teacher = mysqli_fetch_assoc($result);
$teacher_id = $teacher['id_teacher'];

// Ajout ou mise à jour d'une note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_student = mysqli_real_escape_string($conn, $_POST['id_student']);
    $id_subject = mysqli_real_escape_string($conn, $_POST['id_subject']);
    $note_devoir = mysqli_real_escape_string($conn, $_POST['note_devoir']);
    $note_examen = mysqli_real_escape_string($conn, $_POST['note_examen']);

    // Vérifier que l'enseignant est autorisé à modifier cette matière
    $query = "SELECT id_subject FROM subjects WHERE id_subject = '$id_subject' AND id_teacher = '$teacher_id'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        $error = "Vous n'êtes pas autorisé à modifier les notes de cette matière.";
    } else {
        // Vérifier si une note existe déjà
        $query = "SELECT id_note FROM notes WHERE id_student = '$id_student' AND id_subject = '$id_subject'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            // Mise à jour
            $query = "UPDATE notes SET note_devoir = '$note_devoir', note_examen = '$note_examen' WHERE id_student = '$id_student' AND id_subject = '$id_subject'";
        } else {
            // Insertion
            $query = "INSERT INTO notes (id_student, id_subject, note_devoir, note_examen) VALUES ('$id_student', '$id_subject', '$note_devoir', '$note_examen')";
        }

        if (mysqli_query($conn, $query)) {
            header("Location: manage_notes.php?success=Note enregistrée avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'une note
if (isset($_GET['delete'])) {
    $id_note = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "SELECT n.id_subject FROM notes n JOIN subjects s ON n.id_subject = s.id_subject WHERE n.id_note = '$id_note' AND s.id_teacher = '$teacher_id'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        $error = "Vous n'êtes pas autorisé à supprimer cette note.";
    } else {
        $query = "DELETE FROM notes WHERE id_note = '$id_note'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_notes.php?success=Note supprimée avec succès");
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
    <title>Gestion des Notes - Système LMD</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white p-3">
            <h3 class="text-center mb-4">ISCAE LMD</h3>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fas fa-home"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Notes</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter/Modifier une Note</h5>
                    <form action="manage_notes.php" method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="id_student" class="form-label">Étudiant</label>
                                <select class="form-control" id="id_student" name="id_student" required>
                                    <?php
                                    // Sélectionner les étudiants inscrits dans les filières des matières de l'enseignant
                                    $query = "SELECT DISTINCT s.id_student, s.nom, s.prenom 
                                              FROM students s 
                                              JOIN filieres f ON s.id_filiere = f.id_filiere 
                                              JOIN modules m ON m.id_filiere = f.id_filiere 
                                              JOIN subjects su ON su.id_module = m.id_module 
                                              WHERE su.id_teacher = '$teacher_id'";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_student']}'>{$row['nom']} {$row['prenom']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_subject" class="form-label">Matière</label>
                                <select class="form-control" id="id_subject" name="id_subject" required>
                                    <?php
                                    // Sélectionner uniquement les matières assignées à l'enseignant
                                    $query = "SELECT s.id_subject, s.subject_name, m.module_name 
                                              FROM subjects s 
                                              JOIN modules m ON s.id_module = m.id_module 
                                              WHERE s.id_teacher = '$teacher_id'";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_subject']}'>{$row['module_name']} - {$row['subject_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="note_devoir" class="form-label">Note Devoir (0-20)</label>
                                <input type="number" step="0.01" min="0" max="20" class="form-control" id="note_devoir" name="note_devoir" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="note_examen" class="form-label">Note Examen (0-20)</label>
                                <input type="number" step="0.01" min="0" max="20" class="form-control" id="note_examen" name="note_examen" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Enregistrer</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Notes</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Matière</th>
                                <th>Module</th>
                                <th>Note Devoir</th>
                                <th>Note Examen</th>
                                <th>Note Finale</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Afficher les notes des matières assignées à l'enseignant
                            $query = "SELECT n.id_note, n.note_devoir, n.note_examen, n.note_finale, s.subject_name, m.module_name, st.nom, st.prenom 
                                      FROM notes n 
                                      JOIN subjects s ON n.id_subject = s.id_subject 
                                      JOIN modules m ON s.id_module = m.id_module 
                                      JOIN students st ON n.id_student = st.id_student 
                                      WHERE s.id_teacher = '$teacher_id'";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>{$row['nom']} {$row['prenom']}</td>
                                    <td>{$row['subject_name']}</td>
                                    <td>{$row['module_name']}</td>
                                    <td>{$row['note_devoir']}</td>
                                    <td>{$row['note_examen']}</td>
                                    <td>{$row['note_finale']}</td>
                                    <td>
                                        <a href='manage_notes.php?delete={$row['id_note']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
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

    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo $_GET['success']; ?>");
        </script>
    <?php endif; ?>
</body>
</html>