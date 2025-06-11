<?php
require 'config.php';

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } elseif (isStudent()) {
        header("Location: student/dashboard.php");
    } elseif (isTeacher()) {
        header("Location: teacher/dashboard.php");
    }
    exit;
}

// Gérer le formulaire de connexion
$error = '';
$input_username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Requête préparée pour éviter l'injection SQL
    $stmt = mysqli_prepare($conn, "SELECT id_user, username, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, 's', $input_username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Vérifier le mot de passe sans hachage
        if ($password === $row['password']) {
            // Initialiser la session
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['role'] = $row['role'];
            // Rediriger selon le rôle
            if ($row['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($row['role'] === 'student') {
                header("Location: student/dashboard.php");
            } elseif ($row['role'] === 'teacher') {
                header("Location: teacher/dashboard.php");
            }
            exit;
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { 
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0; 
            animation: gradientBG 15s ease infinite; 
            position: relative; 
            overflow: hidden; 
        }
        .card { 
            border-radius: 1rem; 
            animation: fadeIn 0.5s ease-in-out; 
            background: rgba(255, 255, 255, 0.9); 
        }
        .form-label { font-weight: 500; }
        .btn-primary { 
            background: #0056b3; 
            border: none; 
            transition: all 0.3s ease; 
        }
        .btn-primary:hover { 
            background: #003d82; 
            transform: translateY(-2px); 
        }
        .btn-primary:disabled { background: #7da2d6; }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(-20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        .alert { 
            animation: pulse 0.5s ease-in-out; 
        }
        @keyframes pulse { 
            0% { transform: scale(1); } 
            50% { transform: scale(1.05); } 
            100% { transform: scale(1); } 
        }
        @keyframes gradientBG { 
            0% { background-position: 0% 50%; } 
            50% { background-position: 100% 50%; } 
            100% { background-position: 0% 50%; } 
        }
        .particle { 
            position: absolute; 
            background: rgba(255, 255, 255, 0.5); 
            border-radius: 50%; 
            animation: float 10s infinite; 
        }
        @keyframes float { 
            0% { transform: translateY(0) rotate(0deg); } 
            50% { transform: translateY(-20px) rotate(180deg); } 
            100% { transform: translateY(0) rotate(360deg); } 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Connexion à <?php echo SYSTEM_NAME; ?></h3>
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form action="login.php" method="POST" id="loginForm" autocomplete="username">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus value="<?php echo htmlspecialchars($input_username); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Se connecter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
    // Désactive le bouton lors de la soumission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        var btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Connexion...';
    });

    // Ajouter des particules flottantes
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.width = Math.random() * 10 + 'px';
        particle.style.height = particle.style.width;
        particle.style.left = Math.random() * 100 + 'vw';
        particle.style.top = Math.random() * 100 + 'vh';
        document.body.appendChild(particle);
    }
    </script>
</body>
</html>
