<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../database/config.php';
header('Content-Type: application/json; charset=utf-8');

function fmt_time($ts) {
    $createdTs = strtotime($ts);
    $nowTs = time();
    $diff = $nowTs - $createdTs;
    $createdDate = date('Y-m-d', $createdTs);
    $todayDate = date('Y-m-d');
    $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
    if ($createdDate === $todayDate) return 'Today · ' . date('h:i A', $createdTs);
    if ($createdDate === $yesterdayDate) return 'Yesterday · ' . date('h:i A', $createdTs);
    if ($diff < 86400 * 7) return date('D · h:i A', $createdTs);
    return date('M d, Y · h:i A', $createdTs);
}

try {
    // Fetch pending product notifications
    $stmt = $pdo->query("SELECT 'product' as source, p.id, p.name, p.category, p.created_at as timestamp, 
                                s.organization, s.seller_name, NULL as title, NULL as custom_message
                         FROM products p
                         LEFT JOIN sellers s ON s.id = p.seller_id
                         WHERE p.status = 'pending' OR p.status IS NULL
                         UNION ALL
                         SELECT 'notification' as source, NULL as id, NULL as name, NULL as category, 
                                created_at as timestamp, NULL as organization, NULL as seller_name,
                                title, message as custom_message
                         FROM admin_notifications
                         WHERE type = 'stock_update' AND is_read = 0
                         ORDER BY timestamp DESC
                         LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $r) {
        if ($r['source'] === 'product') {
            $sellerLabel = $r['organization'] ?: $r['seller_name'] ?: 'Seller';
            $title = 'New Product Submitted';
            $message = sprintf('%s (%s) • Category: %s', $r['name'] ?? 'Untitled Product', $sellerLabel, $r['category'] ?? 'N/A');
            $icon = 'fa-hourglass-half';
        } else {
            $title = $r['title'];
            $message = $r['custom_message'];
            $icon = 'fa-box';
        }

        $items[] = [
            'id' => (int)($r['id'] ?? 0),
            'icon' => $icon,
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'time_ago' => fmt_time($r['timestamp'])
        ];
    }

    // Count total unread notifications
    $countStmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM products WHERE status = 'pending' OR status IS NULL) +
        (SELECT COUNT(*) FROM admin_notifications WHERE type = 'stock_update' AND is_read = 0) as total");
    $unread = (int)($countStmt->fetchColumn() ?: 0);

    echo json_encode([
        'success' => true,
        'unread_count' => $unread,
        'items' => $items
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
