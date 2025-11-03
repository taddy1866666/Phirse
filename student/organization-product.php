<?php
session_start();
include __DIR__ . '/db/config.php';
include __DIR__ . '/includes/product-image-helper.php';

$pageTitle = 'Organization Products';
include 'includes/header.php';

/**
 * ✅ Helper: Get the first valid product image path
 */
if (!function_exists('getFirstProductImage')) {
function getFirstProductImage(int $sellerId, string $orgName, string $rawCsv): string {
    $parts = array_filter(array_map('trim', explode(',', $rawCsv)));
    $filename = basename(reset($parts) ?: '');

    if (empty($filename)) {
        return "images/default-product.svg";
    }

    $orgFolder = preg_replace('/[^A-Za-z0-9]/', '', $orgName);

    $paths = [
        __DIR__ . "/../uploads/products/{$filename}",
        __DIR__ . "/../seller/uploads/{$orgFolder}/{$filename}",
        __DIR__ . "/../seller/uploads/{$sellerId}/{$filename}",
        __DIR__ . "/../seller/uploads/{$filename}",
        __DIR__ . "/../uploads/{$filename}",
        __DIR__ . "/uploads/{$filename}",
        __DIR__ . "/images/{$filename}",
        reset($parts)
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            if (strpos($path, __DIR__) === 0) {
                $relativePath = str_replace(__DIR__, '.', $path);
                return str_replace('\\', '/', $relativePath);
            }
            return $path;
        }
    }

    return "images/default-product.svg";
}
}

// ✅ Get organization ID from URL
$org_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($org_id <= 0) {
    header('Location: organizations.php');
    exit;
}

// ✅ Get organization details
$org_data = fetchSingle(
    "SELECT id, seller_name, organization, organization_name, logo_path, description, status 
     FROM sellers WHERE id = ? LIMIT 1",
    [$org_id],
    'i'
);

if (!$org_data) {
    $_SESSION['error'] = "Organization not found";
    header('Location: organizations.php');
    exit;
}

$org_name = $org_data['organization_name'] ?: $org_data['organization'];
$org_logo = $org_data['logo_path'] ?? '';

// ✅ Check if logged-in user is affiliated with this organization
$is_affiliated = false;
$student_id = $_SESSION['student_id'] ?? null;

if ($student_id) {
    $affiliation_check = fetchSingle(
        "SELECT id FROM student_seller_affiliations 
         WHERE student_id = ? AND seller_id = ? LIMIT 1",
        [$student_id, $org_id],
        'ii'
    );
    $is_affiliated = !empty($affiliation_check);
}

// 🔍 Filter parameters
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'featured';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Get sales count for all products
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

// Build WHERE conditions
$where_conditions = [
    "p.status = 'approved'",
    "p.seller_id = ?"
];
$params = [$org_id];
$types = 'i';

if (!empty($category_filter)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

/**
 * 🔢 Count total products
 */
$count_sql = "SELECT COUNT(*) as total FROM products p {$where_clause}";
$total_result = fetchSingle($count_sql, $params, $types);
$total_products = $total_result['total'] ?? 0;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

/**
 * 📦 Get products for this organization
 */
$sql = "
    SELECT
        p.id,
        p.seller_id,
        p.name,
        p.category,
        p.price,
        p.stock,
        p.description,
        COALESCE(p.image_path, '') as images,
        s.organization as organization_name,
        p.created_at
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
    LIMIT {$per_page} OFFSET {$offset}
";
$products = fetchAll($sql, $params, $types);

/**
 * 📚 Get categories for this organization
 */
$cat_sql = "
    SELECT DISTINCT p.category 
    FROM products p
    WHERE p.status = 'approved' 
    AND p.seller_id = ?
    AND p.category IS NOT NULL 
    AND p.category != ''
    ORDER BY p.category
";
$categories = fetchAll($cat_sql, [$org_id], 'i');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($org_name) ?> - Products - PHIRSE</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <link rel="stylesheet" href="css/product-image-carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .products-container { max-width: 1200px; margin: 90px auto 0; padding: 2rem; }
        
        /* Organization Header */
        .org-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .org-logo-container {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            background: #f5f5f5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .org-logo {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .org-logo-placeholder {
            font-size: 3rem;
            color: #999;
        }
        .org-header-info {
            flex: 1;
        }
        .org-header-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .org-header-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .org-header-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .back-btn {
            background: #e9ecef;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #333;
            color: white;
            text-decoration: none;
        }
        /* Remove underline for product links and keep link color consistent */
        .product-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .product-card-link:hover {
            text-decoration: none;
            color: inherit;
        }
        /* Ensure other product-area anchors don't show underlines */
        .products-container a { text-decoration: none; color: inherit; }
        .product-count-badge {
            background: #333;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .affiliation-badge {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .no-affiliation-badge {
            background: #ffc107;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Affiliation Notice */
        .affiliation-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: start;
            gap: 1rem;
        }
        .affiliation-notice i {
            color: #ffc107;
            font-size: 1.5rem;
            margin-top: 0.25rem;
        }
        .affiliation-notice-content h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }
        .affiliation-notice-content p {
            margin: 0;
            color: #856404;
            line-height: 1.5;
        }
        
        /* Filter Section - Clean Modern Design */
        .filter-section {
            background: #ffffff;
            padding: 1.75rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
        }

        .filter-grid {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-select {
            padding: 0.85rem 1.1rem;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #fafafa;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .filter-select:hover {
            background: #ffffff;
            border-color: #d0d0d0;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Products Grid */
        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 1rem;
            width: 100%;
        }
        .product-card { 
            background: #ffffff; 
            border: none;
            border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
            overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .product-image-container {
            position: relative;
        }
        .product-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.12); 
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
  .product-image-container { 
    aspect-ratio: 1 / 1;
    width: 100%;
    position: relative;
    overflow: hidden;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0;
    flex-shrink: 0;
}

.product-image { 
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.4s ease;
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
            background: #ffffff;
            min-width: 0;
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-category { 
            font-size: 0.7rem; 
            color: #999999; 
            font-weight: 400; 
            text-transform: none; 
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

        .product-price { 
            font-size: 1rem; 
            font-weight: 600; 
            color: #1a1a1a;
            letter-spacing: 0;
        }
        .product-stock {
            font-size: 0.8rem;
            font-weight: 500;
            color: #28a745;
            margin-top: 0.5rem;
        }
        .product-stock.out-of-stock {
            color: #dc3545;
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
        
        /* Pagination */
        .pagination { 
            display: flex; 
            justify-content: center; 
            gap: 0.5rem; 
            margin-top: 2rem; 
            flex-wrap: wrap; 
        }
        .pagination a, .pagination span { 
            padding: 0.5rem 0.75rem; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            text-decoration: none; 
            color: #333; 
        }
        .pagination a:hover, .pagination .current { 
            background: #333; 
            color: white; 
            border-color: #333; 
        }
        
        /* No Products */
        .no-products { 
            text-align: center; 
            padding: 4rem 2rem; 
            background: white;
            border-radius: 12px;
            color: #666; 
        }
        .no-products i { 
            font-size: 4rem; 
            color: #ddd; 
            margin-bottom: 1rem; 
        }
        
        @media (max-width: 1440px) {
            .products-grid { 
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 1024px) {
            .products-container { padding: 1.5rem; }
            .products-grid { 
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .products-container { padding: 1rem; margin-top: 80px; }
            
            .org-header { 
                flex-direction: column; 
                text-align: center; 
                padding: 1.5rem; 
                gap: 1rem;
            }
            .org-logo-container {
                width: 100px;
                height: 100px;
            }
            .org-header-title {
                font-size: 1.5rem;
            }
            .org-header-description {
                font-size: 0.9rem;
            }
            .org-header-meta { 
                flex-direction: column; 
                width: 100%; 
                gap: 0.75rem;
            }
            .back-btn, .product-count-badge, .affiliation-badge, .no-affiliation-badge { 
                width: 100%; 
                justify-content: center;
                text-align: center;
            }
            
            .affiliation-notice {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
            }
            .affiliation-notice i {
                margin: 0;
            }
            
            .search-section {
                padding: 1rem;
            }
            .search-grid { 
                grid-template-columns: 1fr; 
                gap: 0.75rem;
            }
            .search-input, .filter-select {
                width: 100%;
            }
            .search-btn, .clear-btn {
                width: 100%;
            }
            .clear-btn {
                margin-left: 0;
                margin-top: 0.5rem;
            }
            
            .products-grid { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 0.75rem; 
            }
            .product-card {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .product-image-container {
                height: auto;
                aspect-ratio: 1 / 1;
            }
            .product-info {
                padding: 0.6rem 0.5rem;
            }
            .product-name {
                font-size: 0.8rem;
                line-height: 1.3;
                margin-bottom: 0.25rem;
            }
            .product-price {
                font-size: 0.85rem;
                margin-bottom: 0.25rem;
            }
            .product-category {
                font-size: 0.6rem;
                margin-bottom: 0.25rem;
            }
            .product-stock {
                font-size: 0.65rem;
            }
            .product-card {
                border-radius: 10px;
            }
            
            .pagination {
                gap: 0.25rem;
            }
            .pagination a, .pagination span {
                padding: 0.5rem;
                font-size: 0.85rem;
                min-width: 36px;
            }
            
            .no-products {
                padding: 3rem 1.5rem;
            }
            .no-products i {
                font-size: 3rem;
            }
            
            .locked-overlay {
                padding: 0.75rem;
            }
            .locked-overlay i {
                font-size: 1.5rem;
            }
            .locked-overlay p {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 425px) {
            .products-container {
                padding: 0.75rem;
                margin-top: 70px;
            }
            
            .org-header {
                padding: 1rem;
            }
            .org-logo-container {
                width: 80px;
                height: 80px;
            }
            .org-logo-placeholder {
                font-size: 2rem;
            }
            .org-header-title {
                font-size: 1.25rem;
            }
            .org-header-description {
                font-size: 0.85rem;
            }
            
            .products-grid { 
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }
            .product-card {
                max-width: 100%;
            }
            .product-image-container {
                height: auto;
                aspect-ratio: 1 / 1;
            }
            .product-info {
                padding: 0.5rem;
            }
            .product-name {
                font-size: 0.75rem;
                line-height: 1.2;
                margin-bottom: 0.2rem;
            }
            .product-price {
                font-size: 0.8rem;
                margin-bottom: 0.2rem;
            }
            .product-category {
                font-size: 0.55rem;
                margin-bottom: 0.2rem;
            }
            .product-stock {
                font-size: 0.6rem;
            }
            .product-card {
                border-radius: 8px;
            }
            
            .search-section {
                padding: 0.75rem;
            }
            .search-input, .filter-select, .search-btn, .clear-btn {
                padding: 0.6rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .pagination a, .pagination span {
                padding: 0.4rem;
                min-width: 32px;
                font-size: 0.8rem;
            }
            
            .no-products {
                padding: 2rem 1rem;
            }
            .no-products h3 {
                font-size: 1.1rem;
            }
            .no-products p {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 375px) {
            .products-container {
                padding: 0.6rem;
            }
            .org-header {
                padding: 0.85rem;
            }
            .org-header-title {
                font-size: 1.2rem;
            }
            .org-header-description {
                font-size: 0.8rem;
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
            .product-price {
                font-size: 0.78rem;
            }
            .product-stock {
                font-size: 0.58rem;
            }
            .search-section {
                padding: 0.6rem;
            }
            .search-input, .filter-select, .search-btn, .clear-btn {
                padding: 0.55rem 0.7rem;
                font-size: 0.875rem;
            }
        }

        @media (max-width: 320px) {
            .products-container {
                padding: 0.5rem;
            }
            .org-header {
                padding: 0.75rem;
            }
            .org-logo-container {
                width: 70px;
                height: 70px;
            }
            .org-header-title {
                font-size: 1.1rem;
            }
            .org-header-description {
                font-size: 0.75rem;
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
            .product-price {
                font-size: 0.75rem;
            }
            .product-stock {
                font-size: 0.55rem;
            }
            .search-section {
                padding: 0.5rem;
            }
            .search-input, .filter-select, .search-btn, .clear-btn {
                padding: 0.5rem 0.6rem;
                font-size: 0.85rem;
            }

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
    <?php include 'nav-bar-transparent.php'; ?>

    <main class="products-container">
        <!-- Organization Header -->
        <div class="org-header">
            <div class="org-logo-container">
                <?php if (!empty($org_logo) && file_exists(__DIR__ . '/' . ltrim($org_logo, '/'))): ?>
                    <img src="<?= htmlspecialchars($org_logo) ?>" alt="<?= htmlspecialchars($org_name) ?>" class="org-logo">
                <?php else: ?>
                    <i class="fas fa-store org-logo-placeholder"></i>
                <?php endif; ?>
            </div>
            <div class="org-header-info">
                <h1 class="org-header-title"><?= htmlspecialchars($org_name) ?></h1>
                <?php if (!empty($org_data['description'])): ?>
                    <p class="org-header-description"><?= htmlspecialchars($org_data['description']) ?></p>
                <?php endif; ?>
                <div class="org-header-meta">
                    <a href="organizations.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Organizations
                    </a>
                    <span class="product-count-badge">
                        <i class="fas fa-box"></i> <?= $total_products ?> Product<?= $total_products !== 1 ? 's' : '' ?>
                    </span>
                    <?php if ($student_id): ?>
                        <?php if ($is_affiliated): ?>
                            <span class="affiliation-badge">
                                <i class="fas fa-check-circle"></i> Member
                            </span>
                        <?php else: ?>
                            <span class="no-affiliation-badge">
                                <i class="fas fa-info-circle"></i> Not a Member
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Affiliation Notice for Non-Members -->
        <?php if ($student_id && !$is_affiliated): ?>
        <div class="affiliation-notice">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="affiliation-notice-content">
                <h4>Member-Only Pre-Orders</h4>
                <p>You can view all products, but pre-ordering organization shirts is only available to affiliated members of <?= htmlspecialchars($org_name) ?>.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="organization-product.php">
                <input type="hidden" name="id" value="<?= $org_id ?>">
                <div class="filter-grid">
                    <!-- Sort By -->
                    <div class="filter-group">
                        <label class="filter-label" for="sort_by">Sort By</label>
                        <select name="sort_by" id="sort_by" class="filter-select" onchange="this.form.submit()">
                            <option value="featured" <?= $sort_by == 'featured' ? 'selected' : '' ?>>Featured</option>
                            <option value="best_selling" <?= $sort_by == 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
                            <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>Alphabetically, A-Z</option>
                            <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>Alphabetically, Z-A</option>
                            <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>Price, low to high</option>
                            <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>Price, high to low</option>
                            <option value="date_old" <?= $sort_by == 'date_old' ? 'selected' : '' ?>>Date, old to new</option>
                            <option value="date_new" <?= $sort_by == 'date_new' ? 'selected' : '' ?>>Date, new to old</option>
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
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
        <section class="products-grid">
            <?php foreach ($products as $product):
                $allImages = getAllProductImages((int)$product['seller_id'], $product['organization_name'], $product['images']);
                $imagePath = $allImages[0];
                $imagesData = htmlspecialchars(implode(',', $allImages));
                $is_org_shirt = (stripos($product['category'], 'shirt') !== false || 
                                 stripos($product['name'], 'shirt') !== false ||
                                 stripos($product['category'], 'uniform') !== false);
                $is_locked = $is_org_shirt && (!$student_id || !$is_affiliated);
                $is_new = (strtotime($product['created_at']) > strtotime('-90 days'));
                $rank = array_search($product['id'], $top_selling_ids);
                $rank_labels = ['#1', '#2', '#3'];
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
                                     onerror="this.src='images/default-product.svg'">
                                <div class="locked-overlay">
                                    <i class="fas fa-lock"></i>
                                    <p>Members Only</p>
                                </div>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($product['category'] ?? 'General') ?></div>
                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                <div class="product-stock" style="color: #ffc107;">
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
                                     onerror="this.src='images/default-product.svg'">
                                <div class="product-stats">
                                    <?php if (isset($products_sold_count[$product['id']])): ?>
                                        <div class="sales-count"><?= $products_sold_count[$product['id']] ?> sold</div>
                                    <?php endif; ?>
                                    <?php if ($rank !== false): ?>
                                        <div class="best-seller-badge"><i class="fas fa-crown"></i> <?= $rank_labels[$rank] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($product['category'] ?? 'General') ?></div>
                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                <div class="product-stock <?= $product['stock'] <= 0 ? 'out-of-stock' : '' ?>">
                                    <i class="fas fa-<?= $product['stock'] > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                                    <?= $product['stock'] > 0 ? "{$product['stock']} in stock" : "Out of stock" ?>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($total_pages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php 
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                <?php if ($start_page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <section class="no-products">
            <i class="fas fa-box-open"></i>
            <h3>No Products Found</h3>
            <p>
                <?php if (!empty($category_filter)): ?>
                    No products match your filter criteria from <?= htmlspecialchars($org_name) ?>.
                <?php else: ?>
                    <?= htmlspecialchars($org_name) ?> has no products available at the moment.
                <?php endif; ?>
            </p>
            <?php if (!empty($category_filter)): ?>
                <a href="organization-product.php?id=<?= $org_id ?>" class="filter-btn" style="display: inline-block; margin-top: 1rem; text-decoration: none;">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>

    <footer class="footer" style="margin-top: 3rem;">
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

    <script src="js/product-image-carousel.js"></script>
</body>
</html>
