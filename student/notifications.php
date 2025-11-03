<?php
session_start();
include __DIR__ . '/db/config.php';

$pageTitle = 'Notifications';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['flash_message'] = "Please login to view notifications.";
    $_SESSION['flash_type'] = "error";
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Get all notifications for the student
$sql = "SELECT n.*, p.name as product_name, p.image_path
        FROM notifications n
        LEFT JOIN products p ON n.product_id = p.id
        WHERE n.student_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50";

$notifications = fetchAll($sql, [$student_id], 'i');

// Process images for each notification
foreach ($notifications as &$notification) {
    if (!empty($notification['image_path'])) {
        $imagePaths = array_filter(array_map('trim', explode(',', $notification['image_path'])));
        $firstImage = $imagePaths[0] ?? '';
        if (!empty($firstImage) && file_exists($firstImage)) {
            $notification['image_url'] = $firstImage;
        } else {
            $notification['image_url'] = '../uploads/products/default.jpg';
        }
    } else {
        $notification['image_url'] = '../uploads/products/default.jpg';
    }
}
unset($notification); // Break the reference

// Mark all as read
$updateSql = "UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0";
executeQuery($updateSql, [$student_id], 'i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Phirse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding-top: 90px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 1rem;
        }

        .notifications-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .notification-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #f9fafb;
        }

        .notification-item.unread {
            background: #f0f9ff;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .notification-icon.confirmed {
            background: #d1fae5;
            color: #10b981;
        }

        .notification-icon.completed {
            background: #dbeafe;
            color: #3b82f6;
        }

        .notification-icon.cancelled {
            background: #fee2e2;
            color: #ef4444;
        }

        .notification-icon.paid {
            background: #fef3c7;
            color: #f59e0b;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .notification-message {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .notification-time {
            color: #999;
            font-size: 0.85rem;
        }

        .notification-product {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .product-name {
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .view-order-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .view-order-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <?php include 'nav-bar-transparent.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <p>Stay updated with your order status and transactions</p>
        </div>

        <div class="notifications-container">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon <?php echo strtolower($notif['type']); ?>">
                            <?php
                            $icon = 'fa-bell';
                            if ($notif['type'] === 'confirmed') $icon = 'fa-check-circle';
                            if ($notif['type'] === 'completed') $icon = 'fa-check-double';
                            if ($notif['type'] === 'cancelled') $icon = 'fa-times-circle';
                            if ($notif['type'] === 'paid') $icon = 'fa-money-bill-wave';
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>

                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                            </div>
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </div>

                            <?php if ($notif['product_name']): ?>
                                <div class="notification-product">
                                    <img src="<?php echo htmlspecialchars($notif['image_url']); ?>"
                                         alt="<?php echo htmlspecialchars($notif['product_name']); ?>"
                                         class="product-thumb">
                                    <span class="product-name"><?php echo htmlspecialchars($notif['product_name']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                <?php
                                $time_diff = time() - strtotime($notif['created_at']);
                                if ($time_diff < 60) {
                                    echo 'Just now';
                                } elseif ($time_diff < 3600) {
                                    echo floor($time_diff / 60) . ' minutes ago';
                                } elseif ($time_diff < 86400) {
                                    echo floor($time_diff / 3600) . ' hours ago';
                                } else {
                                    echo date('M d, Y - h:i A', strtotime($notif['created_at']));
                                }
                                ?>
                            </div>

                            <?php if ($notif['order_id']): ?>
                                <a href="myorders.php#order-<?php echo $notif['order_id']; ?>" class="view-order-btn">
                                    <i class="fas fa-eye"></i> View Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/includes/mobile-bottom-nav.php'; ?>
</body>
</html>
