<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../database/config.php';
header('Content-Type: application/json; charset=utf-8');

$seller_id = (int)$_SESSION['seller_id'];

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
    // unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_notifications WHERE seller_id = ? AND is_read = 0");
    $stmt->execute([$seller_id]);
    $unread = (int)($stmt->fetchColumn() ?: 0);

    // recent notifications
    $stmt = $pdo->prepare("SELECT id, type, title, message, is_read, created_at
                           FROM seller_notifications
                           WHERE seller_id = ?
                           ORDER BY created_at DESC
                           LIMIT 10");
    $stmt->execute([$seller_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $n) {
        $icon = 'fa-bell';
        if ($n['type'] === 'approved') $icon = 'fa-check-circle';
        if ($n['type'] === 'rejected') $icon = 'fa-times-circle';
        if ($n['type'] === 'order') $icon = 'fa-shopping-cart';
        if ($n['type'] === 'payment') $icon = 'fa-money-bill-wave';
        $items[] = [
            'id' => (int)$n['id'],
            'title' => htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'),
            'type' => $n['type'],
            'is_read' => (int)$n['is_read'],
            'time_ago' => fmt_time($n['created_at']),
            'icon' => $icon
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unread,
        'notifications' => $items
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
