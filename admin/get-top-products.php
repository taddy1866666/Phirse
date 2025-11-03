<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../database/config.php';

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$period = $_GET['period'] ?? '7';

try {
    // Build date condition for LEFT JOIN
    $dateCondition = '';
    if ($period !== 'all') {
        $days = intval($period);
        $dateCondition = "AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
    }

    // Query to get ALL approved products with their sales (including 0)
    $query = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            p.category,
            COALESCE(s.organization_name, s.organization) as organization,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.quantity ELSE 0 END), 0) as total_quantity,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as order_count,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_price ELSE 0 END), 0) as total_sales
        FROM products p
        INNER JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN orders o ON p.id = o.product_id AND o.status = 'completed'
            " . ($period !== 'all' ? "AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL " . intval($period) . " DAY)" : "") . "
        WHERE p.status = 'approved'
        GROUP BY p.id, p.name, p.category, s.organization, s.organization_name
        ORDER BY total_sales DESC, p.name ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results && count($results) > 0) {
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No products found',
            'debug' => "No approved products in database"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
}