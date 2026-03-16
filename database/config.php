<?php
$host     = getenv('MYSQLHOST')     ?: 'localhost';
$dbname   = getenv('MYSQL_DATABASE') ?: 'railway';
$username = getenv('MYSQLUSER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$port     = getenv('MYSQLPORT')     ?: '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch(PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    if (php_sapi_name() === 'cli-server') {
        die("Connection failed: " . $e->getMessage());
    }
    http_response_code(503);
    echo "Service temporarily unavailable";
    exit;
}
?>
