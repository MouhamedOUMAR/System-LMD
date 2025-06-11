<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer les statistiques
$faculties_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM faculties")->fetch_assoc()['count'];
$departments_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$students_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$teachers_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$academic_years_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM academic_years")->fetch_assoc()['count'];
$semesters_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM semesters")->fetch_assoc()['count'];
$modules_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM modules")->fetch_assoc()['count'];
$subjects_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];

// Récupérer le nom de l'admin connecté
$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrateur';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_years.php"><i class="fas fa-calendar-alt"></i> Années & Semestres</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_faculties.php"><i class="fas fa-university"></i> Facultés</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_departements.php"><i class="fas fa-building"></i> Départements</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_schedules.php"><i class="fas fa-clock"></i> Emplois du Temps</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_soutenances.php"><i class="fas fa-gavel"></i> Soutenances</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Tableau de Bord Administrateur</h1>
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="fas fa-user-shield fa-2x me-2"></i>
                <div>
                    Bienvenue, <strong><?php echo htmlspecialchars($admin_name); ?></strong> !
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-primary shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-university fa-2x mb-2"></i>
                            <h5 class="card-title">Facultés</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $faculties_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-building fa-2x mb-2"></i>
                            <h5 class="card-title">Départements</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $departments_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-info shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-user-graduate fa-2x mb-2"></i>
                            <h5 class="card-title">Étudiants</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $students_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-warning shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                            <h5 class="card-title">Enseignants</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $teachers_count; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-secondary shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-book-open fa-2x mb-2"></i>
                            <h5 class="card-title">Modules</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $modules_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-dark shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-book fa-2x mb-2"></i>
                            <h5 class="card-title">Matières</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $subjects_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-primary shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                            <h5 class="card-title">Années Académiques</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $academic_years_count; ?></p>
                            <a href="manage_academic_years.php" class="btn btn-light btn-sm mt-2">Gérer</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success shadow h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="fas fa-calendar fa-2x mb-2"></i>
                            <h5 class="card-title">Semestres</h5>
                            <p class="card-text display-6 fw-bold"><?php echo $semesters_count; ?></p>
                            <a href="manage_semesters.php" class="btn btn-light btn-sm mt-2">Gérer</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Liste des Départements</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Faculté</th>
                                <th>Nom du Département</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT d.id_department, d.department_name, fac.faculty_name 
                                      FROM departments d 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      ORDER BY fac.faculty_name, d.department_name";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                        <td>" . htmlspecialchars($row['department_name']) . "</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2'>Erreur : " . mysqli_error($conn) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card-hover:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            transition: all 0.2s;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
