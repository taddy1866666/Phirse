<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}
require_once '../database/config.php';
$pageTitle = 'Orders Management';
include 'includes/header.php';

$orders = [];
$totalOrders = 0;
$completedOrders = 0;
$pendingOrders = 0;
$totalRevenue = 0;

try {
    // Get statistics
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $completedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'confirmed', 'claiming')")->fetchColumn();
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
    
    // Get all orders with details
    $stmt = $pdo->query("
        SELECT 
            o.id, o.reference_number, o.quantity, o.total_price, o.order_date, o.status,
            o.payment_method, o.payment_proof_path, o.claiming_datetime, o.product_size,
            s.student_number, s.student_name, s.organization AS student_org,
            s.course_section, s.contact_number,
            p.name AS product_name, p.category AS product_category,
            sl.organization AS seller_org
        FROM orders o
        LEFT JOIN students s ON o.student_id = s.id
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN sellers sl ON p.seller_id = sl.id
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error loading orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            color: #2d3748;
        }

        .main-content {
            margin-left: 220px;
            flex: 1;
            padding: 32px 40px;
            width: calc(100% - 220px);
        }

        .page-header-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icon i {
            font-size: 24px;
            color: #4f46e5;
        }

        .header-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 4px 0;
        }

        .header-text p {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .dashboard-header {
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            background: white;
            padding: 24px 28px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .dashboard-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
        }

        .dashboard-date {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 6px;
            width: fit-content;
        }

        .dashboard-date i {
            color: #4f46e5;
            font-size: 12px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            min-width: 300px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .search-wrapper:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: none;
            font-size: 14px;
            background: transparent;
            color: #1a202c;
            outline: none;
            width: 100%;
        }

        .search-btn {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 0 16px;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .search-btn:hover {
            color: #4f46e5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.orders {
            background: #eff6ff;
            color: #1e40af;
        }

        .stat-icon.completed {
            background: #d1fae5;
            color: #047857;
        }

        .stat-icon.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-icon.revenue {
            background: #e6fffa;
            color: #047857;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
        }

        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .filters-row {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
            color: #2d3748;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: #4a5568;
        }

        .filter-select:hover {
            border-color: #cbd5e0;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .export-btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .orders-table-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        .orders-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e2e8f0;
        }

        .orders-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            color: #1a202c;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #4a5568;
            font-size: 14px;
        }

        .orders-table tbody tr {
            transition: all 0.2s;
        }

        .orders-table tbody tr:hover {
            background: #f8f9fa;
        }

        .reference-number {
            font-weight: 700;
            color: #1a202c;
            font-family: 'Courier New', monospace;
        }

        .product-name {
            font-weight: 600;
            color: #1a202c;
        }

        .student-info {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 2px;
        }

        .student-org {
            font-size: 12px;
            color: #718096;
        }

        .price {
            font-weight: 700;
            color: #1a202c;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-paid { background: #dbeafe; color: #1e3a8a; }
        .status-confirmed { background: #dcfce7; color: #14532d; }
        .status-claiming { background: #cffafe; color: #164e63; }
        .status-completed { background: #d1fae5; color: #065f46; }

        .order-date {
            font-size: 13px;
            color: #718096;
        }

        .btn-view {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-1px);
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
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            margin: 3% auto;
            border-radius: 16px;
            padding: 0;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 2px solid #e2e8f0;
            background: #f8f9fa;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
        }

        .close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #a0aec0;
            transition: all 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .close:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .modal-body {
            padding: 24px;
        }

        .detail-row {
            display: flex;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            min-width: 180px;
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
        }

        .detail-value {
            color: #1a202c;
            font-size: 14px;
            flex: 1;
        }

        .proof-image {
            width: 100%;
            max-width: 450px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }

        .proof-image:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .no-proof {
            color: #a0aec0;
            font-style: italic;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #cbd5e0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 16px;
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: 100%;
            }

            .orders-table-container {
                padding: 16px;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .detail-row {
                flex-direction: column;
                gap: 8px;
            }

            .detail-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header-wrapper">
            <div class="page-header">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="header-text">
                        <h1>Orders Management</h1>
                        <p>View and manage all customer orders</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="dashboard-date">
                        <i class="far fa-calendar"></i>
                        <span><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalOrders ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Completed</div>
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $completedOrders ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Pending</div>
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $pendingOrders ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                </div>
                <div class="stat-number">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by student, product, reference, or organization...">
                    <i class="fas fa-search"></i>
                </div>
                
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="claiming">Claiming</option>
                    <option value="completed">Completed</option>
                </select>

                <a href="export_orders_pdf.php" style="
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
                    white-space: nowrap;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                    <i class="fas fa-file-pdf"></i>
                    Export to PDF
                </a>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <div class="orders-table-container">
            <?php if (!empty($orders)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Product</th>
                            <th>Student</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <span class="reference-number"><?php echo htmlspecialchars($order['reference_number']); ?></span>
                                </td>
                                <td>
                                    <span class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></span>
                                </td>
                                <td>
                                    <div class="student-info">
                                        <span class="student-name"><?php echo htmlspecialchars($order['student_name'] ?? 'N/A'); ?></span>
                                        <span class="student-org"><?php echo htmlspecialchars($order['student_org'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>
                                    <span class="price">₱<?php echo number_format($order['total_price'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="order-date"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                </td>
                                <td>
                                    <button class="btn-view" onclick='viewOrder(<?php echo json_encode($order); ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No orders found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice"></i> Order Details</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        // Search and filter
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value.toLowerCase();
            
            document.getElementById('exportStatus').value = status;

            const rows = document.querySelectorAll('.orders-table tbody tr');
            rows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const rowStatus = row.querySelector('.status-badge').innerText.toLowerCase();
                const matchesSearch = rowText.includes(search);
                const matchesStatus = !status || rowStatus === status;

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        function viewOrder(order) {
            const modal = document.getElementById('orderModal');
            const modalBody = document.getElementById('modalBody');

            let proofHTML = '';
            if (order.payment_proof_path) {
                let imagePath = order.payment_proof_path.replace(/^\.\.\//, '');
                proofHTML = `
                    <div class="detail-row">
                        <div class="detail-label">Payment Proof:</div>
                        <div class="detail-value">
                            <img src="../${imagePath}" alt="Payment Proof" class="proof-image"
                                 onclick="window.open('../${imagePath}', '_blank')">
                            <p style="font-size: 12px; color: #718096; margin-top: 8px;">Click image to view full size</p>
                        </div>
                    </div>`;
            } else {
                proofHTML = `
                    <div class="detail-row">
                        <div class="detail-label">Payment Proof:</div>
                        <div class="detail-value"><span class="no-proof">No proof uploaded</span></div>
                    </div>`;
            }

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
                    <div class="detail-row">
                        <div class="detail-label">Claiming Date & Time:</div>
                        <div class="detail-value">${formattedDate}</div>
                    </div>
                `;
            }

            modalBody.innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Reference Number:</div>
                    <div class="detail-value"><strong>${order.reference_number}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Student Number:</div>
                    <div class="detail-value">${order.student_number || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Student Name:</div>
                    <div class="detail-value">${order.student_name || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Organization:</div>
                    <div class="detail-value">${order.student_org || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Course & Section:</div>
                    <div class="detail-value">${order.course_section || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Contact Number:</div>
                    <div class="detail-value">${order.contact_number || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Seller Organization:</div>
                    <div class="detail-value">${order.seller_org || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Product Name:</div>
                    <div class="detail-value"><strong>${order.product_name}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Quantity:</div>
                    <div class="detail-value">${order.quantity}</div>
                </div>
                ${order.product_category && ['Organization Shirt', 'Merchandise'].includes(order.product_category) && order.product_size ? `
                <div class="detail-row">
                    <div class="detail-label">Size:</div>
                    <div class="detail-value">${order.product_size}</div>
                </div>
                ` : ''}
                <div class="detail-row">
                    <div class="detail-label">Total Price:</div>
                    <div class="detail-value"><strong>₱${parseFloat(order.total_price).toFixed(2)}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge status-${order.status.toLowerCase()}">${order.status.toUpperCase()}</span>
                    </div>
                </div>
                ${claimingHTML}
                <div class="detail-row">
                    <div class="detail-label">Payment Method:</div>
                    <div class="detail-value">
                        <i class="fas fa-${order.payment_method === 'gcash' ? 'mobile-alt' : 'money-bill-wave'}"></i>
                        ${order.payment_method ? order.payment_method.toUpperCase() : 'CASH ON HAND'}
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Order Date & Time:</div>
                    <div class="detail-value">${new Date(order.order_date).toLocaleString('en-US', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit',
                        hour12: true
                    })}</div>
                </div>
                ${proofHTML}
            `;

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>