<?php
session_start();

// Verify admin is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Access denied. Please log in as admin.");
}

require_once '../database/config.php';

// Get product_id from request
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if (!$product_id) {
    http_response_code(400);
    die("Invalid product ID.");
}

try {
    // Fetch the product
    $stmt = $pdo->prepare("SELECT pdf_path, name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || empty($product['pdf_path'])) {
        http_response_code(404);
        die("PDF not found for this product.");
    }

    // Check if file exists
    $file_path = $product['pdf_path'];
    if (!file_exists($file_path)) {
        http_response_code(404);
        die("PDF file not found on server.");
    }

    // Verify it's a valid PDF file
    if (pathinfo($file_path, PATHINFO_EXTENSION) !== 'pdf') {
        http_response_code(403);
        die("Invalid file type.");
    }

    // Sanitize filename for download
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $product['name']) . '.pdf';
    
    // Set headers for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    // Read and output the file
    readfile($file_path);
    exit;

} catch(PDOException $e) {
    http_response_code(500);
    die("Database error: " . $e->getMessage());
}
?>
