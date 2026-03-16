<?php
$host = 'maglev.proxy.rlwy.net';
$user = 'root';
$password = 'kJaWJWzNcfJkBeZxXlKZpmcXLqfdInth';
$port = 43015;
$dbname = 'railway';

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents(__DIR__ . '/phirse_db.sql');
if ($conn->multi_query($sql)) {
    echo "Database imported successfully!";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
