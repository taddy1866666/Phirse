<?php
session_start();
include __DIR__ . '/db/config.php';
include __DIR__ . '/includes/product-image-helper.php';

$pageTitle = 'Products';
include 'includes/header.php';

// Keep old function for compatibility but use new one
if (!function_exists('getFirstProductImage')) {
function getFirstProductImage(int $sellerId, string $orgName, string $rawCsv): string {
    $parts = array_filter(array_map('trim', explode(',', $rawCsv)));
    $filename = basename(reset($parts) ?: '');

    if (empty($filename)) {
        return "images/default-product.svg";
    }

    $orgFolder = preg_replace('/[^A-Za-z0-9]/', '', $orgName);

    // Try different upload paths
    $paths = [
        __DIR__ . "/../uploads/products/{$filename}",
        __DIR__ . "/../seller/uploads/{$orgFolder}/{$filename}",
        __DIR__ . "/../seller/uploads/{$sellerId}/{$filename}",
        __DIR__ . "/../seller/uploads/{$filename}",
        __DIR__ . "/../uploads/{$filename}",
        __DIR__ . "/uploads/{$filename}",
        __DIR__ . "/images/{$filename}",
        reset($parts) // Original path
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            // Convert absolute path to relative web path
            if (strpos($path, __DIR__) === 0) {
                $relativePath = str_replace(__DIR__, '.', $path);
                return str_replace('\\', '/', $relativePath); // Fix Windows path separators
            } else {
                return $path;
            }
        }
    }

    // Special handling for database paths that start with ../uploads/
    $originalPath = reset($parts);
    if (strpos($originalPath, '../uploads/') === 0) {
        $convertedPath = str_replace('../uploads/', '../uploads/', $originalPath);
        if (file_exists(__DIR__ . '/' . $convertedPath)) {
            return $convertedPath;
        }
    }

    return "images/default-product.svg";
}
}

// ✅ Get logged-in student's affiliations
$student_id = $_SESSION['student_id'] ?? null;
$affiliated_seller_ids = [];

if ($student_id) {
    $affiliations = fetchAll(
        "SELECT seller_id FROM student_seller_affiliations WHERE student_id = ?",
        [$student_id],
        'i'
    );
    $affiliated_seller_ids = array_column($affiliations, 'seller_id');
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'featured';

// Build search query
$where_conditions = ["(p.status = 'approved' OR p.status IS NULL)"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR COALESCE(s.organization_name, s.organization) LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total
              FROM products p
              LEFT JOIN sellers s ON p.seller_id = s.id
              {$where_clause}";
$total_result = !empty($params) ? fetchSingle($count_sql, $params, $types) : fetchSingle($count_sql);
$total_products = $total_result['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

// Get sales count for all products and identify top 3
$sales_sql = "SELECT 
    p.id,
    COALESCE(SUM(o.quantity), 0) as total_sold
FROM products p
LEFT JOIN orders o ON p.id = o.product_id AND o.status = 'completed'
GROUP BY p.id
ORDER BY total_sold DESC";

$sales_data = fetchAll($sales_sql);
$products_sold_count = array_column($sales_data, 'total_sold', 'id');

// Get the top 3 selling products (those with actual sales)
$top_sellers = array_filter($sales_data, function($item) {
    return $item['total_sold'] > 0;
});
$top_sellers = array_slice($top_sellers, 0, 3);
$top_selling_ids = array_column($top_sellers, 'id');

// Get products with sales count for sorting
if ($sort_by == 'most_sold' || $sort_by == 'top_selling') {
    $sql = "SELECT
                p.id,
                p.seller_id,
                p.name,
                p.category,
                p.price,
                p.stock,
                p.description,
                COALESCE(p.image_path, '') as images,
                p.status,
                p.created_at,
                COALESCE(s.organization_name, s.organization, 'Student Organization') as organization_name,
                COALESCE(SUM(o.quantity), 0) as total_sold
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN orders o ON p.id = o.product_id AND o.status = 'completed'
            {$where_clause}
            GROUP BY p.id, p.seller_id, p.name, p.category, p.price, p.stock, p.description, p.image_path, p.status, p.created_at, s.organization_name, s.organization";
    
    if ($sort_by == 'most_sold') {
        $sql .= " ORDER BY total_sold DESC, p.created_at DESC";
    } elseif ($sort_by == 'top_selling') {
        // Top selling: prioritize top 3, then sort by sales count
        if (!empty($top_selling_ids)) {
            $rank1 = isset($top_selling_ids[0]) ? $top_selling_ids[0] : 0;
            $rank2 = isset($top_selling_ids[1]) ? $top_selling_ids[1] : 0;
            $rank3 = isset($top_selling_ids[2]) ? $top_selling_ids[2] : 0;
            $sql .= " ORDER BY 
                CASE 
                    WHEN p.id = {$rank1} THEN 1
                    WHEN p.id = {$rank2} THEN 2
                    WHEN p.id = {$rank3} THEN 3
                    ELSE 4
                END, 
                total_sold DESC, 
                p.created_at DESC";
        } else {
            $sql .= " ORDER BY total_sold DESC, p.created_at DESC";
        }
    }
    $sql .= " LIMIT {$per_page} OFFSET {$offset}";
    $products = !empty($params) ? fetchAll($sql, $params, $types) : fetchAll($sql);
} else {
    $sql = "SELECT
                p.id,
                p.seller_id,
                p.name,
                p.category,
                p.price,
                p.stock,
                p.description,
                COALESCE(p.image_path, '') as images,
                p.status,
                p.created_at,
                COALESCE(s.organization_name, s.organization, 'Student Organization') as organization_name
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            {$where_clause}
            ORDER BY " . match($sort_by) {
                'best_selling' => "p.stock ASC, p.created_at DESC",
                'price_low' => "p.price ASC",
                'price_high' => "p.price DESC",
                'name_asc' => "p.name ASC",
                'name_desc' => "p.name DESC",
                'date_old' => "p.created_at ASC",
                'date_new' => "p.created_at DESC",
                default => "CASE WHEN p.image_path IS NOT NULL AND p.image_path != '' THEN 0 ELSE 1 END, p.created_at DESC"
            } . "
            LIMIT {$per_page} OFFSET {$offset}";

    $products = !empty($params) ? fetchAll($sql, $params, $types) : fetchAll($sql);
}

// Get categories for filter dropdown
$categories = fetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

displayFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - PHIRSE</title>
    <link rel="icon" href="N/A" type="image/png">

    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <link rel="stylesheet" href="css/product-image-carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .products-container {
            max-width: 1200px;
            margin: 90px auto 0;
            padding: 2rem;
        }

        .product-stats {
            position: absolute;
            bottom: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 2;
        }

        .sales-count {
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 12px;
            white-space: nowrap;
        }

        .best-seller-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #fff;
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            white-space: nowrap;
        }

        .best-seller-badge i {
            color: #fff;
            font-size: 13px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Filter Section - Modern Design */
        .filter-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 2rem 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: visible;
            z-index: 1;
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .filter-grid {
            display: flex;
            gap: 2rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
            min-width: 220px;
            position: relative;
            z-index: 10;
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label::before {
            content: '';
            width: 4px;
            height: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .filter-select {
            padding: 1rem 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            background: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            color: #1a1a1a;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='%23667eea' d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.25rem center;
            padding-right: 3rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 1000;
            width: 100%;
        }

        .filter-select:hover {
            background: #ffffff;
            border-color: #667eea;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 4px 16px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .filter-select option {
            padding: 0.75rem;
            font-weight: 500;
        }

        .filter-select option:checked {
            background: #667eea;
            color: white;
        }

        /* Prevent dropdown from collapsing layout */
        .filter-select:focus {
            position: relative;
        }

        @supports (-webkit-touch-callout: none) {
            /* iOS specific fixes */
            .filter-select {
                -webkit-appearance: none;
            }
        }

        @supports (not (-webkit-touch-callout: none)) {
            /* Non-iOS browsers */
            .filter-select {
                -moz-appearance: none;
            }
        }


        /* Products Grid */
        .products-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 3rem;
            margin-top: 0;
            opacity: 1 !important;
            visibility: visible !important;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .product-card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
            flex-direction: column;
            height: 100%;
            min-width: 0;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }


        .product-card.locked {
            opacity: 0.7;
        }

        .product-card.locked::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.05);
            pointer-events: none;
        }

        .product-card-link {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            height: 100%;
            flex: 1;
            min-width: 0;
            width: 100%;
        }

        .product-card-link:hover {
            text-decoration: none;
            color: inherit;
        }

        .product-image-container {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border-radius: 0;
            flex-shrink: 0;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.4s ease;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            max-width: 100%;
        }

        .product-card:hover .product-image {
            transform: scale(1.03);
        }

        .locked-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            z-index: 2;
        }

        .locked-overlay i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .locked-overlay p {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .product-info {
            padding: 1rem 0.8rem;
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
            background: #ffffff;
            min-width: 0;
            overflow: hidden;
        }

        .product-category {
            font-size: 0.7rem;
            color: #999999;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.3rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .product-organization {
            font-size: 0.75rem;
            color: #999999;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-price {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0;
            letter-spacing: 0;
        }

        .product-stock {
            font-size: 0.8rem;
            color: #28a745;
            font-weight: 500;
        }

        .product-stock.out-of-stock {
            color: #dc3545;
        }

        .product-stock.member-only {
            color: #ffc107;
        }
        
        /* New Arrival Badge */
        .new-arrival-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.4rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .no-products {
            text-align: center;
            padding: 4rem 0;
            color: #666;
        }

        .no-products i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        .no-products h3 {
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .no-products p {
            font-size: 1rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .pagination a,
        .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            min-width: 40px;
            height: 40px;
        }

        .pagination a:hover {
            background: #333;
            color: white;
            border-color: #333;
        }

        .pagination .current {
            background: #333;
            color: white;
            border-color: #333;
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: #666;
        }

        .results-count {
            font-size: 0.9rem;
        }

        /* Override any fade-in effects */
        .fade-in,
        .product-card.fade-in,
        .products-grid.fade-in,
        section.fade-in {
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            animation: none !important;
        }

        /* Ensure all products are visible immediately */
        .products-container * {
            opacity: 1 !important;
            visibility: visible !important;
        }

        @media (max-width: 1440px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 1024px) {
            .products-container {
                padding: 1.5rem;
            }

            .filter-section {
                padding: 1.75rem 1.5rem;
            }

            .filter-grid {
                gap: 1.5rem;
            }

            .filter-group {
                min-width: 180px;
            }

            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .products-container {
                padding: 1rem;
                margin-top: 80px;
            }

            .page-header {
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 2rem;
                letter-spacing: 1px;
            }

            .filter-section {
                padding: 1.75rem 1.5rem;
                border-radius: 20px;
                margin-bottom: 2rem;
                overflow: visible;
                position: relative;
                z-index: 100;
                min-height: auto;
                isolation: isolate;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            }

            .filter-section::before {
                display: none;
            }

            .filter-grid {
                gap: 1.5rem;
                position: relative;
                display: flex;
                flex-direction: column;
            }

            .filter-group {
                min-width: 100%;
                flex: 1 1 100%;
                position: relative;
                z-index: auto;
                margin-bottom: 0;
                isolation: isolate;
            }

            .filter-label {
                font-size: 0.75rem;
                pointer-events: none;
                color: #ffffff;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1.5px;
                margin-bottom: 0.5rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .filter-label::before {
                content: '';
                width: 3px;
                height: 14px;
                background: #ffffff;
                border-radius: 2px;
            }

            .filter-select {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                position: relative;
                padding: 1.1rem 1.5rem;
                font-size: 1rem;
                padding-right: 3.5rem;
                width: 100%;
                box-sizing: border-box;
                transition: all 0.3s ease;
                transform: none !important;
                will-change: auto;
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
                border: 3px solid rgba(255, 255, 255, 0.3);
                border-radius: 15px;
                background: rgba(255, 255, 255, 0.95);
                font-weight: 600;
                color: #1a1a1a;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'%3E%3Cpath fill='%23667eea' d='M10 12l-6-6h12z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 1.25rem center;
                background-size: 20px;
                cursor: pointer;
                min-height: 56px;
            }

            .filter-select:hover {
                transform: none !important;
                background: #ffffff;
                border-color: rgba(255, 255, 255, 0.5);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }

            .filter-select:focus,
            .filter-select:active {
                position: relative;
                transform: none !important;
                background: #ffffff;
                border-color: #ffffff;
                box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3), 0 8px 25px rgba(0, 0, 0, 0.2);
                outline: none;
            }

            .filter-select option {
                padding: 1rem;
                font-size: 1rem;
                font-weight: 600;
                background: #ffffff;
                color: #1a1a1a;
                min-height: 48px;
                line-height: 1.5;
            }

            .filter-select option:checked {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                font-weight: 700;
            }

            .search-section {
                padding: 1rem;
                margin-bottom: 2rem;
            }

            .search-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .search-input, .filter-select {
                width: 100%;
            }

            .search-btn {
                width: 100%;
                padding: 0.75rem 1rem;
            }

            .results-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
            }

            .results-count {
                font-size: 0.85rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
                margin-bottom: 2rem;
                margin-top: 0;
                position: relative;
                z-index: 1;
            }

            .product-card {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .product-image-container {
                aspect-ratio: 1 / 1;
            }

            .product-info {
                padding: 0.6rem 0.5rem;
            }

            .product-category {
                font-size: 0.6rem;
                margin-bottom: 0.25rem;
            }

            .product-name {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
                line-height: 1.3;
            }

            .product-organization {
                font-size: 0.7rem;
                margin-bottom: 0.4rem;
            }

            .product-organization i {
                font-size: 0.65rem;
            }

            .product-price {
                font-size: 0.85rem;
                margin-bottom: 0.25rem;
            }

            .product-stock {
                font-size: 0.65rem;
            }

            .product-card {
                border-radius: 10px;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 0.25rem;
                margin-top: 2rem;
            }

            .pagination a, .pagination span {
                padding: 0.5rem;
                font-size: 0.85rem;
                min-width: 36px;
                height: 36px;
            }

            .locked-overlay {
                padding: 0.75rem;
            }

            .locked-overlay i {
                font-size: 1.5rem;
                margin-bottom: 0.4rem;
            }

            .locked-overlay p {
                font-size: 0.8rem;
            }

            .no-products {
                padding: 3rem 1.5rem;
            }

            .no-products i {
                font-size: 3rem;
            }

            .no-products h3 {
                font-size: 1.3rem;
            }

            .no-products p {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 425px) {
            .products-container {
                padding: 0.75rem;
                margin-top: 70px;
            }

            .page-header {
                margin-bottom: 1.5rem;
            }

            .page-title {
                font-size: 1.75rem;
            }

            .filter-section {
                padding: 1.5rem 1.25rem;
                border-radius: 18px;
                overflow: visible;
                isolation: isolate;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                box-shadow: 0 8px 30px rgba(102, 126, 234, 0.35);
            }

            .filter-section::before {
                display: none;
            }

            .filter-grid {
                gap: 1.25rem;
            }

            .filter-group {
                z-index: auto;
                isolation: isolate;
            }

            .filter-label {
                font-size: 0.7rem;
                pointer-events: none;
                color: #ffffff;
                font-weight: 700;
                letter-spacing: 1.5px;
            }

            .filter-label::before {
                background: #ffffff;
            }

            .filter-select {
                padding: 1rem 1.25rem;
                font-size: 0.95rem;
                padding-right: 3.25rem;
                transform: none !important;
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
                border: 3px solid rgba(255, 255, 255, 0.3);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.95);
                min-height: 52px;
                background-size: 18px;
            }

            .filter-select:hover,
            .filter-select:focus,
            .filter-select:active {
                transform: none !important;
                background: #ffffff;
                border-color: #ffffff;
            }

            .filter-select option {
                padding: 0.9rem;
                font-size: 0.95rem;
                min-height: 44px;
            }

            .search-section {
                padding: 0.75rem;
                margin-bottom: 1.5rem;
            }

            .search-input, .filter-select, .search-btn {
                padding: 0.6rem 0.75rem;
                font-size: 0.9rem;
            }

            .results-info {
                margin-bottom: 1rem;
            }

            .results-count {
                font-size: 0.8rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
                margin-bottom: 1.5rem;
            }

            .product-card {
                max-width: 100%;
            }

            .product-image-container {
                aspect-ratio: 1 / 1;
            }

            .product-info {
                padding: 0.5rem;
            }

            .product-category {
                font-size: 0.55rem;
                margin-bottom: 0.2rem;
            }

            .product-name {
                font-size: 0.75rem;
                line-height: 1.2;
                margin-bottom: 0.2rem;
            }

            .product-organization {
                font-size: 0.65rem;
                margin-bottom: 0.3rem;
            }

            .product-organization i {
                font-size: 0.6rem;
            }

            .product-price {
                font-size: 0.8rem;
                margin-bottom: 0.2rem;
            }

            .product-stock {
                font-size: 0.6rem;
            }

            .product-stock i {
                font-size: 0.55rem;
            }

            .product-card {
                border-radius: 8px;
            }

            .pagination {
                gap: 0.2rem;
                margin-top: 1.5rem;
            }

            .pagination a, .pagination span {
                padding: 0.4rem;
                min-width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }

            .locked-overlay {
                padding: 0.5rem;
            }

            .locked-overlay i {
                font-size: 1.25rem;
            }

            .locked-overlay p {
                font-size: 0.75rem;
            }

            .no-products {
                padding: 2rem 1rem;
            }

            .no-products i {
                font-size: 2.5rem;
            }

            .no-products h3 {
                font-size: 1.1rem;
            }

            .no-products p {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 375px) {
            .products-container {
                padding: 0.6rem;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .filter-section {
                padding: 1.25rem 1rem;
                border-radius: 16px;
                overflow: visible;
                isolation: isolate;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
            }

            .filter-section::before {
                display: none;
            }

            .filter-grid {
                gap: 1.1rem;
            }

            .filter-group {
                z-index: auto;
                isolation: isolate;
            }

            .filter-label {
                font-size: 0.65rem;
                pointer-events: none;
                color: #ffffff;
                font-weight: 700;
                letter-spacing: 1.5px;
            }

            .filter-label::before {
            .filter-select:focus,
            .filter-select:active {
                transform: none !important;
            }

            .search-section {
                padding: 0.6rem;
            }

            .search-input, .filter-select, .search-btn {
                padding: 0.55rem 0.7rem;
                font-size: 0.875rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.4rem;
            }

            .product-info {
                padding: 0.45rem;
            }

            .product-category {
                font-size: 0.52rem;
            }

            .product-name {
                font-size: 0.72rem;
            }

            .product-organization {
                font-size: 0.62rem;
            }

            .product-price {
                font-size: 0.78rem;
            }

            .product-stock {
                font-size: 0.58rem;
            }

            .pagination a, .pagination span {
                padding: 0.35rem;
                min-width: 30px;
                height: 30px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 320px) {
            .products-container {
                padding: 0.5rem;
            }

            .page-title {
                font-size: 1.4rem;
            }


            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.3rem;
            }

            .product-info {
                padding: 0.4rem;
            }

            .product-category {
                font-size: 0.5rem;
            }

            .product-name {
                font-size: 0.7rem;
            }

            .product-organization {
                font-size: 0.6rem;
            }

            .product-price {
                font-size: 0.75rem;
            }

            .product-stock {
                font-size: 0.55rem;
            }

            .pagination a, .pagination span {
                padding: 0.3rem;
                min-width: 28px;
                height: 28px;
                font-size: 0.7rem;
            }

            .product-stats {
                bottom: 8px;
                right: 8px;
                gap: 4px;
            }

            .sales-count, 
            .best-seller-badge {
                padding: 4px 8px;
                font-size: 11px;
            }

            .best-seller-badge i {
                font-size: 12px;
            }
        }

        /* Desktop styles */
        @media (min-width: 769px) {
            .product-stats {
                bottom: 10px;
                right: 10px;
                gap: 6px;
            }

            .sales-count, 
            .best-seller-badge {
                padding: 6px 10px;
                font-size: 12px;
            }

            .best-seller-badge i {
                font-size: 13px;
            }
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .product-stats {
                bottom: 8px;
                right: 8px;
                gap: 4px;
            }

            .sales-count, 
            .best-seller-badge {
                padding: 3px 6px;
                font-size: 10px;
            }

            .best-seller-badge i {
                font-size: 10px;
            }
        }

        /* Smaller mobile devices */
        @media (max-width: 375px) {
            .product-stats {
                bottom: 6px;
                right: 6px;
                gap: 3px;
            }

            .sales-count, 
            .best-seller-badge {
                padding: 2px 5px;
                font-size: 9px;
            }

            .best-seller-badge i {
                font-size: 9px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include 'nav-bar-transparent.php'; ?>

    <main class="products-container">
        <!-- Page Header -->
        <header class="page-header">
            <h1 class="page-title">Products</h1>
        </header>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="products.php">
                <div class="filter-grid">
                    <!-- Sort By -->
                    <div class="filter-group">
                        <label class="filter-label" for="sort_by">Sort By</label>
                        <select name="sort_by" id="sort_by" class="filter-select" onchange="this.form.submit()">
                            <option value="featured" <?= $sort_by == 'featured' ? 'selected' : '' ?>>⭐ Featured</option>
                            <option value="most_sold" <?= $sort_by == 'most_sold' ? 'selected' : '' ?>>🔥 Most Sold</option>
                            <option value="top_selling" <?= $sort_by == 'top_selling' ? 'selected' : '' ?>>👑 Top Selling Products</option>
                            <option value="best_selling" <?= $sort_by == 'best_selling' ? 'selected' : '' ?>>📊 Best Selling</option>
                            <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>🔤 Alphabetically, A-Z</option>
                            <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>🔤 Alphabetically, Z-A</option>
                            <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>💰 Price, low to high</option>
                            <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>💰 Price, high to low</option>
                            <option value="date_new" <?= $sort_by == 'date_new' ? 'selected' : '' ?>>🆕 Newest First</option>
                            <option value="date_old" <?= $sort_by == 'date_old' ? 'selected' : '' ?>>📅 Oldest First</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label class="filter-label" for="category">Category</label>
                        <select name="category" id="category" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['category']) ?>" 
                                        <?= $category_filter == $cat['category'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
            </form>
        </div>

        <!-- Mobile-friendly Filter (visible on small screens) -->
        <div class="mobile-filter-wrapper">
            <button type="button" class="mobile-filter-toggle" aria-expanded="false">
                <i class="fas fa-sliders-h"></i> Filters
            </button>

            <div class="mobile-filter-panel" aria-hidden="true">
                <form id="mobileFilterForm" method="GET" action="products.php">
                    <div class="mobile-filter-group">
                        <label for="m_sort_by">Sort By</label>
                        <select name="sort_by" id="m_sort_by" class="filter-select">
                            <option value="featured" <?= $sort_by == 'featured' ? 'selected' : '' ?>>⭐ Featured</option>
                            <option value="most_sold" <?= $sort_by == 'most_sold' ? 'selected' : '' ?>>🔥 Most Sold</option>
                            <option value="top_selling" <?= $sort_by == 'top_selling' ? 'selected' : '' ?>>👑 Top Selling Products</option>
                            <option value="best_selling" <?= $sort_by == 'best_selling' ? 'selected' : '' ?>>📊 Best Selling</option>
                            <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>🔤 Alphabetically, A-Z</option>
                            <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>🔤 Alphabetically, Z-A</option>
                            <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>💰 Price, low to high</option>
                            <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>💰 Price, high to low</option>
                            <option value="date_new" <?= $sort_by == 'date_new' ? 'selected' : '' ?>>🆕 Newest First</option>
                            <option value="date_old" <?= $sort_by == 'date_old' ? 'selected' : '' ?>>📅 Oldest First</option>
                        </select>
                    </div>

                    <div class="mobile-filter-group">
                        <label for="m_category">Category</label>
                        <select name="category" id="m_category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category_filter == $cat['category'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>

                    <div class="mobile-filter-actions">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <button type="button" class="btn btn-secondary" id="mobileFilterClose">Close</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
        /* Mobile filter styles */
        .mobile-filter-wrapper { display: none; }

        @media (max-width: 768px) {
            /* hide desktop filter on mobile to avoid duplication */
            .filter-section { display: none; }

            .mobile-filter-wrapper { display: block; margin: 1rem 0; }
            .mobile-filter-toggle {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                padding: 6px 10px;
                background: #ffffff;
                border: 1px solid #eaeaea;
                border-radius: 6px;
                font-weight: normal;
                font-size: 0.85rem;
                color: #333;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }

            .mobile-filter-panel {
                position: absolute;
                display: none;
                background: #ffffff;
                padding: 6px;
                margin-top: 4px;
                border-radius: 6px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                font-size: 0.85rem;
                width: calc(100% - 24px);
                z-index: 100;
            }

            .mobile-filter-panel[aria-hidden="false"] {
                display: block;
            }

            .mobile-filter-group {
                display: flex;
                flex-direction: column;
                gap: 4px;
                margin-bottom: 6px;
                padding: 0 2px;
            }

            .mobile-filter-group label { 
                font-weight: normal;
                color: #666;
                font-size: 0.8rem;
                margin-bottom: -1px;
            }

            .mobile-filter-group select {
                padding: 4px 6px;
                border-radius: 4px;
                border: 1px solid #eaeaea;
                font-size: 0.8rem;
                background: #fafafa;
            }

            .mobile-filter-actions {
                display: flex;
                gap: 4px;
                justify-content: flex-end;
                padding: 2px;
            }

            .mobile-filter-actions .btn {
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: normal;
                font-size: 0.8rem;
                border: none;
                cursor: pointer;
            }

            .mobile-filter-actions .btn-primary { 
                background: #667eea;
                color: #fff;
                flex: 0 0 auto;
                min-width: 60px;
            }
            
            .mobile-filter-actions .btn-secondary { 
                background: #f3f4f6;
                color: #666;
                flex: 0 0 auto;
                min-width: 60px;
            }

            .mobile-filter-actions .btn-primary { background: #667eea; color: #fff; }
            .mobile-filter-actions .btn-secondary { background: #f3f4f6; color: #333; }
        }
        </style>

        <!-- Results Info -->
        <div class="results-info">
            <span class="results-count">
                Showing <?= count($products) ?> of <?= $total_products ?> products
                <?php if (!empty($search)): ?>
                    for "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
            </span>
        </div>

        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
        <section class="products-grid">
            <?php foreach ($products as $product):
                // Get all images for carousel
                $allImages = getAllProductImages(
                    (int)$product['seller_id'],
                    $product['organization_name'],
                    $product['images']
                );
                $imagePath = $allImages[0];
                $imagesData = htmlspecialchars(implode(',', $allImages));
                
                // Check if this is an organization shirt
                $is_org_shirt = (stripos($product['category'], 'shirt') !== false || 
                                 stripos($product['name'], 'shirt') !== false ||
                                 stripos($product['category'], 'uniform') !== false);
                
                // Check if user is affiliated with this product's seller
                $is_affiliated = in_array($product['seller_id'], $affiliated_seller_ids);
                
                // Determine if product should be locked
                $is_locked = $is_org_shirt && (!$student_id || !$is_affiliated);
                
                // Check if product is newly added (within last 90 days for testing)
                $is_new = (strtotime($product['created_at']) > strtotime('-90 days'));
            ?>
                <article class="product-card <?= $is_locked ? 'locked' : '' ?>">
                    <?php if ($is_new && !$is_locked): ?>
                        <span class="new-arrival-badge">New!</span>
                    <?php endif; ?>
                    <?php if ($is_locked): ?>
                        <div style="position: relative;">
                            <div class="product-image-container">
                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="product-image" 
                                     data-images="<?= $imagesData ?>"
                                     loading="lazy">
                                <div class="locked-overlay">
                                    <i class="fas fa-lock"></i>
                                    <p>Members Only</p>
                                </div>
                            </div>

                            <div class="product-info">
                                <?php if (!empty($product['category'])): ?>
                                    <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                                <?php endif; ?>

                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>

                                <div class="product-organization">
                                    <i class="fas fa-users"></i>
                                    <?= htmlspecialchars($product['organization_name']) ?>
                                </div>

                                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>

                                <div class="product-stock member-only">
                                    <i class="fas fa-lock"></i> Member Access Required
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="product-card-link">
                            <div class="product-image-container">
                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="product-image"
                                     data-images="<?= $imagesData ?>"
                                     loading="lazy">
                                <div class="product-stats">
                                    <?php if (isset($products_sold_count[$product['id']])): ?>
                                        <div class="sales-count"><?= $products_sold_count[$product['id']] ?> sold</div>
                                    <?php endif; ?>
                                    <?php
                                    $rank = array_search($product['id'], $top_selling_ids);
                                    if ($rank !== false): 
                                        $rank_labels = ['#1', '#2', '#3'];
                                        echo '<div class="best-seller-badge"><i class="fas fa-crown"></i> ' . $rank_labels[$rank] . '</div>';
                                    endif;
                                    ?>
                                </div>
                            </div>

                            <div class="product-info">
                                <?php if (!empty($product['category'])): ?>
                                    <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                                <?php endif; ?>

                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>

                                <div class="product-organization">
                                    <i class="fas fa-users"></i>
                                    <?= htmlspecialchars($product['organization_name']) ?>
                                </div>

                                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>

                                <div class="product-stock <?= $product['stock'] <= 0 ? 'out-of-stock' : '' ?>">
                                    <?php if ($product['stock'] > 0): ?>
                                        <i class="fas fa-box"></i> <?= $product['stock'] ?> in stock
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Out of stock
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous page">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next page">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <section class="no-products">
            <i class="fas fa-shopping-bag"></i>
            <h3>No Products Found</h3>
            <p>Try adjusting your search criteria or browse our featured products.</p>
        </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <p>&copy; 2025 PHIRSE. All rights reserved.</p>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="organizations.php">Organizations</a>
                <a href="products.php">Products</a>
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php">My Account</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/includes/mobile-bottom-nav.php'; ?>

    <!-- Scripts -->
    <script src="js/script.js"></script>
    <script src="js/product-image-carousel.js"></script>

    <script>
        // Additional search functionality for products page
        function performSearch() {
            const searchInput = document.getElementById('search-input');
            const query = searchInput.value.trim();

            if (query) {
                window.location.href = `products.php?search=${encodeURIComponent(query)}`;
            }
        }

        // Mobile filter toggle behavior
        (function() {
            try {
                const toggle = document.querySelector('.mobile-filter-toggle');
                const panel = document.querySelector('.mobile-filter-panel');
                const closeBtn = document.getElementById('mobileFilterClose');
                const mobileForm = document.getElementById('mobileFilterForm');
                const mSort = document.getElementById('m_sort_by');
                const mCat = document.getElementById('m_category');

                if (!toggle || !panel) return;

                function openPanel() {
                    toggle.setAttribute('aria-expanded', 'true');
                    panel.setAttribute('aria-hidden', 'false');
                }

                function closePanel() {
                    toggle.setAttribute('aria-expanded', 'false');
                    panel.setAttribute('aria-hidden', 'true');
                }

                toggle.addEventListener('click', function() {
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    if (expanded) closePanel(); else openPanel();
                });

                if (closeBtn) closeBtn.addEventListener('click', closePanel);

                // Submit form when selects change for quick filtering
                if (mSort) mSort.addEventListener('change', function() { mobileForm.submit(); });
                if (mCat) mCat.addEventListener('change', function() { mobileForm.submit(); });

                // Close when clicking outside
                document.addEventListener('click', function(e) {
                    if (!panel.contains(e.target) && !toggle.contains(e.target)) {
                        closePanel();
                    }
                });
            } catch (e) {
                // Fail silently
                console.error('Mobile filter init error', e);
            }
        })();
    </script>
</body>
</html>
