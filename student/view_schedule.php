<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

// Récupérer l'ID de l'étudiant connecté
$query = "SELECT s.id_student, s.id_filiere, f.filiere_name, s.sub_level 
          FROM students s 
          JOIN filieres f ON s.id_filiere = f.id_filiere 
          WHERE s.id_user = '{$_SESSION['user_id']}'";
$result = mysqli_query($conn, $query);
$student = mysqli_fetch_assoc($result);
$id_student = $student['id_student'];
$id_filiere = $student['id_filiere'];

// Récupérer le semestre actuel basé sur la date
$query = "SELECT id_semester, semester_name FROM semesters 
          WHERE id_filiere = '$id_filiere' 
          AND CURRENT_DATE BETWEEN start_date AND end_date 
          ORDER BY semester_order DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Si aucun semestre actif, prendre le dernier semestre
    $query = "SELECT id_semester, semester_name FROM semesters 
              WHERE id_filiere = '$id_filiere' 
              ORDER BY semester_order DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
}

$semester = mysqli_fetch_assoc($result);
$id_semester = $semester['id_semester'];
$semester_name = $semester['semester_name'];
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
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        .schedule-table th, .schedule-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }
        .schedule-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .schedule-cell {
            min-height: 80px;
            background-color: #f1f8ff;
            border-radius: 5px;
            padding: 5px;
            margin-bottom: 5px;
        }
        .no-class {
            color: #6c757d;
            font-style: italic;
        }
        .time-slot {
            font-weight: bold;
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Mon Emploi du Temps</h1>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($student['filiere_name'] . ' - ' . $student['sub_level'] . ' - ' . $semester_name); ?></h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th width="10%">Horaire</th>
                                    <th width="15%">Lundi</th>
                                    <th width="15%">Mardi</th>
                                    <th width="15%">Mercredi</th>
                                    <th width="15%">Jeudi</th>
                                    <th width="15%">Vendredi</th>
                                    <th width="15%">Samedi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Définir les créneaux horaires
                                $timeSlots = [
                                    '08:00-10:00',
                                    '10:00-12:00',
                                    '12:00-14:00',
                                    '14:00-16:00',
                                    '16:00-18:00'
                                ];
                                
                                // Jours de la semaine
                                $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                
                                // Récupérer tous les cours pour ce semestre et cette filière
                                $query = "SELECT s.day, s.start_time, s.end_time, s.room, 
                                          sub.subject_name, t.nom as teacher_nom, t.prenom as teacher_prenom
                                          FROM schedules s
                                          LEFT JOIN subjects sub ON s.subject_id = sub.id_subject
                                          LEFT JOIN teachers t ON sub.id_teacher = t.id_teacher
                                          WHERE s.id_filiere = '$id_filiere' 
                                          AND s.id_semester = '$id_semester'
                                          ORDER BY s.day, s.start_time";
                                $result = mysqli_query($conn, $query);
                                
                                // Organiser les cours par jour et créneau horaire
                                $schedule = [];
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $day = $row['day'];
                                    $startTime = substr($row['start_time'], 0, 5);
                                    $endTime = substr($row['end_time'], 0, 5);
                                    $timeKey = $startTime . '-' . $endTime;
                                    
                                    if (!isset($schedule[$day])) {
                                        $schedule[$day] = [];
                                    }
                                    
                                    $schedule[$day][$timeKey] = $row;
                                }
                                
                                // Afficher l'emploi du temps
                                foreach ($timeSlots as $timeSlot) {
                                    echo "<tr>";
                                    echo "<td class='time-slot'>" . htmlspecialchars($timeSlot) . "</td>";
                                    
                                    foreach ($days as $day) {
                                        echo "<td>";
                                        $hasClass = false;
                                        
                                        foreach ($schedule as $scheduleDay => $timeslots) {
                                            if ($scheduleDay == $day) {
                                                foreach ($timeslots as $time => $class) {
                                                    list($startTime, $endTime) = explode('-', $time);
                                                    list($timeSlotStart, $timeSlotEnd) = explode('-', $timeSlot);
                                                    
                                                    // Vérifier si le cours est dans ce créneau
                                                    if ($startTime >= $timeSlotStart && $startTime < $timeSlotEnd) {
                                                        echo "<div class='schedule-cell'>";
                                                        echo "<strong>" . htmlspecialchars($class['subject_name']) . "</strong><br>";
                                                        echo "Prof: " . htmlspecialchars($class['teacher_prenom'] . ' ' . $class['teacher_nom']) . "<br>";
                                                        echo "Salle: " . htmlspecialchars($class['room']) . "<br>";
                                                        echo htmlspecialchars($startTime) . " - " . htmlspecialchars($endTime);
                                                        echo "</div>";
                                                        $hasClass = true;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if (!$hasClass) {
                                            echo "<div class='no-class'>Aucun cours</div>";
                                        }
                                        
                                        echo "</td>";
                                    }
                                    
                                    echo "</tr>";
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

