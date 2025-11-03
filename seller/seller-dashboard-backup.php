<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';

// Get seller info and metrics
try {
    $seller_id = $_SESSION['seller_id'];
    
    // Get seller information including logo
    $stmt = $pdo->prepare("SELECT seller_name, organization, logo_path FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);
        $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
    $seller_logo = $seller_info['logo_path'] ?? null;
    $organization_name = $seller_info['organization'] ?? $_SESSION['organization'] ?? '';
    $seller_name = $seller_info['seller_name'] ?? $_SESSION['seller_name'] ?? '';
    
    // Count seller's products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
    $stmt->execute([$seller_id]);
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count seller's pre-orders (pending orders)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'pending'");
    $stmt->execute([$seller_id]);
    $totalPreOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];


} catch(PDOException $e) {
    $totalProducts = 0;
    $totalPreOrders = 0;
    $seller_logo = null;
    $organization_name = $_SESSION['organization'] ?? '';
    $seller_name = $_SESSION['seller_name'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Phirse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            height: 100vh;
        }
        
        .sidebar {
            width: 220px;
            background-color: #1a1a1a;
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #333;
            text-align: center;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .organization-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #333;
            margin: 0 auto;
            display: block;
        }

        .organization-text {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }

        .organization-name {
            font-size: 14px;
            font-weight: 500;
            margin: 8px 0 0 0;
            text-align: center;
            color: #ccc;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid #333;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu a:hover {
            background-color: #333;
        }
        
        .sidebar-menu a.active {
            background-color: #333;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 16px;
        }
        
        .main-content {
            margin-left: 220px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 16px;
        }
        
        .breadcrumb i {
            margin-right: 10px;
        }
        
        .welcome-text {
            color: #333;
            font-weight: 600;
        }
        
        .metrics-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .metric-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .metric-icon {
            width: 80px;
            height: 80px;
            background-color: #f8f9fa;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        
        .metric-icon i {
            font-size: 32px;
            color: #666;
        }
        
        .metric-number {
            font-size: 48px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            line-height: 1;
        }
        
        .metric-label {
            font-size: 18px;
            color: #666;
            font-weight: 500;
        }
        
        .notification-badge {
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 5px;
        }

        .notification-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
        }

        .notification-panel.show {
            right: 0;
        }

        .notification-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .close-notifications {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .notification-list {
            height: calc(100vh - 60px);
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .notification-item-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .notification-item-message {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .notification-item-time {
            color: #999;
            font-size: 11px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }

        .overlay.show {
            display: block;
        }

        .windows-activation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #ccc;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .metrics-container {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .notification-panel {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <?php if (!empty($seller_logo) && file_exists($seller_logo)): ?>
                <img src="<?php echo htmlspecialchars($seller_logo); ?>" alt="Organization Logo" class="organization-logo">
                <div class="organization-name"><?php echo htmlspecialchars($organization_name ?: 'Organization'); ?></div>
            <?php else: ?>
                <h2 class="organization-text"><?php echo htmlspecialchars($organization_name ?: 'Seller Panel'); ?></h2>
            <?php endif; ?>
        </div>
        <ul class="sidebar-menu">
            <li><a href="seller-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="registered-students.php"><i class="fas fa-users"></i> Registered Students</a></li>
            <li><a href="seller-products.php"><i class="fas fa-box"></i> Product Management</a></li>
            <li><a href="seller-add-product.php"><i class="fas fa-plus"></i> Add Product</a></li>
            <li><a href="seller-orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="seller-logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                Home > Dashboard
            </div>
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($seller_name ?: 'Seller'); ?>!
            </div>
        </div>

        <div class="metrics-container">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="metric-number"><?php echo $totalProducts; ?></div>
                <div class="metric-label">Total Products</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="metric-number"><?php echo $totalPreOrders; ?></div>
                <div class="metric-label">Total Pre-orders</div>
            </div>
        </div>

       
    </div>

    <!-- Notification Panel -->
    <div class="overlay" id="overlay" onclick="toggleNotifications()"></div>
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h3 class="notification-title">Notifications</h3>
            <button class="close-notifications" onclick="toggleNotifications()">×</button>
        </div>
        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div style="text-align: center; color: #999; padding: 50px 20px;">
                    <i class="fas fa-bell-slash" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                        <div class="notification-item-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-item-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-item-time"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleNotifications() {
            const panel = document.getElementById('notificationPanel');
            const overlay = document.getElementById('overlay');

            panel.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        function markAsRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>