<?php
session_start();
require_once 'db/config.php';

// Simple order test
echo "<h1>Order Debug</h1>";

if (!isset($_SESSION['student_id'])) {
    echo "Not logged in!";
    exit;
}

echo "Student ID: " . $_SESSION['student_id'] . "<br>";

// Test database connection
try {
    $result = $conn->query("SELECT * FROM orders LIMIT 1");
    echo "✓ Orders table exists<br>";
} catch (Exception $e) {
    echo "✗ Orders table error: " . $e->getMessage() . "<br>";
}

// Test sellers table
try {
    $result = $conn->query("SELECT * FROM sellers LIMIT 1");
    echo "✓ Sellers table exists<br>";
} catch (Exception $e) {
    echo "✗ Sellers table error: " . $e->getMessage() . "<br>";
}

// Test products table
try {
    $result = $conn->query("SELECT * FROM products LIMIT 1");
    echo "✓ Products table exists<br>";
} catch (Exception $e) {
    echo "✗ Products table error: " . $e->getMessage() . "<br>";
}

// Show database structure
echo "<h2>Orders Table Structure:</h2>";
$result = $conn->query("DESCRIBE orders");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

?>
