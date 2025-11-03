<?php
session_start();
require_once '../database/config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['student_id'])) {
        echo json_encode(['success' => true, 'notifications' => []]);
        exit;
    }

    $student_id = $_SESSION['student_id'];

    $stmt = $pdo->prepare("
        SELECT id, message, created_at 
        FROM notifications 
        WHERE student_id = ? AND is_read = 0 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$student_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
<script>
function fetchNotifications() {
    fetch('fetch-notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                const notifContainer = document.getElementById('notifications');
                data.notifications.forEach(notif => {
                    const div = document.createElement('div');
                    div.className = 'notif-item';
                    div.innerHTML = `
                        <p>${notif.message}</p>
                        <small>${new Date(notif.created_at).toLocaleString()}</small>
                    `;
                    notifContainer.prepend(div);
                });

                // Optionally show a sound or browser alert
                new Audio('notification.mp3').play();
            }
        })
        .catch(err => console.error('Notification fetch error:', err));
}

// Run every 5 seconds
setInterval(fetchNotifications, 5000);
</script>

<div id="notifications"></div>
