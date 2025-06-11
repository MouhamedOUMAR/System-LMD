<?php
// Configuration de sécurité

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(0);

// Configuration des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Durée de vie de la session (en secondes)
ini_set('session.gc_maxlifetime', 3600);

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Erreur de sécurité : Token CSRF invalide');
    }
    return true;
}

// Fonction pour échapper les données HTML
function escapeHTML($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction pour valider les types de fichiers
function validateFileType($file, $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx']) {
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($fileType, $allowedTypes);
}

// Fonction pour valider la taille du fichier (max 10MB)
function validateFileSize($file, $maxSize = 10485760) {
    return $file['size'] <= $maxSize;
}

// Fonction pour sécuriser les mots de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Fonction pour vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour générer un mot de passe aléatoire
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Fonction pour valider les entrées utilisateur
function validateInput($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        default:
            return is_string($data) && !empty(trim($data));
    }
}

// Fonction pour limiter les tentatives de connexion
function checkLoginAttempts($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = 0;
    }
    
    $_SESSION['login_attempts'][$username]++;
    
    if ($_SESSION['login_attempts'][$username] >= 5) {
        $_SESSION['login_blocked'] = time() + 1800; // Blocage pendant 30 minutes
        return false;
    }
    
    return true;
}

// Fonction pour vérifier si l'utilisateur est bloqué
function isUserBlocked() {
    return isset($_SESSION['login_blocked']) && $_SESSION['login_blocked'] > time();
}

// Fonction pour réinitialiser les tentatives de connexion
function resetLoginAttempts($username) {
    if (isset($_SESSION['login_attempts'][$username])) {
        unset($_SESSION['login_attempts'][$username]);
    }
    if (isset($_SESSION['login_blocked'])) {
        unset($_SESSION['login_blocked']);
    }
} 