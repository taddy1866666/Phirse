<?php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['seller_id'])) {
    exit('Unauthorized access');
}

$seller_id = $_SESSION['seller_id'];
$filter = $_GET['filter'] ?? 'month';

// === Choose query based on filter ===
if ($filter === '7days') {
    $query = "SELECT 
            COALESCE(p.category, 'Uncategorized') AS category_name,
            SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, 'Type: ', -1), '\n', 1) AS product_type,
            p.seller_id AS organization_id,
            SUM(o.quantity) AS total_sold
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.status IN ('paid','confirmed','approved','completed')
          AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          AND p.seller_id = :seller_id
        GROUP BY p.category, p.description, p.seller_id
        ORDER BY total_sold DESC
        LIMIT 5
    ";
} elseif ($filter === '30days') {
    $query = "SELECT 
            COALESCE(p.category, 'Uncategorized') AS category_name,
            SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, 'Type: ', -1), '\n', 1) AS product_type,
            p.seller_id AS organization_id,
            SUM(o.quantity) AS total_sold
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.status IN ('paid','confirmed','approved','completed')
          AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND p.seller_id = :seller_id
        GROUP BY p.category, p.description, p.seller_id
        ORDER BY total_sold DESC
        LIMIT 5
    ";
} else {
    // Default to current month
    $query = "SELECT 
            COALESCE(p.category, 'Uncategorized') AS category_name,
            SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, 'Type: ', -1), '\n', 1) AS product_type,
            p.seller_id AS organization_id,
            SUM(o.quantity) AS total_sold
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.status IN ('paid','confirmed','approved','completed')
          AND MONTH(o.order_date) = MONTH(CURRENT_DATE())
          AND YEAR(o.order_date) = YEAR(CURRENT_DATE())
          AND p.seller_id = :seller_id
        GROUP BY p.category, p.description, p.seller_id
        ORDER BY total_sold DESC
        LIMIT 5
    ";
}

$stmt = $pdo->prepare($query);
$stmt->execute(['seller_id' => $seller_id]);
$topCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($topCategories)):
    foreach ($topCategories as $index => $row):
        $rank = $index + 1;
        $total = (int)$row['total_sold'];
?>
    <div class="category-row">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">
                <?= $rank ?>
            </div>
            <div style="flex: 1;">
                <div class="category-name" style="margin: 0 0 4px 0;"><?= htmlspecialchars($row['category_name'] ?: 'Uncategorized') ?></div>
                <div style="font-size: 12px; color: #4a5568; margin-bottom: 2px;">
                    <span style="font-weight: 600;">Product Type:</span> <?= htmlspecialchars($row['product_type']) ?>
                </div>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; padding-left: 40px; margin-top: 8px;">
            <div style="font-size: 12px; color: #718096;">
                <?= number_format($total) ?> units sold
            </div>
            <div class="category-sales"><?= number_format($total) ?> sold</div>
        </div>
    </div>
<?php
    endforeach;
else:
?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No sales data found.</p>
    </div>
<?php endif; ?>
