<?php
session_start();
include __DIR__ . '/db/config.php';

$pageTitle = 'My Orders';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['flash_message'] = "Please login to view your orders.";
    $_SESSION['flash_type'] = "error";
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Get all orders for the student
$sql = "SELECT o.*, p.name as product_name, p.image_path, p.category,
        s.seller_name as seller_name, o.reference_number, o.payment_method, o.payment_proof_path
        FROM orders o
        JOIN products p ON o.product_id = p.id
        LEFT JOIN sellers s ON o.seller_id = s.id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC";

$orders = fetchAll($sql, [$student_id], 'i');

// Process images for each order
foreach ($orders as &$order) {
    if (!empty($order['image_path'])) {
        $imagePaths = array_filter(array_map('trim', explode(',', $order['image_path'])));
        $firstImage = $imagePaths[0] ?? '';
        if (!empty($firstImage) && file_exists($firstImage)) {
            $order['image_url'] = $firstImage;
        } else {
            $order['image_url'] = '../uploads/products/default.jpg';
        }
    } else {
        $order['image_url'] = '../uploads/products/default.jpg';
    }
}
unset($order); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Phirse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding-top: 90px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            color: #000000;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 1rem;
        }

        .orders-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .order-item {
            display: flex;
            gap: 20px;
            padding: 25px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
            position: relative;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item:hover {
            background: #f9fafb;
        }

        .order-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #f0f0f0;
            flex-shrink: 0;
        }

        .order-content {
            flex: 1;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .order-ref {
            font-weight: 600;
            color: #667eea;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .order-date {
            color: #999;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            gap: 8px;
            color: #666;
            font-size: 0.95rem;
        }

        .detail-label {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pending {
            background: #fef3c7;
            color: #f59e0b;
        }

        .status-paid {
            background: #dbeafe;
            color: #3b82f6;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #10b981;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .total-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-top: 15px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .view-proof-btn, .view-receipt-btn, .download-receipt-btn {
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .view-proof-btn {
            background: #10b981;
        }

        .view-proof-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .view-receipt-btn {
            background: #3b82f6;
        }

        .view-receipt-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .download-receipt-btn {
            background: #8b5cf6;
        }

        .download-receipt-btn:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .empty-state a:hover {
            background: #5568d3;
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
            padding: 30px;
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
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
        }

        .close {
            color: #aaa;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close:hover {
            color: #000;
        }

        .proof-image {
            width: 100%;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        /* Receipt Styles */
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 2px dashed #333;
            font-family: 'Courier New', monospace;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .receipt-header h3 {
            font-size: 18px;
            margin: 5px 0;
            font-weight: bold;
        }

        .receipt-header p {
            font-size: 12px;
            color: #666;
            margin: 3px 0;
        }

        .receipt-divider {
            border-top: 1px dashed #999;
            margin: 15px 0;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }

        .receipt-item strong {
            font-weight: bold;
        }

        .receipt-total {
            border-top: 2px dashed #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .receipt-total .receipt-item {
            font-size: 16px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #333;
            font-size: 12px;
        }

        .receipt-footer p {
            margin: 5px 0;
        }

        .receipt-date {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 10px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .download-btn {
            background: #8b5cf6;
            color: white;
        }

        .download-btn:hover {
            background: #7c3aed;
        }

        @media (max-width: 768px) {
            .order-item {
                flex-direction: column;
            }

            .order-image {
                width: 100%;
                height: 200px;
            }

            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav-bar-transparent.php'; ?>
    <?php displayFlashMessage(); ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
            <p>Track and manage all your orders</p>
        </div>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php">
                        <i class="fas fa-shopping-cart"></i> Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-item" id="order-<?php echo $order['id']; ?>">
                        <img src="<?php echo htmlspecialchars($order['image_url'] ?: '../assets/placeholder.png'); ?>"
                             alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                             class="order-image">

                        <div class="order-content">
                            <div class="order-header">
                                <div>
                                    <div class="order-ref">
                                        <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($order['reference_number']); ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('F d, Y - h:i A', strtotime($order['order_date'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php
                                        $statusText = $order['status'];
                                        if (strtolower($order['status']) === 'completed') {
                                            $statusText = 'Payment Received';
                                        }
                                        echo htmlspecialchars($statusText);
                                    ?>
                                </span>
                            </div>

                            <div class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">Seller:</span>
                                    <span><?php echo htmlspecialchars($order['seller_name'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Quantity:</span>
                                    <span><?php echo htmlspecialchars($order['quantity']); ?></span>
                                </div>
                                <?php if (in_array($order['category'], ['Organization Shirt', 'Merchandise']) && !empty($order['product_size'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Size:</span>
                                    <span><?php echo htmlspecialchars($order['product_size']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <span class="detail-label">Payment:</span>
                                    <span>
                                        <i class="fas fa-<?php echo $order['payment_method'] === 'gcash' ? 'mobile-alt' : 'money-bill-wave'; ?>"></i>
                                        <?php echo strtoupper(htmlspecialchars($order['payment_method'] ?: 'CASH ON HAND')); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="total-price">
                                ₱<?php echo number_format($order['total_price'], 2); ?>
                            </div>

                            <div class="order-actions">
                                <?php if ($order['payment_method'] === 'gcash' && $order['payment_proof_path']): ?>
                                    <button onclick="viewProof('<?php echo htmlspecialchars($order['payment_proof_path']); ?>')" class="view-proof-btn">
                                        <i class="fas fa-image"></i> View Payment Proof
                                    </button>
                                <?php endif; ?>

                                <?php if (strtolower($order['status']) === 'completed'): ?>
                                    <button onclick="viewReceipt(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="view-receipt-btn">
                                        <i class="fas fa-receipt"></i> View Receipt
                                    </button>
                                    <button onclick="downloadReceipt(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="download-receipt-btn">
                                        <i class="fas fa-download"></i> Download Receipt
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Proof Modal -->
    <div id="proofModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Payment Proof</h2>
                <button class="close" onclick="closeProofModal()">&times;</button>
            </div>
            <img id="proofImage" src="" alt="Payment Proof" class="proof-image">
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Receipt</h2>
                <button class="close" onclick="closeReceiptModal()">&times;</button>
            </div>
            <div id="receiptWrapper" class="receipt-container">
                <div class="receipt-header">
                    <h3>PHIRSE</h3>
                    <p>Student E-Commerce Platform</p>
                    <p>Order Receipt</p>
                </div>

                <div class="receipt-divider"></div>

                <div id="receiptContent"></div>

                <div class="receipt-footer">
                    <p>Thank You For Your Purchase!</p>
                    <p>Phirse - Student E-Commerce</p>
                </div>
            </div>
            <div class="modal-actions">
                <button class="download-btn" onclick="downloadReceiptFromModal()">
                    <i class="fas fa-download"></i> Download as Image
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentOrder = null;

        function viewProof(imagePath) {
            const modal = document.getElementById('proofModal');
            const proofImage = document.getElementById('proofImage');
            proofImage.src = imagePath;
            modal.style.display = 'block';
        }

        function closeProofModal() {
            document.getElementById('proofModal').style.display = 'none';
        }

        function viewReceipt(order) {
            currentOrder = order;
            const modal = document.getElementById('receiptModal');
            const receiptContent = document.getElementById('receiptContent');

            const orderDate = new Date(order.order_date);
            const formattedDate = orderDate.toLocaleString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            receiptContent.innerHTML = `
                <div class="receipt-item">
                    <span>Product:</span>
                    <span>${order.product_name}</span>
                </div>
                <div class="receipt-item">
                    <span>Seller:</span>
                    <span>${order.seller_name}</span>
                </div>
                <div class="receipt-item">
                    <span>Order ID:</span>
                    <span>#${order.reference_number}</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="receipt-item">
                    <span>Quantity:</span>
                    <span>${order.quantity} pc(s)</span>
                </div>
                <div class="receipt-item">
                    <span>Price per unit:</span>
                    <span>₱${(order.total_price / order.quantity).toFixed(2)}</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="receipt-item">
                    <span>Payment Method:</span>
                    <span>${order.payment_method ? order.payment_method.toUpperCase() : 'CASH ON HAND'}</span>
                </div>
                <div class="receipt-total">
                    <div class="receipt-item">
                        <strong>TOTAL:</strong>
                        <strong>₱${parseFloat(order.total_price).toFixed(2)}</strong>
                    </div>
                </div>
                <div class="receipt-date">
                    <p>Paid By: Credit</p>
                    <p>${formattedDate}</p>
                    <p>Transaction ID: ${order.reference_number}</p>
                </div>
            `;

            modal.style.display = 'block';
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
            currentOrder = null;
        }

        function downloadReceipt(order) {
            currentOrder = order;
            
            // Create a temporary container for the receipt
            const tempContainer = document.createElement('div');
            tempContainer.style.position = 'absolute';
            tempContainer.style.left = '-9999px';
            tempContainer.style.background = 'white';
            document.body.appendChild(tempContainer);

            const orderDate = new Date(order.order_date);
            const formattedDate = orderDate.toLocaleString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            tempContainer.innerHTML = `
                <div class="receipt-container" style="max-width: 400px; margin: 0 auto; background: white; padding: 30px; border: 2px dashed #333; font-family: 'Courier New', monospace;">
                    <div style="text-align: center; border-bottom: 2px dashed #333; padding-bottom: 15px; margin-bottom: 15px;">
                        <h3 style="font-size: 18px; margin: 5px 0; font-weight: bold;">PHIRSE</h3>
                        <p style="font-size: 12px; color: #666; margin: 3px 0;">Student E-Commerce Platform</p>
                        <p style="font-size: 12px; color: #666; margin: 3px 0;">Order Receipt</p>
                    </div>
                    <div style="border-top: 1px dashed #999; margin: 15px 0;"></div>
                    <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px;">
                        <span>Product:</span>
                        <span>${order.product_name}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px;">
                        <span>Seller:</span>
                        <span>${order.seller_name}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px;">
                        <span>Order ID:</span>
                        <span>#${order.reference_number}</span>
                    </div>
                    <div style="border-top: 1px dashed #999; margin: 15px 0;"></div>
                    <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px;">
                        <span>Quantity:</span>
                        <span>${order.quantity} pc(s)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px;">
                        <span>Price per unit:</span>
                        <span>₱${(order.total_price / order.quantity).toFixed(2)}</span>
                    </div>
                    <div style="border-top: 1px dashed #999; margin: 15px 0;"></div>
                    <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px;">
                        <span>Payment Method:</span>
                        <span>${order.payment_method ? order.payment_method.toUpperCase() : 'CASH ON HAND'}</span>
                    </div>
                    <div style="border-top: 2px dashed #333; padding-top: 10px; margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold;">
                            <strong>TOTAL:</strong>
                            <strong>₱${parseFloat(order.total_price).toFixed(2)}</strong>
                        </div>
                    </div>
                    <div style="text-align: center; font-size: 11px; color: #666; margin-top: 10px;">
                        <p style="margin: 5px 0;">Paid By: Credit</p>
                        <p style="margin: 5px 0;">${formattedDate}</p>
                        <p style="margin: 5px 0;">Transaction ID: ${order.reference_number}</p>
                    </div>
                    <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px dashed #333; font-size: 12px;">
                        <p style="margin: 5px 0;">Thank You For Your Purchase!</p>
                        <p style="margin: 5px 0;">Phirse - Student E-Commerce</p>
                    </div>
                </div>
            `;

            // Use html2canvas to convert to image
            html2canvas(tempContainer, {
                backgroundColor: '#ffffff',
                scale: 2
            }).then(canvas => {
                // Convert canvas to blob and download
                canvas.toBlob(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `receipt_${order.reference_number}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    // Remove temporary container
                    document.body.removeChild(tempContainer);
                });
            });
        }

        function downloadReceiptFromModal() {
            if (currentOrder) {
                const receiptWrapper = document.getElementById('receiptWrapper');
                
                html2canvas(receiptWrapper, {
                    backgroundColor: '#ffffff',
                    scale: 2
                }).then(canvas => {
                    canvas.toBlob(blob => {
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `receipt_${currentOrder.reference_number}.png`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    });
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const proofModal = document.getElementById('proofModal');
            const receiptModal = document.getElementById('receiptModal');
            if (event.target == proofModal) {
                closeProofModal();
            }
            if (event.target == receiptModal) {
                closeReceiptModal();
            }
        }
    </script>
    <?php include __DIR__ . '/includes/mobile-bottom-nav.php'; ?>
</body>
</html>