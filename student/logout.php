<?php
session_start();
require_once 'db/config.php';

// Log activity before logout
if (isset($_SESSION['student_id'])) {
    logActivity('logout', 'Student logged out');
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage with message
header("Location: index.php?message=You have been logged out successfully");
exit();
?>