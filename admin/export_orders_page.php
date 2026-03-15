<?php
session_start();
require_once '../database/config.php';

// Only allow admins
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

$pageTitle = 'Export Orders to PDF';
include 'includes/header.php';

// Get statistics and orders data
$orders = [];
$totalOrders = 0;
$completedOrders = 0;
$pendingOrders = 0;
$totalRevenue = 0;
$status_filter = '';

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
    <title>Export Orders - Admin Panel</title>
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
            font-size: 24px;
            color: #667eea;
        }

        .header-title h1 {
            font-size: 28px;
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .header-title p {
            font-size: 14px;
            color: #64748b;
        }

        .export-container {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card.completed {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        .stat-card.revenue {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .filter-section {
            margin-bottom: 32px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .filter-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1e293b;
        }

        .filter-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .export-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .export-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-pdf {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .btn-csv {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-csv:hover {
            background: #cbd5e1;
        }

        .preview-section {
            margin-top: 32px;
        }

        .preview-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1e293b;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            background: white;
        }

        .preview-table thead {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .preview-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .preview-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .preview-table tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #ff9800;
        }

        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-claiming {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .text-muted {
            color: #64748b;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            margin-top: 24px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 12px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header-wrapper">
            <div class="page-header">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="header-title">
                        <h1>Export Orders to PDF</h1>
                        <p>Generate and download detailed orders report</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-value"><?php echo number_format($completedOrders); ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-value"><?php echo number_format($pendingOrders); ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-value">₱ <?php echo number_format($totalRevenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Export Container -->
        <div class="export-container">
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title">
                    <i class="fas fa-filter"></i> Filter Options
                </div>
                <div class="filter-group">
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="claiming">Claiming</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="export-buttons">
                <form id="pdfExportForm" method="POST" action="export_orders_pdf.php" style="display: inline;">
                    <input type="hidden" name="status" id="pdfStatus">
                    <button type="submit" class="export-btn btn-pdf">
                        <i class="fas fa-file-pdf"></i>
                        Export to PDF
                    </button>
                </form>
                
                <form id="csvExportForm" method="POST" action="export_orders.php" style="display: inline;">
                    <input type="hidden" name="status" id="csvStatus">
                    <button type="submit" class="export-btn btn-csv">
                        <i class="fas fa-file-csv"></i>
                        Export to CSV
                    </button>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="preview-section">
                <div class="preview-title">
                    <i class="fas fa-table"></i> Orders Preview (First 10 Records)
                </div>
                
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Reference No.</th>
                            <th>Product</th>
                            <th>Student</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 0;
                        foreach ($orders as $order): 
                            if ($count >= 10) break;
                            $count++;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['reference_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td>₱ <?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($order['order_date'], 0, 16)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Back Link -->
            <a href="admin-orders.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Orders Management
            </a>
        </div>
    </div>

    <script>
        // Update status filter for both forms
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            document.getElementById('pdfStatus').value = status;
            document.getElementById('csvStatus').value = status;
        });

        // Prevent double submission
        const forms = ['pdfExportForm', 'csvExportForm'];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = '0.6';
                        btn.style.cursor = 'not-allowed';
                    }
                });
            }
        });
    </script>
</body>
</html>
