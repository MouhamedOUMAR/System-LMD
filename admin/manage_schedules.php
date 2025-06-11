<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'un emploi du temps
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $id_semester = mysqli_real_escape_string($conn, $_POST['id_semester']);
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $subject_id = !empty($_POST['subject_id']) ? mysqli_real_escape_string($conn, $_POST['subject_id']) : null;
    $room = mysqli_real_escape_string($conn, $_POST['room']);

    // Vérifier les conflits d'horaire
    $query = "SELECT id_schedule FROM schedules 
              WHERE id_filiere = '$id_filiere' AND id_semester = '$id_semester' AND day = '$day' 
              AND (('$start_time' BETWEEN start_time AND end_time) OR ('$end_time' BETWEEN start_time AND end_time) 
              OR (start_time BETWEEN '$start_time' AND '$end_time'))";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Conflit d'horaire pour cette filière, semestre et jour.";
    } else {
        $query = "INSERT INTO schedules (id_filiere, id_semester, day, start_time, end_time, subject_id, room) 
                  VALUES ('$id_filiere', '$id_semester', '$day', '$start_time', '$end_time', " . ($subject_id ? "'$subject_id'" : "NULL") . ", '$room')";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_schedules.php?success=Emploi du temps ajouté avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'un emploi du temps
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $id_schedule = mysqli_real_escape_string($conn, $_POST['id_schedule']);
    $id_filiere = mysqli_real_escape_string($conn, $_POST['id_filiere']);
    $id_semester = mysqli_real_escape_string($conn, $_POST['id_semester']);
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $subject_id = !empty($_POST['subject_id']) ? mysqli_real_escape_string($conn, $_POST['subject_id']) : null;
    $room = mysqli_real_escape_string($conn, $_POST['room']);

    $query = "SELECT id_schedule FROM schedules 
              WHERE id_filiere = '$id_filiere' AND id_semester = '$id_semester' AND day = '$day' 
              AND (('$start_time' BETWEEN start_time AND end_time) OR ('$end_time' BETWEEN start_time AND end_time) 
              OR (start_time BETWEEN '$start_time' AND '$end_time')) 
              AND id_schedule != '$id_schedule'";
    if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
        $error = "Erreur : Conflit d'horaire pour cette filière, semestre et jour.";
    } else {
        $query = "UPDATE schedules SET 
                  id_filiere = '$id_filiere', id_semester = '$id_semester', day = '$day', 
                  start_time = '$start_time', end_time = '$end_time', 
                  subject_id = " . ($subject_id ? "'$subject_id'" : "NULL") . ", room = '$room' 
                  WHERE id_schedule = '$id_schedule'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_schedules.php?success=Emploi du temps mis à jour avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'un emploi du temps
if (isset($_GET['delete'])) {
    $id_schedule = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM schedules WHERE id_schedule = '$id_schedule'";
    if (mysqli_query($conn, $query)) {
        header("Location: manage_schedules.php?success=Emploi du temps supprimé avec succès");
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
    <title>Gestion des Emplois du Temps - <?php echo SYSTEM_NAME; ?></title>
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
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="fas fa-home"></i> Tableau de Bord</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_faculties.php"><i class="fas fa-university"></i> Facultés</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_filieres.php"><i class="fas fa-graduation-cap"></i> Filières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_students.php"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Enseignants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_modules.php"><i class="fas fa-book-open"></i> Modules</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_subjects.php"><i class="fas fa-book"></i> Matières</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_notes.php"><i class="fas fa-calculator"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_course_materials.php"><i class="fas fa-file-pdf"></i> Supports de Cours</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="manage_schedules.php"><i class="fas fa-clock"></i> Emplois du Temps</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="manage_academic_years.php"><i class="fas fa-calendar-alt"></i> Années Académiques</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Emplois du Temps</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter un Emploi du Temps</h5>
                    <form action="manage_schedules.php" method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="id_faculty" class="form-label">Faculté</label>
                                <select class="form-control" id="id_faculty" name="id_faculty" onchange="loadDepartments()" required>
                                    <option value="">Sélectionner une faculté</option>
                                    <?php
                                    $query = "SELECT id_faculty, faculty_name FROM faculties";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_faculty']}'>" . htmlspecialchars($row['faculty_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_department" class="form-label">Département</label>
                                <select class="form-control" id="id_department" name="id_department" onchange="loadFilieres()" required>
                                    <option value="">Sélectionner un département</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_filiere" class="form-label">Filière</label>
                                <select class="form-control" id="id_filiere" name="id_filiere" onchange="loadSemesters()" required>
                                    <option value="">Sélectionner une filière</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_semester" class="form-label">Semestre</label>
                                <select class="form-control" id="id_semester" name="id_semester" onchange="loadSubjects()" required>
                                    <option value="">Sélectionner un semestre</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="day" class="form-label">Jour</label>
                                <select class="form-control" id="day" name="day" required>
                                    <option value="">Sélectionner un jour</option>
                                    <option value="Lundi">Lundi</option>
                                    <option value="Mardi">Mardi</option>
                                    <option value="Mercredi">Mercredi</option>
                                    <option value="Jeudi">Jeudi</option>
                                    <option value="Vendredi">Vendredi</option>
                                    <option value="Samedi">Samedi</option>
                                    <option value="Dimanche">Dimanche</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="start_time" class="form-label">Heure de Début</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="end_time" class="form-label">Heure de Fin</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="subject_id" class="form-label">Matière</label>
                                <select class="form-control" id="subject_id" name="subject_id">
                                    <option value="">Aucune matière</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="room" class="form-label">Salle</label>
                                <input type="text" class="form-control" id="room" name="room" required>
                            </div>
                        </div>
                        <button type="submit" name="add_schedule" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <h2 class="mb-3">Liste des Emplois du Temps</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Faculté</th>
                                <th>Département</th>
                                <th>Filière</th>
                                <th>Semestre</th>
                                <th>Jour</th>
                                <th>Horaire</th>
                                <th>Matière</th>
                                <th>Salle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT sch.id_schedule, sch.day, sch.start_time, sch.end_time, sch.room, 
                                      f.filiere_name, d.department_name, fac.faculty_name, s.semester_name, sub.subject_name 
                                      FROM schedules sch 
                                      JOIN filieres f ON sch.id_filiere = f.id_filiere 
                                      JOIN departments d ON f.id_department = d.id_department 
                                      JOIN faculties fac ON d.id_faculty = fac.id_faculty 
                                      JOIN semesters s ON sch.id_semester = s.id_semester 
                                      LEFT JOIN subjects sub ON sch.subject_id = sub.id_subject 
                                      ORDER BY fac.faculty_name, d.department_name, f.filiere_name, s.semester_name, sch.day, sch.start_time";
                            $result = mysqli_query($conn, $query);
                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $horaire = htmlspecialchars($row['start_time']) . ' - ' . htmlspecialchars($row['end_time']);
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['faculty_name']) . "</td>
                                        <td>" . htmlspecialchars($row['department_name']) . "</td>
                                        <td>" . htmlspecialchars($row['filiere_name']) . "</td>
                                        <td>" . htmlspecialchars($row['semester_name']) . "</td>
                                        <td>" . htmlspecialchars($row['day']) . "</td>
                                        <td>$horaire</td>
                                        <td>" . ($row['subject_name'] ? htmlspecialchars($row['subject_name']) : '-') . "</td>
                                        <td>" . htmlspecialchars($row['room']) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editSchedule{$row['id_schedule']}'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_schedules.php?delete={$row['id_schedule']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Confirmer la suppression ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";
                                    // Modal pour modification
                                    echo "<div class='modal fade' id='editSchedule{$row['id_schedule']}' tabindex='-1'>
                                        <div class='modal-dialog modal-lg'>
                                            <div class='modal-content'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Modifier Emploi du Temps</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <form action='manage_schedules.php' method='POST'>
                                                        <input type='hidden' name='id_schedule' value='{$row['id_schedule']}'>
                                                        <div class='row'>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='id_faculty_{$row['id_schedule']}' class='form-label'>Faculté</label>
                                                                <select class='form-control' id='id_faculty_{$row['id_schedule']}' name='id_faculty' onchange='loadDepartments({$row['id_schedule']})' required>
                                                                    <option value=''>" . htmlspecialchars($row['faculty_name']) . "</option>";
                                    $fac_query = "SELECT id_faculty, faculty_name FROM faculties";
                                    $fac_result = mysqli_query($conn, $fac_query);
                                    while ($fac = mysqli_fetch_assoc($fac_result)) {
                                        echo "<option value='{$fac['id_faculty']}'>" . htmlspecialchars($fac['faculty_name']) . "</option>";
                                    }
                                    echo "</select>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='id_department_{$row['id_schedule']}' class='form-label'>Département</label>
                                                                <select class='form-control' id='id_department_{$row['id_schedule']}' name='id_department' onchange='loadFilieres({$row['id_schedule']})' required>
                                                                    <option value='{$row['id_department']}'>" . htmlspecialchars($row['department_name']) . "</option>
                                                                </select>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='id_filiere_{$row['id_schedule']}' class='form-label'>Filière</label>
                                                                <select class='form-control' id='id_filiere_{$row['id_schedule']}' name='id_filiere' onchange='loadSemesters({$row['id_schedule']})' required>
                                                                    <option value='{$row['id_filiere']}'>" . htmlspecialchars($row['filiere_name']) . "</option>
                                                                </select>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='id_semester_{$row['id_schedule']}' class='form-label'>Semestre</label>
                                                                <select class='form-control' id='id_semester_{$row['id_schedule']}' name='id_semester' onchange='loadSubjects({$row['id_schedule']})' required>
                                                                    <option value='{$row['id_semester']}'>" . htmlspecialchars($row['semester_name']) . "</option>
                                                                </select>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='day_{$row['id_schedule']}' class='form-label'>Jour</label>
                                                                <select class='form-control' id='day_{$row['id_schedule']}' name='day' required>
                                                                    <option value='{$row['day']}'>" . htmlspecialchars($row['day']) . "</option>
                                                                    <option value='Lundi'>Lundi</option>
                                                                    <option value='Mardi'>Mardi</option>
                                                                    <option value='Mercredi'>Mercredi</option>
                                                                    <option value='Jeudi'>Jeudi</option>
                                                                    <option value='Vendredi'>Vendredi</option>
                                                                    <option value='Samedi'>Samedi</option>
                                                                </select>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='start_time_{$row['id_schedule']}' class='form-label'>Heure de Début</label>
                                                                <input type='time' class='form-control' id='start_time_{$row['id_schedule']}' name='start_time' value='" . htmlspecialchars($row['start_time']) . "' required>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='end_time_{$row['id_schedule']}' class='form-label'>Heure de Fin</label>
                                                                <input type='time' class='form-control' id='end_time_{$row['id_schedule']}' name='end_time' value='" . htmlspecialchars($row['end_time']) . "' required>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='subject_id_{$row['id_schedule']}' class='form-label'>Matière</label>
                                                                <select class='form-control' id='subject_id_{$row['id_schedule']}' name='subject_id'>
                                                                    <option value=''>" . ($row['subject_name'] ? htmlspecialchars($row['subject_name']) : 'Aucune matière') . "</option>
                                                                </select>
                                                            </div>
                                                            <div class='col-md-3 mb-3'>
                                                                <label for='room_{$row['id_schedule']}' class='form-label'>Salle</label>
                                                                <input type='text' class='form-control' id='room_{$row['id_schedule']}' name='room' value='" . htmlspecialchars($row['room']) . "' required>
                                                            </div>
                                                        </div>
                                                        <button type='submit' name='update_schedule' class='btn btn-primary'>Mettre à jour</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr><td colspan='9'>Erreur : " . mysqli_error($conn) . "</td></tr>";
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        function loadDepartments(scheduleId = '') {
            const facultyId = document.getElementById(`id_faculty${scheduleId ? '_' + scheduleId : ''}`).value;
            const departmentSelect = document.getElementById(`id_department${scheduleId ? '_' + scheduleId : ''}`);
            departmentSelect.innerHTML = '<option value="">Sélectionner un département</option>';
            if (facultyId) {
                fetch(`get_departments.php?id_faculty=${facultyId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id_department;
                            option.textContent = dept.department_name;
                            departmentSelect.appendChild(option);
                        });
                    });
            }
        }

        function loadFilieres(scheduleId = '') {
            const departmentId = document.getElementById(`id_department${scheduleId ? '_' + scheduleId : ''}`).value;
            const filiereSelect = document.getElementById(`id_filiere${scheduleId ? '_' + scheduleId : ''}`);
            filiereSelect.innerHTML = '<option value="">Sélectionner une filière</option>';
            if (departmentId) {
                fetch(`get_filieres.php?id_department=${departmentId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(filiere => {
                            const option = document.createElement('option');
                            option.value = filiere.id_filiere;
                            option.textContent = filiere.filiere_name;
                            filiereSelect.appendChild(option);
                        });
                    });
            }
        }

        function loadSemesters(scheduleId = '') {
            const filiereId = document.getElementById(`id_filiere${scheduleId ? '_' + scheduleId : ''}`).value;
            const semesterSelect = document.getElementById(`id_semester${scheduleId ? '_' + scheduleId : ''}`);
            
            // Réinitialiser le sélecteur de semestre
            semesterSelect.innerHTML = '<option value="">Sélectionner un semestre</option>';
            
            if (filiereId) {
                // Utiliser le chemin correct pour get_semesters.php
                fetch(`ajax/get_semesters.php?id_filiere=${filiereId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur réseau');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(semester => {
                                const option = document.createElement('option');
                                option.value = semester.id_semester;
                                option.textContent = semester.semester_name;
                                semesterSelect.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "Aucun semestre disponible";
                            semesterSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        // Afficher un message d'erreur dans le sélecteur
                        semesterSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    });
            }
        }

        function loadSubjects(scheduleId = '') {
            const semesterId = document.getElementById(`id_semester${scheduleId ? '_' + scheduleId : ''}`).value;
            const subjectSelect = document.getElementById(`subject_id${scheduleId ? '_' + scheduleId : ''}`);
            
            // Réinitialiser le sélecteur de matière
            subjectSelect.innerHTML = '<option value="">Aucune matière</option>';
            
            if (semesterId) {
                // Utiliser le chemin correct pour get_subjects.php
                fetch(`ajax/get_subjects.php?id_semester=${semesterId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur réseau');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(subject => {
                                const option = document.createElement('option');
                                option.value = subject.id_subject;
                                option.textContent = subject.subject_name;
                                subjectSelect.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "Aucune matière disponible";
                            subjectSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        // Afficher un message d'erreur dans le sélecteur
                        subjectSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    });
            }
        }
    </script>
    <?php if (isset($_GET['success'])): ?>
        <script>
            showToast("<?php echo htmlspecialchars($_GET['success']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>
