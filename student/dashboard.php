<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$query = "SELECT s.id_student, s.nom, s.prenom, s.matricule, s.sub_level, f.filiere_name, l.level_name 
          FROM students s 
          JOIN filieres f ON s.id_filiere = f.id_filiere 
          JOIN levels l ON f.id_level = l.id_level 
          WHERE s.id_user = '$id_user'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Erreur : Étudiant non trouvé.");
}

$id_student = $student['id_student'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white active" href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="view_results.php"><i class="fas fa-calculator"></i> Mes Résultats</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="view_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="view_schedule.php"><i class="fas fa-clock"></i> Emploi du Temps</a></li>
                <?php if ($student['level_name'] === 'Doctorat'): ?>
                    <li class="nav-item"><a class="nav-link text-white" href="view_soutenance.php"><i class="fas fa-gavel"></i> Ma Soutenance</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <div class="welcome-message">
                <h2>Bienvenue, <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></h2>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Fiche Personnalisée</h5>
                            <p><strong>Matricule :</strong> <?php echo htmlspecialchars($student['matricule']); ?></p>
                            <p><strong>Filière :</strong> <?php echo htmlspecialchars($student['filiere_name']); ?></p>
                            <p><strong>Niveau :</strong> <?php echo htmlspecialchars($student['level_name']); ?></p>
                            <p><strong>Sous-niveau :</strong> <?php echo htmlspecialchars($student['sub_level']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Actions Rapides</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="view_results.php" class="btn btn-primary w-100"><i class="fas fa-calculator"></i> Voir Mes Résultats</a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="view_course_materials.php" class="btn btn-success w-100"><i class="fas fa-file-pdf"></i> Supports de Cours</a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="view_schedule.php" class="btn btn-info w-100"><i class="fas fa-clock"></i> Emploi du Temps</a>
                                </div>
                                <?php if ($student['level_name'] === 'Doctorat'): ?>
                                    <div class="col-md-4 mb-3">
                                        <a href="view_soutenance.php" class="btn btn-warning w-100"><i class="fas fa-gavel"></i> Ma Soutenance</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($student['level_name'] === 'Doctorat'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Prochaine Soutenance</h5>
                        <?php
                        $soutenance_query = "SELECT title, defense_date, defense_time, room, jury_members 
                                            FROM soutenances 
                                            WHERE id_student = '$id_student' 
                                            ORDER BY defense_date DESC LIMIT 1";
                        $soutenance_result = mysqli_query($conn, $soutenance_query);
                        if ($soutenance_row = mysqli_fetch_assoc($soutenance_result)) {
                            echo "<p><strong>Titre :</strong> " . htmlspecialchars($soutenance_row['title']) . "</p>";
                            echo "<p><strong>Date :</strong> " . htmlspecialchars($soutenance_row['defense_date']) . "</p>";
                            echo "<p><strong>Heure :</strong> " . htmlspecialchars($soutenance_row['defense_time']) . "</p>";
                            echo "<p><strong>Salle :</strong> " . htmlspecialchars($soutenance_row['room']) . "</p>";
                            echo "<p><strong>Jury :</strong> " . htmlspecialchars($soutenance_row['jury_members']) . "</p>";
                        } else {
                            echo "<p>Aucune soutenance enregistrée.</p>";
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>