<?php
require '../config.php';
if (!isStudent()) {
    header("Location: ../login.php");
    exit;
}

$id_student = $_SESSION['user_id'];

// Récupérer les informations de l'étudiant
$stmt = $pdo->prepare("SELECT s.*, f.filiere_name, l.level_name 
                      FROM students s 
                      JOIN filieres f ON s.id_filiere = f.id_filiere 
                      JOIN levels l ON f.id_level = l.id_level 
                      WHERE s.id_user = ?");
$stmt->execute([$id_student]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Étudiant non trouvé";
    exit;
}

// Récupérer les semestres
$stmt = $pdo->prepare("SELECT * FROM semesters ORDER BY semester_order");
$stmt->execute();
$semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes - <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .student-info {
            margin-bottom: 20px;
        }
        @media print {
            .btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>BULLETIN DE NOTES</h2>
            <p>Année Académique: 
                <?php 
                $stmt = $pdo->prepare("SELECT * FROM academic_years WHERE is_current = 1");
                $stmt->execute();
                $year = $stmt->fetch(PDO::FETCH_ASSOC);
                echo $year ? htmlspecialchars($year['academic_year_name']) : "Non définie";
                ?>
            </p>
        </div>
        
        <div class="student-info">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Matricule:</strong> <?php echo htmlspecialchars($student['matricule']); ?></p>
                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($student['nom']); ?></p>
                    <p><strong>Prénom:</strong> <?php echo htmlspecialchars($student['prenom']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Filière:</strong> <?php echo htmlspecialchars($student['filiere_name']); ?></p>
                    <p><strong>Niveau:</strong> <?php echo htmlspecialchars($student['level_name'] . ' (' . $student['sub_level'] . ')'); ?></p>
                    <?php if (isset($student['nni']) && !empty($student['nni'])): ?>
                    <p><strong>NNI:</strong> <?php echo htmlspecialchars($student['nni']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="bulletin-content">
            <?php
            foreach ($semesters as $semester) {
                echo "<div class='card shadow-sm mb-4'>
                    <div class='card-body'>
                        <h5 class='card-title'>{$semester['semester_name']}</h5>
                        <table class='table table-hover'>
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Note CC</th>
                                    <th>Note Examen</th>
                                    <th>Moyenne</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>";
                $stmt = $pdo->prepare("SELECT n.note_cc, n.note_examen, n.moyenne, n.status, m.nom_module 
                                    FROM notes n 
                                    JOIN modules m ON n.id_module = m.id_module 
                                    WHERE n.id_student = ? AND m.id_semester = ?");
                $stmt->execute([$student['id_student'], $semester['id_semester']]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Calculer la moyenne si elle n'est pas définie
                    if (empty($row['moyenne']) && isset($row['note_cc']) && isset($row['note_examen'])) {
                        $row['moyenne'] = round(($row['note_cc'] * 0.4) + ($row['note_examen'] * 0.6), 2);
                    }
                    
                    // Déterminer le statut si non défini
                    if (empty($row['status']) && isset($row['moyenne'])) {
                        if ($row['moyenne'] >= 10) {
                            $row['status'] = 'Validé';
                        } elseif ($row['moyenne'] >= 7) {
                            $row['status'] = 'Rattrapage';
                        } else {
                            $row['status'] = 'Non validé';
                        }
                    }
                    
                    echo "<tr>
                        <td>{$row['nom_module']}</td>
                        <td>{$row['note_cc']}</td>
                        <td>{$row['note_examen']}</td>
                        <td>{$row['moyenne']}</td>
                        <td>{$row['status']}</td>
                    </tr>";
                }
                echo "</tbody></table>";

                $stmt = $pdo->prepare("SELECT moyenne_semestre, status FROM results WHERE id_student = ? AND id_semester = ?");
                $stmt->execute([$student['id_student'], $semester['id_semester']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    echo "<p><strong>Moyenne Semestre :</strong> {$result['moyenne_semestre']}</p>
                        <p><strong>Statut :</strong> {$result['status']}</p>";
                }
                echo "</div></div>";
            }
            ?>
            <button class="btn btn-primary" onclick="generatePDF()"><i class="fas fa-download"></i> Télécharger en PDF</button>
        </div>
    </div>

    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script src="../assets/jspdf.umd.min.js"></script>
    <script src="../assets/html2canvas.min.js"></script>
    <script>
        function generatePDF() {
            // Utiliser html2canvas pour capturer le contenu
            html2canvas(document.querySelector('.container')).then(canvas => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                
                // Dimensions de la page A4 en mm
                const pageWidth = 210;
                const pageHeight = 297;
                
                // Dimensions du canvas
                const canvasWidth = canvas.width;
                const canvasHeight = canvas.height;
                
                // Calculer le ratio pour adapter le canvas à la page
                const ratio = Math.min(pageWidth / canvasWidth, pageHeight / canvasHeight);
                
                // Calculer les nouvelles dimensions
                const imgWidth = canvasWidth * ratio;
                const imgHeight = canvasHeight * ratio;
                
                // Centrer l'image sur la page
                const x = (pageWidth - imgWidth) / 2;
                const y = 10; // Marge supérieure
                
                // Ajouter l'image au PDF
                const imgData = canvas.toDataURL('image/png');
                doc.addImage(imgData, 'PNG', x, y, imgWidth, imgHeight);
                
                // Télécharger le PDF
                doc.save("bulletin_<?php echo htmlspecialchars($student['matricule']); ?>.pdf");
            });
        }
    </script>
</body>
</html>
