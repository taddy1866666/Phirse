<?php
session_start();
require_once '../database/config.php';

$pageTitle = 'Orders';
include 'includes/seller-header.php';

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

try {
    $seller_id = $_SESSION['seller_id'];

    // Fetch seller info
    $stmt = $pdo->prepare("SELECT seller_name, organization, logo_path FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller_info) {
        session_destroy();
        header('Location: ../index.html');
        exit();
    }

    $seller_logo = $seller_info['logo_path'] ?? null;
    $organization_name = $seller_info['organization'] ?? '';
    $seller_name = $seller_info['seller_name'] ?? '';

    // Check if cancellation_reason column exists
    $hasReasonColumn = false;
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'cancellation_reason'");
        $hasReasonColumn = $testStmt && $testStmt->rowCount() > 0;
    } catch(Exception $e) {
        $hasReasonColumn = false;
    }

    // Fetch seller orders with conditional column selection
    if ($hasReasonColumn) {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.quantity, o.total_price, o.order_date, o.status,
                o.payment_method, o.payment_proof_path, o.reference_number,
                o.claiming_datetime, o.product_size, o.cancellation_reason,
                p.name AS product_name,
                s.student_number, s.student_name, s.organization AS student_organization,
                s.course_section, s.contact_number
            FROM orders o
            JOIN products p ON o.product_id = p.id
            LEFT JOIN students s ON o.student_id = s.id
            WHERE p.seller_id = ?
            ORDER BY o.order_date DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.quantity, o.total_price, o.order_date, o.status,
                o.payment_method, o.payment_proof_path, o.reference_number,
                o.claiming_datetime, o.product_size,
                p.name AS product_name,
                s.student_number, s.student_name, s.organization AS student_organization,
                s.course_section, s.contact_number
            FROM orders o
            JOIN products p ON o.product_id = p.id
            LEFT JOIN students s ON o.student_id = s.id
            WHERE p.seller_id = ?
            ORDER BY o.order_date DESC
        ");
    }
    $stmt->execute([$seller_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add cancellation_reason to each order if column doesn't exist
    if (!$hasReasonColumn) {
        foreach ($orders as &$order) {
            $order['cancellation_reason'] = null;
        }
        unset($order);
    }

} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Phirse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            height: 100vh;
        }

        .organization-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #333;
            margin: 0 auto;
            display: block;
        }

        .organization-text {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }

        .organization-name {
            font-size: 14px;
            font-weight: 500;
            margin: 8px 0 0 0;
            text-align: center;
            color: #ccc;
        }

        .main-content {
            margin-left: 220px;
            flex: 1;
            padding: 30px;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
        }

        .breadcrumb i {
            margin-right: 8px;
        }

        .welcome-text {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .orders-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .orders-header {
            margin-bottom: 20px;
        }

        .orders-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .orders-subtitle {
            color: #666;
            font-size: 16px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th {
            background-color: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-bottom: 2px solid #e9ecef;
        }

        .orders-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            color: #333;
        }

        .orders-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            text-transform: uppercase; 
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-claiming {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background-color: #cfe2ff;
            color: #084298;
        }

        .status-confirmed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #842029;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .btn-cancel:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-cancelled {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c6cb;
        }

        .btn-cancelled:hover {
            background-color: #f5c6cb;
        }

        .btn-cancelled:disabled {
            cursor: not-allowed;
            opacity: 0.8;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            margin-right: 8px;
            margin-bottom: 4px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view {
            background-color: #667eea;
            color: white;
        }

        .btn-view:hover {
            background-color: #5568d3;
            transform: translateY(-1px);
        }

        .btn-received {
            background-color: #10b981;
            color: white;
        }

        .btn-received:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .btn-received:disabled {
            background-color: #d1d5db;
            cursor: not-allowed;
            opacity: 1;
            transform: none;
        }

        .btn-cancel {
            background-color: #ef4444;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }

        .btn-cancel:disabled {
            background-color: #d1d5db;
            cursor: not-allowed;
            opacity: 1;
            transform: none;
        }

        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-orders i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }

        .close {
            color: #aaa;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            padding: 30px;
        }

        .order-detail {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
        }

        .order-detail strong {
            min-width: 160px;
            color: #555;
            font-weight: 600;
        }

        .order-detail span {
            color: #333;
        }

        .proof-image {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            margin-top: 10px;
            cursor: pointer;
        }

        .no-proof {
            color: #999;
            font-style: italic;
        }

        .modal-footer {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-confirm-order {
            background-color: #198754;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-confirm-order:hover {
            background-color: #157347;
        }

        /* Cancellation Modal */
        .modal.cancel-modal {
            display: none;
        }

        .cancel-modal .modal-content {
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        /* Reason Button Styles */
        .reason-btn {
            padding: 10px 12px;
            background: #f0f0f0;
            color: #333;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: center;
            width: 100%;
        }

        .reason-btn:hover {
            background: #e8e8e8;
            border-color: #bbb;
        }

        .reason-btn-active {
            background: #4CAF50;
            color: white;
            border-color: #45a049;
            font-weight: 600;
        }

        .reason-btn-active:hover {
            background: #45a049;
            border-color: #3d8b40;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .modal-content {
                margin: 20% auto;
                width: 95%;
                padding: 20px;
            }

            .modal-header h2 {
                font-size: 18px;
            }

            .reason-btn {
                font-size: 12px;
                padding: 8px 10px;
            }

            /* Single column for mobile */
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }

        .modal-footer-cancel {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
        }

        .mobile-menu-toggle:hover {
            background: #333;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px 15px;
            }

            .top-bar {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .page-title {
                font-size: 20px;
                text-align: center;
            }

            .orders-container {
                overflow-x: auto;
            }

            table {
                min-width: 900px;
            }

            th, td {
                padding: 10px;
                font-size: 12px;
            }

            .order-actions {
                flex-direction: column;
                gap: 5px;
            }

            .btn-view-receipt,
            .btn-confirm-order {
                padding: 6px 10px;
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 10px 10px;
            }

            .page-title {
                font-size: 18px;
            }

            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>
   
    <div class="main-content">
        <div class="top-bar">
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                Home > Orders
            </div>
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($seller_name ?: 'Seller'); ?>!
            </div>
        </div>

        <div class="orders-container">
            <div class="orders-header">
                <h1 class="orders-title">Customer Orders</h1>
                <p class="orders-subtitle">This is where your orders will be shown.</p>
            </div>

            <div style="display: flex; gap: 10px; margin-bottom: 20px; justify-content: flex-end;">
                <a href="export_seller_orders.php" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 10px 20px;
                    border-radius: 6px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                    transition: all 0.3s ease;
                    border: none;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                    <i class="fas fa-file-pdf"></i>
                    Export to PDF
                </a>
            </div>

            <?php if (isset($error_message)): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($orders)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Organization</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['student_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['student_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['student_organization'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <?php 
                                    // Debug: Check what status value we have
                                    $current_status = $order['status'] ?? 'pending';
                                    if (empty($current_status)) {
                                        $current_status = 'pending';
                                    }
                                    ?>
                                    <span class="status-badge status-<?php echo strtolower($current_status); ?>">
                                        <?php echo strtoupper($current_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick='viewOrder(<?php echo json_encode($order); ?>)' class="action-btn btn-view">
                                        View
                                    </button>
                                    <?php if ($order['status'] === 'cancelled'): ?>
                                        <button class="action-btn btn-cancelled" disabled>
                                            <i class="fas fa-times-circle"></i> Cancelled
                                        </button>
                                    <?php elseif ($order['status'] === 'completed'): ?>
                                        <button class="action-btn btn-received" disabled>
                                            <i class="fas fa-check-circle"></i> Received
                                        </button>
                                    <?php else: ?>
                                        <button onclick="markAsReceived(<?php echo $order['id']; ?>)"
                                                class="action-btn btn-received">
                                            Mark as Received
                                        </button>
                                        <button onclick="openCancellationModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['product_name'], ENT_QUOTES); ?>')"
                                                class="action-btn btn-cancel">
                                            Cancel Order
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <p>No orders found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Order Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="modal cancel-modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Cancel Order</h2>
                <button class="close" onclick="closeCancellationModal()">&times;</button>
            </div>
            <form id="cancelForm" style="padding: 20px;">
                <p style="margin-bottom: 15px; color: #555;">
                    <strong>Product:</strong> <span id="cancelProductName"></span>
                </p>
                
                <div class="form-group">
                    <label class="form-label">Select Reason(s) (or type custom reason below)</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                        <button type="button" class="reason-btn" data-reason="Product discontinued">
                            Discontinued
                        </button>
                        <button type="button" class="reason-btn" data-reason="Inventory unavailable">
                            Inventory Issue
                        </button>
                        <button type="button" class="reason-btn" data-reason="Defective product">
                            Defective Product
                        </button>
                        <button type="button" class="reason-btn" data-reason="Payment not received">
                            Payment Not Received
                        </button>
                        <button type="button" class="reason-btn" data-reason="Supplier issue">
                            Supplier Problem
                        </button>
                        <button type="button" class="reason-btn" data-reason="Other">
                            Others
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cancellationReason" class="form-label">
                        Cancellation Reason <span style="color: red;">*</span>
                    </label>
                    <textarea 
                        id="cancellationReason" 
                        class="form-textarea" 
                        placeholder="Selected reasons will appear here. You can edit or add custom text..."
                        required
                        style="min-height: 120px; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"
                    ></textarea>
                    <small style="color: #666; margin-top: 8px; display: block;">
                        Click reason buttons to add them (multiple selections allowed). Edit the text as needed.
                    </small>
                </div>
                
                <div class="modal-footer-cancel" style="gap: 10px; display: flex; justify-content: flex-end;">
                    <button type="button" onclick="closeCancellationModal()" class="btn-secondary">
                        Keep Order
                    </button>
                    <button type="submit" class="btn-danger">
                        <i class="fas fa-trash-alt"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentCancellationOrderId = null;
        const selectedReasons = new Set();

        function markAsPaid(orderId) {
            if (confirm('Mark this order as Paid?')) {
                fetch('update-order-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&status=paid`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order marked as Paid!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update status'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }

        function setClaimingDateTime(orderId) {
            const datetimeInput = document.getElementById('claimingDateTime');
            const claimingDateTime = datetimeInput.value;

            if (!claimingDateTime) {
                alert('Please select a date and time.');
                return;
            }

            if (confirm(`Set claiming date & time to ${claimingDateTime}?`)) {
                fetch('update-order-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&status=claiming&claiming_datetime=${encodeURIComponent(claimingDateTime)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Claiming date & time set successfully!');
                        location.reload(); // Reload the page to show updated status
                    } else {
                        alert('Error: ' + (data.message || 'Failed to set claiming date'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }

        function openCancellationModal(orderId, productName) {
            currentCancellationOrderId = orderId;
            document.getElementById('cancelProductName').textContent = productName;
            document.getElementById('cancellationReason').value = '';
            document.getElementById('cancelModal').style.display = 'block';
            
            // Reset reason buttons
            selectedReasons.clear();
            document.querySelectorAll('.reason-btn').forEach(btn => {
                btn.classList.remove('reason-btn-active');
            });
        }

        function closeCancellationModal() {
            document.getElementById('cancelModal').style.display = 'none';
            currentCancellationOrderId = null;
            document.getElementById('cancellationReason').value = '';
            selectedReasons.clear();
            document.querySelectorAll('.reason-btn').forEach(btn => {
                btn.classList.remove('reason-btn-active');
            });
        }

        // Setup reason button selection on page load
        document.addEventListener('DOMContentLoaded', function() {
            const reasonButtons = document.querySelectorAll('.reason-btn');
            const cancellationReasonInput = document.getElementById('cancellationReason');
            const cancelForm = document.getElementById('cancelForm');
            
            // Setup reason button clicks
            reasonButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const reason = this.dataset.reason;
                    
                    // Toggle selection
                    if (selectedReasons.has(reason)) {
                        selectedReasons.delete(reason);
                        this.classList.remove('reason-btn-active');
                    } else {
                        selectedReasons.add(reason);
                        this.classList.add('reason-btn-active');
                    }
                    
                    // Update textarea with selected reasons
                    if (selectedReasons.size > 0) {
                        cancellationReasonInput.value = Array.from(selectedReasons)
                            .map(r => '• ' + r)
                            .join('\n');
                    } else {
                        cancellationReasonInput.value = '';
                    }
                });
            });
            
            // Handle form submission
            if (cancelForm) {
                cancelForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const reasonText = cancellationReasonInput.value.trim();
                    if (!reasonText) {
                        alert('Please select at least one cancellation reason or provide your own.');
                        return;
                    }
                    
                    if (!confirm('Are you sure you want to cancel this order? This will notify the student.')) {
                        return;
                    }
                    
                    fetch('update-order-status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `order_id=${currentCancellationOrderId}&status=cancelled&cancellation_reason=${encodeURIComponent(reasonText)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Order cancelled successfully! Student has been notified.');
                            closeCancellationModal();
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to cancel order'));
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error);
                    });
                });
            }
        });

        function viewOrder(order) {
            console.log('Order data:', order);
            const modal = document.getElementById('orderModal');
            const modalBody = document.getElementById('modalBody');

            // Build payment proof HTML
            let proofHTML = '';
            if (order.payment_proof_path) {
                let imagePath = order.payment_proof_path.replace(/^\.\.\//, '');
                proofHTML = `
                    <div class="order-detail">
                        <strong>Payment Proof:</strong>
                        <div>
                            <img src="../${imagePath}"
                                 alt="Payment Proof"
                                 class="proof-image"
                                 onclick="window.open('../${imagePath}', '_blank')"
                                 onerror="console.error('Image not found:', this.src); this.style.border='3px solid red'; this.alt='Image not found: ${imagePath}';">
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Click image to view full size</p>
                        </div>
                    </div>
                `;
            } else {
                proofHTML = `
                    <div class="order-detail">
                        <strong>Payment Proof:</strong>
                        <span class="no-proof">No payment proof uploaded</span>
                    </div>
                `;
            }

            // Build claiming datetime HTML if it exists
            let claimingHTML = '';
            if (order.claiming_datetime) {
                const formattedDate = new Date(order.claiming_datetime).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                claimingHTML = `
                    <div class="order-detail claiming-datetime">
                        <strong>Claiming Date & Time:</strong>
                        <span>${formattedDate}</span>
                    </div>
                `;
            }

            // Build cancellation reason HTML if order is cancelled
            let cancellationHTML = '';
            if (order.status === 'cancelled' && order.cancellation_reason) {
                cancellationHTML = `
                    <div class="order-detail">
                        <strong>Cancellation Reason:</strong>
                        <span style="color: #d32f2f;">${order.cancellation_reason}</span>
                    </div>
                `;
            }

            // Build action button HTML based on status
            let actionHTML = '';
            if (order.status === 'pending') {
                actionHTML = `
                    <div class="modal-footer">
                        <button onclick="markAsPaid(${order.id})" class="btn-confirm-order">
                            <i class="fas fa-money-bill-wave"></i> Mark as Paid
                        </button>
                    </div>
                `;
            } else if (order.status === 'paid' || order.status === 'confirmed') {
                actionHTML = `
                    <div class="modal-footer" style="flex-direction: column; align-items: flex-start; gap: 10px;">
                        <label for="claimingDateTime" style="font-weight: 600;">Set Claiming Date & Time:</label>
                        <input type="datetime-local" id="claimingDateTime" style="padding:8px; border-radius:6px; border:1px solid #ccc; width:100%;">
                        <button onclick="setClaimingDateTime(${order.id})" class="btn-confirm-order">Set Date & Ready</button>
                    </div>
                `;
            } else if (order.status === 'claiming') {
                // Don't show any action button for claiming status
                actionHTML = '';
            } else if (order.status === 'completed') {
                actionHTML = `
                    <div class="modal-footer">
                        <span style="color: #198754; font-weight: 600;">
                            <i class="fas fa-check-double"></i> Order Completed
                        </span>
                    </div>
                `;
            } else if (order.status === 'cancelled') {
                actionHTML = `
                    <div class="modal-footer">
                        <span style="color: #d32f2f; font-weight: 600;">
                            <i class="fas fa-times-circle"></i> Order Cancelled
                        </span>
                    </div>
                `;
            }

            modalBody.innerHTML = `
                <div class="order-detail">
                    <strong>Student Number:</strong>
                    <span>${order.student_number || 'N/A'}</span>
                </div>
                <div class="order-detail">
                    <strong>Student Name:</strong>
                    <span>${order.student_name || 'N/A'}</span>
                </div>
                <div class="order-detail">
                    <strong>Organization:</strong>
                    <span>${order.student_organization || 'N/A'}</span>
                </div>
                <div class="order-detail">
                    <strong>Course & Section:</strong>
                    <span>${order.course_section || 'N/A'}</span>
                </div>
                <div class="order-detail">
                    <strong>Contact Number:</strong>
                    <span>${order.contact_number || 'N/A'}</span>
                </div>
                <div class="order-detail">
                    <strong>Reference Number:</strong>
                    <span>${order.reference_number || 'N/A'}</span>
                </div>
                <div class="order-detail">
                    <strong>Product Name:</strong>
                    <span>${order.product_name}</span>
                </div>
                <div class="order-detail">
                    <strong>Quantity:</strong>
                    <span>${order.quantity}</span>
                </div>
                ${order.product_size ? `
                <div class="order-detail">
                    <strong>Size:</strong>
                    <span>${order.product_size}</span>
                </div>
                ` : ''}
                <div class="order-detail">
                    <strong>Total Price:</strong>
                    <span>₱${parseFloat(order.total_price).toFixed(2)}</span>
                </div>
                <div class="order-detail">
                    <strong>Order Date & Time:</strong>
                    <span>
                        ${new Date(order.order_date).toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: true
                        })}
                    </span>
                </div>
                <div class="order-detail">
                    <strong>Status:</strong>
                    <span class="status-badge status-${order.status.toLowerCase()}">${order.status.toUpperCase()}</span>
                </div>
                ${claimingHTML}
                ${cancellationHTML}
                <div class="order-detail">
                    <strong>Payment Method:</strong>
                    <span>${order.payment_method ? order.payment_method.toUpperCase() : 'CASH ON HAND'}</span>
                </div>
                ${proofHTML}
                ${actionHTML}
            `;

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            const cancelModal = document.getElementById('cancelModal');
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == cancelModal) {
                closeCancellationModal();
            }
        }

        // Mark as received function
        function markAsReceived(orderId) {
            if (confirm('Are you sure you want to mark this order as received?')) {
                fetch('update-order-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&status=completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order marked as received successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update order'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }
    </script>
</body>
</html>