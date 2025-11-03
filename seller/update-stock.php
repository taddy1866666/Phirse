<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $new_stock = $_POST['new_stock'] ?? null;
    $seller_id = $_SESSION['seller_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get the current product details
        $stmt = $pdo->prepare("SELECT p.*, s.organization, s.seller_name 
                              FROM products p 
                              JOIN sellers s ON p.seller_id = s.id 
                              WHERE p.id = ? AND p.seller_id = ?");
        $stmt->execute([$product_id, $seller_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found or you don't have permission to update it.");
        }

        if ($product['status'] !== 'approved') {
            throw new Exception("Only approved products can have their stock updated.");
        }

        // Update the stock
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ? AND seller_id = ?");
        $result = $stmt->execute([$new_stock, $product_id, $seller_id]);
        
        if (!$result) {
            error_log("Stock update failed. Error: " . json_encode($stmt->errorInfo()));
            throw new Exception("Failed to update stock. Please try again.");
        }

        $rowCount = $stmt->rowCount();
        if ($rowCount === 0) {
            error_log("Stock update affected 0 rows. Product ID: $product_id, Seller ID: $seller_id");
            throw new Exception("No changes were made to the stock. Please try again.");
        }

        // Insert admin notification
        $stmt = $pdo->prepare("INSERT INTO admin_notifications (type, title, message) VALUES (?, ?, ?)");
        
        $sellerLabel = $product['organization'] ?: $product['seller_name'];
        $stockChange = $new_stock - $product['stock'];
        $changeType = $stockChange > 0 ? 'increased' : 'decreased';
        $title = 'Stock Update';
        $message = sprintf(
            '%s (%s) • %s stock %s from %d to %d (%s%d units)', 
            $product['name'], 
            $sellerLabel,
            $product['category'],
            $changeType,
            $product['stock'],
            $new_stock,
            $stockChange > 0 ? '+' : '',
            $stockChange
        );

        $stmt->execute(['stock_update', $title, $message]);

        // Commit transaction
        $pdo->commit();

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully!']);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log the error
        error_log("Stock update error: " . $e->getMessage());
        
        // Return JSON error response
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // Return JSON error response for invalid request method
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
}