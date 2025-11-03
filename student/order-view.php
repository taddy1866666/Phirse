<?php
include __DIR__ . '/db/config.php';

$pageTitle = 'Order Details';
include 'includes/header.php';

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo "Invalid order ID.";
    exit();
}

$order_id = intval($_GET['order_id']);

// Fetch order with student details
$sql = "SELECT 
            o.*, 
            s.student_number, 
            s.student_name, 
            s.organization, 
            s.course_section, 
            s.contact_number,
            p.name AS product_name
        FROM orders o
        JOIN students s ON o.user_id = s.id
        JOIN products p ON o.product_id = p.id
        WHERE o.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Order not found.";
    exit();
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { margin-bottom: 10px; }
        .section { margin-bottom: 20px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>

<h2>Order Details</h2>

<div class="section">
    <p><span class="label">Reference #:</span> <?= htmlspecialchars($order['reference_number']) ?></p>
    <p><span class="label">Product:</span> <?= htmlspecialchars($order['product_name']) ?></p>
    <p><span class="label">Quantity:</span> <?= $order['quantity'] ?></p>
    <p><span class="label">Total Price:</span> ₱<?= number_format($order['total_price'], 2) ?></p>
    <p><span class="label">Payment Method:</span> <?= ucfirst($order['payment_method']) ?></p>
    <p><span class="label">Status:</span> <?= ucfirst($order['status']) ?></p>
    <p><span class="label">Order Date:</span> <?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></p>
</div>

<h3>Student Details</h3>
<div class="section">
    <p><span class="label">Student Number:</span> <?= htmlspecialchars($order['student_number']) ?></p>
    <p><span class="label">Name:</span> <?= htmlspecialchars($order['student_name']) ?></p>
    <p><span class="label">Organization:</span> <?= htmlspecialchars($order['organization']) ?></p>
    <p><span class="label">Course & Section:</span> <?= htmlspecialchars($order['course_section']) ?></p>
    <p><span class="label">Contact Number:</span> <?= htmlspecialchars($order['contact_number']) ?></p>
</div>

</body>
</html>
