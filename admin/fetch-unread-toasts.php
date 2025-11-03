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
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        icon VARCHAR(50) DEFAULT 'fa-bell',
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Fetch unread notifications
    $stmt = $pdo->query("SELECT id, type, title, message, icon, created_at FROM admin_notifications WHERE is_read = 0 ORDER BY created_at ASC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $ids = [];
    foreach ($rows as $r) {
        $ids[] = (int)$r['id'];
        $items[] = [
            'id' => (int)$r['id'],
            'type' => $r['type'],
            'title' => htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8'),
            'icon' => $r['icon'] ?: 'fa-bell',
            'time_ago' => fmt_time($r['created_at'])
        ];
    }

    // Mark fetched as read
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $mark = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id IN ($in)");
        $mark->execute($ids);
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
