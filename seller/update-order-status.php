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
$cancellation_reason = $_POST['cancellation_reason'] ?? null;

try {
    // Start transaction for data consistency
    $pdo->beginTransaction();
    
    // Update order status
    $query = "UPDATE orders SET status = ?";
    $params = [$status];

    if (!empty($claiming_datetime)) {
        $query .= ", claiming_datetime = ?";
        $params[] = $claiming_datetime;
    }

    if (!empty($cancellation_reason) && strtolower($status) === 'cancelled') {
        $query .= ", cancellation_reason = ?";
        $params[] = $cancellation_reason;
    }

    $query .= " WHERE id = ?";
    $params[] = $order_id;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    // If order is being cancelled, restore stock
    if (strtolower($status) === 'cancelled') {
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($orderData) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$orderData['quantity'], $orderData['product_id']]);
        }
    }

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
            'cancelled' => 'Cancelled',
            default => ucfirst($status),
        };

        $message = "Your order status was updated to: " . $status_text;

        if (!empty($claiming_datetime)) {
            $formatted_date = date('F j, Y g:i A', strtotime($claiming_datetime));
            $message .= " | Claiming Date: " . $formatted_date;
        }

        // Add cancellation reason to the message
        if (!empty($cancellation_reason) && strtolower($status) === 'cancelled') {
            $message = "Your order has been cancelled. Reason: " . $cancellation_reason;
        }

        // Insert into notifications table
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, message, created_at, is_read)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$student_id, $message]);
    }

    // Commit transaction
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>  