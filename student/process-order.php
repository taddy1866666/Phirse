<?php
session_start();

date_default_timezone_set('Asia/Manila');
error_reporting(E_ALL);
ini_set('display_errors', 1);
include __DIR__ . '/db/config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['flash_message'] = "Please login to place an order.";
    $_SESSION['flash_type'] = "error";
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit();
}

// Get form data
$student_id = $_SESSION['student_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'onhand';
$product_size = isset($_POST['product_size']) ? $_POST['product_size'] : null;

// Get product info to validate size requirement (category + description/name)
$productCatSql = "SELECT category, description, name FROM products WHERE id = ?";
$productCat = fetchSingle($productCatSql, [$product_id], 'i');

// Determine whether this product actually requires a size selection.
// Only Organization Shirt always requires size. For Merchandise, require size
// only if the product name/description contains size indicators (e.g. S, M, L, XL, "Size", "Sizes", etc.).
$requires_size = false;
if ($productCat) {
    $cat = $productCat['category'];
    if ($cat === 'Organization Shirt') {
        $requires_size = true;
    } elseif ($cat === 'Merchandise') {
        $desc = strtolower($productCat['description'] ?? '');
        $name = strtolower($productCat['name'] ?? '');

        // common size keywords / tokens to look for
        $sizeIndicators = ['size', 'sizes', 'small', 'medium', 'large', 'xs', 's', 'm', 'l', 'xl', 'xxl', 'one size', 'size:'];

        foreach ($sizeIndicators as $kw) {
            if ($kw === 's' || $kw === 'm' || $kw === 'l') {
                // to avoid matching lots of unrelated words when checking single letters,
                // check for word boundaries in description and name
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $desc) || preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $name)) {
                    $requires_size = true;
                    break;
                }
            } else {
                if (strpos($desc, $kw) !== false || strpos($name, $kw) !== false) {
                    $requires_size = true;
                    break;
                }
            }
        }
    }
}

// Validate required fields
if ($product_id <= 0 || $seller_id <= 0 || $quantity <= 0 || $total_price <= 0) {
    $_SESSION['flash_message'] = "Invalid order data.";
    $_SESSION['flash_type'] = "error";
    header('Location: products.php');
    exit();
}

// Validate size when it's actually required
if ($requires_size && empty($product_size)) {
    $_SESSION['flash_message'] = "Please select a size for this item.";
    $_SESSION['flash_type'] = "error";
    header("Location: product-details.php?id=$product_id");
    exit();
}

// Validate payment method
if (!in_array($payment_method, ['gcash', 'onhand'])) {
    $_SESSION['flash_message'] = "Invalid payment method.";
    $_SESSION['flash_type'] = "error";
    header("Location: product-details.php?id=$product_id");
    exit();
}

// Handle payment proof for GCash only
$db_path = null;
$file = null;

if ($payment_method === 'gcash') {
    // Ensure seller has both GCash QR code and number; if not, reject GCash payment
    $sellerGcashInfo = fetchSingle("SELECT gcash_qr_path, gcash_number FROM sellers WHERE id = ? LIMIT 1", [$seller_id], 'i');
    $seller_qr = $sellerGcashInfo['gcash_qr_path'] ?? null;
    $seller_number = $sellerGcashInfo['gcash_number'] ?? null;

    // Robust QR code existence check: sellers may store paths in different formats (relative, uploads/gcash/..., etc.)
    $seller_qr_exists = false;
    if (!empty($seller_qr)) {
        $possible_qr_paths = [
            __DIR__ . '/../' . ltrim($seller_qr, '/'),
            __DIR__ . '/../uploads/gcash/' . basename($seller_qr),
            __DIR__ . '/../uploads/' . basename($seller_qr),
            __DIR__ . '/' . ltrim($seller_qr, '/'),
        ];
        foreach ($possible_qr_paths as $p) {
            if (file_exists($p)) {
                $seller_qr_exists = true;
                break;
            }
        }
    }

    // Normalize and validate phone number in common Filipino formats:
    // Accept: 09171234567 (11 digits), 9171234567 (10 digits), 639171234567 (12 digits)
    $seller_number_valid = false;
    if (!empty($seller_number)) {
        $normalized = preg_replace('/\D+/', '', $seller_number);
        $len = strlen($normalized);
        if (($len === 11 && strpos($normalized, '09') === 0) || ($len === 10 && strpos($normalized, '9') === 0) || ($len === 12 && strpos($normalized, '63') === 0)) {
            $seller_number_valid = true;
        }
    }

    if (!$seller_qr_exists || !$seller_number_valid) {
        $_SESSION['flash_message'] = "Seller's GCash details are incomplete. Please use Cash on Hand.";
        $_SESSION['flash_type'] = "error";
        header("Location: product-details.php?id=$product_id");
        exit();
    }

    // Validate payment proof upload for GCash
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_message'] = "Please upload payment proof for GCash payment.";
        $_SESSION['flash_type'] = "error";
        header("Location: product-details.php?id=$product_id");
        exit();
    }

    // Validate file type and size
    $file = $_FILES['payment_proof'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['flash_message'] = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
        $_SESSION['flash_type'] = "error";
        header("Location: product-details.php?id=$product_id");
        exit();
    }

    if ($file['size'] > $max_size) {
        $_SESSION['flash_message'] = "File size too large. Maximum 5MB allowed.";
        $_SESSION['flash_type'] = "error";
        header("Location: product-details.php?id=$product_id");
        exit();
    }
}

// Use student_id from session as user_id for orders
$user_id = $student_id;

// Check product availability
$productSql = "SELECT stock, price, name FROM products WHERE id = ?";
$product = fetchSingle($productSql, [$product_id], 'i');

if (!$product) {
    $_SESSION['flash_message'] = "Product not found.";
    $_SESSION['flash_type'] = "error";
    header('Location: products.php');
    exit();
}

if ($product['stock'] < $quantity) {
    $_SESSION['flash_message'] = "Insufficient stock available.";
    $_SESSION['flash_type'] = "error";
    header("Location: product-details.php?id=$product_id");
    exit();
}

// Handle file upload for GCash payment
$upload_path = null;
if ($payment_method === 'gcash') {
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/payment_proofs/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'payment_' . $user_id . '_' . $product_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    $db_path = '../uploads/payment_proofs/' . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $_SESSION['flash_message'] = "Failed to upload payment proof.";
        $_SESSION['flash_type'] = "error";
        header("Location: product-details.php?id=$product_id");
        exit();
    }
}

try {
    // Use the correct database connection (assuming you're using MySQLi based on your code)
    global $conn;
    
    // If $conn doesn't exist, try to get it from config
    if (!isset($conn)) {
        throw new Exception("Database connection not found.");
    }

    // Begin transaction
    $conn->begin_transaction();

    // Generate unique reference number
    $reference_number = generateReferenceNumber();

    // For cash on hand, status is 'pending'. For GCash with payment proof, it's 'paid'
    $order_status = ($payment_method === 'gcash' && !empty($db_path)) ? 'paid' : 'pending';

    // Insert order - using simpler approach for compatibility
    $insertOrderSql = "INSERT INTO orders 
                       (reference_number, user_id, seller_id, product_id, student_id, quantity, total_price, status, payment_method, payment_proof_path, product_size, order_date)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($insertOrderSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare order statement: " . $conn->error);
    }

    // Bind parameters more carefully
    $bind_types = 'siiiiidssss';
    $bind_vars = [
        $reference_number,  // s
        $user_id,           // i
        $seller_id,         // i  
        $product_id,        // i
        $student_id,        // i
        $quantity,          // i
        $total_price,       // d
        $order_status,      // s
        $payment_method,    // s
        $db_path,          // s (can be null for cash)
        $product_size       // s (can be null)
    ];
    
    if (!$stmt->bind_param($bind_types, ...$bind_vars)) {
        throw new Exception("Failed to bind parameters: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }

    $order_id = $conn->insert_id;
    $stmt->close();

    // Update product stock
    $updateStockSql = "UPDATE products SET stock = stock - ? WHERE id = ?";
    $stmt = $conn->prepare($updateStockSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare stock update: " . $conn->error);
    }
    
    $stmt->bind_param('ii', $quantity, $product_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update stock: " . $stmt->error);
    }
    $stmt->close();

    // Create notification for seller
    $student_number = $_SESSION['student_number'] ?? 'N/A';
    $student_name = $_SESSION['student_name'] ?? 'A student';
    $notif_title = "New Order Received";
    $notif_message = "New Order from {$student_name} ({$student_number})\nProduct: {$product['name']}\nQuantity: {$quantity}\nTotal: ₱" . number_format($total_price, 2);
    $notif_type = "order";

    $notifSql = "INSERT INTO seller_notifications (seller_id, product_id, order_id, type, title, message, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($notifSql);
    if ($stmt) {
        $stmt->bind_param('iiisss', $seller_id, $product_id, $order_id, $notif_type, $notif_title, $notif_message);
        $stmt->execute();
        $stmt->close();
    }

    // Create notification for student
    $student_notif_title = "Order Placed Successfully";
    $student_notif_message = "Your order for {$product['name']} has been placed. Reference: {$reference_number}";
    $student_notif_type = ($payment_method === 'gcash') ? 'paid' : 'pending';

    $studentNotifSql = "INSERT INTO notifications (student_id, order_id, product_id, type, title, message, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($studentNotifSql);
    if ($stmt) {
        $stmt->bind_param('iiisss', $student_id, $order_id, $product_id, $student_notif_type, $student_notif_title, $student_notif_message);
        $stmt->execute();
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Success message
    $_SESSION['flash_message'] = "Order placed successfully! Reference: {$reference_number}";
    $_SESSION['flash_type'] = "success";

    // Redirect to product details page with success dialog
    $redirect_url = "product-details.php?id=$product_id&success=1&order_id=$order_id&ref=$reference_number&payment_method=$payment_method&total=$total_price";
    header("Location: $redirect_url");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }

    // Delete uploaded file if order failed
    if ($upload_path && file_exists($upload_path)) {
        unlink($upload_path);
    }

    $error_msg = $e->getMessage();
    error_log("Order processing error: " . $error_msg);
    
    // Log to file for debugging
    file_put_contents(__DIR__ . '/order_errors.log', date('Y-m-d H:i:s') . " - " . $error_msg . "\n", FILE_APPEND);
    
    $_SESSION['flash_message'] = "Failed to place order: " . $error_msg;
    $_SESSION['flash_type'] = "error";
    header("Location: product-details.php?id=$product_id");
    exit();
}

/**
 * Generate unique reference number
 * Format: PHRS-YYYYMMDD-XXXXX (e.g., PHRS-20250101-A1B2C)
 */
function generateReferenceNumber() {
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
    return "PHRS-{$date}-{$random}";
}