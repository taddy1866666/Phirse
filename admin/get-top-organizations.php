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

    // Query to get ALL active organizations with their sales (including 0)
    $query = "
        SELECT 
            s.id as seller_id,
            COALESCE(s.organization_name, s.organization) as organization_name,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as order_count,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_price ELSE 0 END), 0) as total_sales
        FROM sellers s
        LEFT JOIN orders o ON s.id = o.seller_id 
            " . ($period !== 'all' ? "AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL " . intval($period) . " DAY)" : "") . "
        WHERE s.status = 'active'
        GROUP BY s.id, s.organization, s.organization_name
        ORDER BY total_sales DESC, s.organization ASC
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
            'message' => 'No organizations found',
            'debug' => "No active sellers in database"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
}