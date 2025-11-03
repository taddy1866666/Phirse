<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

header('Content-Type: application/json');
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['seller_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (empty($_POST['order_id']) || empty($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$order_id = $_POST['order_id'];
$status = $_POST['status'];
$claiming_datetime = $_POST['claiming_datetime'] ?? null;

try {
    // Update order status
    $query = "UPDATE orders SET status = ?";
    $params = [$status];

    if (!empty($claiming_datetime)) {
        $query .= ", claiming_datetime = ?";
        $params[] = $claiming_datetime;
    }

    $query .= " WHERE id = ?";
    $params[] = $order_id;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    // Get student_id from the order
    $stmt = $pdo->prepare("SELECT student_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $student_id = $stmt->fetchColumn();

    if ($student_id) {
        // Format status text
        $status_text = match (strtolower($status)) {
            'paid' => 'Payment Received',
            'claiming' => 'Ready for Claiming',
            'completed' => 'Completed',
            'pending' => 'Pending',
            default => ucfirst($status),
        };

        $message = "Your order status was updated to: " . $status_text;

        if (!empty($claiming_datetime)) {
            $formatted_date = date('F j, Y g:i A', strtotime($claiming_datetime));
            $message .= " | Claiming Date: " . $formatted_date;
        }

        // Insert into notifications table
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, message, created_at, is_read)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$student_id, $message]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>  