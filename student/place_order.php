<?php
session_start();
require_once 'db/config.php';

// Make sure the student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get data from frontend (via POST)
$student_id  = $_SESSION['student_id'];
$seller_id   = $_POST['seller_id'] ?? null;
$product_id  = $_POST['product_id'] ?? null;
$quantity    = $_POST['quantity'] ?? 1;
$total_price = $_POST['total_price'] ?? 0;

if (!$seller_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert into orders table
try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (student_id, seller_id, product_id, quantity, total_price)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$student_id, $seller_id, $product_id, $quantity, $total_price]);

    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
<form action="place_order.php" method="POST">
  <input type="hidden" name="seller_id" value="<?= $seller_id; ?>">
  <input type="hidden" name="product_id" value="<?= $product_id; ?>">
  <input type="hidden" name="quantity" value="1">
  <input type="hidden" name="total_price" value="<?= $price; ?>">
  <button type="submit">Order Now</button>
</form>
    