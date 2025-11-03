<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../database/config.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$notifId = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;
$seller_id = (int)$_SESSION['seller_id'];

if ($notifId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification id']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE seller_notifications SET is_read = 1 WHERE id = ? AND seller_id = ?");
    $stmt->execute([$notifId, $seller_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
