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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relevé de Notes - <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .table-notes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-notes th, .table-notes td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table-notes th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .module-header {
            background-color: #f8f9fa;
        }
        .module-average {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .semester-average {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .annual-average {
            background-color: #dee2e6;
            font-weight: bold;
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>RELEVÉ DE NOTES</h2>
            <p>Année Académique: <?php 
                $query = "SELECT * FROM academic_years WHERE is_current = 1";
                $result = mysqli_query($conn, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    $year = mysqli_fetch_assoc($result);
                    echo htmlspecialchars($year['academic_year_name']);
                } else {
                    echo "Non définie";
                }
            ?></p>
        </div>

        <div class="student-info row">
            <div class="col-md-6">
                <p><strong>Nom et prénom:</strong> <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></p>
                <p><strong>Date de Naissance:</strong> <?php echo htmlspecialchars($student['date_naissance'] ?? 'Non renseigné'); ?></p>
                <?php if (isset($student['lieu_naissance']) && !empty($student['lieu_naissance'])): ?>
                <p><strong>Lieu de Naissance:</strong> <?php echo htmlspecialchars($student['lieu_naissance']); ?></p>
                <?php endif; ?>
                <p><strong>Numéro d'inscription:</strong> <?php echo htmlspecialchars($student['matricule']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Filière:</strong> <?php echo htmlspecialchars($student['filiere_name']); ?></p>
                <p><strong>Niveau:</strong> <?php echo htmlspecialchars($student['level_name'] . ' (' . $student['sub_level'] . ')'); ?></p>
                <?php if (isset($student['nni']) && !empty($student['nni'])): ?>
                <p><strong>NNI:</strong> <?php echo htmlspecialchars($student['nni']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Inclure le contenu du relevé de notes
        include 'get_student_notes.php';
        ?>

        <div class="signatures">
            <div>
                <p>Le Directeur des Études</p>
                <br><br>
                <p>_______________________</p>
            </div>
            <div>
                <p>Le Directeur</p>
                <br><br>
                <p>_______________________</p>
            </div>
        </div>
    </div>

    <script>
        // Imprimer automatiquement
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>