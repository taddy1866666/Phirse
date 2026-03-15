<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h2>Database Migration - Add Cancellation Support</h2>";

try {
    $errors = [];
    $successes = [];

    // Check if cancellation_reason column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'cancellation_reason'");
    $column_exists = $stmt->rowCount() > 0;

    if (!$column_exists) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN cancellation_reason TEXT DEFAULT NULL AFTER claiming_datetime");
        $successes[] = "✓ Added 'cancellation_reason' column";
    } else {
        $successes[] = "✓ 'cancellation_reason' column already exists";
    }

    // Check and update status enum to include 'cancelled'
    $stmt = $pdo->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
    $statusColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statusColumn) {
        $currentType = $statusColumn['Type'];
        if (strpos($currentType, 'cancelled') === false) {
            $pdo->exec("ALTER TABLE orders MODIFY COLUMN status enum('pending','paid','confirmed','claiming','completed','cancelled') DEFAULT 'pending'");
            $successes[] = "✓ Updated status enum to include 'cancelled'";
        } else {
            $successes[] = "✓ Status enum already includes 'cancelled'";
        }
    }

    // Display results
    foreach ($successes as $msg) {
        echo "<div class='success'>" . htmlspecialchars($msg) . "</div>";
    }

    if (empty($errors)) {
        echo "<div class='success'><strong>✓ All database migrations completed successfully!</strong></div>";
    } else {
        foreach ($errors as $msg) {
            echo "<div class='error'>" . htmlspecialchars($msg) . "</div>";
        }
    }

    echo "<div class='info'><strong>Next Steps:</strong><br>
    1. Clear your browser cache if needed<br>
    2. Go to <a href='../seller/seller-orders.php'>Seller Orders</a> to test the cancel feature<br>
    3. Try cancelling an order and providing a reason
    </div>";

} catch (Exception $e) {
    echo "<div class='error'><strong>Error during migration:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
