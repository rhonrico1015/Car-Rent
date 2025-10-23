<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log logout activity
if (is_logged_in()) {
    log_activity($pdo, 'logout', 'User logged out');
}

session_destroy();
header('Location: index.php');
exit();
?>