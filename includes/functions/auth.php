<?php
/**
 * Fonctions d'authentification et de gestion des sessions
 */

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est un administrateur
 * 
 * @return bool True si l'utilisateur est un administrateur, false sinon
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Vérifie si l'utilisateur est un enseignant
 * 
 * @return bool True si l'utilisateur est un enseignant, false sinon
 */
function isTeacher() {
    return isLoggedIn() && $_SESSION['user_role'] === 'teacher';
}

/**
 * Vérifie si l'utilisateur est un étudiant
 * 
 * @return bool True si l'utilisateur est un étudiant, false sinon
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['user_role'] === 'student';
}

/**
 * Authentifie un utilisateur
 * 
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe de l'utilisateur
 * @return array|bool Données de l'utilisateur ou false si échec
 */
function authenticateUser($email, $password) {
    $query = "SELECT id, email, password, role, full_name FROM users WHERE email = ?";
    $user = fetchRow($query, [$email]);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        
        return $user;
    }
    
    return false;
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Redirige l'utilisateur selon son rôle
 */
function redirectByRole() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
    
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } elseif (isTeacher()) {
        header("Location: teacher/dashboard.php");
    } elseif (isStudent()) {
        header("Location: student/dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

/**
 * Vérifie les permissions d'accès
 * 
 * @param array $allowedRoles Rôles autorisés
 * @return bool True si l'utilisateur a les permissions, false sinon
 */
function checkPermission($allowedRoles = ['admin']) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return in_array($_SESSION['user_role'], $allowedRoles);
}

/**
 * Génère un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * 
 * @param string $token Token à vérifier
 * @return bool True si le token est valide, false sinon
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}