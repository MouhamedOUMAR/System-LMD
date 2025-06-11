<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer l'ID de l'étudiant connecté
$id_student = $_SESSION['user_id'];

// Récupérer les informations de l'étudiant
$query = "SELECT s.*, f.filiere_name, d.department_name, fac.faculty_name 
          FROM students s 
          JOIN filieres f ON s.id_filiere = f.id_filiere 
          JOIN departments d ON f.id_department = d.id_department 
          JOIN faculties fac ON d.id_faculty = fac.id_faculty 
          WHERE s.id_student = '$id_student'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);

// Récupérer l'ID de la filière de l'étudiant
$id_filiere = $student['id_filiere'];
$filiere_name = $student['filiere_name'];

// Récupérer le semestre actif (si disponible)
$active_semester = null;
$query = "SELECT s.* FROM semesters s 
          JOIN modules m ON s.id_semester = m.id_semester 
          WHERE m.id_filiere = '$id_filiere' 
          ORDER BY s.semester_name LIMIT 1";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    $active_semester = mysqli_fetch_assoc($result);
}

// Vérifier si un semestre est sélectionné dans l'URL
if (isset($_GET['semester']) && !empty($_GET['semester'])) {
    $selected_semester = mysqli_real_escape_string($conn, $_GET['semester']);
    $query = "SELECT * FROM semesters WHERE id_semester = '$selected_semester'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $active_semester = mysqli_fetch_assoc($result);
    }
}

// Définir l'ID du semestre actif
$id_semester = $active_semester ? $active_semester['id_semester'] : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Emploi du Temps - Système LMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .schedule-cell {
            height: 100px;
            border: 1px solid #dee2e6;
            padding: 5px;
            position: relative;
        }
        .schedule-item {
            background-color: #e9ecef;
            border-radius: 5px;
            padding: 5px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        .time-column {
            width: 80px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Mon Emploi du Temps</h1>
            
            <?php if (!$id_semester): ?>
                <div class="alert alert-info">Aucun semestre actif trouvé pour votre filière.</div>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Emploi du temps - <?php echo htmlspecialchars($filiere_name); ?></h5>
                        <div>
                            <form method="get" action="" class="d-flex">
                                <select class="form-select me-2" name="semester" onchange="this.form.submit()">
                                    <?php
                                    $query = "SELECT DISTINCT s.id_semester, s.semester_name 
                                              FROM semesters s 
                                              JOIN modules m ON s.id_semester = m.id_semester 
                                              WHERE m.id_filiere = '$id_filiere' 
                                              ORDER BY s.semester_name";
                                    $semesters_result = mysqli_query($conn, $query);
                                    while ($semester = mysqli_fetch_assoc($semesters_result)) {
                                        $selected = ($semester['id_semester'] == $id_semester) ? 'selected' : '';
                                        echo "<option value='" . $semester['id_semester'] . "' " . $selected . ">" . htmlspecialchars($semester['semester_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="time-column">Horaire</th>
                                        <th>Lundi</th>
                                        <th>Mardi</th>
                                        <th>Mercredi</th>
                                        <th>Jeudi</th>
                                        <th>Vendredi</th>
                                        <th>Samedi</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Vérifier si la table schedules existe
                                    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'schedules'");
                                    if (mysqli_num_rows($check_table) == 0) {
                                        // Créer la table si elle n'existe pas
                                        $create_table = "CREATE TABLE schedules (
                                            id_schedule INT AUTO_INCREMENT PRIMARY KEY,
                                            id_filiere INT NOT NULL,
                                            id_semester INT NOT NULL,
                                            day VARCHAR(20) NOT NULL,
                                            start_time TIME NOT NULL,
                                            end_time TIME NOT NULL,
                                            subject_id INT,
                                            room VARCHAR(50),
                                            FOREIGN KEY (id_filiere) REFERENCES filieres(id_filiere) ON DELETE CASCADE,
                                            FOREIGN KEY (id_semester) REFERENCES semesters(id_semester) ON DELETE CASCADE,
                                            FOREIGN KEY (subject_id) REFERENCES subjects(id_subject) ON DELETE SET NULL
                                        )";
                                        mysqli_query($conn, $create_table);
                                    }
                                    
                                    // Récupérer les horaires
                                    $query = "SELECT sch.*, sub.subject_name 
                                              FROM schedules sch 
                                              LEFT JOIN subjects sub ON sch.subject_id = sub.id_subject 
                                              WHERE sch.id_filiere = '$id_filiere' AND sch.id_semester = '$id_semester'";
                                    $schedules_result = mysqli_query($conn, $query);
                                    
                                    // Récupérer les horaires uniques
                                    $hours = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
                                    foreach ($hours as $hour) {
                                        echo "<tr><td>$hour - " . date('H:i', strtotime($hour . ' +2 hours')) . "</td>";
                                        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                        foreach ($days as $day) {
                                            $query = "SELECT s.subject_name, sch.room 
                                                      FROM schedules sch 
                                                      JOIN subjects s ON sch.id_subject = s.id_subject 
                                                      JOIN modules m ON s.id_module = m.id_module 
                                                      WHERE m.id_filiere = '$id_filiere' 
                                                      AND sch.day_of_week = '$day' 
                                                      AND sch.start_time = '$hour'";
                                            $result = mysqli_query($conn, $query);
                                            if ($row = mysqli_fetch_assoc($result)) {
                                                echo "<td>{$row['subject_name']} (Salle {$row['room']})</td>";
                                            } else {
                                                echo "<td>-</td>";
                                            }
                                        }
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
