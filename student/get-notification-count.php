<?php
session_start();
include __DIR__ . '/db/config.php';

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$student_id = $_SESSION['student_id'];

try {
    // Count unread notifications
    $sql = "SELECT COUNT(*) as count
            FROM notifications
            WHERE student_id = ?
            AND is_read = 0";

    $result = fetchSingle($sql, [$student_id], 'i');

    $count = $result ? $result['count'] : 0;

    echo json_encode(['count' => (int)$count]);

} catch (Exception $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
?>
