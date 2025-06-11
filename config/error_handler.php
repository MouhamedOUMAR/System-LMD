<?php
// Gestionnaire d'erreurs personnalisé
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/../logs/error.log';
    $date = date('Y-m-d H:i:s');
    
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $errorType = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'Unknown Error';
    
    $errorMessage = sprintf(
        "[%s] %s: %s in %s on line %d\n",
        $date,
        $errorType,
        $errstr,
        $errfile,
        $errline
    );
    
    // Écrire dans le fichier de log
    error_log($errorMessage, 3, $logFile);
    
    // En production, ne pas afficher les erreurs
    if (ini_get('display_errors')) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Erreur :</strong> Une erreur est survenue. Veuillez contacter l'administrateur.";
        echo "</div>";
    }
    
    return true;
}

// Gestionnaire d'exceptions personnalisé
function customExceptionHandler($exception) {
    $logFile = __DIR__ . '/../logs/exception.log';
    $date = date('Y-m-d H:i:s');
    
    $errorMessage = sprintf(
        "[%s] Exception: %s in %s on line %d\nStack trace:\n%s\n",
        $date,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    // Écrire dans le fichier de log
    error_log($errorMessage, 3, $logFile);
    
    // En production, ne pas afficher les détails de l'exception
    if (ini_get('display_errors')) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Exception :</strong> Une erreur est survenue. Veuillez contacter l'administrateur.";
        echo "</div>";
    }
}

// Enregistrer les gestionnaires d'erreurs
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Fonction pour logger les actions des utilisateurs
function logUserAction($userId, $action, $details = '') {
    $logFile = __DIR__ . '/../logs/user_actions.log';
    $date = date('Y-m-d H:i:s');
    
    $logMessage = sprintf(
        "[%s] User ID: %d - Action: %s - Details: %s\n",
        $date,
        $userId,
        $action,
        $details
    );
    
    error_log($logMessage, 3, $logFile);
}

// Fonction pour logger les accès aux fichiers
function logFileAccess($userId, $filePath, $action) {
    $logFile = __DIR__ . '/../logs/file_access.log';
    $date = date('Y-m-d H:i:s');
    
    $logMessage = sprintf(
        "[%s] User ID: %d - File: %s - Action: %s\n",
        $date,
        $userId,
        $filePath,
        $action
    );
    
    error_log($logMessage, 3, $logFile);
}

// Fonction pour logger les modifications de données
function logDataModification($userId, $table, $action, $recordId) {
    $logFile = __DIR__ . '/../logs/data_modifications.log';
    $date = date('Y-m-d H:i:s');
    
    $logMessage = sprintf(
        "[%s] User ID: %d - Table: %s - Action: %s - Record ID: %d\n",
        $date,
        $userId,
        $table,
        $action,
        $recordId
    );
    
    error_log($logMessage, 3, $logFile);
}

// Fonction pour nettoyer les anciens logs
function cleanOldLogs($days = 30) {
    $logDir = __DIR__ . '/../logs';
    $files = glob($logDir . '/*.log');
    
    foreach ($files as $file) {
        if (filemtime($file) < time() - ($days * 86400)) {
            unlink($file);
        }
    }
}

// Nettoyer les anciens logs une fois par jour
if (date('H') == '00') {
    cleanOldLogs();
} 