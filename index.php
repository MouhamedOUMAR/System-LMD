<?php
require 'config.php';

// Récupérer la liste des facultés, départements et filières
$faculties = [];
$query = "SELECT fac.id_faculty, fac.faculty_name, d.id_department, d.department_name, f.id_filiere, f.filiere_name 
          FROM faculties fac 
          LEFT JOIN departments d ON fac.id_faculty = d.id_faculty 
          LEFT JOIN filieres f ON d.id_department = f.id_department 
          ORDER BY fac.faculty_name, d.department_name, f.filiere_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $faculties[$row['id_faculty']]['name'] = $row['faculty_name'];
        if ($row['id_department']) {
            $faculties[$row['id_faculty']]['departments'][$row['id_department']]['name'] = $row['department_name'];
            if ($row['id_filiere']) {
                $faculties[$row['id_faculty']]['departments'][$row['id_department']]['filieres'][] = [
                    'id_filiere' => $row['id_filiere'],
                    'filiere_name' => $row['filiere_name']
                ];
            }
        }
    }
} else {
    $error = "Erreur lors de la récupération des données : " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - <?php echo SYSTEM_NAME; ?></title>
    <meta name="description" content="Système de gestion académique LMD - Licence, Master, Doctorat">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        .header-banner {
            background: url('https://source.unsplash.com/random/1920x1080/?university') no-repeat center center;
            background-size: cover;
            color: white;
            text-align: center;
            padding: 100px 20px;
            position: relative;
            overflow: hidden;
        }
        .header-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
        }
        .header-banner h1 {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
        }
        .header-banner p {
            font-size: 1.5rem;
            opacity: 0.9;
            position: relative;
        }
        .card-custom {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .navbar {
            background: rgba(0, 0, 0, 0.8) !important;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-link {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo SYSTEM_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Connexion</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="header-banner">
        <h1>LMD</h1>
        <p>Licence - Master - Doctorat</p>
    </div>
    <div class="container mt-5">
        <p class="text-center lead mb-5">Découvrez notre système de gestion académique structuré selon le système LMD.</p>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card card-custom shadow-sm">
                    <img src="images/licence.jpg" class="card-img-top" alt="Education">
                    <div class="card-body">
                        <h5 class="card-title">Licence</h5>
                        <p class="card-text">Formation de premier cycle universitaire.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card card-custom shadow-sm">
                    <img src="images/master.jpg" class="card-img-top" alt="University">
                    <div class="card-body">
                        <h5 class="card-title">Master</h5>
                        <p class="card-text">Formation de deuxième cycle universitaire.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card card-custom shadow-sm">
                    <img src="images/doctorat.jpg" class="card-img-top" alt="Research">
                    <div class="card-body">
                        <h5 class="card-title">Doctorat</h5>
                        <p class="card-text">Formation de troisième cycle universitaire.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>