<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';
$pageTitle = 'Notifications';
include 'includes/seller-header.php';

// Get seller info
$seller_id = $_SESSION['seller_id'];
$stmt = $pdo->prepare("SELECT seller_name, organization, logo_path FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller_info = $stmt->fetch(PDO::FETCH_ASSOC);
$seller_name = $seller_info['seller_name'] ?? 'Seller';
$seller_logo = $seller_info['logo_path'] ?? null;
$organization_name = $seller_info['organization'] ?? $_SESSION['organization'] ?? '';

// Get all notifications for seller
$notifSql = "SELECT * FROM seller_notifications
             WHERE seller_id = ?
             ORDER BY created_at DESC
             LIMIT 50";
$stmt = $pdo->prepare($notifSql);
$stmt->execute([$seller_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as read
$updateSql = "UPDATE seller_notifications SET is_read = 1 WHERE seller_id = ? AND is_read = 0";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([$seller_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Phirse Seller</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 60%, #ffffff 100%);
            display: flex;
            min-height: 100vh;
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
        }

        .organization-name {
            font-size: 14px;
            font-weight: 500;
            margin: 8px 0 0 0;
            text-align: center;
            color: #ccc;
        }

        .main-content {
            margin-left: 220px;
            flex: 1;
            padding: 30px;
        }

        .page-header {
            background: rgba(255,255,255,0.9);
            padding: 28px;
            border-radius: 16px;
            margin-bottom: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(102, 126, 234, 0.12);
        }

        .page-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .notifications-container {
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            padding: 0;
            box-shadow: 0 24px 45px rgba(31, 41, 55, 0.08);
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .notif-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
        }

        .notif-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-refresh {
            background: rgba(255,255,255,0.18);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.35);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: transform .15s ease, background .2s ease;
        }

        .btn-refresh:hover { transform: translateY(-1px); background: rgba(255,255,255,0.28); }

        .notification-item {
            display: flex;
            gap: 16px;
            padding: 16px 18px;
            border-bottom: 1px solid #f1f5f9;
            transition: background .2s ease, transform .15s ease;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover { background: #f8fafc; }

        .notification-item.unread { background: #f8fbff; }

        .notification-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .notification-icon.approved { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }

        .notification-icon.rejected { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }

        .notification-icon.order { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }

        .notification-icon.payment { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }

        .notification-content {
            flex: 1;
        }

        .notification-title { font-weight: 800; color: #0f172a; font-size: 1rem; margin-bottom: 4px; }

        .notification-message { color: #334155; font-size: 0.95rem; margin-bottom: 6px; line-height: 1.55; }

        .notification-time { color: #6b7280; font-size: 0.82rem; display: flex; align-items: center; gap: 6px; }

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

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
        }

        .mobile-menu-toggle:hover {
            background: #333;
        }

   
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px 15px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .page-header p {
                font-size: 12px;
            }

            .notifications-container {
                padding: 10px;
            }

            .notification-item {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }

            .notification-content {
                padding-left: 0;
            }

            .notification-title {
                font-size: 14px;
            }

            .notification-message {
                font-size: 12px;
            }

            .notification-time {
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 10px 10px;
            }

            .page-header h1 {
                font-size: 18px;
            }

            .empty-state h3 {
                font-size: 18px;
            }

            .empty-state p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>


    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <p>Stay updated with product approvals and order updates</p>
        </div>

        <div class="notifications-container" id="notificationsContainer">
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
                            if ($notif['type'] === 'approved') $icon = 'fa-check-circle';
                            if ($notif['type'] === 'rejected') $icon = 'fa-times-circle';
                            if ($notif['type'] === 'order') $icon = 'fa-shopping-cart';
                            if ($notif['type'] === 'payment') $icon = 'fa-money-bill-wave';
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>

                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="notification-message">
                                <?php echo nl2br(htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                <?php
                                $createdTs = strtotime($notif['created_at']);
                                $nowTs = time();
                                $diff = $nowTs - $createdTs;

                                $createdDate = date('Y-m-d', $createdTs);
                                $todayDate = date('Y-m-d');
                                $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

                                if ($createdDate === $todayDate) {
                                    echo 'Today · ' . date('h:i A', $createdTs);
                                } elseif ($createdDate === $yesterdayDate) {
                                    echo 'Yesterday · ' . date('h:i A', $createdTs);
                                } elseif ($diff < 86400 * 7) {
                                    // Within last 7 days: show weekday and time
                                    echo date('D · h:i A', $createdTs);
                                } else {
                                    echo date('M d, Y · h:i A', $createdTs);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('notificationsContainer');

    function iconFor(type) {
        if (type === 'approved') return 'fa-check-circle';
        if (type === 'rejected') return 'fa-times-circle';
        if (type === 'order') return 'fa-shopping-cart';
        if (type === 'payment') return 'fa-money-bill-wave';
        return 'fa-bell';
    }

    function render(notifs) {
        if (!notifs || notifs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications yet.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = notifs.map(n => `
            <div class="notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}">
                <div class="notification-icon ${n.type}">
                    <i class="fas ${iconFor(n.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${n.title}</div>
                    <div class="notification-message">${n.message}</div>
                    <div class="notification-time"><i class="far fa-clock"></i> ${n.time_ago}</div>
                </div>
            </div>
        `).join('');

        // Click to mark as read (like header behavior)
        container.querySelectorAll('.notification-item.unread').forEach(el => {
            el.addEventListener('click', async () => {
                const id = parseInt(el.getAttribute('data-id')) || 0;
                if (!id) return;
                try {
                    const res = await fetch('mark-notification-read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ notification_id: id })
                    });
                    if (res.ok) {
                        el.classList.remove('unread');
                    }
                } catch (e) {}
            });
        });
    }

    async function fetchAndUpdate() {
        try {
            const res = await fetch('fetch-notifications.php', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data && data.success) {
                render(data.notifications || []);
            }
        } catch (e) {
            // ignore
        }
    }

    // initial and polling (no reload required)
    fetchAndUpdate();
    const POLL_MS = 5000;
    let pollTimer = setInterval(fetchAndUpdate, POLL_MS);

    // Refresh immediately when tab gains focus
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchAndUpdate();
        }
    });
});
</script>
</html>
