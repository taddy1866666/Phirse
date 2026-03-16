<?php
// Database configuration for Student Portal
$servername = getenv('MYSQLHOST')     ?: 'localhost';
$username   = getenv('MYSQLUSER')     ?: 'root';
$password   = getenv('MYSQLPASSWORD') ?: '';
$dbname     = getenv('MYSQLDATABASE') ?: 'phirse_db';
$port       = (int)(getenv('MYSQLPORT') ?: 3306);

try {
    // Create connection using mysqli
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Alternative PDO connection (commented out - uncomment if needed)
/*
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
*/

// Helper function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Helper function to execute prepared statements
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
        
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

// Helper function to get single row
function fetchSingle($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if ($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return false;
}

// Helper function to get multiple rows
function fetchAll($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if ($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 hours
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Error reporting (disable in production)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone setting
date_default_timezone_set('Asia/Manila');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Helper function to redirect with message
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

// Helper function to display flash messages
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        echo "<div class='flash-message flash-{$type}' style='
            position: fixed;
            top: 90px;
            right: 20px;
            background: " . getFlashColor($type) . ";
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            max-width: 300px;
            font-size: 0.9rem;
            font-weight: 500;
        '>{$message}</div>";
        
        echo "<script>
            setTimeout(() => {
                const flashMsg = document.querySelector('.flash-message');
                if (flashMsg) {
                    flashMsg.style.transform = 'translateX(100%)';
                    setTimeout(() => flashMsg.remove(), 300);
                }
            }, 3000);
        </script>";
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

function getFlashColor($type) {
    $colors = [
        'success' => '#10b981',
        'error' => '#ef4444',
        'warning' => '#f59e0b',
        'info' => '#3b82f6'
    ];
    return $colors[$type] ?? $colors['info'];
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['student_id']);
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT * FROM students WHERE id = ?";
    return fetchSingle($sql, [$_SESSION['student_id']], 'i');
}

// Require login for protected pages
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        redirectWithMessage($redirectUrl, 'Please log in to access this page.', 'warning');
    }
}

// Log activity (optional)
function logActivity($action, $details = '') {
    if (!isLoggedIn()) return;
    
    $sql = "INSERT INTO activity_logs (student_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
    executeQuery($sql, [$_SESSION['student_id'], $action, $details], 'iss');
}

// Clean up old sessions (call this periodically)
function cleanupOldSessions() {
    $sql = "DELETE FROM student_sessions WHERE expires_at < NOW()";
    executeQuery($sql);
}
?>