<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';

// Get seller info and metrics
try {
    $seller_id = $_SESSION['seller_id'];

    // Get seller information including logo, GCash QR and number
    $stmt = $pdo->prepare("SELECT seller_name, organization, logo_path, gcash_qr_path, gcash_number FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);

    $seller_logo = $seller_info['logo_path'] ?? null;
    $gcash_qr = $seller_info['gcash_qr_path'] ?? null;
    $gcash_number = $seller_info['gcash_number'] ?? null;
    $organization_name = $seller_info['organization'] ?? $_SESSION['organization'] ?? '';
    $seller_name = $seller_info['seller_name'] ?? $_SESSION['seller_name'] ?? '';

    // Count seller's products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
    $stmt->execute([$seller_id]);
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count seller's pre-orders (pending orders)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'pending'");
    $stmt->execute([$seller_id]);
    $totalPreOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT ROUND(SUM(total_price),2) AS total FROM orders WHERE status = 'completed' AND seller_id = :seller_id");
    $stmt->execute(['seller_id' => $seller_id]);
    $totalRevenue = $stmt->fetchColumn() ?? 0;

    // Today
    $stmt = $pdo->prepare("SELECT ROUND(SUM(total_price),2) FROM orders WHERE status='completed' AND seller_id=:seller_id AND DATE(order_date)=CURDATE()");
    $stmt->execute(['seller_id' => $seller_id]);
    $revenueToday = $stmt->fetchColumn() ?? 0;

    // This Week (ISO week)
    $stmt = $pdo->prepare("SELECT ROUND(SUM(total_price),2) FROM orders WHERE status='completed' AND seller_id=:seller_id AND YEARWEEK(order_date,1)=YEARWEEK(CURDATE(),1)");
    $stmt->execute(['seller_id' => $seller_id]);
    $revenueWeek = $stmt->fetchColumn() ?? 0;

    // This Month
    // (similar to your admin queries)

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status='completed' AND seller_id=:seller_id");
    $stmt->execute(['seller_id' => $seller_id]);
    $completedOrders = (int)$stmt->fetchColumn();

    $averageOrderValue = $completedOrders > 0 ? round($totalRevenue / $completedOrders, 2) : 0;

    $stmt = $pdo->prepare(
        "SELECT
    SUM(CASE WHEN YEAR(order_date)=YEAR(CURDATE()) AND MONTH(order_date)=MONTH(CURDATE()) THEN total_price ELSE 0 END) AS current_month,
    SUM(CASE WHEN YEAR(order_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(order_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN total_price ELSE 0 END) AS prev_month
  FROM orders
  WHERE status='completed' AND seller_id = :seller_id"
    );
    $stmt->execute(['seller_id' => $seller_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_month = (float)($row['current_month'] ?? 0);
    $prev_month = (float)($row['prev_month'] ?? 0);
    $growthRate = ($prev_month > 0) ? round((($current_month - $prev_month) / $prev_month) * 100, 2) : ($current_month > 0 ? 100 : 0);

    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, SUM(o.quantity) AS total_sold, SUM(o.total_price) AS revenue
  FROM orders o
  JOIN products p ON p.id = o.product_id
  WHERE o.status='completed' AND o.seller_id = :seller_id
    AND YEAR(o.order_date)=YEAR(CURDATE()) AND MONTH(o.order_date)=MONTH(CURDATE())
  GROUP BY p.id
  ORDER BY total_sold DESC
  LIMIT 3"
    );
    $stmt->execute(['seller_id' => $seller_id]);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC); // loop when rendering

    // ===== TOP ORGANIZATION SALES (current month) - PDO version =====
    try {
        $currentMonth = (int)date('m');
        $currentYear  = (int)date('Y');

        $topOrganizationsQuery = "SELECT 
            COALESCE(NULLIF(s.organization, ''), s.seller_name) AS organization_name,
            ROUND(SUM(o.total_price), 2) AS total_sales
        FROM orders o
        INNER JOIN sellers s ON o.seller_id = s.id
        WHERE MONTH(o.order_date) = :month
          AND YEAR(o.order_date) = :year
          AND o.status IN ('paid','confirmed','approved','completed')
        GROUP BY organization_name
        ORDER BY total_sales DESC
        LIMIT 5";

        $stmt = $pdo->prepare($topOrganizationsQuery);
        $stmt->execute(['month' => $currentMonth, 'year' => $currentYear]);
        $topOrganizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // optional: log $e->getMessage(); keep UI clean
        $topOrganizations = [];
    }
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalPreOrders = 0;
    $seller_logo = null;
    $gcash_qr = null;
    $organization_name = $_SESSION['organization'] ?? '';
    $seller_name = $_SESSION['seller_name'] ?? '';
}

// Handle GCash information update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gcash_number = $_POST['gcash_number'] ?? '';
    $has_qr_upload = !empty($_FILES['gcash_qr']['name']);
    $update_successful = false;
    $upload_path = null;

    // Handle QR code upload if provided
    if ($has_qr_upload) {
        $upload_dir = '../uploads/gcash/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['gcash_qr'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];

        if (in_array($file['type'], $allowed_types)) {
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'gcash_qr_' . $seller_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old QR code if exists
                if (!empty($gcash_qr) && file_exists($gcash_qr)) {
                    unlink($gcash_qr);
                }
                $update_successful = true;
            } else {
                $error_message = "Failed to upload QR code.";
            }
        } else {
            $error_message = "Only JPG, JPEG, and PNG files are allowed.";
        }
    } else {
        $update_successful = true; // No QR upload but we can still update the number
    }

    // Update database with new information
    if ($update_successful) {
        $sql = "UPDATE sellers SET gcash_number = ?";
        $params = [$gcash_number];
        
        if ($has_qr_upload && $upload_path) {
            $sql .= ", gcash_qr_path = ?";
            $params[] = $upload_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $seller_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            if ($has_qr_upload && $upload_path) {
                $gcash_qr = $upload_path;
            }
            $success_message = "GCash information updated successfully!";
        } else {
            $error_message = "Failed to update GCash information.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Phirse</title>
    <link rel="icon" type="image/png" href="../uploads/images/Plogo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 16px;
        }

        .breadcrumb i {
            margin-right: 10px;
        }

        .welcome-text {
            color: #333;
            font-weight: 600;
        }

        /* Notification Bell Styles */
        .notification-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-bell {
            position: relative;
            background: #f8f9fa;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            color: #666;
        }

        .notification-bell:hover {
            background: #e9ecef;
            transform: scale(1.05);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            min-width: 20px;
            animation: pulse 2s infinite;
        }

        .notification-badge.hidden {
            display: none;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Notification Popup */
        .notification-popup {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            width: 380px;
            max-height: 500px;
            overflow: hidden;
            z-index: 1000;
            transform: translateY(-10px) scale(0.95);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .notification-popup.show {
            transform: translateY(0) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .notification-popup-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification-popup-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-popup-close {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .notification-popup-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .notification-popup-content {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-popup-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .notification-popup-item:hover {
            background: #f8f9fa;
        }

        .notification-popup-item.unread {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }

        .notification-popup-item:last-child {
            border-bottom: none;
        }

        .notification-popup-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-popup-message {
            color: #666;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .notification-popup-time {
            color: #999;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notification-popup-footer {
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
            background: #f8f9fa;
        }

        .notification-popup-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .notification-popup-footer a:hover {
            text-decoration: underline;
        }

        .notification-loading {
            text-align: center;
            padding: 20px;
            color: #999;
        }

        .notification-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .notification-empty i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .notification-popup {
                width: 320px;
                right: -10px;
            }

            .notification-container {
                gap: 15px;
            }

            .notification-bell {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        .metrics-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            justify-content: center;
            align-items: stretch;
        }


        .metric-card-link {
            text-decoration: none;
            color: inherit;
            display: contents;
            /* allows grid layout to treat inner card as grid item */
        }

        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card-link:hover .metric-card,
        .metric-card-link:active .metric-card {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background-color: #f9f9f9;
            cursor: pointer;
        }


        .metric-icon {
            width: 80px;
            height: 80px;
            background-color: #f8f9fa;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .metric-icon i {
            font-size: 32px;
            color: #666;
        }

        .metric-number {
            font-size: 48px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            line-height: 1;
        }

        .metric-label {
            font-size: 18px;
            color: #666;
            font-weight: 500;
        }

        .gcash-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .gcash-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
        }

        .gcash-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .gcash-upload-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .gcash-upload-form label {
            font-weight: 500;
            color: #333;
            font-size: 16px;
        }

        .gcash-upload-form input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: pointer;
        }

        .gcash-upload-form button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .gcash-upload-form button:hover {
            background-color: #0056b3;
        }

        .gcash-preview {
            text-align: center;
        }

        .gcash-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 2px solid #ddd;
            max-height: 400px;
        }

        .no-qr-message {
            color: #666;
            font-style: italic;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

            .metrics-container {
                grid-template-columns: 1fr;
            }

            .metric-card {
                padding: 20px;
            }

            .gcash-section {
                padding: 20px;
            }

            .gcash-title {
                font-size: 18px;
            }

            .gcash-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 10px 10px;
            }

            .page-title {
                font-size: 18px;
            }

            .metric-card {
                padding: 15px;
            }

            .metric-number {
                font-size: 28px;
            }

            .metric-label {
                font-size: 11px;
            }

            .gcash-section {
                padding: 15px;
            }
        }

        /* --- ANALYTICS SECTIONS (Overview, Sales Insights, etc.) --- */
        .analytics-wrapper {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 20px;
        }

        /* .analytics-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        } */

        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* .placeholder-box {
            text-align: center;
            color: #999;
            background: #f9f9f9;
            padding: 60px 0;
            border-radius: 10px;
            font-style: italic;
            font-size: 16px;
        } */



        /* Each section (card-like container) */
        .analytics-section {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Make sure the placeholder boxes look centered */
        .placeholder-box {
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
            padding: 50px 0;
            font-size: 1rem;
            color: #888;
            border: 1px dashed #ccc;
        }

        /* Optional: make responsive for mobile */
        @media (max-width: 900px) {
            .analytics-wrapper {
                grid-template-columns: 1fr;
            }

            .metrics-container {
                grid-template-columns: 1fr;
            }
        }

        /* 🏆 Top Organization Sales */
        .top-orgs-container {
            margin-top: 10px;
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .org-ranking {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .org-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            margin-bottom: 12px;
            padding: 12px 16px;
            border-radius: 15px;
            font-weight: 500;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .org-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        .org-item .rank {
            font-weight: bold;
            color: #007bff;
            margin-right: 10px;
        }

        .org-item .org-name {
            flex-grow: 1;
            color: #333;
        }

        .org-item .org-sales {
            font-weight: bold;
            color: #28a745;
        }

        .top-org-container {
            display: flex;
            flex-direction: column;
            gap: 12px;

            margin-top: 10px;
        }

        .org-row {
            background: linear-gradient(145deg, #ffffff, #f3f3f3);
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            animation: fadeInRow 0.6s ease forwards;
            opacity: 0;
        }

        .org-row:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            background: linear-gradient(145deg, #fafafa, #ffffff);
        }

        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .org-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .org-name {
            color: #222;
            font-weight: 600;
        }

        .org-sales {
            font-weight: 700;
            color: #28a745;
        }

        .org-bar {
            position: relative;
            width: 100%;
            background: #eaeaea;
            border-radius: 5px;
            height: 30px;
            overflow: hidden;
        }

        @keyframes fillBar {
            from {
                width: 0;
            }

            to {
                width: var(--target-width);
            }
        }

        .org-bar-fill {
            height: 30px;
            border-radius: 5px;
            background: linear-gradient(180deg, #fac013ff, #ffc107);
            width: 0;
            animation: fillBar 1s ease-out forwards;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.3);
        }

        /* when element becomes visible */
        .org-bar-fill.visible {
            width: var(--target-width);
        }

        .no-data {
            text-align: center;
            color: #888;
            font-size: 14px;
            padding: 15px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .month-filter {
            padding: 5px 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 14px;
            background-color: #fff;
            cursor: pointer;
        }

        .analytics-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;

        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .analytics-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .filter-buttons button {
            background: #f0f0f0;
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            margin-left: 5px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .filter-buttons button:hover {
            background: #ddd;
        }

        #revenueChart {
            width: 100%;
            max-height: 300px;
        }

        .top-org-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .summary-box {
            margin-top: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            font-size: 14px;
            color: #444;
        }

        .summary-box p {
            margin: 6px 0;
        }

        .positive-change {
            color: #28a745;
        }

        .negative-change {
            color: #dc3545;
        }

        /* 🧮 Product Performance Styles (Separate from Top Org Sales) */
        .product-performance-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 10px;
        }

        .product-row {
            background: linear-gradient(145deg, #ffffff, #f9f9f9);
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            animation: fadeInProduct 0.5s ease forwards;
            opacity: 0;
        }

        .product-row:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        @keyframes fadeInProduct {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-info {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            color: #333;
            margin-bottom: 6px;
        }

        .product-name {
            font-weight: 600;
        }

        .product-sales {
            color: #28a745;
            font-weight: 700;
        }

        .product-bar {
            background: #eaeaea;
            border-radius: 6px;
            height: 25px;
            overflow: hidden;
        }

        .product-bar-fill {
            height: 25px;
            border-radius: 6px;
            background: linear-gradient(180deg, #6f42c1, #8b5cf6);
            width: 0;
            animation: fillProductBar 1s ease-out forwards;
        }

        @keyframes fillProductBar {
            from {
                width: 0;
            }

            to {
                width: var(--target-width);
            }
        }

        /* === 🔘 Filter Buttons Styling (Shared Style) === */
        .filter-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .filter-buttons button {
            background: #f1f1f1;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            color: #444;
            transition: all 0.25s ease;
        }

        .filter-buttons button:hover {
            background: #e3e3e3;
            transform: translateY(-1px);
        }

        .filter-buttons button.active {
            background: #34c759;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 3px 6px rgba(52, 199, 89, 0.3);
            transform: translateY(-2px);
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
                Home > Dashboard
            </div>
            <div class="notification-container">
                <div class="welcome-text">
                    Welcome, <?php echo htmlspecialchars($seller_name ?: 'Seller'); ?>!
                </div>
                <button class="notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge hidden" id="notificationBadge">0</span>
                </button>
                
                <!-- Notification Popup -->
                <div class="notification-popup" id="notificationPopup">
                    <div class="notification-popup-header">
                        <h3>
                            <i class="fas fa-bell"></i>
                            Notifications
                        </h3>
                        <button class="notification-popup-close" id="notificationCloseBtn" aria-label="Close notifications">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="notification-popup-content" id="notificationContent">
                        <div class="notification-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            Loading notifications...
                        </div>
                    </div>
                    <div class="notification-popup-footer">
                        <a href="seller-notifications.php">View All Notifications</a>
                    </div>
                </div>
            </div>
        </div>


        <!-- === MAIN ANALYTICS CONTAINERS === -->
        <div class="analytics-wrapper">

            <!-- 📈 OVERVIEW SECTION -->
            <div class="analytics-section">
                <h2 class="section-title"><i class="fas fa-chart-pie"></i> Overview</h2>
                <div class="metrics-container">

                    <!-- Total Sales Revenue -->
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-sack-dollar" style="color:#28a745;"></i></div>
                        <div class="metric-number">₱<?= number_format($totalRevenue, 2) ?></div>
                        <div class="metric-label">Total Sales Revenue</div>
                    </div>

                    <!-- Average Order Value -->
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-coins" style="color:#ffc107;"></i></div>
                        <div class="metric-number">₱<?= number_format($averageOrderValue, 2) ?></div>
                        <div class="metric-label">Average Order Value</div>
                    </div>

                    <a href="seller-orders.php" class="metric-card-link">
    <div class="metric-card">
        <div class="metric-icon">
            <i class="fas fa-clipboard-list" style="color:#17a2b8;"></i>
        </div>
        <div class="metric-number"><?= $totalPreOrders ?></div>
        <div class="metric-label">Total Pre-orders</div>
    </div>
</a>

                    <!-- Total Products -->
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fas fa-boxes" style="color:#6f42c1;"></i></div>
                        <div class="metric-number"><?= $totalProducts ?></div>
                        <div class="metric-label">Total Products</div>
                    </div>

                </div>
            </div>

            <!-- 🔲 OTHER SECTIONS (Empty for now) -->

            <!-- 🏆 TOP ORGANIZATION SALES -->
            <div class="analytics-section">

                <div class="card-header">
                    <h2 class="section-title"><i class="fas fa-trophy"></i> Top Organization Sales</h2>
                    <select id="monthFilter" class="month-filter">
                        <option value="current">This Month</option>
                        <option value="last6">Last 6 Months</option>
                    </select>
                </div>

                <div id="topOrgSalesContainer" class="top-org-container">
                    <?php
                    if (!empty($topOrganizations)) {
                        // Find the top sales value for bar scaling
                        $maxSales = max(array_column($topOrganizations, 'total_sales'));
                        $delay = 0;
                        foreach ($topOrganizations as $org) {
                            $percent = ($maxSales > 0) ? ($org['total_sales'] / $maxSales) * 100 : 0;
                    ?>
                            <div class="org-row">
                                <div class="org-info">
                                    <span class="org-name"><?= htmlspecialchars($org['organization_name']) ?></span>
                                    <span class="org-sales">₱<?= number_format($org['total_sales'], 2) ?></span>
                                </div>
                                <div class="org-bar">
                                    <div class="org-bar-fill" style="--target-width: <?= $percent ?>%;"></div>
                                </div>
                            </div>
                        <?php
                            $delay += 0.1; // each bar animates 0.1s after the previous
                        }
                        ?>
                    <?php
                    } else {
                        echo "<p class='no-data'>No sales data available this month.</p>";
                    }
                    ?>
                </div>
            </div>

            <!-- 📊 REVENUE REPORT -->
            <div class="analytics-section">
                <div class="card-header">
                    <h2 class="section-title"><i class="fa-solid fa-chart-simple"></i> Revenue Report</h2>
                    <select id="revenueRange" class="month-filter">
                        <option value="7days">Last 7 Days</option>
                        <option value="month">This Month</option>
                        <option value="30days">Last 30 Days</option>
                    </select>
                </div>

                <canvas id="revenueChart" style="max-height: 300px;"></canvas>

                <div id="revenueSummary" class="summary-box">
                    <p><strong>Total Revenue:</strong> ₱<span id="totalRevenue">0</span></p>
                    <p><strong>Change vs Previous:</strong> <span id="changePercent">0%</span></p>
                    <p><strong>Average Daily Revenue:</strong> ₱<span id="averageRevenue">0</span></p>
                    <p><strong>Best Day:</strong> <span id="bestDay">N/A</span></p>
                    <p><strong>Lowest Day:</strong> <span id="lowDay">N/A</span></p>
                </div>
            </div>


            <div class="analytics-card">
                <div class="analytics-header">
                    <h2 class="section-title"><i class="fas fa-chart-line"></i> Product Performance</h2>
                    <div class="filter-buttons" id="productPerformanceFilters">
                        <button data-range="7days" class="active">Last 7 Days</button>
                        <button data-range="month">This Month</button>
                        <button data-range="30days">Last 30 Days</button>
                    </div>
                </div>
                <div id="productPerformanceContainer" class="top-org-container"></div>
                <div id="productSummary" class="summary-box">
                    <p><strong>Total Revenue:</strong> ₱<span id="prodTotalRevenue">0</span></p>
                    <p><strong>Total Sold:</strong> <span id="prodTotalSold">0</span> pcs</p>
                </div>
            </div>


        </div>

        <div class="gcash-section">
            <h2><i class="fas fa-qrcode"></i> GCash Payment QR Code</h2>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="gcash-content">
                <div class="gcash-upload-form">
                    <form method="POST" enctype="multipart/form-data">
                        <div style="margin-bottom: 20px;">
                            <label for="gcash_number" style="display: block; margin-bottom: 8px;">GCash Number:</label>
                            <input type="text" name="gcash_number" id="gcash_number" value="<?php echo htmlspecialchars($gcash_number ?? ''); ?>" 
                                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
                                placeholder="Enter GCash number" maxlength="11">
                        </div>
                        
                        <label for="gcash_qr">Upload GCash QR Code:</label>
                        <input type="file" name="gcash_qr" id="gcash_qr" accept="image/jpeg,image/jpg,image/png">
                        <button type="submit">
                            <i class="fas fa-upload"></i> Save Changes
                        </button>
                    </form>
                    <p style="color: #666; font-size: 14px; margin-top: 10px;">
                        <i class="fas fa-info-circle"></i> Accepted formats: JPG, JPEG, PNG
                    </p>
                </div>

                <div class="gcash-preview">
                    <h3 style="margin-bottom: 15px; color: #333;">Current QR Code:</h3>
                    <?php if (!empty($gcash_qr) && file_exists($gcash_qr)): ?>
                        <div style="text-align: center;">
                            <img src="<?php echo htmlspecialchars($gcash_qr); ?>" alt="GCash QR Code" style="max-width: 300px;">
                        </div>
                    <?php else: ?>
                        <div class="no-qr-message">
                            <i class="fas fa-image" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>No QR code uploaded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.querySelectorAll('.metric-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'scale(1.03)';
                card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
                card.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const bars = document.querySelectorAll(".org-bar-fill");

            const observer = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("visible");
                            observer.unobserve(entry.target); // animate only once
                        }
                    });
                }, {
                    threshold: 0.3
                } // triggers when 30% of element is visible
            );

            bars.forEach(bar => observer.observe(bar));
        });
    </script>

    <script>
        document.getElementById('monthFilter').addEventListener('change', function() {
            const value = this.value;
            fetch(`top_org_sales.php?filter=${value}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('topOrgSalesContainer').innerHTML = html;
                });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById("topOrgSalesContainer");
            const filter = document.getElementById("monthFilter");

            // Function to load data dynamically
            function loadTopSales(filterValue) {
                fetch(`top_org_sales.php?filter=${filterValue}`)
                    .then(res => res.text())
                    .then(html => {
                        container.innerHTML = html;

                        // re-apply animation when new data loads
                        const bars = container.querySelectorAll(".org-bar-fill");
                        const observer = new IntersectionObserver(entries => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    entry.target.classList.add("visible");
                                    observer.unobserve(entry.target);
                                }
                            });
                        }, {
                            threshold: 0.3
                        });
                        bars.forEach(bar => observer.observe(bar));
                    });
            }

            // Initial load
            loadTopSales(filter.value);

            // Change event for filter
            filter.addEventListener("change", () => {
                loadTopSales(filter.value);
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const ctx = document.getElementById("revenueChart").getContext("2d");
            const rangeSelector = document.getElementById("revenueRange");

            let revenueChart;

            async function loadRevenueData(range = "7days") {
                const res = await fetch(`revenue_report.php?range=${range}`);
                const data = await res.json();

                // Color bars based on average
                const colors = data.totals.map(v => v >= data.averageRevenue ? "#28a745" : "#dc3545");

                if (revenueChart) revenueChart.destroy();

                revenueChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: "Revenue (₱)",
                            data: data.totals,
                            backgroundColor: colors,
                            borderRadius: 5,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "#eee"
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => `₱${ctx.formattedValue}`
                                }
                            }
                        }
                    }
                });

                // Update summary
                document.getElementById("totalRevenue").textContent = data.totalRevenue.toLocaleString(undefined, {
                    minimumFractionDigits: 2
                });
                document.getElementById("averageRevenue").textContent = data.averageRevenue.toFixed(2);
                document.getElementById("bestDay").textContent = data.bestDay;
                document.getElementById("lowDay").textContent = data.lowDay;

                const changeElem = document.getElementById("changePercent");
                changeElem.textContent = (data.change > 0 ? "+" : "") + data.change + "%";
                changeElem.className = data.change >= 0 ? "positive-change" : "negative-change";
            }

            // Initial load
            loadRevenueData();

            // On change
            rangeSelector.addEventListener("change", e => {
                loadRevenueData(e.target.value);
            });
        });
    </script>
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1"></script>
    <script src="revenue_report.js"></script>
    <script src="product_performance.js"></script>

    <!-- Notification System JavaScript -->
    <script>
        class NotificationSystem {
            constructor() {
                this.bell = document.getElementById('notificationBell');
                this.badge = document.getElementById('notificationBadge');
                this.popup = document.getElementById('notificationPopup');
                this.content = document.getElementById('notificationContent');
                this.closeBtn = document.getElementById('notificationCloseBtn');
                this.isOpen = false;
                this.updateInterval = null;
                
                this.init();
            }

            init() {
                // Toggle popup on bell click
                this.bell.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.togglePopup();
                });

                if (this.closeBtn) {
                    this.closeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.closePopup();
                    });
                }

                // Close popup when clicking outside
                document.addEventListener('click', (e) => {
                    if (!this.popup.contains(e.target) && !this.bell.contains(e.target)) {
                        this.closePopup();
                    }
                });

                // Close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.closePopup();
                    }
                });

                // Load initial notifications
                this.loadNotifications();
                
                // Set up real-time updates (every 30 seconds)
                this.updateInterval = setInterval(() => {
                    this.loadNotifications();
                }, 30000);
            }

            async loadNotifications() {
                try {
                    const response = await fetch('fetch-notifications.php');
                    const data = await response.json();

                    if (data.success) {
                        this.updateBadge(data.unread_count);
                        this.renderNotifications(data.notifications);
                    }
                } catch (error) {
                    console.error('Error loading notifications:', error);
                }
            }

            updateBadge(count) {
                if (count > 0) {
                    this.badge.textContent = count > 99 ? '99+' : count;
                    this.badge.classList.remove('hidden');
                } else {
                    this.badge.classList.add('hidden');
                }
            }

            renderNotifications(notifications) {
                if (notifications.length === 0) {
                    this.content.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <h4>No notifications</h4>
                            <p>You're all caught up!</p>
                        </div>
                    `;
                    return;
                }

                const notificationsHtml = notifications.map(notif => `
                    <div class="notification-popup-item ${notif.is_read ? '' : 'unread'}" 
                         onclick="notificationSystem.markAsRead(${notif.id})">
                        <div class="notification-popup-title">
                            <i class="fas ${notif.icon}"></i>
                            ${this.escapeHtml(notif.title)}
                        </div>
                        <div class="notification-popup-message">
                            ${this.escapeHtml(notif.message)}
                        </div>
                        <div class="notification-popup-time">
                            <i class="far fa-clock"></i>
                            ${notif.time_ago}
                        </div>
                    </div>
                `).join('');

                this.content.innerHTML = notificationsHtml;
            }

            togglePopup() {
                if (this.isOpen) {
                    this.closePopup();
                } else {
                    this.openPopup();
                }
            }

            openPopup() {
                this.popup.classList.add('show');
                this.isOpen = true;
                this.loadNotifications(); // Refresh when opening
            }

            closePopup() {
                this.popup.classList.remove('show');
                this.isOpen = false;
            }

            async markAsRead(notificationId) {
                try {
                    const response = await fetch('mark-notification-read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ notification_id: notificationId })
                    });

                    if (response.ok) {
                        // Reload notifications to update the UI
                        this.loadNotifications();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            destroy() {
                if (this.updateInterval) {
                    clearInterval(this.updateInterval);
                }
            }
        }

        // Initialize notification system when DOM is loaded
        let notificationSystem;
        document.addEventListener('DOMContentLoaded', () => {
            notificationSystem = new NotificationSystem();
        });

        // Clean up when page unloads
        window.addEventListener('beforeunload', () => {
            if (notificationSystem) {
                notificationSystem.destroy();
            }
        });
    </script>

</body>

</html>