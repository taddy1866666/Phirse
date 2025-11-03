<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';

// Get counts from database
try {
    // Count Register Organization
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sellers");
    $sellersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count registered users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $usersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count pending products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'pending'");
    $pendingProductsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count approved products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'approved'");
    $approvedProductsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $totalOrdersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pendingOrdersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'approved'");
    $approvedOrdersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];


} catch(PDOException $e) {
    // Default values if database query fails
    $sellersCount = 0;
    $usersCount = 0;
    $pendingProductsCount = 0;
    $approvedProductsCount = 0;
    $totalOrdersCount = 0;
    $pendingOrdersCount = 0;
    $approvedOrdersCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Phirse</title>
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
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
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
        
        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dashboard-title {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .dashboard-title i {
            margin-right: 10px;
            background: #f0f0f0;
            padding: 8px;
            border-radius: 8px;
        }
        
        .dashboard-subtitle {
            color: #666;
            margin: 0;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .metric-icon {
            font-size: 24px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .metric-number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .metric-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .metric-status {
            font-size: 11px;
            color: #666;
        }
        
        
        .windows-activation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #ccc;
            font-size: 12px;
        }
        
        @media (max-width: 1200px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
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
            <h2 class="sidebar-title">Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="registered-sellers.php"><i class="fas fa-users"></i> Register Organization</a></li>
            <li><a href="product-management.php"><i class="fas fa-box"></i> Product Management</a></li>
            <li><a href="#" onclick="toggleNotifications()"><i class="fas fa-bell"></i> Notifications <?php if($unreadCount > 0): ?><span class="notification-badge"><?php echo $unreadCount; ?></span><?php endif; ?></a></li>
            <li><a href="change-password.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <p class="dashboard-subtitle">Key metrics and quick tools at a glance.</p>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-laptop"></i>
                </div>
                <div class="metric-number"><?php echo $sellersCount; ?></div>
                <div class="metric-label">Student Sellers</div>
                <div class="metric-status">↑ Active</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="metric-number"><?php echo $pendingProductsCount; ?></div>
                <div class="metric-label">Pending Products</div>
                <div class="metric-status">⚬ Awaiting Review</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="metric-number"><?php echo $totalOrdersCount; ?></div>
                <div class="metric-label">Total Orders</div>
                <div class="metric-status">All Time</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-number"><?php echo $usersCount; ?></div>
                <div class="metric-label">Student Users</div>
                <div class="metric-status">👤 Registered</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="metric-number"><?php echo $pendingOrdersCount; ?></div>
                <div class="metric-label">Pending Orders</div>
                <div class="metric-status">⚬ Needs Action</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                </div>
                <div class="metric-number"><?php echo $approvedProductsCount; ?></div>
                <div class="metric-label">Approved Products</div>
                <div class="metric-status">✓ Ready for Sale</div>
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