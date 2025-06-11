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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Enseignant - Système LMD</title>
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
                <li class="nav-item"><a class="nav-link text-white active" href="dashboard.php"><i class="fas fa-home"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Tableau de Bord Enseignant</h1>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Matières Assignées</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Module</th>
                                <th>Filière</th>
                                <th>Coefficient</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT s.subject_name, s.coefficient, m.module_name, f.filiere_name 
                                      FROM subjects s 
                                      JOIN modules m ON s.id_module = m.id_module 
                                      JOIN filieres f ON m.id_filiere = f.id_filiere 
                                      WHERE s.id_teacher = '$teacher_id'";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>{$row['subject_name']}</td>
                                    <td>{$row['module_name']}</td>
                                    <td>{$row['filiere_name']}</td>
                                    <td>{$row['coefficient']}</td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>