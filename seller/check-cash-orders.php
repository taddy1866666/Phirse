<?php
require_once '../database/config.php';

try {
    $stmt = $pdo->query("
        SELECT id, reference_number, payment_method, status
        FROM orders
        WHERE payment_method LIKE '%cash%'
    ");

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Cash Orders:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Reference</th><th>Payment Method</th><th>Status</th></tr>";

    if (empty($orders)) {
        echo "<tr><td colspan='4'>No cash orders found</td></tr>";
    } else {
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['reference_number']}</td>";
            echo "<td><strong>{$order['payment_method']}</strong></td>";
            echo "<td>{$order['status']}</td>";
            echo "</tr>";
        }
    }

    echo "</table>";
    echo "<br><br>";
    echo "<p>If payment_method is not exactly 'cash', we need to update the code to match it.</p>";
    echo "<br><a href='seller-orders.php'>Back to Orders</a>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
