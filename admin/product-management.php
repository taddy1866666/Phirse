<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';
$pageTitle = 'Product Management';
include 'includes/header.php';

// Handle approve/reject/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $action = $_POST['action'];
        
        try {

            if ($action === 'approve') {
                // Get product and seller info
                $productStmt = $pdo->prepare("SELECT p.name, p.category, p.seller_id FROM products p WHERE p.id = ?");
                $productStmt->execute([$product_id]);
                $productInfo = $productStmt->fetch();

                $stmt = $pdo->prepare("UPDATE products SET status = 'approved', rejection_reason = NULL WHERE id = ?");
                $stmt->execute([$product_id]);
                $message = "Product approved successfully";

                // Create notification for seller
                if ($productInfo) {
                    $notif_message = 'Category: ' . $productInfo['category'] . "\nProduct: " . $productInfo['name'] . "\nStatus: Your product has been approved by admin and is now live!";
                    $notifStmt = $pdo->prepare("
                        INSERT INTO seller_notifications (seller_id, product_id, type, title, message)
                        VALUES (?, ?, 'approved', ?, ?)
                    ");
                    $notifStmt->execute([
                        $productInfo['seller_id'],
                        $product_id,
                        'Product Approved: ' . $productInfo['name'],
                        $notif_message
                    ]);
                }

            } elseif ($action === 'reject') {
                $rejection_reason = $_POST['rejection_reason'] ?? '';

                // Get product and seller info
                $productStmt = $pdo->prepare("SELECT p.name, p.category, p.seller_id FROM products p WHERE p.id = ?");
                $productStmt->execute([$product_id]);
                $productInfo = $productStmt->fetch();

                $stmt = $pdo->prepare("UPDATE products SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                $stmt->execute([$rejection_reason, $product_id]);
                $message = "Product rejected successfully";

                // Create notification for seller
                if ($productInfo) {
                    $notif_message = 'Category: ' . $productInfo['category'] . "\nProduct: " . $productInfo['name'] . "\nReason: " . $rejection_reason;
                    $notifStmt = $pdo->prepare("
                        INSERT INTO seller_notifications (seller_id, product_id, type, title, message)
                        VALUES (?, ?, 'rejected', ?, ?)
                    ");
                    $notifStmt->execute([
                        $productInfo['seller_id'],
                        $product_id,
                        'Product Rejected: ' . $productInfo['name'],
                        $notif_message
                    ]);
                }

            } elseif ($action === 'delete') {
                // Get product and seller info before deleting
                $productStmt = $pdo->prepare("SELECT p.name, p.category, p.seller_id FROM products p WHERE p.id = ?");
                $productStmt->execute([$product_id]);
                $productInfo = $productStmt->fetch();

                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $message = "Product deleted successfully";

                // Create notification for seller
                if ($productInfo) {
                    $notif_message = 'Category: ' . $productInfo['category'] . "\nProduct: " . $productInfo['name'] . "\nStatus: Your product has been deleted by admin.";
                    $notifStmt = $pdo->prepare("
                        INSERT INTO seller_notifications (seller_id, product_id, type, title, message)
                        VALUES (?, ?, 'deleted', ?, ?)
                    ");
                    $notifStmt->execute([
                        $productInfo['seller_id'],
                        $product_id,
                        'Product Deleted: ' . $productInfo['name'],
                        $notif_message
                    ]);
                }
            }
        } catch(PDOException $e) {
            $error = "Error updating product: " . $e->getMessage();
        }
    }
    
    // Handle bulk delete
    if (isset($_POST['delete_multiple'])) {
        try {
            $ids = json_decode($_POST['delete_multiple'], true);
            
            if (!is_array($ids) || empty($ids)) {
                throw new Exception("Invalid selection");
            }
            
            // Validate all IDs are numeric
            foreach ($ids as $id) {
                if (!is_numeric($id)) {
                    throw new Exception("Invalid product ID");
                }
            }
            
            // Get product info before deleting for notifications
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $getProductsStmt = $pdo->prepare("SELECT id, name, category, seller_id FROM products WHERE id IN ($placeholders)");
            $getProductsStmt->execute($ids);
            $productsToDelete = $getProductsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete all selected products
            $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            // Create notifications for each deleted product
            foreach ($productsToDelete as $product) {
                $notif_message = 'Category: ' . $product['category'] . "\nProduct: " . $product['name'] . "\nStatus: Your product has been deleted by admin.";
                $notifStmt = $pdo->prepare("
                    INSERT INTO seller_notifications (seller_id, product_id, type, title, message)
                    VALUES (?, ?, 'deleted', ?, ?)
                ");
                $notifStmt->execute([
                    $product['seller_id'],
                    $product['id'],
                    'Product Deleted: ' . $product['name'],
                    $notif_message
                ]);
            }
            
            $message = "Successfully deleted " . count($ids) . " product(s)!";
        } catch(Exception $e) {
            $error = "Error deleting products: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'all';

// Get products from database based on filters
try {
    $sql = "SELECT p.*, s.seller_name, s.organization, 
            CASE 
                WHEN p.category IN ('Organization Shirt', 'Merchandise') 
                AND p.description REGEXP 'Sizes:([^:\\n]+)'
                THEN SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, 'Sizes:', -1), '\\n', 1)
                ELSE NULL 
            END as available_sizes
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id ";
    
    $where_conditions = [];
    $params = [];
    
    // Status filter
    if ($status_filter === 'pending') {
        $where_conditions[] = "p.status = 'pending'";
    } elseif ($status_filter === 'approved') {
        $where_conditions[] = "p.status = 'approved'";
    } elseif ($status_filter === 'rejected') {
        $where_conditions[] = "p.status = 'rejected'";
    }
    
    // Category filter
    if ($category_filter === 'org_shirt') {
        $where_conditions[] = "p.category = 'Organization Shirt'";
    } elseif ($category_filter === 'event_ticket') {
        $where_conditions[] = "p.category = 'Event Ticket'";
    } elseif ($category_filter === 'merch') {
        $where_conditions[] = "p.category = 'Merchandise'";
    }
    
    // Search filter
    if (!empty($search_query)) {
        $where_conditions[] = "(p.name LIKE ? OR s.seller_name LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Build WHERE clause
    if (!empty($where_conditions)) {
        $sql .= "WHERE " . implode(" AND ", $where_conditions) . " ";
    }
    
    $sql .= "ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
    $error = "Error fetching products: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Dashboard</title>
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
            padding: 32px 40px;
            flex: 1;
            width: calc(100% - 220px);
            background: #f8f9fa;
        }

        .dashboard-header {
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            flex-direction: column;
            gap: 16px;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            min-width: 300px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .search-wrapper:focus-within {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .status-dropdown {
            padding: 12px 16px;
            border: none;
            border-right: 2px solid #e9ecef;
            font-size: 14px;
            font-weight: 500;
            color: #4a5568;
            background: transparent;
            cursor: pointer;
            outline: none;
            min-width: 180px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 36px;
            transition: all 0.3s ease;
            position: relative;
        }

        .category-dropdown:hover,
        .status-dropdown:hover {
            color: #667eea;
            background-color: #f8fafc;
            transform: translateY(-1px);
        }

        .category-dropdown:focus,
        .status-dropdown:focus {
            color: #667eea;
            background-color: #f8fafc;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .category-dropdown,
        .status-dropdown {
            padding: 12px 20px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            color: #4a5568;
            background: #f8fafc;
            cursor: pointer;
            outline: none;
            min-width: 180px;
            border-radius: 8px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .category-dropdown:hover,
        .status-dropdown:hover {
            background-color: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .category-dropdown:focus,
        .status-dropdown:focus {
            background-color: #f1f5f9;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            border-color: #4f46e5;
        }

        /* Add a separator between dropdowns */
        .category-dropdown {
            border-right: 2px solid #e2e8f0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .status-dropdown {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .status-dropdown option {
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            background: white;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .category-dropdown option:hover,
        .status-dropdown option:hover {
            background-color: #f8fafc;
            color: #667eea;
        }

        /* Animation for the dropdown arrow */
        .category-dropdown::after,
        .status-dropdown::after {
            content: '';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 10px;
            height: 10px;
            border-right: 2px solid #4a5568;
            border-bottom: 2px solid #4a5568;
            transform-origin: center;
            transition: transform 0.3s ease;
            transform: translateY(-50%) rotate(45deg);
        }

        .category-dropdown:focus::after,
        .status-dropdown:focus::after {
            transform: translateY(-25%) rotate(-135deg);
        }

        .category-dropdown:hover,
        .status-dropdown:hover {
            color: #667eea;
            background-color: #f8fafc;
        }

        .category-dropdown:focus,
        .status-dropdown:focus {
            color: #667eea;
            border-right-color: #667eea;
            background-color: #f8fafc;
        }

        /* Style the dropdown options */
        .category-dropdown option,
        .status-dropdown option {
            padding: 12px;
            font-weight: 500;
            background-color: white;
            color: #4a5568;
        }

        /* Custom select arrow */
        .search-wrapper select {
            position: relative;
            z-index: 1;
        }

        /* Add icons to status dropdown */
        .status-dropdown option[value="all"]::before {
            content: "\\f00a";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 8px;
            color: #4a5568;
        }

        .status-dropdown option[value="pending"]::before {
            content: "\\f017";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 8px;
            color: #d97706;
        }

        .status-dropdown option[value="approved"]::before {
            content: "\\f00c";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 8px;
            color: #059669;
        }

        .status-dropdown option[value="rejected"]::before {
            content: "\\f00d";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 8px;
            color: #dc2626;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: none;
            font-size: 14px;
            background: transparent;
            color: #1a202c;
            outline: none;
        }

        .search-input::placeholder {
            color: #94a3b8;
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

        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            text-decoration: none;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.15);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #764ba2;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .filters-section {
            background: white;
            padding: 24px 28px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }

        .filters-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .search-container {
            display: flex;
            align-items: center;
            flex: 0 0 auto;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            width: 550px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .search-wrapper:focus-within {
            border-color: #007bff;
            background: white;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }

        .category-dropdown {
            border: none;
            background: none;
            padding: 14px 18px;
            border-right: 2px solid #e9ecef;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            cursor: pointer;
            outline: none;
            min-width: 140px;
            transition: color 0.3s;
        }

        .category-dropdown:hover {
            color: #007bff;
        }

        .search-bar {
            flex: 1;
            padding: 14px 18px;
            border: none;
            font-size: 15px;
            outline: none;
            background: transparent;
            color: #495057;
        }

        .search-bar::placeholder {
            color: #6c757d;
            font-style: italic;
        }
        
        .search-bar:focus {
            border-color: #007bff;
        }
        
        .filter-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
        }
        
        .category-filters {
            display: none;
        }
        
        .filter-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            text-decoration: none;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-color: #007bff;
            color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.15);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-color: #0056b3;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        
        .filter-btn i {
            font-size: 12px;
        }
        
        .products-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-top: 24px;
        }
        
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .select-all-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #475569;
            user-select: none;
        }
        
        .select-all-label input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .delete-selected-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            margin-left: auto;
        }
        
        .delete-selected-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .product-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #1a202c;
            border-bottom: 2px solid #e2e8f0;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .products-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
            font-size: 13px;
            vertical-align: middle;
        }        .products-table tr:hover {
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            transition: transform 0.2s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
            border-color: #4f46e5;
        }
        
        .pdf-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: #fff5f5;
            color: #dc3545;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .pdf-link:hover {
            background: #f8d7da;
            border-color: #dc3545;
            color: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
        
        .pdf-link i {
            font-size: 14px;
        }
        
        .price {
            font-weight: 600;
            color: #333;
        }
        
        .stock {
            font-weight: 500;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .action-btn {
            padding: 5px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }
        
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        
        .approve-btn:hover {
            background-color: #218838;
        }
        
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .reject-btn:hover {
            background-color: #c82333;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }
        
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 18px;
        }
        
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #F3F4F6;
            color: #374151;
            border: none;
        }
        
        .status-badge.approved {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-badge.pending {
            background-color: #F3F4F6;
            color: #374151;
        }
        
        .status-badge.rejected {
            background-color: #fef2f2;
            color: #991b1b;
        }

        .status-badge i {
            font-size: 10px;
        }        .no-actions {
            color: #999;
            font-size: 14px;
        }
        
        .windows-activation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #ccc;
            font-size: 12px;
        }
        
        /* Rejection Modal Styles */
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
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 80vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .form-textarea:focus {
            border-color: #007bff;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }

        .reason-btn {
            padding: 8px 10px;
            background-color: #f8f9fa;
            color: #495057;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            white-space: normal;
            line-height: 1.4;
        }

        .reason-btn:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #212529;
        }

        .reason-btn.selected {
            background-color: #dc3545;
            color: white;
            border-color: #c82333;
        }

        .reason-btn.selected:hover {
            background-color: #c82333;
            border-color: #a81f2e;
        }

        /* Image Viewer Modal */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            overflow: auto;
        }

        .image-modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 1200px;
            max-height: 90vh;
            object-fit: contain;
            margin-top: 5vh;
        }

        .image-modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 2001;
        }

        .image-modal-close:hover {
            color: #bbb;
        }

        .image-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .image-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: border-color 0.3s;
        }

        .image-thumbnail:hover {
            border-color: #007bff;
        }

        .image-thumbnail.active {
            border-color: #007bff;
        }

        .image-counter {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 14px;
            z-index: 2001;
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

            .page-header {
                margin-bottom: 20px;
            }

            .page-title {
                font-size: 24px;
            }

            .filters-section {
                padding: 15px;
            }

            .filters-row {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .search-wrapper {
                width: 100%;
            }

            .category-dropdown {
                min-width: 100px;
                padding: 12px 14px;
                font-size: 13px;
            }

            .filter-buttons {
                justify-content: flex-start;
                width: 100%;
                flex-wrap: wrap;
            }

            .filter-btn {
                padding: 8px 14px;
                font-size: 12px;
            }

            .products-container {
                overflow-x: auto;
            }

            table {
                min-width: 700px;
            }

            .actions {
                flex-direction: column;
                gap: 8px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 10px 10px;
            }

            .page-title {
                font-size: 20px;
            }

            .filters-section {
                padding: 12px;
            }

            .search-wrapper {
                flex-direction: column;
            }

            .category-dropdown {
                width: 100%;
                border-right: none;
                border-bottom: 2px solid #e9ecef;
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
        <div class="dashboard-header">
            <div>
                <h1>Product Management</h1>
                <div class="dashboard-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <form method="GET" style="display: flex; gap: 16px;">
                    <div class="search-wrapper">
                        <select name="category" class="category-dropdown" onchange="this.form.submit()">
                            <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <option value="org_shirt" <?php echo $category_filter === 'org_shirt' ? 'selected' : ''; ?>>Organization Shirt</option>
                            <option value="event_ticket" <?php echo $category_filter === 'event_ticket' ? 'selected' : ''; ?>>Event Ticket</option>
                            <option value="merch" <?php echo $category_filter === 'merch' ? 'selected' : ''; ?>>Merchandise</option>
                        </select>
                        <select name="status" class="status-dropdown" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Products</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search products..."
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="products-container">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <?php 
                    $message = "No products found.";
                    
                    if (!empty($search_query)) {
                        $message = "No products found matching '" . htmlspecialchars($search_query) . "'.";
                    }
                    
                    if ($category_filter !== 'all') {
                        $category_name = '';
                        switch($category_filter) {
                            case 'org_shirt':
                                $category_name = 'Org Shirt';
                                break;
                            case 'event_ticket':
                                $category_name = 'Event Ticket';
                                break;
                            case 'merch':
                                $category_name = 'Merch';
                                break;
                        }
                        if ($category_name) {
                            $message = "No " . $category_name . " products found.";
                            if (!empty($search_query)) {
                                $message = "No " . $category_name . " products found matching '" . htmlspecialchars($search_query) . "'.";
                            }
                        }
                    }
                    
                    if ($status_filter !== 'all') {
                        $status_name = ucfirst($status_filter);
                        if ($status_filter === 'pending') {
                            $status_name = 'In Progress';
                        }
                        $message = "No " . $status_name . " products found.";
                        if (!empty($search_query)) {
                            $message = "No " . $status_name . " products found matching '" . htmlspecialchars($search_query) . "'.";
                        }
                    }
                    
                    echo $message;
                    ?>
                </div>
            <?php else: ?>
                <div class="bulk-actions">
                    <label class="select-all-label">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        <span>Select All</span>
                    </label>
                    <button class="delete-selected-btn" onclick="deleteSelected()" id="deleteSelectedBtn" style="display: none;">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"></th>
                            <th>Organization</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Sizes</th>
                            <th>Images</th>
                            <th>PDF</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="product-row" data-product-id="<?php echo $product['id']; ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" onchange="updateSelectAllState()">
                                </td>
                                <td><?php echo htmlspecialchars($product['organization'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td class="price">₱<?php echo number_format($product['price'], 2); ?></td>
                                <td class="stock"><?php echo htmlspecialchars($product['stock']); ?></td>
                                <td>
                                    <?php 
                                    if (in_array($product['category'], ['Organization Shirt', 'Merchandise']) && !empty($product['available_sizes'])) {
                                        $sizes = array_map('trim', explode(',', $product['available_sizes']));
                                        echo htmlspecialchars(implode(', ', $sizes));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $hasMultipleImages = false;
                                    $firstImage = '';
                                    
                                    if (!empty($product['image_path'])) {
                                        // Check if it's multiple images (comma-separated)
                                        $imagePaths = array_filter(array_map('trim', explode(',', $product['image_path'])));
                                        $hasMultipleImages = count($imagePaths) > 1;
                                        $firstImage = $imagePaths[0] ?? '';
                                    }
                                    
                                    if (!empty($firstImage) && file_exists($firstImage)): ?>
                                        <div style="position: relative; display: inline-block; cursor: pointer;" 
                                             onclick="openImageModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['image_path']); ?>', '<?php echo htmlspecialchars($product['name']); ?>')">
                                            <img src="<?php echo htmlspecialchars($firstImage); ?>" 
                                                 alt="Product Image" class="product-image">
                                            <?php if ($hasMultipleImages): ?>
                                                <div style="position: absolute; top: -5px; right: -5px; background: #007bff; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                                    <?php echo count($imagePaths); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($product['pdf_path']) && file_exists($product['pdf_path'])): ?>
                                        <a href="download-pdf.php?product_id=<?php echo $product['id']; ?>&token=<?php echo urlencode(base64_encode($product['id'] . '|' . time())); ?>" 
                                           class="pdf-link" 
                                           title="Download PDF">
                                            <i class="fas fa-file-pdf" style="color: #dc3545;"></i> PDF
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['status'] === 'pending'): ?>
                                        <span class="status-badge pending">
                                            <i class="fas fa-clock"></i> In Progress
                                        </span>
                                    <?php elseif ($product['status'] === 'approved'): ?>
                                        <span class="status-badge approved">
                                            <i class="fas fa-check-circle"></i> Approved
                                        </span>
                                    <?php elseif ($product['status'] === 'rejected'): ?>
                                        <span class="status-badge rejected">
                                            <i class="fas fa-times-circle"></i> Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <?php if ($product['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="action-btn approve-btn" title="Approve" onclick="return confirm('Approve this product?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <button type="button" class="action-btn reject-btn" title="Reject" onclick="openRejectModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 12px;">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

       
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <div class="image-counter" id="imageCounter">1 / 1</div>
        <img class="image-modal-content" id="modalImage">
        <div class="image-gallery" id="imageGallery"></div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Reject Product</h3>
                <span class="close" onclick="closeRejectModal()">&times;</span>
            </div>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="product_id" id="rejectProductId">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label class="form-label">Select Reason(s) (or type custom reason below)</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                        <button type="button" class="reason-btn" data-reason="Poor image quality or unclear product details">
                            Poor Image Quality
                        </button>
                        <button type="button" class="reason-btn" data-reason="Incomplete product information or description">
                            Incomplete Information
                        </button>
                        <button type="button" class="reason-btn" data-reason="Inappropriate product content or description">
                            Inappropriate Content
                        </button>
                        <button type="button" class="reason-btn" data-reason="Price seems unreasonable or inconsistent">
                            Unreasonable Price
                        </button>
                        <button type="button" class="reason-btn" data-reason="Product does not meet organizational standards">
                            Non-Standard Product
                        </button>
                        <button type="button" class="reason-btn" data-reason="Duplicate product listing">
                            Duplicate Listing
                        </button>
                        <button type="button" class="reason-btn" data-reason="Product specification is not clear">
                            Unclear Specifications
                        </button>
                        <button type="button" class="reason-btn" data-reason="Other">
                            Others
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">
                        Rejection Reason <span style="color: red;">*</span>
                    </label>
                    <textarea 
                        id="rejection_reason" 
                        name="rejection_reason" 
                        class="form-textarea" 
                        placeholder="Selected reasons will appear here. You can edit or add custom text..."
                        required
                        style="min-height: 120px;"
                    ></textarea>
                    <small style="color: #666; margin-top: 8px; display: block;">
                        Click reason buttons to add them (multiple selections allowed). Edit the text as needed.
                    </small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Reject Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentImageIndex = 0;
        let currentImages = [];

        function openImageModal(productId, imagePathString, productName) {
            // Parse image paths
            currentImages = imagePathString ? imagePathString.split(',').map(img => img.trim()).filter(img => img) : [];
            
            if (currentImages.length === 0) {
                alert('No images available for this product.');
                return;
            }

            currentImageIndex = 0;
            showImage(0);
            updateImageCounter();
            buildImageGallery();
            
            document.getElementById('imageModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showImage(index) {
            if (index >= 0 && index < currentImages.length) {
                currentImageIndex = index;
                document.getElementById('modalImage').src = currentImages[index];
                updateImageCounter();
                updateThumbnailSelection();
            }
        }

        function updateImageCounter() {
            document.getElementById('imageCounter').textContent = `${currentImageIndex + 1} / ${currentImages.length}`;
        }

        function buildImageGallery() {
            const gallery = document.getElementById('imageGallery');
            gallery.innerHTML = '';

            currentImages.forEach((imagePath, index) => {
                const thumbnail = document.createElement('img');
                thumbnail.src = imagePath;
                thumbnail.className = 'image-thumbnail';
                if (index === currentImageIndex) {
                    thumbnail.classList.add('active');
                }
                thumbnail.onclick = () => showImage(index);
                gallery.appendChild(thumbnail);
            });
        }

        function updateThumbnailSelection() {
            const thumbnails = document.querySelectorAll('.image-thumbnail');
            thumbnails.forEach((thumb, index) => {
                thumb.classList.toggle('active', index === currentImageIndex);
            });
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('imageModal').style.display === 'block') {
                if (e.key === 'ArrowLeft' && currentImageIndex > 0) {
                    showImage(currentImageIndex - 1);
                } else if (e.key === 'ArrowRight' && currentImageIndex < currentImages.length - 1) {
                    showImage(currentImageIndex + 1);
                } else if (e.key === 'Escape') {
                    closeImageModal();
                }
            }
        });

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const imageModal = document.getElementById('imageModal');
            const rejectModal = document.getElementById('rejectModal');
            
            if (event.target === imageModal) {
                closeImageModal();
            } else if (event.target === rejectModal) {
                closeRejectModal();
            }
        }

        function openRejectModal(productId, productName) {
            document.getElementById('rejectProductId').value = productId;
            document.getElementById('rejection_reason').value = '';
            
            // Reset all reason button selections
            document.querySelectorAll('.reason-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        // Handle reason button clicks
        document.querySelectorAll('.reason-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const reason = this.getAttribute('data-reason');
                const reasonTextarea = document.getElementById('rejection_reason');
                const currentText = reasonTextarea.value.trim();
                
                // Toggle selection
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    
                    // Remove this reason from textarea
                    const reasons = currentText.split('\n• ').filter(r => r.trim());
                    const updatedReasons = reasons.filter(r => r !== reason);
                    reasonTextarea.value = updatedReasons.length > 0 ? '• ' + updatedReasons.join('\n• ') : '';
                } else {
                    // Add selection to clicked button
                    this.classList.add('selected');
                    
                    // Add this reason to textarea
                    if (currentText === '') {
                        reasonTextarea.value = '• ' + reason;
                    } else {
                        reasonTextarea.value = currentText + '\n• ' + reason;
                    }
                }
            });
        });
        
        // Handle form submission
        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            const reason = document.getElementById('rejection_reason').value.trim();
            if (!reason) {
                e.preventDefault();
                alert('Please select or provide a reason for rejection.');
                return;
            }

            if (!confirm('Are you sure you want to reject this product?')) {
                e.preventDefault();
            }
        });
        
        // Bulk delete functions
        function toggleSelectAll(checkbox) {
            const allCheckboxes = document.querySelectorAll('.product-checkbox');
            allCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateDeleteButtonState();
        }
        
        function updateSelectAllState() {
            const allCheckboxes = document.querySelectorAll('.product-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
            
            selectAllCheckbox.checked = checkedCount === allCheckboxes.length && allCheckboxes.length > 0;
            updateDeleteButtonState();
        }
        
        function updateDeleteButtonState() {
            const checkedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            
            if (checkedCheckboxes.length > 0) {
                deleteBtn.style.display = 'flex';
            } else {
                deleteBtn.style.display = 'none';
            }
        }
        
        function deleteSelected() {
            const checkedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
            if (checkedCheckboxes.length === 0) {
                alert('Please select at least one product to delete.');
                return;
            }
            
            const selectedIds = Array.from(checkedCheckboxes).map(cb => cb.value);
            const count = selectedIds.length;
            
            if (confirm(`Are you sure you want to delete ${count} product(s)? This action cannot be undone.`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'product-management.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_multiple';
                input.value = JSON.stringify(selectedIds);
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

    </script>
</body>
</html>