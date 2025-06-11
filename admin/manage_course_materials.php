<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Créer le dossier uploads s'il n'existe pas
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Ajout d'un support de cours
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_subject = mysqli_real_escape_string($conn, $_POST['id_subject']);
    $file_name = mysqli_real_escape_string($conn, $_FILES['file']['name']);
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['pdf', 'doc', 'docx']; // Extensions autorisées

    // Vérifier l'extension du fichier
    if (!in_array($file_ext, $allowed_ext)) {
        $error = "Erreur : Seuls les fichiers PDF, DOC et DOCX sont autorisés.";
    } else {
        // Générer un nom de fichier unique pour éviter les conflits
        $new_file_name = uniqid() . '_' . $file_name;
        $file_path = $upload_dir . $new_file_name;

        // Déplacer le fichier vers le dossier uploads
        if (move_uploaded_file($file_tmp, $file_path)) {
            $query = "INSERT INTO course_materials (id_subject, file_name, file_path) VALUES ('$id_subject', '$file_name', '$file_path')";
            if (mysqli_query($conn, $query)) {
                header("Location: manage_course_materials.php?success=Support de cours ajouté avec succès");
            } else {
                $error = "Erreur : " . mysqli_error($conn);
                // Supprimer le fichier s'il n'a pas été inséré dans la base
                unlink($file_path);
            }
        } else {
            $error = "Erreur : Impossible de télécharger le fichier.";
        }
    }
}

// Suppression d'un support de cours
if (isset($_GET['delete'])) {
    $id_material = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Récupérer le chemin du fichier pour le supprimer
    $query = "SELECT file_path FROM course_materials WHERE id_material = '$id_material'";
    $result = mysqli_query($conn, $query);
    $material = mysqli_fetch_assoc($result);

    if ($material) {
        // Supprimer le fichier du serveur
        if (file_exists($material['file_path'])) {
            unlink($material['file_path']);
        }
        
        // Supprimer l'enregistrement de la base de données
        $query = "DELETE FROM course_materials WHERE id_material = '$id_material'";
        if (mysqli_query($conn, $query)) {
            header("Location: manage_course_materials.php?success=Support de cours supprimé avec succès");
        } else {
            $error = "Erreur : " . mysqli_error($conn);
        }
    } else {
        $error = "Erreur : Support de cours introuvable.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Supports de Cours - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="content flex-grow-1 p-4">
            <h1 class="mb-4 text-primary">
                <i class="fas fa-file-pdf me-2"></i>Gestion des Supports de Cours
            </h1>
            <!-- Toast Container -->
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div id="mainToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong class="me-auto">Notification</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body"></div>
                </div>
            </div>
            <?php if (isset($error)) : ?>
                <script>
                    window.addEventListener('DOMContentLoaded', function() {
                        var toastEl = document.getElementById('mainToast');
                        toastEl.querySelector('.toast-header i').className = 'fas fa-times-circle text-danger me-2';
                        toastEl.querySelector('.me-auto').textContent = 'Erreur';
                        toastEl.querySelector('.toast-body').textContent = <?php echo json_encode($error); ?>;
                        var toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    });
                </script>
            <?php endif; ?>
            <?php if (isset($_GET['success'])) : ?>
                <script>
                    window.addEventListener('DOMContentLoaded', function() {
                        var toastEl = document.getElementById('mainToast');
                        toastEl.querySelector('.toast-header i').className = 'fas fa-check-circle text-success me-2';
                        toastEl.querySelector('.me-auto').textContent = 'Succès';
                        toastEl.querySelector('.toast-body').textContent = <?php echo json_encode($_GET['success']); ?>;
                        var toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    });
                </script>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter un Support de Cours
                    </h5>
                </div>
                <div class="card-body">
                    <form action="manage_course_materials.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_subject" class="form-label">
                                    <i class="fas fa-book me-1"></i>Matière
                                </label>
                                <select class="form-select" id="id_subject" name="id_subject" required>
                                    <option value="">Sélectionner une matière</option>
                                    <?php
                                    $query = "SELECT s.id_subject, s.subject_name, m.module_name 
                                              FROM subjects s 
                                              JOIN modules m ON s.id_module = m.id_module
                                              ORDER BY m.module_name, s.subject_name";
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id_subject']}'>{$row['module_name']} - {$row['subject_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="file" class="form-label">
                                    <i class="fas fa-file me-1"></i>Fichier (PDF, DOC, DOCX)
                                </label>
                                <input type="file" class="form-control" id="file" name="file" accept=".pdf,.doc,.docx" required
                                       onchange="validateFile(this)">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Taille maximale : 10MB. Formats acceptés : PDF, DOC, DOCX
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>Uploader
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Liste des Supports de Cours
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Module</th>
                                <th>Nom du Fichier</th>
                                <th>Date d'Ajout</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT cm.id_material, cm.file_name, cm.file_path, cm.uploaded_at, s.subject_name, m.module_name 
                                      FROM course_materials cm 
                                      JOIN subjects s ON cm.id_subject = s.id_subject 
                                          JOIN modules m ON s.id_module = m.id_module
                                          ORDER BY cm.uploaded_at DESC";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                    $file_ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
                                    $file_icon = '';
                                    switch($file_ext) {
                                        case 'pdf':
                                            $file_icon = 'fa-file-pdf text-danger';
                                            break;
                                        case 'doc':
                                        case 'docx':
                                            $file_icon = 'fa-file-word text-primary';
                                            break;
                                        default:
                                            $file_icon = 'fa-file text-secondary';
                                    }
                                echo "<tr>
                                    <td>{$row['subject_name']}</td>
                                    <td>{$row['module_name']}</td>
                                        <td>
                                            <a href='{$row['file_path']}' target='_blank' class='text-decoration-none'>
                                                <i class='fas {$file_icon} me-2'></i>
                                                {$row['file_name']}
                                            </a>
                                        </td>
                                    <td>" . date('d/m/Y H:i', strtotime($row['uploaded_at'])) . "</td>
                                    <td>
                                            <a href='{$row['file_path']}' target='_blank' class='btn btn-sm btn-info me-1' title='Voir'>
                                                <i class='fas fa-eye'></i>
                                            </a>
                                            <a href='manage_course_materials.php?delete={$row['id_material']}' 
                                               class='btn btn-sm btn-danger' 
                                               onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce support de cours ?\")'
                                               title='Supprimer'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                    </td>
                                </tr>";
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
        <script>
    function validateFile(input) {
        const file = input.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['.pdf', '.doc', '.docx'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
            alert('Le fichier est trop volumineux. Taille maximale : 10MB');
            input.value = '';
            return false;
        }
        
        if (!allowedTypes.includes(fileExt)) {
            alert('Format de fichier non autorisé. Formats acceptés : PDF, DOC, DOCX');
            input.value = '';
            return false;
        }
        
        return true;
    }
        </script>
</body>
</html>