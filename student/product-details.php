<?php
session_start();
include __DIR__ . '/db/config.php';

$pageTitle = 'Product Details';
include 'includes/header.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: products.php');
    exit();
}

// Get product details with seller information
$sql = "SELECT
            p.*,
            COALESCE(s.organization_name, s.organization, 'Student Organization') as organization_name,
            s.contact_number,
            s.email as seller_email,
            s.gcash_qr_path,
            s.gcash_number,
            s.id as seller_id
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE p.id = ?";

$product = fetchSingle($sql, [$product_id], 'i');

if (!$product) {
    header('Location: products.php');
    exit();
}

// Function to get product images
function getProductImages($imagePathString) {
    if (empty($imagePathString)) {
        return ['images/default-product.svg'];
    }

    $paths = array_filter(array_map('trim', explode(',', $imagePathString)));
    $validImages = [];

    foreach ($paths as $path) {
        $filename = basename($path);

        // Try different possible paths
        $possiblePaths = [
            __DIR__ . "/../uploads/products/{$filename}",
            __DIR__ . "/../seller/uploads/{$filename}",
            __DIR__ . "/../uploads/{$filename}",
            __DIR__ . "/images/{$filename}",
            $path
        ];

        foreach ($possiblePaths as $testPath) {
            if (file_exists($testPath)) {
                // Convert to web path
                if (strpos($testPath, __DIR__) === 0) {
                    $webPath = str_replace(__DIR__, '.', $testPath);
                    $validImages[] = str_replace('\\', '/', $webPath);
                } else {
                    $validImages[] = $path;
                }
                break;
            }
        }
    }

    return !empty($validImages) ? $validImages : ['images/default-product.svg'];
}

$productImages = getProductImages($product['image_path']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['student_id']);
$studentId = $isLoggedIn ? $_SESSION['student_id'] : null;

// Check if student is affiliated with the product's seller organization
$isAffiliated = false;
if ($isLoggedIn && $studentId) {
    $affiliationCheck = fetchSingle(
        "SELECT id FROM student_seller_affiliations WHERE student_id = ? AND seller_id = ? LIMIT 1",
        [$studentId, $product['seller_id']],
        'ii'
    );
    $isAffiliated = !empty($affiliationCheck);
}

// Check if this is an organization-only Event Ticket
$isOrganizationOnlyEvent = ($product['category'] === 'Event Ticket' && $product['ticket_type'] === 'for_organization');

displayFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - PHIRSE</title>
    <link rel="icon" href="N/A" type="image/png">

    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .product-details-container {
            max-width: 1200px;
            margin: 90px auto 0;
            padding: 2rem;
        }

        .breadcrumb {
            margin-bottom: 2rem;
            color: #666;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .product-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .product-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .main-image {
            width: 100%;
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-thumbnails {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }

        .thumbnail.active,
        .thumbnail:hover {
            border-color: #667eea;
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .product-category {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .product-organization {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 1rem;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
        }

        .product-stock {
            font-size: 1rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: inline-block;
        }

        .in-stock {
            background: #d4edda;
            color: #155724;
        }

        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }

        .product-description {
            color: #666;
            line-height: 1.8;
            font-size: 1rem;
        }

        .seller-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .seller-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1rem;
        }

        .seller-info p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .order-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .quantity-selector label {
            font-weight: 600;
            color: #333;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .quantity-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .quantity-input {
            width: 60px;
            height: 35px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .total-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .order-btn {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .order-btn:hover {
            background: #5a67d8;
        }

        .order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .login-prompt {
            text-align: center;
            padding: 1rem;
            background: #fff3cd;
            border-radius: 8px;
            border: 1px solid #ffc107;
        }

        .login-prompt a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }

        .restriction-prompt {
            text-align: center;
            padding: 1rem;
            background: #f8d7da;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .restriction-prompt i {
            margin-right: 0.5rem;
        }

        .order-btn.disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .order-btn.disabled:hover {
            background: #ccc;
        }

        /* Payment Modal */
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            /* Ensure modal overlays the bottom navigation (which uses z-index:1000) */
            z-index: 2100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            -webkit-overflow-scrolling: touch; /* Smooth scroll on iOS */
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: calc(85vh - 72px); /* Leave space for bottom nav */
            overflow-y: auto;
            position: relative;
            margin: 20px 20px 24px;
        }

        /* Better touch targets for mobile */
        @media (max-width: 768px) {
            .modal-content {
                padding: 1.5rem;
                width: 95%;
                margin: 10px;
            }

            .proof-upload input[type="file"] {
                width: 100%;
                height: 100%;
                opacity: 0;
                position: absolute;
                top: 0;
                left: 0;
                cursor: pointer;
                -webkit-tap-highlight-color: transparent;
            }

            /* Ensure the absolute-positioned file input is contained within the proof upload box
               so it doesn't overlay other elements (which caused taps elsewhere to open the file picker) */
            .proof-upload {
                position: relative;
            }

            .proof-upload label {
                position: relative;
                display: block;
                padding: 15px;
                background: #f8f9fa;
                border: 2px dashed #ddd;
                border-radius: 8px;
                text-align: center;
                margin: 10px 0;
                font-size: 16px;
                min-height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .payment-method-option {
                padding: 15px;
                min-height: 60px;
            }

            .payment-method-option input[type="radio"] {
                width: 24px;
                height: 24px;
                margin-right: 12px;
            }

            .confirm-order-btn,
            .order-btn,
            .payment-method-option,
            button {
                min-height: 48px;
                padding: 12px 20px;
                font-size: 16px;
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
            }

            .close-modal {
                position: absolute;
                right: 10px;
                top: 10px;
                width: 44px;
                height: 44px;
                font-size: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fa;
                border-radius: 50%;
                border: none;
                cursor: pointer;
            }

            /* Make modal scrolling smooth on iOS */
            .modal-content {
                -webkit-overflow-scrolling: touch;
            }

            /* Prevent text selection when tapping buttons */
            .payment-method-option,
            .confirm-order-btn,
            .order-btn,
            button {
                user-select: none;
                -webkit-user-select: none;
            }
        }

        /* Ensure disabled elements show correct state on mobile */
        @media (max-width: 768px) {
            .payment-method-option.disabled {
                opacity: 0.6;
                pointer-events: none;
            }

            input[type="file"]:disabled + label {
                opacity: 0.6;
                pointer-events: none;
                background: #eee;
            }

            /* Better touch feedback */
            .payment-method-option:active,
            .confirm-order-btn:active,
            .order-btn:active {
                transform: scale(0.98);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
        }

        .close-modal {
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .order-summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            padding-top: 0.5rem;
            border-top: 2px solid #ddd;
            margin-top: 0.5rem;
        }

        .payment-section {
            margin-bottom: 1.5rem;
        }

        .payment-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .gcash-qr {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .gcash-qr img {
            max-width: 300px;
            width: 100%;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .payment-instructions {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .payment-instructions ol {
            margin: 0.5rem 0 0 1rem;
            padding: 0;
        }

        .payment-instructions li {
            margin: 0.5rem 0;
            color: #333;
        }

        .proof-upload {
            margin-bottom: 1rem;
        }

        .proof-upload label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .proof-upload input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: pointer;
        }

        .confirm-order-btn {
            width: 100%;
            padding: 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            position: relative;
            z-index: 1100; /* Higher than bottom-links nav */
        }

        .confirm-order-btn:hover {
            background: #218838;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .payment-method-selector {
            margin-bottom: 1.5rem;
        }

        .payment-method-selector h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .payment-method-option {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            background: white;
        }

        .payment-method-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .payment-method-option.selected {
            border-color: #667eea;
            background: #e7f3ff;
        }

        .payment-method-option input[type="radio"] {
            display: none;
        }

        .payment-method-option i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .payment-method-option .method-name {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .payment-method-option .method-description {
            font-size: 0.85rem;
            color: #666;
        }

        .payment-method-option .method-info {
            flex: 1;
        }

        .payment-method-option .gcash-number {
            display: inline-block;
            color: #2563eb;
            font-weight: 500;
            margin-top: 4px;
        }

        .payment-method-option .gcash-number i {
            font-size: 0.8em;
            margin-right: 4px;
        }

        .payment-content {
            display: none;
        }

        .payment-content.active {
            display: block;
        }

        .cash-payment-info {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
        }

        .cash-payment-info h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }

        .cash-payment-info p {
            margin: 0.25rem 0;
            color: #856404;
            font-size: 0.9rem;
        }

        /* Success Dialog */
        .success-dialog-overlay {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
            padding: 20px;
        }

        .success-dialog-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-dialog {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            position: relative;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .success-icon {
            width: 60px;
            height: 60px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            animation: scaleIn 0.3s ease-out;
        }

        .success-icon i {
            color: white;
            font-size: 30px;
            animation: checkmark 0.3s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .dialog-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }

        .dialog-btn {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none !important;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            line-height: 20px;
            color: inherit;
        }

        .dialog-btn.btn-primary-dialog {
            background-color: #0d6efd;
            color: white !important;
        }

        .dialog-btn.btn-secondary-dialog {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #212529 !important;
        }

        .order-info-text {
            margin: 15px 0;
            text-align: center;
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-primary-dialog {
            background: #0066cc;
            color: white;
            border: none;
        }

        .btn-primary-dialog:hover {
            background: #0052a3;
        }

        .btn-secondary-dialog {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary-dialog:hover {
            background: #e9ecef;
        }

        .product-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }

        .product-info-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .product-info-value {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            text-align: right;
        }

        .reference-box {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: center;
        }

        .reference-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .reference-number {
            font-size: 16px;
            color: #0d6efd;
            font-weight: 600;
            word-break: break-all;
            letter-spacing: 1px;
            line-height: 1.4;
        }

        @media (min-width: 768px) {
            .reference-box {
                margin: 30px 0;
                padding: 20px;
            }

            .reference-label {
                font-size: 14px;
                margin-bottom: 10px;
            }

            .reference-number {
                font-size: 18px;
                letter-spacing: 1.5px;
            }
        }

        .order-details {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .order-details-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            line-height: 1.4;
        }

        .order-details-item:last-child {
            border-bottom: none;
        }
        
        .order-details-item span {
            color: #666;
            font-size: 14px;
        }

        .order-details-item strong {
            color: #333;
            font-size: 14px;
            text-align: right;
        }

        @media (min-width: 768px) {
            .order-details {
                padding: 25px;
                margin: 25px 0;
            }
            
            .order-details-item {
                padding: 15px 0;
                font-size: 15px;
            }

            .order-details-item span {
                font-size: 15px;
            }

            .order-details-item strong {
                font-size: 15px;
            }
        }

        .order-details-item span {
            color: #666;
        }

        .order-details-item strong {
            color: #333;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .success-dialog {
                width: 100%;
                margin: 0;
                padding: 20px;
                max-height: 90vh;
                overflow-y: auto;
            }

            .success-dialog h2 {
                font-size: 1.5rem;
                color: #28a745;
                margin-bottom: 10px;
            }

            .order-details-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
            }
            
            .order-details-item span {
                color: #666;
                font-size: 14px;
            }
            
            .order-details-item strong {
                color: #333;
                font-size: 14px;
                text-align: right;
            }

            .dialog-btn {
                padding: 15px;
                font-size: 16px;
                font-weight: 500;
                border-radius: 8px;
                width: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                text-decoration: none;
                margin-bottom: 8px;
                transition: all 0.2s ease;
            }

            .btn-primary-dialog {
                background-color: #0d6efd;
                border: none;
                color: white;
            }

            .btn-primary-dialog:hover {
                background-color: #0b5ed7;
                transform: translateY(-1px);
            }

            .btn-secondary-dialog {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                color: #212529;
            }
            
            .btn-secondary-dialog:hover {
                background-color: #e9ecef;
                transform: translateY(-1px);
            }

            @media (min-width: 768px) {
                .dialog-btn {
                    padding: 18px;
                    font-size: 17px;
                }
                
                .dialog-actions {
                    gap: 15px;
                    margin-top: 30px;
                }
            }

            .dialog-actions {
                margin-top: 20px;
            }
        }
        }

        .success-dialog {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.3s;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #28a745;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
        }

        .success-dialog h2 {
            color: #28a745;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .success-dialog p {
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .reference-box {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .reference-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .reference-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }

        .order-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .order-details-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .order-details-item strong {
            color: #333;
        }

        .dialog-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .dialog-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 1rem;
        }

        .btn-primary-dialog {
            background: #667eea;
            color: white;
        }

        .btn-primary-dialog:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .btn-secondary-dialog {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary-dialog:hover {
            background: #f8f9ff;
        }

        @media (max-width: 768px) {
            .product-details-container {
                padding: 1rem;
                margin-top: 80px;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .product-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .main-image {
                height: 300px;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .product-price {
                font-size: 2rem;
            }

            .quantity-selector {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-primary {
                width: 100%;
            }

            .success-dialog {
                padding: 2rem;
                margin: 1rem;
            }

            .reference-number {
                font-size: 1.5rem;
            }

            .dialog-actions {
                flex-direction: column;
            }

            .dialog-btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .product-details-container {
                padding: 0.75rem;
            }

            .breadcrumb {
                font-size: 13px;
                margin-bottom: 1rem;
            }

            .main-image {
                height: 250px;
            }

            .thumbnail-images {
                gap: 8px;
            }

            .thumbnail {
                width: 60px;
                height: 60px;
            }

            .product-title {
                font-size: 1.25rem;
            }

            .product-price {
                font-size: 1.75rem;
            }

            .product-info-item {
                padding: 12px;
            }

            .success-dialog {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'nav-bar-transparent.php'; ?>

    <div class="product-details-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a> /
            <a href="products.php">Products</a> /
            <?= htmlspecialchars($product['name']) ?>
        </div>

        <!-- Product Content -->
        <div class="product-content">
            <!-- Product Images -->
            <div class="product-images">
                <div class="main-image" id="mainImage">
                    <img src="<?= htmlspecialchars($productImages[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <?php if (count($productImages) > 1): ?>
                <div class="image-thumbnails">
                    <?php foreach ($productImages as $index => $image): ?>
                        <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage('<?= htmlspecialchars($image) ?>', this)">
                            <img src="<?= htmlspecialchars($image) ?>" alt="Product thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <?php if (!empty($product['category'])): ?>
                    <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                <?php endif; ?>

                <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

                <div class="product-organization">
                    <i class="fas fa-users"></i>
                    <?= htmlspecialchars($product['organization_name']) ?>
                </div>

                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>

                <div class="product-stock <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <?php if ($product['stock'] > 0): ?>
                        <i class="fas fa-check-circle"></i> <?= $product['stock'] ?> in stock
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i> Out of stock
                    <?php endif; ?>
                </div>

                <?php if (!empty($product['description'])): ?>
                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <div class="seller-info">
                    <h4><i class="fas fa-store"></i> Seller Information</h4>
                    <p><strong>Organization:</strong> <?= htmlspecialchars($product['organization_name']) ?></p>
                    <?php if (!empty($product['contact_number'])): ?>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($product['contact_number']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($product['seller_email'])): ?>
                        <p><strong>Email:</strong> <?= htmlspecialchars($product['seller_email']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Order Section -->
                <div class="order-section">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($product['stock'] > 0): ?>
                            <?php if ($isOrganizationOnlyEvent && !$isAffiliated): ?>
                                <!-- Organization-only Event Ticket restriction -->
                                <div class="restriction-prompt">
                                    <i class="fas fa-lock"></i>
                                    This Event Ticket is for organization members only. You must be a member of <strong><?= htmlspecialchars($product['organization_name']) ?></strong> to pre-order this item.
                                </div>
                                <button class="order-btn disabled" disabled>
                                    <i class="fas fa-lock"></i> Organization Members Only
                                </button>
                            <?php else: ?>
                                <?php if ($product['category'] === 'Organization Shirt' || $product['category'] === 'Merchandise'): 
                                    // Extract sizes from description
                                    $sizes = [];
                                    $description = $product['description'];
                                    if (preg_match('/Sizes:\s*([^:\n]+)/i', $description, $matches)) {
                                        // Get all sizes from the description, only clean up whitespace
                                        $sizes = array_map('trim', explode(',', $matches[1]));
                                        // Remove empty values
                                        $sizes = array_filter($sizes, function($size) {
                                            return !empty($size);
                                        });
                                    }
                                    if (!empty($sizes)):
                                ?>
                                <div class="size-selector" style="margin-bottom: 1rem;">
                                    <label>Size:</label>
                                    <select id="productSize" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px; margin-top: 0.5rem;" required>
                                        <option value="">Select Size</option>
                                        <?php foreach ($sizes as $size): ?>
                                        <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; endif; ?>

                                <div class="quantity-selector">
                                    <label>Quantity:</label>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                                        <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?= $product['max_order'] ? min($product['max_order'], $product['stock']) : $product['stock'] ?>" onchange="updateTotal()">
                                        <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                                    </div>
                                    <?php if ($product['max_order'] && $product['max_order'] < $product['stock']): ?>
                                        <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                            <i class="fas fa-info-circle"></i> Maximum <?= $product['max_order'] ?> per order
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <div class="total-price">
                                    Total: ₱<span id="totalPrice"><?= number_format($product['price'], 2) ?></span>
                                </div>

                                <button class="order-btn" onclick="openPaymentModal()">
                                    <i class="fas fa-shopping-cart"></i> Order Now
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="order-btn" disabled>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="login-prompt">
                            <i class="fas fa-info-circle"></i>
                            Please <a href="login.php">login</a> to place an order
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-credit-card"></i> Payment Details</h2>
                <button class="close-modal" onclick="closePaymentModal()">&times;</button>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="order-summary-item">
                    <span>Product:</span>
                    <span><?= htmlspecialchars($product['name']) ?></span>
                </div>
                <div class="order-summary-item">
                    <span>Price:</span>
                    <span>₱<?= number_format($product['price'], 2) ?></span>
                </div>
                <div class="order-summary-item">
                    <span>Quantity:</span>
                    <span id="modalQuantity">1</span>
                </div>
                <div class="order-summary-total">
                    <span>Total:</span>
                    <span>₱<span id="modalTotal"><?= number_format($product['price'], 2) ?></span></span>
                </div>
            </div>

            <!-- Payment Method Selector -->
            <div class="payment-method-selector">
                <h3><i class="fas fa-wallet"></i> Select Payment Method</h3>
                <div class="payment-methods">
                    <?php 
                    // Get current seller's GCash QR path
                    $seller_id = $product['seller_id'];
                    $qr_path = $product['gcash_qr_path'];
                    
                    // Check multiple possible paths for this specific seller's QR code
                    $possible_qr_paths = [
                        __DIR__ . '/../uploads/gcash/' . basename($qr_path),  // Main GCash uploads directory
                        __DIR__ . '/../' . ltrim($qr_path, '/'),              // Relative to student directory
                        __DIR__ . '/' . ltrim($qr_path, '/'),                 // Direct in student directory
                        __DIR__ . '/../uploads/' . basename($qr_path),        // General uploads directory
                    ];
                    
                    // Only enable GCash if this specific seller has uploaded their QR
                    $has_gcash_qr = false;
                    if (!empty($qr_path) && !empty($seller_id)) {
                        foreach ($possible_qr_paths as $path) {
                            if (file_exists($path)) {
                                $has_gcash_qr = true;
                                break;
                            }
                        }
                    }
                    ?>
                    <label class="payment-method-option <?= $has_gcash_qr ? '' : 'disabled' ?>" id="gcashOption">
                        <input type="radio" name="payment_method" value="gcash" <?= $has_gcash_qr ? '' : 'disabled' ?> onchange="selectPaymentMethod('gcash')">
                        <i class="fas fa-qrcode"></i>
                        <div class="method-info">
                            <div class="method-name">GCash</div>
                            <div class="method-description">
                                Pay via GCash QR Code

                            </div>
                        </div>
                        <?php if (!$has_gcash_qr): ?>
                        <div class="tooltip">This seller hasn't set up GCash payments yet. Please use Cash on Hand.</div>
                        <?php endif; ?>
                    </label>

                    <label class="payment-method-option <?= $has_gcash_qr ? '' : 'selected' ?>" id="cashOption">
                        <input type="radio" name="payment_method" value="cash" <?= $has_gcash_qr ? '' : 'checked' ?> onchange="selectPaymentMethod('cash')">
                        <i class="fas fa-money-bill-wave"></i>
                        <div class="method-name">Cash on Hand</div>
                        <div class="method-description">Pay with cash</div>
                    </label>
                </div>
            </div>

            <!-- GCash Payment Content -->
            <div class="payment-content active" id="gcashContent">
                <div class="payment-section">
                    <h3><i class="fas fa-qrcode"></i> GCash Payment</h3>

                    <div class="payment-instructions">
                        <strong>Payment Instructions:</strong>
                        <ol>
                            <li>Scan the QR code below using your GCash app</li>
                            <li>Pay the exact total amount</li>
                            <li>Take a screenshot of the payment confirmation</li>
                            <li>Upload the screenshot below</li>
                            <li>Click "Confirm Order" to complete</li>
                        </ol>
                    </div>

                    <?php if ($has_gcash_qr): ?>
                        <div class="gcash-qr">
                            <img src="<?= htmlspecialchars($product['gcash_qr_path']) ?>" alt="GCash QR Code">
                            <?php if (!empty($product['gcash_number'])): ?>
                                <div style="text-align: center; margin-top: 10px; font-size: 16px; color: #333;">
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($product['gcash_number']) ?>
                                </div>
                            <?php endif; ?>
                            <p style="color: #666; margin-top: 0.5rem; font-size: 0.9rem;">
                                Scan this QR code to pay via GCash
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            GCash QR code not available. Seller hasn't uploaded their GCash QR — GCash payment is not available for this product.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cash Payment Content -->
            <div class="payment-content" id="cashContent">
                <div class="cash-payment-info">
                    <h4><i class="fas fa-info-circle"></i> Cash on Hand Payment</h4>
                    <p><strong>Instructions:</strong></p>
                    <p>• Your order will be marked as "Pending"</p>
                    <p>• Prepare the exact amount: ₱<span id="cashAmount"><?= number_format($product['price'], 2) ?></span></p>
                    <p>• Seller will confirm once payment is received</p>
                </div>

                <div class="payment-instructions">
                    <strong>Important Notes:</strong>
                    <ol>
                        <li>Order confirmation does not guarantee availability</li>
                        <li>Payment must be made within the agreed timeframe</li>
                        <li>Order may be cancelled if payment is not received</li>
                    </ol>
                </div>
            </div>

            <form id="orderForm" method="POST" action="process-order.php" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="seller_id" value="<?= $product['seller_id'] ?>">
                <input type="hidden" name="quantity" id="formQuantity" value="1">
                <input type="hidden" name="total_price" id="formTotalPrice" value="<?= $product['price'] ?>">
                <input type="hidden" name="payment_method" id="formPaymentMethod" value="gcash">
                <?php if ($product['category'] === 'Organization Shirt' || $product['category'] === 'Merchandise'): ?>
                <input type="hidden" name="product_size" id="formProductSize" value="">
                <?php endif; ?>

                <div class="proof-upload" id="proofUploadSection">
                    <label for="paymentProof">
                        <i class="fas fa-upload"></i> Upload Payment Proof (Screenshot) *
                    </label>
                    <input type="file" id="paymentProof" name="payment_proof" accept="image/*" <?= $has_gcash_qr ? '' : 'disabled' ?>>
                    <p style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">
                        Accepted formats: JPG, PNG, JPEG (Max 5MB)
                    </p>
                </div>

                <button type="submit" class="confirm-order-btn">
                    <i class="fas fa-check-circle"></i> Confirm Order
                </button>
            </form>
        </div>
    </div>

    <style>
        /* Success Dialog Animations */
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Success Dialog Base Styles */
        .success-dialog {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            position: relative;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .success-dialog h2 {
            font-size: 1.8rem;
            color: #28a745;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .success-dialog > p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .success-dialog {
                padding: 40px;
                max-width: 600px;
            }

            .success-dialog .success-icon {
                width: 80px;
                height: 80px;
                margin-bottom: 25px;
            }

            .success-dialog .success-icon i {
                font-size: 40px;
            }

            .dialog-actions {
                padding: 0 40px;
                margin-bottom: 80px;
            }

            .dialog-btn {
                padding: 16px;
                font-size: 16px;
            }
        }

        /* Product Summary Styles */
        .product-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .product-info-item {
            display: grid;
            grid-template-columns: 100px 1fr;
            align-items: center;
            gap: 10px;
        }

        .product-info-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .product-info-value {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            text-align: left;
        }

        /* Order Details Styles */
        .order-details {
            background: #fff;
            padding: 15px 0;
            margin: 15px 0;
            border-top: 1px solid #eee;
        }

        .order-details-item {
            display: grid;
            grid-template-columns: 100px 1fr;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            line-height: 1.4;
        }

        .order-details-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .order-details-item:first-child {
            padding-top: 0;
        }
        
        .order-details-item span {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .order-details-item strong {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            text-align: left;
        }

        .order-details-item:last-child strong {
            color: #0d6efd;
            font-weight: 600;
        }

        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            100% { transform: scale(1) rotate(0); opacity: 1; }
        }

        .success-dialog .success-icon {
            width: 70px;
            height: 70px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.3s ease-out;
        }

        .success-dialog .success-icon i {
            color: white;
            font-size: 35px;
            animation: checkmark 0.3s ease-out 0.2s both;
        }

        .success-dialog h2 {
            color: #28a745;
            margin-bottom: 15px;
        }

        .success-dialog > p {
            color: #666;
            font-size: 15px;
            margin-bottom: 20px;
        }
    </style>

    <!-- Success Dialog -->
    <div id="successDialog" class="success-dialog-overlay">
        <div class="success-dialog">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>Order Placed Successfully!</h2>
            <p>Your order has been received and is being processed.</p>

            <div class="reference-box">
                <div class="reference-label">Reference Number</div>
                <div class="reference-number" id="referenceNumber">-</div>
            </div>

            <div class="product-summary">
                <div class="product-info">
                    <div class="product-info-item">
                        <span class="product-info-label">Product Name:</span>
                        <span class="product-info-value" id="productNameDisplay">-</span>
                    </div>
                    <div class="product-info-item" id="sizeContainer">
                        <span class="product-info-label">Size:</span>
                        <span class="product-info-value" id="productSizeDisplay">-</span>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Quantity:</span>
                        <span class="product-info-value" id="productQuantityDisplay">-</span>
                    </div>
                </div>
            </div>

            <div class="order-details">
                <div class="order-details-item">
                    <span>Order ID:</span>
                    <strong id="orderIdDisplay">-</strong>
                </div>
                <div class="order-details-item">
                    <span>Payment Method:</span>
                    <strong id="paymentMethodDisplay">-</strong>
                </div>
                <div class="order-details-item">
                    <span>Total Amount:</span>
                    <strong id="totalAmountDisplay">-</strong>
                </div>
            </div>

            <div class="order-info-text">
                <i class="fas fa-info-circle"></i> Please save your reference number for tracking purposes.
            </div>
            
            <div class="dialog-actions" style="margin-bottom: 70px;">
                <a href="myorders.php" class="dialog-btn btn-primary-dialog">
                    <i class="fas fa-shopping-bag"></i> View My Orders
                </a>
                <a href="products.php" class="dialog-btn btn-secondary-dialog">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <p>&copy; 2025 PHIRSE. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/script.js"></script>

    <script>
        const productPrice = <?= $product['price'] ?>;
        const maxStock = <?= $product['stock'] ?>;
        const maxOrder = <?= $product['max_order'] ?? 'null' ?>;
        const sellerId = <?= $product['seller_id'] ?>; // Add seller ID for AJAX checks

        // Check for GCash QR updates every 30 seconds
        function checkGcashQrStatus() {
            fetch('check-seller-qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'seller_id=' + sellerId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.has_qr) {
                    // Enable GCash option
                    const gcashOption = document.getElementById('gcashOption');
                    const gcashInput = gcashOption.querySelector('input[type="radio"]');
                    const paymentProof = document.getElementById('paymentProof');
                    const tooltip = gcashOption.querySelector('.tooltip');
                    const badge = gcashOption.querySelector('.badge-warning');
                    
                    // Remove disabled state
                    gcashOption.classList.remove('disabled');
                    gcashInput.disabled = false;
                    if (paymentProof) paymentProof.disabled = false;
                    
                    // Remove tooltip and badge
                    if (tooltip) tooltip.remove();
                    if (badge) badge.remove();

                    // Update GCash QR image if shown
                    const qrImage = document.querySelector('.gcash-qr img');
                    if (qrImage && data.qr_path) {
                        qrImage.src = data.qr_path;
                        const qrContainer = qrImage.closest('.gcash-qr');
                        if (qrContainer) {
                            const errorAlert = qrContainer.parentNode.querySelector('.alert-error');
                            if (errorAlert) errorAlert.remove();
                        }
                    }

                    // Stop checking once QR is found
                    clearInterval(qrCheckInterval);
                }
            })
            .catch(error => console.error('Error checking GCash QR status:', error));
        }

        // Start periodic checks if GCash is currently disabled
        let qrCheckInterval;
        if (document.querySelector('#gcashOption.disabled')) {
            checkGcashQrStatus(); // Check immediately
            qrCheckInterval = setInterval(checkGcashQrStatus, 30000); // Then every 30 seconds
        }

        function changeImage(imageSrc, thumbnail) {
            const mainImage = document.querySelector('#mainImage img');
            mainImage.src = imageSrc;

            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        function increaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseInt(quantityInput.value);
            const maxAllowed = maxOrder ? Math.min(maxStock, maxOrder) : maxStock;

            if (currentValue < maxAllowed) {
                quantityInput.value = currentValue + 1;
                updateTotal();
            }
        }

        function decreaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseInt(quantityInput.value);

            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
                updateTotal();
            }
        }

        function updateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const maxAllowed = maxOrder ? Math.min(maxStock, maxOrder) : maxStock;
            
            // Ensure quantity doesn't exceed max allowed
            if (quantity > maxAllowed) {
                document.getElementById('quantity').value = maxAllowed;
                return updateTotal(); // Recursive call with corrected value
            }
            
            const total = productPrice * quantity;
            document.getElementById('totalPrice').textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function selectPaymentMethod(method) {
            // Update UI
            const gcashOption = document.getElementById('gcashOption');
            const cashOption = document.getElementById('cashOption');
            const gcashContent = document.getElementById('gcashContent');
            const cashContent = document.getElementById('cashContent');
            const proofUploadSection = document.getElementById('proofUploadSection');
            const paymentProof = document.getElementById('paymentProof');

            // If GCash option is disabled (no QR), prevent selecting it
            const gcashInput = document.querySelector('#gcashOption input[name="payment_method"]');
            const gcashDisabled = gcashInput && gcashInput.disabled;

            if (method === 'gcash' && gcashDisabled) {
                alert('GCash payment is not available for this seller. Please choose Cash on Hand.');
                // fallback to cash
                method = 'cash';
            }

            if (method === 'gcash') {
                gcashOption.classList.add('selected');
                cashOption.classList.remove('selected');
                gcashContent.classList.add('active');
                cashContent.classList.remove('active');
                proofUploadSection.style.display = 'block';
                paymentProof.required = true;
            } else {
                cashOption.classList.add('selected');
                gcashOption.classList.remove('selected');
                cashContent.classList.add('active');
                gcashContent.classList.remove('active');
                proofUploadSection.style.display = 'none';
                paymentProof.required = false;
            }

            // Update form hidden field
            document.getElementById('formPaymentMethod').value = method;

            // Update cash amount display
            const quantity = parseInt(document.getElementById('quantity').value);
            const total = productPrice * quantity;
            document.getElementById('cashAmount').textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function openPaymentModal() {
            // Check if size is required and selected
            const sizeSelect = document.getElementById('productSize');
            if (sizeSelect && sizeSelect.value === '') {
                alert('Please select a size before proceeding.');
                return;
            }

            const quantity = parseInt(document.getElementById('quantity').value);
            const total = productPrice * quantity;

            document.getElementById('modalQuantity').textContent = quantity;
            document.getElementById('modalTotal').textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            document.getElementById('formQuantity').value = quantity;
            document.getElementById('formTotalPrice').value = total.toFixed(2);

            // Update product size if applicable
            if (sizeSelect) {
                document.getElementById('formProductSize').value = sizeSelect.value;
            }

            // Update cash amount
            document.getElementById('cashAmount').textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            document.getElementById('paymentModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target === modal) {
                closePaymentModal();
            }
        }

        // Check for success parameter in URL and initialize mobile enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile enhancements: avoid interfering with native touch interactions for inputs/buttons.

            // Enhance file input for mobile
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                const fileLabel = fileInput.nextElementSibling;
                const originalText = fileLabel.textContent;

                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const fileName = this.files[0].name;
                        fileLabel.textContent = fileName.length > 25 ? 
                            fileName.substring(0, 22) + '...' : fileName;
                    } else {
                        fileLabel.textContent = originalText;
                    }
                });

                // Reset file input if there's an error
                fileInput.addEventListener('invalid', function() {
                    this.value = '';
                    fileLabel.textContent = originalText;
                });
            }

            // URL parameters check
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            const orderId = urlParams.get('order_id');
            const refNum = urlParams.get('ref');
            const paymentMethod = urlParams.get('payment_method');
            const total = urlParams.get('total');

            if (success === '1' && orderId && refNum) {
                showSuccessDialog(orderId, refNum, paymentMethod, total);
            }
            // Ensure payment UI matches the initially-checked method (handles disabled GCash)
            const checked = document.querySelector('input[name="payment_method"]:checked');
            if (checked) {
                selectPaymentMethod(checked.value);
            }
        });

        function showSuccessDialog(orderId, referenceNumber, paymentMethod, totalAmount) {
            // Get product details from the page
            const productName = document.querySelector('h1.product-title')?.textContent || '';
            const selectedSize = document.getElementById('formProductSize')?.value || '';
            const quantity = document.getElementById('formQuantity')?.value || '1';

            // Populate dialog with data
            document.getElementById('referenceNumber').textContent = referenceNumber;
            document.getElementById('orderIdDisplay').textContent = '#' + orderId;
            document.getElementById('paymentMethodDisplay').textContent = paymentMethod === 'gcash' ? 'GCash' : 'Cash on Hand';
            document.getElementById('totalAmountDisplay').textContent = '₱' + parseFloat(totalAmount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            // Populate product details
            document.getElementById('productNameDisplay').textContent = productName;
            document.getElementById('productQuantityDisplay').textContent = quantity + ' pcs';
            
            // Handle size display
            const sizeContainer = document.getElementById('sizeContainer');
            if (selectedSize) {
                document.getElementById('productSizeDisplay').textContent = selectedSize;
            } else {
                sizeContainer.style.display = 'none';
            }

            // Show dialog
            document.getElementById('successDialog').classList.add('active');
            document.body.style.overflow = 'hidden';

            // Close payment modal if open
            closePaymentModal();
        }
    </script>
    <?php include __DIR__ . '/includes/mobile-bottom-nav.php'; ?>
</body>
</html>
