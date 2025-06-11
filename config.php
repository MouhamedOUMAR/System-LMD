<?php
// Nom générique du système
define('SYSTEM_NAME', 'Système LMD');

// Vérifier si la session n'est pas déjà active avant de la démarrer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de la connexion à la base de données
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lmd_system';

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Fonction pour vérifier si l'utilisateur est un admin
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour vérifier si l'utilisateur est un étudiant
function isStudent() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Fonction pour vérifier si l'utilisateur est un enseignant
function isTeacher() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}
?>