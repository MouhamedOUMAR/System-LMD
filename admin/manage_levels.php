<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Ajout d'un niveau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_level'])) {
    $level_name = mysqli_real_escape_string($conn, $_POST['level_name']);

    // Vérifier si le niveau existe déjà
    $check_query = "SELECT id_level FROM levels WHERE level_name = '$level_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Erreur : Ce niveau existe déjà.";
    } else {
        $query = "INSERT INTO levels (level_name) VALUES ('$level_name')";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_levels.php?success=Niveau ajouté avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Mise à jour d'un niveau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_level'])) {
    $id_level = mysqli_real_escape_string($conn, $_POST['id_level']);
    $level_name = mysqli_real_escape_string($conn, $_POST['level_name']);

    // Vérifier si le niveau existe déjà (sauf lui-même)
    $check_query = "SELECT id_level FROM levels WHERE level_name = '$level_name' AND id_level != '$id_level'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Erreur : Un niveau avec ce nom existe déjà.";
    } else {
        $query = "UPDATE levels SET level_name = '$level_name' WHERE id_level = '$id_level'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_levels.php?success=Niveau mis à jour avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Suppression d'un niveau
if (isset($_GET['delete'])) {
    $id_level = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Vérifier si le niveau est utilisé
    $check_query = "SELECT id_filiere FROM filieres WHERE id_level = '$id_level'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        header("Location: manage_levels.php?error=Impossible de supprimer ce niveau car il est utilisé par des filières");
        exit;
    } else {
        $query = "DELETE FROM levels WHERE id_level = '$id_level'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_levels.php?success=Niveau supprimé avec succès");
            exit;
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Niveaux - Système LMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">Gestion des Niveaux</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ajouter un Niveau</h5>
                    <form action="manage_levels.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="level_name" class="form-label">Nom du Niveau</label>
                                <input type="text" class="form-control" id="level_name" name="level_name" required>
                            </div>
                        </div>
                        <button type="submit" name="add_level" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Liste des Niveaux</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom du Niveau</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT id_level, level_name FROM levels ORDER BY level_name";
                            $result = mysqli_query($conn, $query);
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['level_name']) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editLevel{$row['id_level']}'><i class='fas fa-edit'></i> Modifier</button>
                                            <a href='manage_levels.php?delete={$row['id_level']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce niveau ?\")'><i class='fas fa-trash'></i> Supprimer</a>
                                        </td>
                                    </tr>";

                                    // Modal pour modification
                                    echo "<div class='modal fade' id='editLevel{$row['id_level']}' tabindex='-1'>
                                        <div class='modal-dialog'>
                                            <div class='modal-content'>
                                                <div class='modal-header'>
                                                    <h5 class='modal-title'>Modifier le Niveau</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <form action='manage_levels.php' method='POST'>
                                                        <input type='hidden' name='id_level' value='{$row['id_level']}'>
                                                        <div class='mb-3'>
                                                            <label for='level_name_{$row['id_level']}' class='form-label'>Nom du Niveau</label>
                                                            <input type='text' class='form-control' id='level_name_{$row['id_level']}' name='level_name' value='" . htmlspecialchars($row['level_name']) . "' required>
                                                        </div>
                                                        <button type='submit' name='update_level' class='btn btn-primary'>Mettre à jour</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<tr><td colspan='2'>Aucun niveau trouvé.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 