<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../database/config.php';

try {
    // Recent products (last 10) ordered by created_at or updated status
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.status, p.created_at, p.updated_at, s.seller_name, s.organization
        FROM products p
        LEFT JOIN sellers s ON s.id = p.seller_id
        ORDER BY COALESCE(p.updated_at, p.created_at) DESC
        LIMIT 10");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count pending products as 'unread' for admin attention
    $countStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending' OR status IS NULL");
    $unread = (int)($countStmt->fetchColumn() ?: 0);

    $formatted = [];
    foreach ($rows as $r) {
        $createdTs = strtotime($r['created_at']);
        $nowTs = time();
        $diff = $nowTs - $createdTs;

        $createdDate = date('Y-m-d', $createdTs);
        $todayDate = date('Y-m-d');
        $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

        if ($createdDate === $todayDate) {
            $time_ago = 'Today · ' . date('h:i A', $createdTs);
        } elseif ($createdDate === $yesterdayDate) {
            $time_ago = 'Yesterday · ' . date('h:i A', $createdTs);
        } elseif ($diff < 86400 * 7) {
            $time_ago = date('D · h:i A', $createdTs);
        } else {
            $time_ago = date('M d, Y · h:i A', $createdTs);
        }

        $status = $r['status'] ?: 'pending';
        $icon = 'fa-box';
        if ($status === 'pending') $icon = 'fa-hourglass-half';
        if ($status === 'approved') $icon = 'fa-check-circle';
        if ($status === 'rejected') $icon = 'fa-times-circle';

        $title = ($status === 'pending') ? 'New Product Submitted' : 'Product Update';
        if ($status === 'approved') $title = 'Product Approved';
        if ($status === 'rejected') $title = 'Product Rejected';

        $sellerLabel = $r['organization'] ?: $r['seller_name'] ?: 'Seller';
        $message = sprintf('%s (%s) • Status: %s', $r['name'] ?? 'Untitled Product', $sellerLabel, ucfirst($status));

        $formatted[] = [
            'id' => (int)$r['id'],
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'status' => $status,
            'time_ago' => $time_ago,
            'icon' => $icon,
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unread,
        'notifications' => $formatted,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
