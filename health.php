<?php
// Healthcheck endpoint
try {
    // Test database connection
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $dbname = getenv('MYSQLDATABASE') ?: 'phirse_db';
    $username = getenv('MYSQLUSER') ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: '';
    $port = getenv('MYSQLPORT') ?: '3306';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    http_response_code(200);
    echo "OK";
} catch (Exception $e) {
    error_log("Health check failed: " . $e->getMessage());
    http_response_code(503);
    echo "Service unavailable";
}
?>
