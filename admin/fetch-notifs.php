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
    // Ensure admin_notifications table exists (idempotent)
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        icon VARCHAR(50) DEFAULT 'fa-bell',
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Prefer explicit admin_notifications if present
    $notifStmt = $pdo->query("SELECT id, type, title, message, icon, is_read, created_at AS ts
                              FROM admin_notifications
                              ORDER BY created_at DESC
                              LIMIT 20");
    $notifs = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    if (!empty($notifs)) {
        $unread = (int)($pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn() ?: 0);
        foreach ($notifs as $n) {
            $items[] = [
                'type' => $n['type'],
                'id' => (int)$n['id'],
                'title' => htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8'),
                'message' => htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'),
                'time_ago' => fmt_time($n['ts']),
                'icon' => $n['icon'] ?: 'fa-bell',
                'timestamp' => $n['ts']
            ];
        }
    } else {
        // Fallback to legacy aggregation (products + orders)
        // Products
        $prodStmt = $pdo->query("SELECT p.id, p.name, p.status, COALESCE(p.updated_at, p.created_at) AS ts, s.seller_name, s.organization
                                 FROM products p
                                 LEFT JOIN sellers s ON s.id = p.seller_id
                                 ORDER BY COALESCE(p.updated_at, p.created_at) DESC
                                 LIMIT 10");
        $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

        // Orders (latest 10)
        $orderStmt = $pdo->query("SELECT o.id, o.status, o.order_date AS ts, o.total_price, s.seller_name, s.organization
                                  FROM orders o
                                  LEFT JOIN sellers s ON s.id = o.seller_id
                                  ORDER BY o.order_date DESC
                                  LIMIT 10");
        $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

        // Unread count heuristic: pending products + pending orders
        $pendingProducts = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending' OR status IS NULL")->fetchColumn() ?: 0);
        $pendingOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','paid','approved','confirmed')")->fetchColumn() ?: 0);
        $unread = $pendingProducts + $pendingOrders;

        foreach ($products as $p) {
            $status = $p['status'] ?: 'pending';
            $icon = 'fa-box';
            if ($status === 'pending') $icon = 'fa-hourglass-half';
            if ($status === 'approved') $icon = 'fa-check-circle';
            if ($status === 'rejected') $icon = 'fa-times-circle';
            $sellerLabel = $p['organization'] ?: $p['seller_name'] ?: 'Seller';
            $title = ($status === 'pending') ? 'New Product Submitted' : 'Product Update';
            if ($status === 'approved') $title = 'Product Approved';
            if ($status === 'rejected') $title = 'Product Rejected';
            $message = sprintf('%s (%s) • Status: %s', $p['name'] ?? 'Untitled Product', $sellerLabel, ucfirst($status));
            $items[] = [
                'type' => 'product',
                'id' => (int)$p['id'],
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                'time_ago' => fmt_time($p['ts']),
                'icon' => $icon,
                'timestamp' => $p['ts']
            ];
        }

        foreach ($orders as $o) {
            $status = $o['status'] ?: 'pending';
            $icon = 'fa-receipt';
            if ($status === 'pending') $icon = 'fa-receipt';
            if ($status === 'paid') $icon = 'fa-money-bill-wave';
            if ($status === 'approved' || $status === 'confirmed') $icon = 'fa-check-circle';
            if ($status === 'completed') $icon = 'fa-circle-check';
            if ($status === 'rejected' || $status === 'cancelled') $icon = 'fa-times-circle';
            $sellerLabel = $o['organization'] ?: $o['seller_name'] ?: 'Seller';
            $title = 'Order Update';
            if ($status === 'pending') $title = 'New Order Placed';
            if ($status === 'paid') $title = 'Payment Received';
            if (in_array($status, ['approved','confirmed'])) $title = 'Order Confirmed';
            if ($status === 'completed') $title = 'Order Completed';
            $amount = is_numeric($o['total_price']) ? number_format((float)$o['total_price'], 2) : '0.00';
            $message = sprintf('%s • Status: %s • Amount: ₱%s', $sellerLabel, ucfirst($status), $amount);
            $items[] = [
                'type' => 'order',
                'id' => (int)$o['id'],
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                'time_ago' => fmt_time($o['ts']),
                'icon' => $icon,
                'timestamp' => $o['ts']
            ];
        }

        // Sort by timestamp desc and cut to top 12 for popup brevity
        usort($items, function($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });
        $items = array_slice($items, 0, 12);
    }
    // Products
    $prodStmt = $pdo->query("SELECT p.id, p.name, p.status, COALESCE(p.updated_at, p.created_at) AS ts, s.seller_name, s.organization
                             FROM products p
                             LEFT JOIN sellers s ON s.id = p.seller_id
                             ORDER BY COALESCE(p.updated_at, p.created_at) DESC
                             LIMIT 10");
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    // Orders (latest 10)
    $orderStmt = $pdo->query("SELECT o.id, o.status, o.order_date AS ts, o.total_price, s.seller_name, s.organization
                              FROM orders o
                              LEFT JOIN sellers s ON s.id = o.seller_id
                              ORDER BY o.order_date DESC
                              LIMIT 10");
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    // Unread count heuristic: pending products + pending orders
    $pendingProducts = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending' OR status IS NULL")->fetchColumn() ?: 0);
    $pendingOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','paid','approved','confirmed')")->fetchColumn() ?: 0);
    $unread = $pendingProducts + $pendingOrders;

    $items = [];

    foreach ($products as $p) {
        $status = $p['status'] ?: 'pending';
        $icon = 'fa-box';
        if ($status === 'pending') $icon = 'fa-hourglass-half';
        if ($status === 'approved') $icon = 'fa-check-circle';
        if ($status === 'rejected') $icon = 'fa-times-circle';
        $sellerLabel = $p['organization'] ?: $p['seller_name'] ?: 'Seller';
        $title = ($status === 'pending') ? 'New Product Submitted' : 'Product Update';
        if ($status === 'approved') $title = 'Product Approved';
        if ($status === 'rejected') $title = 'Product Rejected';
        $message = sprintf('%s (%s) • Status: %s', $p['name'] ?? 'Untitled Product', $sellerLabel, ucfirst($status));
        $items[] = [
            'type' => 'product',
            'id' => (int)$p['id'],
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'time_ago' => fmt_time($p['ts']),
            'icon' => $icon,
            'timestamp' => $p['ts']
        ];
    }

    foreach ($orders as $o) {
        $status = $o['status'] ?: 'pending';
        $icon = 'fa-receipt';
        if ($status === 'pending') $icon = 'fa-receipt';
        if ($status === 'paid') $icon = 'fa-money-bill-wave';
        if ($status === 'approved' || $status === 'confirmed') $icon = 'fa-check-circle';
        if ($status === 'completed') $icon = 'fa-circle-check';
        if ($status === 'rejected' || $status === 'cancelled') $icon = 'fa-times-circle';
        $sellerLabel = $o['organization'] ?: $o['seller_name'] ?: 'Seller';
        $title = 'Order Update';
        if ($status === 'pending') $title = 'New Order Placed';
        if ($status === 'paid') $title = 'Payment Received';
        if (in_array($status, ['approved','confirmed'])) $title = 'Order Confirmed';
        if ($status === 'completed') $title = 'Order Completed';
        $amount = is_numeric($o['total_price']) ? number_format((float)$o['total_price'], 2) : '0.00';
        $message = sprintf('%s • Status: %s • Amount: ₱%s', $sellerLabel, ucfirst($status), $amount);
        $items[] = [
            'type' => 'order',
            'id' => (int)$o['id'],
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'time_ago' => fmt_time($o['ts']),
            'icon' => $icon,
            'timestamp' => $o['ts']
        ];
    }

    // Sort by timestamp desc and cut to top 12 for popup brevity
    usort($items, function($a, $b) {
        return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
    });
    $items = array_slice($items, 0, 12);

    echo json_encode([
        'success' => true,
        'unread_count' => max(isset($unread) ? (int)$unread : 0, count($items)),
        'notifications' => $items,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
