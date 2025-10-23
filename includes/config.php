<?php
// Advanced CarRent Pro Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'carrent_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_URL', 'http://carrent.local');
define('ADMIN_EMAIL', 'admin@carrentpro.com');
define('SUPER_ADMIN_EMAIL', 'superadmin@carrentpro.com');

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Chat configuration
define('CHAT_ENABLED', true);
define('AUTO_RESPONSE_ENABLED', true);
define('CHAT_HISTORY_LIMIT', 100);

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection with better error handling
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    if ($e->getCode() == 1049) {
        if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
            header('Location: setup.php');
            exit();
        }
    } else {
        if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
            header('Location: setup.php');
            exit();
        }
    }
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/New_York');
?>
