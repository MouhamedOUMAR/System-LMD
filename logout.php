<?php
require 'config.php';

// Détruire la session
session_unset();
session_destroy();

// Rediriger vers la page de connexion
header("Location: login.php");
exit;
?>