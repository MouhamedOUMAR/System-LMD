<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id_student'])) {
    echo '<div class="alert alert-danger">ID étudiant non spécifié</div>';
    exit;
}

$id_student = mysqli_real_escape_string($conn, $_GET['id_student']);

// Récupérer les informations de l'étudiant
$student_query = "SELECT s.*, f.filiere_name, l.level_name 
                 FROM students s 
                 JOIN filieres f ON s.id_filiere = f.id_filiere 
                 JOIN levels l ON f.id_level = l.id_level 
                 WHERE s.id_student = '$id_student'";
$student_result = mysqli_query($conn, $student_query);

if (!$student_result || mysqli_num_rows($student_result) == 0) {
    echo '<div class="alert alert-danger">Étudiant non trouvé</div>';
    exit;
}

$student = mysqli_fetch_assoc($student_result);

// Récupérer les semestres
$semesters_query = "SELECT * FROM semesters ORDER BY semester_order";
$semesters_result = mysqli_query($conn, $semesters_query);

// Vérifier si la requête a réussi
if (!$semesters_result) {
    // Si la colonne semester_order n'existe pas, utiliser une requête alternative
    $semesters_query = "SELECT * FROM semesters";
    $semesters_result = mysqli_query($conn, $semesters_query);
    
    if (!$semesters_result) {
        echo '<div class="alert alert-danger">Erreur lors de la récupération des semestres: ' . mysqli_error($conn) . '</div>';
        exit;
    }
}

$semesters = [];
while ($semester = mysqli_fetch_assoc($semesters_result)) {
    $semesters[$semester['id_semester']] = $semester;
}

// Récupérer les notes par semestre
$notes_query = "SELECT n.*, s.subject_name, s.coefficient as credit, s.id_semester, s.id_module, 
                      m.module_name, sem.semester_name
               FROM notes n 
               JOIN subjects s ON n.id_subject = s.id_subject 
               JOIN modules m ON s.id_module = m.id_module 
               JOIN semesters sem ON m.id_semester = sem.id_semester
               WHERE n.id_student = '$id_student'";

// Vérifier si la colonne credit existe
$check_credit = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'credit'");
if (mysqli_num_rows($check_credit) > 0) {
    $notes_query = "SELECT n.*, s.subject_name, s.credit, s.id_semester, s.id_module, 
                          m.module_name, sem.semester_name
                   FROM notes n 
                   JOIN subjects s ON n.id_subject = s.id_subject 
                   JOIN modules m ON s.id_module = m.id_module 
                   JOIN semesters sem ON m.id_semester = sem.id_semester
                   WHERE n.id_student = '$id_student'";
}

// Ajouter l'ordre si disponible
$check_order = mysqli_query($conn, "SHOW COLUMNS FROM semesters LIKE 'semester_order'");
if (mysqli_num_rows($check_order) > 0) {
    $notes_query .= " ORDER BY sem.semester_order, m.module_name, s.subject_name";
} else {
    $notes_query .= " ORDER BY sem.semester_name, m.module_name, s.subject_name";
}

$notes_result = mysqli_query($conn, $notes_query);

// Vérifier si la requête a réussi
if (!$notes_result) {
    echo '<div class="alert alert-danger">Erreur lors de la récupération des notes: ' . mysqli_error($conn) . '</div>';
    exit;
}

// Organiser les notes par semestre et par module
$notes_by_semester = [];
$semester_credits = [];
$semester_totals = [];
$semester_averages = [];

while ($note = mysqli_fetch_assoc($notes_result)) {
    $id_semester = $note['id_semester'];
    $id_module = $note['id_module'];
    
    if (!isset($notes_by_semester[$id_semester])) {
        $notes_by_semester[$id_semester] = [];
        $semester_credits[$id_semester] = 0;
        $semester_totals[$id_semester] = 0;
    }
    
    if (!isset($notes_by_semester[$id_semester][$id_module])) {
        $notes_by_semester[$id_semester][$id_module] = [
            'module_name' => $note['module_name'],
            'subjects' => [],
            'module_total' => 0,
            'module_credits' => 0
        ];
    }
    
    // Calculer la note finale si elle n'est pas définie
    if (!isset($note['note_finale']) || $note['note_finale'] == 0) {
        $note['note_finale'] = ($note['note_devoir'] * 0.4) + ($note['note_examen'] * 0.6);
    }
    
    // Ajouter la matière au module
    $notes_by_semester[$id_semester][$id_module]['subjects'][] = $note;
    
    // Mettre à jour les totaux du module
    $notes_by_semester[$id_semester][$id_module]['module_total'] += $note['note_finale'] * $note['credit'];
    $notes_by_semester[$id_semester][$id_module]['module_credits'] += $note['credit'];
    
    // Mettre à jour les totaux du semestre
    $semester_totals[$id_semester] += $note['note_finale'] * $note['credit'];
    $semester_credits[$id_semester] += $note['credit'];
}

// Calculer les moyennes par semestre
foreach ($semester_credits as $id_semester => $credits) {
    if ($credits > 0) {
        $semester_averages[$id_semester] = round($semester_totals[$id_semester] / $credits, 2);
    } else {
        $semester_averages[$id_semester] = 0;
    }
}

// Calculer les moyennes annuelles (pour chaque paire de semestres)
$annual_averages = [];
$annual_credits = [];
$annual_decisions = [];

// Regrouper les semestres par année (S1+S2, S3+S4, etc.)
$semester_pairs = [];
foreach ($semesters as $id_semester => $semester) {
    // Utiliser semester_order si disponible, sinon extraire le numéro du nom du semestre
    $semester_num = isset($semester['semester_order']) ? $semester['semester_order'] : 
                   (preg_match('/(\d+)/', $semester['semester_name'], $matches) ? $matches[1] : 1);
    
    $year = floor(($semester_num - 1) / 2);
    if (!isset($semester_pairs[$year])) {
        $semester_pairs[$year] = [];
    }
    $semester_pairs[$year][] = $id_semester;
}

// Calculer les moyennes annuelles
foreach ($semester_pairs as $year => $semester_ids) {
    $annual_total = 0;
    $annual_credit = 0;
    
    foreach ($semester_ids as $id_semester) {
        if (isset($semester_totals[$id_semester])) {
            $annual_total += $semester_totals[$id_semester];
            $annual_credit += $semester_credits[$id_semester];
        }
    }
    
    if ($annual_credit > 0) {
        $annual_average = round($annual_total / $annual_credit, 2);
        $annual_averages[$year] = $annual_average;
        $annual_credits[$year] = $annual_credit;
        
        // Déterminer la décision du jury
        if ($annual_average >= 10) {
            $annual_decisions[$year] = $annual_average >= 12 ? 'Admis' : 'Admis avec dette';
        } else {
            $annual_decisions[$year] = 'Ajourné';
        }
    }
}

// Afficher le relevé de notes
?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="print-header">
            <h2 class="text-center mb-4">RELEVE DE NOTES</h2>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Nom et prénom:</strong> <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></p>
                <p><strong>Date de Naissance:</strong> <?php echo htmlspecialchars($student['date_naissance'] ?? 'Non renseigné'); ?></p>
                <?php if (isset($student['lieu_naissance']) && !empty($student['lieu_naissance'])): ?>
                <p><strong>Lieu de Naissance:</strong> <?php echo htmlspecialchars($student['lieu_naissance']); ?></p>
                <?php endif; ?>
                <p><strong>Numéro d'inscription:</strong> <?php echo htmlspecialchars($student['matricule']); ?></p>
                <p><strong>Filière:</strong> <?php echo htmlspecialchars($student['filiere_name']); ?></p>
                <p><strong>Niveau:</strong> <?php echo htmlspecialchars($student['level_name'] . ' (' . $student['sub_level'] . ')'); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <?php
                if (isset($student['nni']) && !empty($student['nni'])) {
                    echo '<p><strong>NNI:</strong> ' . htmlspecialchars($student['nni']) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <?php
        // Afficher les notes par semestre
        $semester_count = 0;
        foreach ($notes_by_semester as $id_semester => $modules):
            $semester = $semesters[$id_semester];
            $semester_count++;
            
            // Commencer une nouvelle ligne tous les 2 semestres
            if ($semester_count % 2 == 1):
        ?>
        <div class="row">
        <?php endif; ?>
            
            <div class="col-md-6">
                <h4 class="text-center"><?php echo htmlspecialchars($semester['semester_name']); ?></h4>
                <table class="table table-bordered table-notes">
                    <thead>
                        <tr>
                            <th rowspan="2">Libellé de la Matière</th>
                            <th colspan="3">Notes</th>
                            <th rowspan="2">Crédit</th>
                            <th rowspan="2">Décisions</th>
                        </tr>
                        <tr>
                            <th>Contrôle Continu</th>
                            <th>Contrôle Final</th>
                            <th>Note Finale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_credits = 0;
                        foreach ($modules as $id_module => $module): 
                            // Afficher l'en-tête du module
                            echo "<tr class='module-header'><td colspan='6'><strong>" . htmlspecialchars($module['module_name']) . "</strong></td></tr>";
                            
                            // Afficher les matières du module
                            $module_total = 0;
                            $module_credits = 0;
                            
                            foreach ($module['subjects'] as $subject):
                                $total_credits += $subject['credit'];
                                $module_credits += $subject['credit'];
                                $status = isset($subject['status']) && !empty($subject['status']) ? $subject['status'] : 'Validé';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td><?php echo number_format($subject['note_devoir'], 1); ?></td>
                            <td><?php echo number_format($subject['note_examen'], 1); ?></td>
                            <td><?php echo number_format($subject['note_finale'], 1); ?></td>
                            <td><?php echo number_format($subject['credit'], 1); ?></td>
                            <td><?php echo htmlspecialchars($status); ?></td>
                        </tr>
                        <?php 
                            endforeach;
                            
                            // Calculer la moyenne du module
                            if ($module['module_credits'] > 0) {
                                $module_average = round($module['module_total'] / $module['module_credits'], 2);
                                echo "<tr class='module-average'><td colspan='3'>Moyenne du module</td><td>" . number_format($module_average, 2) . "</td><td>" . number_format($module_credits, 1) . "</td><td></td></tr>";
                            }
                        endforeach; 
                        ?>
                        <tr class="semester-average">
                            <td colspan="3">Moyenne du semestre / 20</td>
                            <td><?php echo number_format($semester_averages[$id_semester], 2); ?></td>
                            <td colspan="2"><?php echo number_format($total_credits, 1); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        <?php 
            // Fermer la ligne tous les 2 semestres ou à la fin
            if ($semester_count % 2 == 0 || $semester_count == count($notes_by_semester)):
        ?>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>
        
        <!-- Afficher les moyennes annuelles -->
        <?php if (!empty($annual_averages)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Total des crédits obtenus</th>
                            <th>Moyenne annuelle</th>
                            <th>Décision du jury</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($annual_averages as $year => $average): ?>
                        <tr>
                            <td>L<?php echo $year + 1; ?></td>
                            <td><?php echo $annual_credits[$year]; ?></td>
                            <td><?php echo number_format($average, 2); ?></td>
                            <td><?php echo $annual_decisions[$year]; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="print-footer">
            <div class="print-signatures">
                <div>
                    <p>Pour le Directeur et P.O</p>
                    <p>Le Directeur des Études</p>
                </div>
            </div>
        </div>
    </div>
</div>


