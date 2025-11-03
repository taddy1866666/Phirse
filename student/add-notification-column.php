<?php
require_once '../database/config.php';

$pageTitle = 'Database Update';
include 'includes/header.php';

try {
    // Add is_notified column to orders table
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS is_notified TINYINT(1) DEFAULT 0");

    echo "✓ Successfully added 'is_notified' column to orders table<br><br>";
    echo "<a href='index.php'>Back to Home</a>";

} catch(PDOException $e) {
    // Check if column already exists
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✓ Column 'is_notified' already exists<br><br>";
        echo "<a href='index.php'>Back to Home</a>";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
