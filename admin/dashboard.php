<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}
require_once '../database/config.php';
$pageTitle = 'Dashboard';
include 'includes/header.php';

if (!$pdo) {
    die("Database connection failed.");
}

// --- Overview metrics ---
$totalOrders = 0;
$totalStudents = 0;

try {
    // Get statistics
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    
    // Products
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;

} catch (PDOException $e) {
    $totalOrders = 0;
    $totalStudents = 0;
    $totalProducts = 0;
}

// --- Analytics metrics ---
try {
    $totalRevenue = $pdo->query("SELECT ROUND(SUM(total_price), 2) AS total FROM orders WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $monthlyRevenue = $pdo->query("SELECT ROUND(SUM(total_price), 2) AS total FROM orders WHERE status = 'completed' AND YEAR(order_date)=YEAR(CURDATE()) AND MONTH(order_date)=MONTH(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $completedOrders = $pdo->query("SELECT COUNT(*) AS count FROM orders WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    $averageOrderValue = $completedOrders > 0 ? round($totalRevenue / $completedOrders, 2) : 0;

    // growth
    $growthRateData = $pdo->query("SELECT
            SUM(CASE WHEN YEAR(order_date)=YEAR(CURDATE()) AND MONTH(order_date)=MONTH(CURDATE()) THEN total_price ELSE 0 END) AS current_month,
            SUM(CASE WHEN YEAR(order_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(order_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN total_price ELSE 0 END) AS prev_month
        FROM orders
        WHERE status = 'completed'
    ")->fetch(PDO::FETCH_ASSOC);
    $current_month = (float)($growthRateData['current_month'] ?? 0);
    $prev_month = (float)($growthRateData['prev_month'] ?? 0);
    $growthRate = $prev_month > 0 ? round((($current_month - $prev_month) / $prev_month) * 100, 2) : ($current_month > 0 ? 100 : 0);

    // Revenue data for last 30 days
    $revenueData = $pdo->query("
        SELECT DATE(order_date) as date, SUM(total_price) as revenue 
        FROM orders 
        WHERE status = 'completed' AND order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Revenue per Organization
    $revenuePerOrg = $pdo->query("
        SELECT 
            s.organization,
            s.logo_path,
            COUNT(o.id) as total_orders,
            ROUND(SUM(o.total_price), 2) as total_revenue
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN sellers s ON p.seller_id = s.id
        WHERE o.status = 'completed'
        GROUP BY s.organization, s.logo_path
        ORDER BY total_revenue DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (PDOException $e) {
    $totalRevenue = $monthlyRevenue = $averageOrderValue = $growthRate = 0;
    $revenueData = [];
    $revenuePerOrg = [];
}

// Format revenue data for Chart.js
$chartDates = [];
$chartRevenues = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartDates[] = date('M d', strtotime($date));
    
    $revenue = 0;
    foreach ($revenueData as $data) {
        if ($data['date'] === $date) {
            $revenue = $data['revenue'];
            break;
        }
    }
    $chartRevenues[] = $revenue;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard - Phirse</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
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
            padding: 32px 40px;
            flex: 1;
            width: calc(100% - 220px);
            background: #f8f9fa;
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
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-header p {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
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

        .export-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Notification Bell (Admin Header) */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-bell {
            position: relative;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            color: #4a5568;
        }

        .notification-bell:hover {
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: #fff;
            border-radius: 999px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            padding: 0 5px;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
        }

        .notification-badge.hidden { display: none; }

        .notification-popup {
            position: absolute;
            right: 0;
            top: 56px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            width: 380px;
            max-height: 520px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
            transform: translateY(-8px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
            z-index: 1000;
        }

        .notification-popup.show {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        .notification-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .notification-popup-header h3 { font-size: 16px; font-weight: 700; display: flex; gap: 8px; align-items: center; }

        .notification-popup-close {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.35);
            width: 30px;
            height: 30px;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .notification-list { max-height: 440px; overflow-y: auto; }
        .notification-item { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; cursor: default; }
        .notification-item:last-child { border-bottom: none; }
        .notification-item .title { font-weight: 700; color: #1a202c; font-size: 14px; margin-bottom: 4px; display: flex; gap: 8px; align-items: center; }
        .notification-item .meta { font-size: 12px; color: #64748b; display: flex; gap: 8px; align-items: center; }
        .notification-item .message { font-size: 13px; color: #334155; margin: 6px 0 0; }

        /* Popup footer removed for simplified header notification */

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .export-btn:active {
            transform: translateY(0);
        }

        .export-btn i {
            font-size: 16px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .metric-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: #cbd5e0;
        }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .metric-label {
            font-size: 13px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .metric-icon.revenue {
            background: #e6fffa;
            color: #047857;
        }

        .metric-icon.orders {
            background: #eff6ff;
            color: #1e40af;
        }

        .metric-icon.average {
            background: #fef3c7;
            color: #d97706;
        }

        .metric-icon.students {
            background: #fce7f3;
            color: #be185d;
        }

        .metric-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            line-height: 1;
        }

        .analytics-wrapper {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
            align-items: start;
        }

        .analytics-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
            font-size: 20px;
        }

        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            gap: 8px;
            background: #f8f9fa;
            padding: 4px;
            border-radius: 8px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: #718096;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            background: white;
            color: #4a5568;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Revenue Section */
        .revenue-section {
            grid-column: span 12;
        }

        .revenue-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
        }

        .stat-value.positive {
            color: #047857;
        }

        .stat-value.negative {
            color: #dc2626;
        }

        .chart-container {
            position: relative;
            height: 320px;
            margin-bottom: 20px;
        }

        .org-container {
            max-height: 480px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .org-container::-webkit-scrollbar {
            width: 6px;
        }

        .org-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .org-container::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        .org-container::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        .org-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .org-row:hover {
            background: white;
            border-color: #cbd5e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .org-rank {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            background: #667eea;
            color: white;
        }

        .org-rank.rank-1 {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
        }

        .org-rank.rank-2 {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            box-shadow: 0 2px 8px rgba(148, 163, 184, 0.3);
        }

        .org-rank.rank-3 {
            background: linear-gradient(135deg, #fb923c, #f97316);
            box-shadow: 0 2px 8px rgba(251, 146, 60, 0.3);
        }

        .org-info {
            flex: 1;
        }

        .org-name {
            font-size: 15px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .org-details {
            font-size: 12px;
            color: #718096;
        }

        .org-sales {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            text-align: right;
        }

        .org-orders {
            font-size: 12px;
            color: #718096;
            text-align: right;
            margin-top: 2px;
        }

        .product-container {
            max-height: 480px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .product-container::-webkit-scrollbar {
            width: 6px;
        }

        .product-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .product-container::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        .product-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .product-row:hover {
            background: white;
            border-color: #cbd5e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .product-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .product-sales {
            font-size: 14px;
            font-weight: 700;
            color: #667eea;
        }

        .orgs-section {
            grid-column: span 6;
        }

        .products-section {
            grid-column: span 6;
        }

        /* Revenue per Organization Styles */
        .org-revenue-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .org-revenue-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .org-revenue-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .org-logo {
            flex-shrink: 0;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .org-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-logo {
            font-size: 32px;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .org-info {
            flex: 1;
            min-width: 0;
        }

        .org-info h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 6px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-count {
            font-size: 13px;
            opacity: 0.9;
            margin: 0;
        }

        .org-revenue {
            flex-shrink: 0;
            text-align: right;
        }

        .revenue-amount {
            font-size: 18px;
            font-weight: 700;
            display: block;
        }

        /* Revenue per Organization Top Section */
        .org-revenue-section {
            width: 100%;
            margin-bottom: 32px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 14px;
            font-weight: 500;
        }

        @media (max-width: 1200px) {
            .orgs-section,
            .products-section {
                grid-column: span 12;
            }

            .org-revenue-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 16px;
                width: 100%;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .analytics-section {
                padding: 20px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-buttons {
                width: 100%;
                justify-content: space-between;
            }

            .filter-btn {
                flex: 1;
                padding: 8px 12px;
                font-size: 12px;
            }

            .revenue-stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .orgs-section,
            .products-section {
                grid-column: span 12;
            }

            .org-revenue-container {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 640px) {
            .org-revenue-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .org-revenue-card {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .org-info {
                text-align: center;
            }

            .org-info h3 {
                font-size: 14px;
            }

            .org-revenue {
                text-align: center;
                margin-top: 8px;
            }

            .revenue-amount {
                font-size: 16px;
            }

            .org-logo {
                width: 60px !important;
                height: 60px !important;
            }
        }

        @media (max-width: 480px) {
            .dashboard-header h1 {
                font-size: 24px;
            }

            .metric-number {
                font-size: 28px;
            }

            .section-title {
                font-size: 16px;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .loading-spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Generating PDF Report...</div>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header" style="position: relative;">
            <div>
                <h1>Dashboard Overview</h1>
                <div class="dashboard-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <button class="export-btn" onclick="exportToPDF()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-file-pdf"></i>
                    Export Report
                </button>
                <button class="notification-bell" id="adminNotifBell" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge hidden" id="adminNotifBadge">0</span>
                </button>

                <div class="notification-popup" id="adminNotifPopup">
                    <div class="notification-popup-header">
                        <h3><i class="fas fa-bell"></i> New Product Submissions</h3>
                        <button class="notification-popup-close" id="adminNotifClose" aria-label="Close notifications">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="notification-list" id="adminNotifList">
                        <div style="padding: 16px; text-align:center; color:#64748b;">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Overview Metrics -->
        <div class="metrics-grid" id="metricsSection">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-icon revenue">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                </div>
                <div class="metric-number">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-label">Average Order</div>
                    <div class="metric-icon average">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="metric-number">₱<?= number_format($averageOrderValue, 2) ?></div>
            </div>

   <a href="admin-orders.php" style="text-decoration: none; color: inherit;">
    <div class="metric-card">
        <div class="metric-header">
            <div class="metric-label">Total Orders</div>
            <div class="metric-icon orders">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="metric-number"><?= intval($totalOrders) ?></div>
    </div>
</a>
<a href="students-list.php" style="text-decoration: none; color: inherit;">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-label">Total Students</div>
                    <div class="metric-icon students">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="metric-number"><?= intval($totalStudents) ?></div>
            </div>
        </div>
    </a>

        <!-- Revenue per Organization - Top Section -->
        <div class="org-revenue-section">
            <div class="analytics-section" id="revenuePerOrgSection">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-store"></i>
                        Revenue per Organization
                    </div>
                </div>

                <div class="org-revenue-container">
                    <?php if (!empty($revenuePerOrg)): ?>
                        <?php foreach ($revenuePerOrg as $org): ?>
                            <div class="org-revenue-card">
                                <div class="org-logo">
                                    <?php if ($org['logo_path']): ?>
                                        <img src="<?php echo htmlspecialchars($org['logo_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($org['organization']); ?>"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2224%22 fill=%22%23999%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22%3E?%3C/text%3E%3C/svg%3E'">
                                    <?php else: ?>
                                        <div class="no-logo">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="org-info">
                                    <h3><?php echo htmlspecialchars($org['organization']); ?></h3>
                                    <p class="order-count">Orders: <?php echo $org['total_orders']; ?></p>
                                </div>
                                <div class="org-revenue">
                                    <span class="revenue-amount">₱<?php echo number_format($org['total_revenue'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#718096; text-align: center; padding: 40px;">No revenue data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="analytics-wrapper">
            <!-- Revenue Report with Chart -->
            <div class="analytics-section revenue-section" id="revenueSection">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Revenue Report 
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="revenue-stats">
                    <div class="stat-item">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Monthly Revenue</div>
                        <div class="stat-value">₱<?= number_format($monthlyRevenue, 2) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Growth Rate</div>
                        <div class="stat-value <?= $growthRate >= 0 ? 'positive' : 'negative' ?>">
                            <?= ($growthRate >= 0 ? '+' : '') . $growthRate ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Organization Sales -->
            <div class="analytics-section orgs-section" id="orgsSection">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-trophy"></i>
                        Top Organizations
                    </div>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-period="7" onclick="loadOrganizations(7, this)">7 Days</button>
                        <button class="filter-btn" data-period="30" onclick="loadOrganizations(30, this)">Month</button>
                        <button class="filter-btn" data-period="365" onclick="loadOrganizations(365, this)">Year</button>
                        <button class="filter-btn" data-period="all" onclick="loadOrganizations('all', this)">All Time</button>
                    </div>
                </div>

                <div id="topOrgsContainer" class="org-container">
                    <p style="color:#718096; text-align: center;">Loading...</p>
                </div>
            </div>

            <!-- Product Performance -->
            <div class="analytics-section products-section" id="productsSection">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-box-open"></i>
                        Top Products
                    </div>
                    <div class="filter-buttons">
                        <button class="filter-btn product-filter active" data-period="7" onclick="loadProducts(7, this)">7 Days</button>
                        <button class="filter-btn product-filter" data-period="30" onclick="loadProducts(30, this)">Month</button>
                        <button class="filter-btn product-filter" data-period="365" onclick="loadProducts(365, this)">Year</button>
                        <button class="filter-btn product-filter" data-period="all" onclick="loadProducts('all', this)">All Time</button>
                    </div>
                </div>

                <div id="topProductsContainer" class="product-container">
                    <p style="color:#718096; text-align: center;">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toast system
        class AdminToast {
            constructor() {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }

            show({ icon = 'fa-bell', title = 'Notification', msg = '', time = '' } = {}) {
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.innerHTML = `
                    <div class="title"><i class="fas ${icon}"></i> ${title}</div>
                    <div class="msg">${msg}</div>
                    <div class="time"><i class="far fa-clock"></i> ${time}</div>
                `;
                this.container.appendChild(toast);
                // animate in
                requestAnimationFrame(() => toast.classList.add('show'));
                // auto dismiss
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }
        }

        // Poll unread admin_notifications for toasts
        class AdminToastPoller {
            constructor() {
                this.toast = new AdminToast();
                this.timer = null;
                this.inFlight = false;
                this.init();
            }

            init() {
                this.tick();
                this.timer = setInterval(() => this.tick(), 8000);
            }

            async tick() {
                if (this.inFlight) return;
                this.inFlight = true;
                try {
                    const res = await fetch('fetch-unread-toasts.php', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (data.success && Array.isArray(data.items)) {
                        data.items.forEach(item => {
                            this.toast.show({ icon: item.icon || 'fa-bell', title: item.title, msg: item.message, time: item.time_ago });
                        });
                    }
                } catch (e) {
                    // silent
                } finally {
                    this.inFlight = false;
                }
            }
        }
        // Admin Notification System (pending products only)
        class AdminNotificationSystem {
            constructor() {
                this.bell = document.getElementById('adminNotifBell');
                this.badge = document.getElementById('adminNotifBadge');
                this.popup = document.getElementById('adminNotifPopup');
                this.list = document.getElementById('adminNotifList');
                this.closeBtn = document.getElementById('adminNotifClose');
                this.isOpen = false;
                this.timer = null;
                this.init();
            }

            init() {
                if (!this.bell || !this.badge || !this.popup || !this.list) return;

                this.bell.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggle();
                });

                if (this.closeBtn) {
                    this.closeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.close();
                    });
                }

                document.addEventListener('click', (e) => {
                    if (!this.popup.contains(e.target) && !this.bell.contains(e.target)) {
                        this.close();
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') this.close();
                });

                this.refresh();
                this.timer = setInterval(() => this.refresh(), 10000);
            }

            async refresh() {
                try {
                    const res = await fetch('fetch-pending-products-notifs.php', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (!data.success) throw new Error('API error');
                    this.updateBadge(data.unread_count || 0);
                    this.render(data.items || []);
                } catch (e) {
                    this.list.innerHTML = '<div style="padding:16px; text-align:center; color:#ef4444;">Failed to load notifications</div>';
                }
            }

            updateBadge(count) {
                if (count > 0) {
                    this.badge.textContent = count > 99 ? '99+' : String(count);
                    this.badge.classList.remove('hidden');
                } else {
                    this.badge.classList.add('hidden');
                }
            }

            render(items) {
                if (!items.length) {
                    this.list.innerHTML = '<div style="padding:16px; text-align:center; color:#64748b;">No pending products</div>';
                    return;
                }
                this.list.innerHTML = items.map(it => `
                    <div class="notification-item" data-id="${it.id}">
                        <div class="title"><i class="fas ${it.icon}"></i> ${it.title}</div>
                        <div class="message">${it.message}</div>
                        <div class="meta"><i class="far fa-clock"></i> ${it.time_ago}</div>
                    </div>
                `).join('');

                // Attach click handlers to mark as read
                this.list.querySelectorAll('.notification-item').forEach(el => {
                    el.addEventListener('click', async () => {
                        const id = parseInt(el.getAttribute('data-id')) || 0;
                        if (!id) return;
                        try {
                            const res = await fetch('mark-pending-product-seen.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ product_id: id })
                            });
                            if (res.ok) {
                                // Remove element and update badge
                                el.remove();
                                const remaining = this.list.querySelectorAll('.notification-item').length;
                                this.updateBadge(remaining);
                                if (remaining === 0) {
                                    this.list.innerHTML = '<div style="padding:16px; text-align:center; color:#64748b;">No pending products</div>';
                                }
                            }
                        } catch (e) {}
                    });
                });
            }

            toggle() { this.isOpen ? this.close() : this.open(); }
            open() { this.popup.classList.add('show'); this.isOpen = true; this.refresh(); }
            close() { this.popup.classList.remove('show'); this.isOpen = false; }
        }

        let adminNotifSystem;
        document.addEventListener('DOMContentLoaded', () => {
            adminNotifSystem = new AdminNotificationSystem();
        });
        const { jsPDF } = window.jspdf;

        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartDates) ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?= json_encode($chartRevenues) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: { size: 13, weight: '600' },
                            color: '#4a5568',
                            padding: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            },
                            font: { size: 12 },
                            color: '#718096'
                        },
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: 12 },
                            color: '#718096'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Global variables to store current data
        let currentOrganizations = [];
        let currentProducts = [];

        // Load Organizations
        function loadOrganizations(period, button) {
            const filterButtons = document.querySelectorAll('.filter-btn:not(.product-filter)');
            filterButtons.forEach(btn => btn.classList.remove('active'));
            if (button) {
                button.classList.add('active');
            }

            const container = document.getElementById('topOrgsContainer');
            if (!container) return;
            
            container.innerHTML = '<p style="color:#718096; text-align: center;">Loading...</p>';

            fetch(`get-top-organizations.php?period=${period}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Organizations data:', data);
                    
                    if (data.success && data.data && data.data.length > 0) {
                        currentOrganizations = data.data;
                        let html = '';
                        data.data.forEach((org, index) => {
                            const rank = index + 1;
                            const rankClass = rank <= 3 ? `rank-${rank}` : '';
                            const orgName = org.organization_name || org.organization || 'Unknown Organization';
                            const orderCount = parseInt(org.order_count) || 0;
                            const totalSales = parseFloat(org.total_sales) || 0;
                            
                            html += `
                                <div class="org-row">
                                    <div class="org-rank ${rankClass}">${rank}</div>
                                    <div class="org-info">
                                        <div class="org-name">${orgName}</div>
                                        <div class="org-details">${orderCount} order${orderCount !== 1 ? 's' : ''}</div>
                                    </div>
                                    <div>
                                        <div class="org-sales">₱${totalSales.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    } else {
                        currentOrganizations = [];
                        const debugMsg = data.debug ? `<br><small style="color: #a0aec0; margin-top: 8px; display: block;">${data.debug}</small>` : '';
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>${data.message || 'No organization data available'}${debugMsg}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading organizations:', error);
                    currentOrganizations = [];
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Failed to load data</p>
                            <small style="color: #a0aec0; margin-top: 8px; display: block;">Error: ${error.message}</small>
                        </div>
                    `;
                });
        }

        // Load Products with Organization Name
        function loadProducts(period, button) {
            const productFilters = document.querySelectorAll('.product-filter');
            productFilters.forEach(btn => btn.classList.remove('active'));
            if (button) {
                button.classList.add('active');
            }

            const container = document.getElementById('topProductsContainer');
            if (!container) return;
            
            container.innerHTML = '<p style="color:#718096; text-align: center;">Loading...</p>';

            fetch(`get-top-products.php?period=${period}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Products data:', data);
                    
                    if (data.success && data.data && data.data.length > 0) {
                        currentProducts = data.data;
                        let html = '';
                        data.data.forEach((product, index) => {
                            const rank = index + 1;
                            const productName = product.product_name || 'Unknown Product';
                            const organization = product.organization || 'N/A';
                            const quantity = parseInt(product.total_quantity) || 0;
                            const orderCount = parseInt(product.order_count) || 0;
                            const totalSales = parseFloat(product.total_sales) || 0;
                            
                            html += `
                                <div class="product-row">
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                        <div style="width: 28px; height: 28px; border-radius: 6px; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">
                                            ${rank}
                                        </div>
                                        <div style="flex: 1;">
                                            <div class="product-name" style="margin: 0;">${productName}</div>
                                            <div style="font-size: 11px; color: #667eea; font-weight: 600; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                ${organization}
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding-left: 40px;">
                                        <div style="font-size: 12px; color: #718096;">
                                            ${quantity} units sold • ${orderCount} orders
                                        </div>
                                        <div class="product-sales">₱${totalSales.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    } else {
                        currentProducts = [];
                        const debugMsg = data.debug ? `<br><small style="color: #a0aec0; margin-top: 8px; display: block;">${data.debug}</small>` : '';
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>${data.message || 'No product data available'}${debugMsg}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    currentProducts = [];
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Failed to load data</p>
                            <small style="color: #a0aec0; margin-top: 8px; display: block;">Error: ${error.message}</small>
                        </div>
                    `;
                });
        }

        // Export to PDF Function
        async function exportToPDF() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.add('active');

            try {
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                let yPosition = 20;

                // Header
                pdf.setFillColor(102, 126, 234);
                pdf.rect(0, 0, pageWidth, 35, 'F');
                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(24);
                pdf.setFont(undefined, 'bold');
                pdf.text('Dashboard Report', pageWidth / 2, 15, { align: 'center' });
                pdf.setFontSize(12);
                pdf.setFont(undefined, 'normal');
                pdf.text(`Generated on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}`, pageWidth / 2, 25, { align: 'center' });

                yPosition = 45;

                // Metrics Section
                pdf.setTextColor(26, 32, 44);
                pdf.setFontSize(16);
                pdf.setFont(undefined, 'bold');
                pdf.text('Key Metrics', 15, yPosition);
                yPosition += 10;

                const metrics = [
                    { label: 'Total Revenue', value: '₱<?= number_format($totalRevenue, 2) ?>', color: [4, 120, 87] },
                    { label: 'Average Order', value: '₱<?= number_format($averageOrderValue, 2) ?>', color: [217, 119, 6] },
                    { label: 'Total Orders', value: '<?= intval($totalOrders) ?>', color: [30, 64, 175] },
                    { label: 'Total Students', value: '<?= intval($totalStudents) ?>', color: [190, 24, 93] }
                ];

                const metricWidth = (pageWidth - 40) / 2;
                const metricHeight = 20;
                let xPos = 15;
                let row = 0;

                metrics.forEach((metric, index) => {
                    if (index % 2 === 0 && index > 0) {
                        row++;
                        xPos = 15;
                        yPosition += metricHeight + 5;
                    } else if (index > 0) {
                        xPos = 15 + metricWidth + 10;
                    }

                    // Metric box
                    pdf.setFillColor(248, 249, 250);
                    pdf.roundedRect(xPos, yPosition, metricWidth, metricHeight, 2, 2, 'F');
                    
                    pdf.setTextColor(113, 128, 150);
                    pdf.setFontSize(9);
                    pdf.setFont(undefined, 'bold');
                    pdf.text(metric.label.toUpperCase(), xPos + 5, yPosition + 7);
                    
                    pdf.setTextColor(26, 32, 44);
                    pdf.setFontSize(14);
                    pdf.setFont(undefined, 'bold');
                    pdf.text(metric.value, xPos + 5, yPosition + 15);
                });

                yPosition += metricHeight + 15;

                // Revenue Chart
                if (yPosition > pageHeight - 100) {
                    pdf.addPage();
                    yPosition = 20;
                }

                pdf.setTextColor(26, 32, 44);
                pdf.setFontSize(16);
                pdf.setFont(undefined, 'bold');
                pdf.text('Revenue Report', 15, yPosition);
                yPosition += 10;

                const canvas = document.getElementById('revenueChart');
                const chartImage = canvas.toDataURL('image/png');
                const imgWidth = pageWidth - 30;
                const imgHeight = 80;
                pdf.addImage(chartImage, 'PNG', 15, yPosition, imgWidth, imgHeight);
                yPosition += imgHeight + 10;

                // Revenue Stats
                pdf.setFillColor(248, 249, 250);
                pdf.roundedRect(15, yPosition, pageWidth - 30, 25, 2, 2, 'F');
                
                const statsX = [25, (pageWidth / 2) - 25, pageWidth - 65];
                const statsLabels = ['Total Revenue', 'Monthly Revenue', 'Growth Rate'];
                const statsValues = [
                    '₱<?= number_format($totalRevenue, 2) ?>',
                    '₱<?= number_format($monthlyRevenue, 2) ?>',
                    '<?= ($growthRate >= 0 ? '+' : '') . $growthRate ?>%'
                ];

                statsLabels.forEach((label, index) => {
                    pdf.setTextColor(113, 128, 150);
                    pdf.setFontSize(8);
                    pdf.setFont(undefined, 'bold');
                    pdf.text(label.toUpperCase(), statsX[index], yPosition + 8);
                    
                    pdf.setTextColor(26, 32, 44);
                    pdf.setFontSize(12);
                    pdf.setFont(undefined, 'bold');
                    pdf.text(statsValues[index], statsX[index], yPosition + 17);
                });

                yPosition += 35;

                // Top Organizations
                pdf.addPage();
                yPosition = 20;
                
                pdf.setTextColor(26, 32, 44);
                pdf.setFontSize(16);
                pdf.setFont(undefined, 'bold');
                pdf.text('Top Organizations', 15, yPosition);
                yPosition += 10;

                if (currentOrganizations.length > 0) {
                    currentOrganizations.slice(0, 10).forEach((org, index) => {
                        if (yPosition > pageHeight - 30) {
                            pdf.addPage();
                            yPosition = 20;
                        }

                        const rank = index + 1;
                        const orgName = org.organization_name || org.organization || 'Unknown';
                        const orderCount = parseInt(org.order_count) || 0;
                        const totalSales = parseFloat(org.total_sales) || 0;

                        // Organization row
                        pdf.setFillColor(248, 249, 250);
                        pdf.roundedRect(15, yPosition, pageWidth - 30, 15, 2, 2, 'F');

                        // Rank badge
                        if (rank <= 3) {
                            const colors = [[251, 191, 36], [148, 163, 184], [251, 146, 60]];
                            pdf.setFillColor(...colors[rank - 1]);
                        } else {
                            pdf.setFillColor(102, 126, 234);
                        }
                        pdf.roundedRect(20, yPosition + 3, 8, 8, 1, 1, 'F');
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFontSize(10);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(rank.toString(), 24, yPosition + 8.5, { align: 'center' });

                        // Organization name
                        pdf.setTextColor(26, 32, 44);
                        pdf.setFontSize(11);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(orgName.substring(0, 40), 33, yPosition + 7);

                        // Order count
                        pdf.setTextColor(113, 128, 150);
                        pdf.setFontSize(9);
                        pdf.setFont(undefined, 'normal');
                        pdf.text(`${orderCount} order${orderCount !== 1 ? 's' : ''}`, 33, yPosition + 12);

                        // Sales
                        pdf.setTextColor(102, 126, 234);
                        pdf.setFontSize(11);
                        pdf.setFont(undefined, 'bold');
                        const salesText = `₱${totalSales.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                        pdf.text(salesText, pageWidth - 20, yPosition + 9, { align: 'right' });

                        yPosition += 20;
                    });
                } else {
                    pdf.setTextColor(160, 174, 192);
                    pdf.setFontSize(10);
                    pdf.text('No organization data available', pageWidth / 2, yPosition + 20, { align: 'center' });
                }

                // Revenue per Organization Page
                pdf.addPage();
                yPosition = 20;
                
                pdf.setTextColor(26, 32, 44);
                pdf.setFontSize(16);
                pdf.setFont(undefined, 'bold');
                pdf.text('Revenue per Organization', 15, yPosition);
                yPosition += 10;

                const revenuePerOrgData = <?php echo json_encode($revenuePerOrg); ?>;

                if (revenuePerOrgData && revenuePerOrgData.length > 0) {
                    revenuePerOrgData.forEach((org, index) => {
                        if (yPosition > pageHeight - 30) {
                            pdf.addPage();
                            yPosition = 20;
                        }

                        const rank = index + 1;
                        const orgName = org.organization || 'Unknown';
                        const totalOrders = parseInt(org.total_orders) || 0;
                        const totalRevenue = parseFloat(org.total_revenue) || 0;

                        // Organization row
                        pdf.setFillColor(248, 249, 250);
                        pdf.roundedRect(15, yPosition, pageWidth - 30, 15, 2, 2, 'F');

                        // Rank badge
                        if (rank <= 3) {
                            const colors = [[251, 191, 36], [148, 163, 184], [251, 146, 60]];
                            pdf.setFillColor(...colors[rank - 1]);
                        } else {
                            pdf.setFillColor(102, 126, 234);
                        }
                        pdf.roundedRect(20, yPosition + 3, 8, 8, 1, 1, 'F');
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFontSize(10);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(rank.toString(), 24, yPosition + 8.5, { align: 'center' });

                        // Organization name
                        pdf.setTextColor(26, 32, 44);
                        pdf.setFontSize(11);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(orgName.substring(0, 40), 33, yPosition + 7);

                        // Order count
                        pdf.setTextColor(113, 128, 150);
                        pdf.setFontSize(9);
                        pdf.setFont(undefined, 'normal');
                        pdf.text(`${totalOrders} order${totalOrders !== 1 ? 's' : ''}`, 33, yPosition + 12);

                        // Revenue
                        pdf.setTextColor(102, 126, 234);
                        pdf.setFontSize(11);
                        pdf.setFont(undefined, 'bold');
                        const revenueText = `₱${totalRevenue.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                        pdf.text(revenueText, pageWidth - 20, yPosition + 9, { align: 'right' });

                        yPosition += 20;
                    });
                } else {
                    pdf.setTextColor(160, 174, 192);
                    pdf.setFontSize(10);
                    pdf.text('No revenue per organization data available', pageWidth / 2, yPosition + 20, { align: 'center' });
                }

                // Top Products
                pdf.addPage();
                yPosition = 20;
                
                pdf.setTextColor(26, 32, 44);
                pdf.setFontSize(16);
                pdf.setFont(undefined, 'bold');
                pdf.text('Top Products', 15, yPosition);
                yPosition += 10;

                if (currentProducts.length > 0) {
                    currentProducts.slice(0, 10).forEach((product, index) => {
                        if (yPosition > pageHeight - 35) {
                            pdf.addPage();
                            yPosition = 20;
                        }

                        const rank = index + 1;
                        const productName = product.product_name || 'Unknown Product';
                        const organization = product.organization || 'N/A';
                        const quantity = parseInt(product.total_quantity) || 0;
                        const orderCount = parseInt(product.order_count) || 0;
                        const totalSales = parseFloat(product.total_sales) || 0;

                        // Product row
                        pdf.setFillColor(248, 249, 250);
                        pdf.roundedRect(15, yPosition, pageWidth - 30, 18, 2, 2, 'F');

                        // Rank badge
                        pdf.setFillColor(102, 126, 234);
                        pdf.roundedRect(20, yPosition + 3, 8, 8, 1, 1, 'F');
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFontSize(10);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(rank.toString(), 24, yPosition + 8.5, { align: 'center' });

                        // Product name
                        pdf.setTextColor(26, 32, 44);
                        pdf.setFontSize(10);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(productName.substring(0, 35), 33, yPosition + 6);

                        // Organization
                        pdf.setTextColor(102, 126, 234);
                        pdf.setFontSize(8);
                        pdf.setFont(undefined, 'bold');
                        pdf.text(organization.substring(0, 30), 33, yPosition + 11);

                        // Details
                        pdf.setTextColor(113, 128, 150);
                        pdf.setFontSize(8);
                        pdf.setFont(undefined, 'normal');
                        pdf.text(`${quantity} units • ${orderCount} orders`, 33, yPosition + 15);

                        // Sales
                        pdf.setTextColor(102, 126, 234);
                        pdf.setFontSize(11);
                        pdf.setFont(undefined, 'bold');
                        const salesText = `₱${totalSales.toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                        pdf.text(salesText, pageWidth - 20, yPosition + 10, { align: 'right' });

                        yPosition += 23;
                    });
                } else {
                    pdf.setTextColor(160, 174, 192);
                    pdf.setFontSize(10);
                    pdf.text('No product data available', pageWidth / 2, yPosition + 20, { align: 'center' });
                }

                // Footer on all pages
                const totalPages = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    pdf.setPage(i);
                    pdf.setFillColor(248, 249, 250);
                    pdf.rect(0, pageHeight - 15, pageWidth, 15, 'F');
                    pdf.setTextColor(113, 128, 150);
                    pdf.setFontSize(8);
                    pdf.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 8, { align: 'center' });
                    pdf.text('Phirse Admin Dashboard', 15, pageHeight - 8);
                    pdf.text(new Date().toLocaleDateString(), pageWidth - 15, pageHeight - 8, { align: 'right' });
                }

                // Save PDF
                const fileName = `Dashboard_Report_${new Date().toISOString().split('T')[0]}.pdf`;
                pdf.save(fileName);

            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
            } finally {
                loadingOverlay.classList.remove('active');
            }
        }

        // Load on page load
        document.addEventListener('DOMContentLoaded', function() {
            const orgButton = document.querySelector('.filter-btn.active:not(.product-filter)');
            const productButton = document.querySelector('.product-filter.active');
            
            if (orgButton) {
                loadOrganizations(7, orgButton);
            } else {
                loadOrganizations(7, null);
            }
            
            if (productButton) {
                loadProducts(7, productButton);
            } else {
                loadProducts(7, null);
            }

            // Start toast poller
            new AdminToastPoller();
        });
    </script>

</body>

</html>