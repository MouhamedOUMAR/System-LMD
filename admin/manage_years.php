<?php
require '../config.php';
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Rediriger vers la nouvelle page de gestion des années académiques
header("Location: manage_academic_years.php");
exit;
?>
