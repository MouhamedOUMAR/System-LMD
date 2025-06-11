<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit;
}

$id_student = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT s.*, u.username, f.id_filiere 
          FROM students s 
          JOIN users u ON s.id_user = u.id_user 
          LEFT JOIN filieres f ON s.id_filiere = f.id_filiere 
          WHERE s.id_student = '$id_student'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    header("Location: manage_students.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = mysqli_real_escape_string($conn, $_POST['matricule']);
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $niveau = mysqli_real_escape_string($conn, $_POST['niveau']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = !empty($_POST['password']) ? $_POST['password'] : $student['password'];

    mysqli_begin_transaction($conn);
    try {
        $query = "UPDATE users SET username = '$username', password = '$password' WHERE id_user = '{$student['id_user']}'";
        if (!mysqli_query($conn, $query)) {
            throw new Exception(mysqli_error($conn));
        }

        $query = "UPDATE students SET matricule = '$matricule', nom = '$nom', prenom = '$prenom', email = '$email', niveau = '$niveau', id_filiere = '$id_filiere' WHERE id_student = '$id_student'";
        if (!mysqli_query($conn, $query)) {
            throw new Exception(mysqli_error($conn));
        }
        mysqli_commit($conn);
        header("Location: manage_students.php?success=Étudiant modifié avec succès");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Étudiant - Système LMD</title>
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
                <li class="nav-item"><a class="nav-link text-white active" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-university"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_years.php"><i class="fas fa-calendar"></i> Années & Semestres</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Modifier un Étudiant</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Modifier les Informations de l'Étudiant</h5>
                    <form action="edit_student.php?id=<?php echo $id_student; ?>" method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="matricule" class="form-label">Matricule</label>
                                <input type="text" class="form-control" id="matricule" name="matricule" value="<?php echo htmlspecialchars($student['matricule']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($student['nom']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($student['prenom']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="niveau" class="form-label">Niveau</label>
                                <select class="form-control" id="niveau" name="niveau" required>
                                    <option value="L1" <?php echo $student['niveau'] === 'L1' ? 'selected' : ''; ?>>L1</option>
                                    <option value="L2" <?php echo $student['niveau'] === 'L2' ? 'selected' : ''; ?>>L2</option>
                                    <option value="L3" <?php echo $student['niveau'] === 'L3' ? 'selected' : ''; ?>>L3</option>
                                    <option value="M1" <?php echo $student['niveau'] === 'M1' ? 'selected' : ''; ?>>M1</option>
                                    <option value="M2" <?php echo $student['niveau'] === 'M2' ? 'selected' : ''; ?>>M2</option>
                                    <option value="Doctorat" <?php echo $student['niveau'] === 'Doctorat' ? 'selected' : ''; ?>>Doctorat</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_filiere" class="form-label">Filière</label>
                                <select class="form-control" id="id_filiere" name="id_filiere" required>
                                    <?php
                                    $query = "SELECT f.id_filiere, f.filiere_name, d.department_name 
                                              FROM filieres f 
                                              JOIN departments d ON f.id_department = d.id_department";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $selected = $row['id_filiere'] == $student['id_filiere'] ? 'selected' : '';
                                        echo "<option value='{$row['id_filiere']}' $selected>{$row['department_name']} - {$row['filiere_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="password" class="form-label">Nouveau Mot de passe (facultatif)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        <a href="manage_students.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
                    </form>
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
</body>
</html>