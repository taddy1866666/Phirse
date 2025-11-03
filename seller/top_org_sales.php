<?php
require_once '../database/config.php'; // adjust path if needed

$filter = $_GET['filter'] ?? 'current';

// === Choose query based on filter ===
if ($filter === 'last6') {
    $query = "SELECT 
            s.organization AS organization_name,
            SUM(o.total_price) AS total_sales
        FROM orders o
        JOIN sellers s ON o.seller_id = s.id
        WHERE o.status = 'completed'
          AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY s.organization
        ORDER BY total_sales DESC
        LIMIT 5
    ";
} else {
    $query = "SELECT 
            s.organization AS organization_name,
            SUM(o.total_price) AS total_sales
        FROM orders o
        JOIN sellers s ON o.seller_id = s.id
        WHERE o.status = 'completed'
          AND MONTH(o.order_date) = MONTH(CURRENT_DATE())
          AND YEAR(o.order_date) = YEAR(CURRENT_DATE())
        GROUP BY s.organization
        ORDER BY total_sales DESC
        LIMIT 5
    ";
}

$stmt = $pdo->prepare($query);
$stmt->execute();
$topOrganizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($topOrganizations)):
    // find max sales for percentage calculation
    $maxSales = max(array_column($topOrganizations, 'total_sales'));
    $delay = 0;
    foreach ($topOrganizations as $row):
        $total = (float)$row['total_sales'];
        $percent = ($maxSales > 0) ? ($total / $maxSales) * 100 : 0;
?>
    <div class="org-row">
        <div class="org-info">
            <span class="org-name"><?= htmlspecialchars($row['organization_name'] ?: '—') ?></span>
            <span class="org-sales">₱<?= number_format($total, 2) ?></span>
        </div>
        <div class="org-bar">
            <div class="org-bar-fill" 
                 style="--target-width: <?= $percent ?>%; animation-delay: <?= $delay ?>s;"></div>
        </div>
    </div>
<?php
        $delay += 0.08;
    endforeach;
else:
?>
    <p>No sales data found.</p>
<?php endif; ?>
