<?php
// Student Portal - Main Index Page
include __DIR__ . '/db/config.php';
include __DIR__ . '/includes/product-image-helper.php';

$pageTitle = 'Home';
include 'includes/header.php';

$studentId = $_SESSION['student_id'] ?? null;
$affiliatedOrgs = [];

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

if ($studentId) {
    $sql = "SELECT seller_id FROM student_seller_affiliations WHERE student_id = ?";
    $result = fetchAll($sql, [$studentId], 'i');
    $affiliatedOrgs = array_column($result, 'seller_id');
}

$allProducts = [];

if (!empty($affiliatedOrgs)) {
    $placeholders = str_repeat('?,', count($affiliatedOrgs) - 1) . '?';
    $types = str_repeat('i', count($affiliatedOrgs));

    $query = "
        SELECT
            p.id,
            p.seller_id as organization_id,
            COALESCE(s.organization_name, s.organization) as organization_name,
            p.name,
            p.price,
            COALESCE(p.image_path, '') as images,
            p.created_at
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE (p.status = 'approved' OR p.status IS NULL)
        AND p.seller_id IN ($placeholders)
        ORDER BY
            CASE WHEN p.image_path IS NOT NULL AND p.image_path != '' THEN 0 ELSE 1 END,
            p.created_at DESC
        LIMIT 12
    ";

    $allProducts = fetchAll($query, $affiliatedOrgs, $types);
}

if (empty($allProducts)) {
    $query = "
        SELECT
            p.id,
            p.seller_id as organization_id,
            COALESCE(s.organization_name, s.organization) as organization_name,
            p.name,
            p.price,
            COALESCE(p.image_path, '') as images,
            p.stock,
            p.created_at
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE (p.status = 'approved' OR p.status IS NULL)
        ORDER BY
            CASE WHEN p.image_path IS NOT NULL AND p.image_path != '' THEN 0 ELSE 1 END,
            p.created_at DESC
        LIMIT 12
    ";

    $allProducts = fetchAll($query);
}

if (empty($allProducts)) {
    $fallback_query = "SELECT id, seller_id as organization_id, name, price, image_path as images, created_at FROM products ORDER BY created_at DESC LIMIT 12";
    $allProducts = fetchAll($fallback_query);

    if (!empty($allProducts)) {
        foreach ($allProducts as &$product) {
            if (empty($product['organization_name'])) {
                $product['organization_name'] = 'Student Organization';
            }
        }
    }
}

$stats = [
    'organizations' => 0,
    'products' => 0,
    'students' => 0,
    'support' => '24/7'
];

$orgResult = fetchSingle("SELECT COUNT(*) as total FROM sellers");
$stats['organizations'] = $orgResult['total'] ?? 0;

$productResult = fetchSingle("SELECT COUNT(*) as total FROM products WHERE status = 'approved' OR status IS NULL");
$stats['products'] = $productResult['total'] ?? 0;

$studentResult = fetchSingle("SELECT COUNT(*) as total FROM users");
$stats['students'] = $studentResult['total'] ?? 0;

$featuredOrgs = fetchAll("
    SELECT id, organization_name, organization, logo_path, description
    FROM sellers
    WHERE status = 'active' OR status IS NULL
    ORDER BY RAND()
    LIMIT 6
");

displayFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHIRSE - Student Marketplace</title>
    <link rel="icon" href="N/A" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <link rel="stylesheet" href="css/product-image-carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            color: #000000;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10rem 2rem 6rem;
            margin: 30px 2rem 0;
            position: relative;
            overflow: hidden;
            border-radius: 12px;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(0,0,0,0.03) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: 10%;
            animation: floatSlow 20s ease-in-out infinite;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0,0,0,0.02) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: 10%;
            animation: floatSlow 25s ease-in-out infinite reverse;
        }

        @keyframes floatSlow {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 2rem;
            padding-right: 2rem;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: #ffffff;
            letter-spacing: -2px;
            line-height: 1.1;
            animation: fadeInUp 1s ease-out 0.2s both;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 3rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
            animation: fadeInUp 1s ease-out 0.4s both;
            font-weight: 400;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .hero-cta {
            display: inline-flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        .btn-hero {
            padding: 1.2rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-hero::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-hero:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background: #ffffff;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(255, 255, 255, 0.3);
        }

        /* Stats Section */
        .stats-section {
            padding: 5rem 2rem;
            background: #ffffff;
        }
        
        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
        }
        
        .stat-item {
            text-align: center;
            background: #ffffff;
            padding: 3rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #000000, #666666);
            transform: scaleX(0);
            transition: transform 0.6s ease;
        }

        .stat-item:hover::before {
            transform: scaleX(1);
        }
        
        .stat-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            color: #000000;
            margin-bottom: 1rem;
            letter-spacing: -2px;
        }
        
        .stat-label {
            color: #666666;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Section Styling */
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
            z-index: 1;
        }

        .section-header-with-link {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .section-header-left {
            text-align: left;
        }

        .view-all-link {
            text-decoration: none;
            color: #667eea;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border: 2px solid #667eea;
            border-radius: 50px;
            background: rgba(102, 126, 234, 0.05);
        }

        .view-all-link:hover {
            background: #667eea;
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .view-all-link i {
            transition: transform 0.3s ease;
        }

        .view-all-link:hover i {
            transform: translateX(3px);
        }

        .section-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #000000;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #666666;
            max-width: 600px;
            margin: 0 auto;
            font-weight: 400;
        }

        /* Featured Products */
        .all-products {
            padding: 2rem 0;
            background: #ffffff;
            position: relative;
        }
        .main-section-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 2rem;
            padding-right: 2rem;
        }

        .all-products::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 300px;
            background: linear-gradient(180deg, #f8f8f8 0%, #ffffff 100%);
            z-index: 0;
        }

        .slider-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .slider-btn {
            background: #ffffff;
            border: 2px solid #000000;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: #000000;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
        }

        .slider-container:hover .slider-btn {
            opacity: 1;
            visibility: visible;
        }

        .slider-btn:hover {
            background: #000000;
            color: #ffffff;
            transform: scale(1.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .slider-btn:active {
            transform: scale(0.95);
        }

        .product-slider {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 1.5rem 0;
            flex: 1;
            scrollbar-width: none;
            -ms-overflow-style: none;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
            touch-action: pan-x;
        }

        .product-slider::-webkit-scrollbar {
            display: none;
        }

        .product-card {
            min-width: 260px;
            width: 260px;
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }

        .product-image-container {
            position: relative;
        }

        .product-card::before {
            display: none;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }


        .product-image-container {
            width: 100%;
            aspect-ratio: 1 / 1;
            position: relative;
            overflow: hidden;
            background: #f5f5f5;
            border-radius: 0;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.4s ease;
            position: relative;
            z-index: 0;
        }

        .product-card:hover .product-image {
            transform: scale(1.03);
        }

        .product-info {
            padding: 1.2rem 1rem;
            background: #ffffff;
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        .product-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #1a1a1a;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card:hover .product-info h3 {
            color: #1a1a1a;
        }

        .product-info .category {
            font-size: 0.75rem;
            color: #999999;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            margin-bottom: 0.8rem;
        }

        .product-info .price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0;
            letter-spacing: 0;
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

        .quick-view-btn {
            background: #000000;
            color: #ffffff;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            width: 100%;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: none;
        }

        /* Featured Organizations */
        .featured-orgs {
            padding: 6rem 2rem;
            background: #ffffff;
        }

        .orgs-slider-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .orgs-slider {
            display: flex;
            gap: 2rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 2rem 0;
            flex: 1;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .orgs-slider::-webkit-scrollbar {
            display: none;
        }
        
        .org-card {
            min-width: 300px;
            width: 300px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            flex-shrink: 0;
            border: 1px solid #f0f0f0;
        }
        
        .org-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            border-color: #e0e0e0;
        }
        
        .org-logo {
            height: 160px;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .org-logo::before {
            display: none;
        }
        
        .org-logo img {
            max-width: 90px;
            max-height: 90px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .org-card:hover .org-logo img {
            transform: scale(1.1);
        }

        .org-logo i {
            font-size: 2.5rem;
            color: #cccccc;
        }
        
        .org-info {
            padding: 1.5rem;
        }
        
        .org-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: #000000;
        }
        
        .org-description {
            color: #666666;
            font-size: 0.9rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* No Products Section */
        .no-products {
            padding: 5rem 2rem;
            background: #fafafa;
        }

        .no-products .container {
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .no-products i {
            font-size: 5rem;
            color: #e0e0e0;
            margin-bottom: 2rem;
        }

        .no-products h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333333;
        }

        .no-products p {
            color: #666666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        /* Footer */
        .footer {
            background: #000000;
            color: #ffffff;
            padding: 3rem 2rem;
            margin-top: 5rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }

        .footer-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ffffff;
            transition: width 0.3s ease;
        }

        .footer-links a:hover {
            color: #ffffff;
        }

        .footer-links a:hover::after {
            width: 100%;
        }

        /* View All Button */
        .view-all-container {
            text-align: center;
            margin-top: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .slider-container,
            .orgs-slider-container {
                gap: 1rem;
            }

            .slider-btn {
                width: 50px;
                height: 50px;
                font-size: 1.1rem;
            }

            .product-card {
                min-width: 280px;
                width: 280px;
                border-radius: 10px;
            }
            
            .org-card {
                min-width: 280px;
                width: 280px;
            }
            .all-products {
                padding: 2rem 1.5rem;
            }
            .main-section-inner {
                max-width: 100%;
                margin: 0;
                padding-left: 0;
                padding-right: 0;
            }
        }

        @media (max-width: 768px) {
            .hero-banner {
                padding: 7rem 1.5rem 6rem;
                margin: 15px 1.5rem 0;
                min-height: 66vh;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .section-header-with-link {
                flex-direction: row;
                gap: 1rem;
                text-align: right;
                justify-content: flex-end;
            }

            .section-header-left {
                text-align: right;
            }

            .view-all-link {
                font-size: 0.7rem;
                padding: 0.4rem 0.8rem;
            }

            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
            }

            .stat-item {
                padding: 2rem 1.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            /* Hide slider buttons on mobile */
            .slider-btn {
                display: none;
            }

            .product-slider {
                gap: 1rem;
                padding: 1rem 0;
                scroll-behavior: auto !important;
            }
            
            .orgs-slider {
                gap: 1.5rem;
                padding: 1.5rem 0;
            }

            .product-card {
                min-width: 220px;
                width: 220px;
                border-radius: 10px;
            }
            
            .org-card {
                min-width: 260px;
                width: 260px;
            }
            .all-products {
                padding: 2rem 1rem;
            }
            .main-section-inner {
                max-width: 100%;
                margin: 0;
                padding-left: 0;
                padding-right: 0;
            }
        }

        @media (max-width: 480px) {
            .hero-banner {
                padding: 7rem 1rem 5.5rem;
                margin: 10px 1rem 0;
                min-height: 64vh;
            }

            .hero-title {
                font-size: 2.5rem;
                letter-spacing: -2px;
                margin-bottom: 1rem;
            }

            .hero-subtitle {
                font-size: 0.8rem;
                line-height: 1.45;
                max-width: 100%;
                text-align: left;
                margin-left: 0;
                margin-right: 0;
                margin-bottom: 2rem;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }

            .hero-cta {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
                justify-content: flex-start;
            }

            .btn-hero {
                width: auto;
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }

            .btn-hero span {
                display: none;
            }

            .btn-hero::after {
                content: attr(data-mobile-text);
            }

            .btn-hero i {
                font-size: 0.9rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: 1.2rem;
            }

            .stat-item {
                padding: 1.8rem 1.2rem;
            }

            .stat-number {
                font-size: 2.2rem;
            }

            .stat-label {
                font-size: 0.95rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .section-subtitle {
                font-size: 1rem;
            }

            .product-card {
                min-width: calc(50% - 0.5rem);
                width: calc(50% - 0.5rem);
            }
            
            .org-card {
                min-width: 240px;
                width: 240px;
            }

            .product-image-container {
                aspect-ratio: 1 / 1;
            }

            .product-info {
                padding: 1rem 0.8rem;
            }
            
            .org-info {
                padding: 1.5rem;
            }

            .product-info h3 {
                font-size: 0.95rem;
            }
            
            .org-name {
                font-size: 1.2rem;
            }

            .product-info .price {
                font-size: 1rem;
            }

            .footer-links {
                gap: 1.5rem;
                font-size: 0.9rem;
            }

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

        @media (max-width: 360px) {
            .hero-title {
                font-size: 1.9rem;
            }
        }

        /* Scroll Reveal Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Index page specific navbar positioning - Desktop only */
        @media (min-width: 769px) {
            .navbar {
                top: 40px;
                transition: all 0.3s ease, top 0.3s ease, transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .navbar.scrolled {
                top: 0;
            }
        }
        /* Organization section wrapper for desktop only */
        @media (min-width: 769px) {
            .featured-orgs-inner {
                max-width: 1200px;
                margin: 0 auto;
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }
        @media (max-width: 1024px) {
            .featured-orgs-inner {
                max-width: 100%;
                margin: 0;
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
        @media (max-width: 768px) {
            .featured-orgs-inner {
                max-width: 100%;
                margin: 0;
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        @media (min-width: 769px) {
            .main-section-inner {
                max-width: 1200px;
                margin: 0 auto;
                padding-left: 2rem;
                padding-right: 2rem;
            }
            .slider-btn {
                display: none !important;
            }
            .product-slider {
                display: flex;
                gap: 1.5rem;
                overflow-x: auto;
                scroll-behavior: smooth;
                padding: 1.5rem 0;
                flex: 1;
                scrollbar-width: none;
                -ms-overflow-style: none;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior-x: contain;
                touch-action: pan-x;
            }
        }
    </style>
</head>

<body>
    <?php include 'nav-bar-transparent.php'; ?>

    <main>
        <!-- Hero Banner -->
        <section class="hero-banner">
            <div class="hero-content">
                <h1 class="hero-title">PLV Student Marketplace</h1>
                <p class="hero-subtitle">Discover merchandise from PLV student organizations. Support your community while expressing your pride.</p>
                <div class="hero-cta">
                    <a href="organizations.php" class="btn-hero btn-primary" data-mobile-text="Organizations">
                        <i class="fas fa-store"></i>
                        <span>Explore Organizations</span>
                    </a>
                    <a href="products.php" class="btn-hero btn-secondary" data-mobile-text="Products">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Shop Products</span>
                    </a>
                </div>
            </div>
        </section>



        <!-- Featured Products -->
        <?php if (!empty($allProducts)): ?>
        <section class="all-products fade-in">
            <div class="main-section-inner">
                <div class="section-header-with-link">
                    <div class="section-header-left">
                    </div>
                    <a href="products.php" class="view-all-link">
                        <span>View All</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="slider-container">
                    <button class="slider-btn prev" onclick="slidePrev()" aria-label="Previous products">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="product-slider" id="productSlider">
                        <?php foreach ($allProducts as $p): 
                            $allImages = getAllProductImages(
                                (int)$p['organization_id'],
                                $p['organization_name'],
                                $p['images']
                            );
                            $imagePath = $allImages[0];
                            $imagesData = htmlspecialchars(implode(',', $allImages));
                            $is_new = (strtotime($p['created_at']) > strtotime('-90 days'));
                            $rank = array_search($p['id'], $top_selling_ids);
                            $rank_labels = ['#1', '#2', '#3'];
                        ?>
                            <a href="product-details.php?id=<?= htmlspecialchars($p['id']) ?>" class="product-card">
                                <?php if ($is_new): ?>
                                    <span class="new-arrival-badge">New!</span>
                                <?php endif; ?>
                                <div class="product-image-container">
                                    <img src="<?= htmlspecialchars($imagePath) ?>" 
                                         alt="<?= htmlspecialchars($p['name']) ?>" 
                                         class="product-image" 
                                         data-images="<?= $imagesData ?>"
                                         loading="lazy">
                                    <div class="product-stats">
                                        <?php if (isset($products_sold_count[$p['id']])): ?>
                                            <div class="sales-count"><?= $products_sold_count[$p['id']] ?> sold</div>
                                        <?php endif; ?>
                                        <?php if ($rank !== false): ?>
                                            <div class="best-seller-badge"><i class="fas fa-crown"></i> <?= $rank_labels[$rank] ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                                    <p class="category product-category"><?= htmlspecialchars($p['organization_name']) ?></p>
                                    <p class="price product-price">₱<?= number_format($p['price'], 2) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-btn next" onclick="slideNext()" aria-label="Next products">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>
        <?php else: ?>
        <section class="no-products fade-in">
            <div class="container">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Products Available</h3>
                <p>No approved products available at the moment. Organizations may need to add and approve products first.</p>
                <a href="organizations.php" class="btn-hero btn-primary">
                    <i class="fas fa-store"></i>
                    <span>Explore Organizations</span>
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Featured Organizations -->
        <?php if (!empty($featuredOrgs)): ?>
        <section class="featured-orgs fade-in">
            <div class="featured-orgs-inner">
                <div class="section-header">
                    <h2 class="section-title">Featured Organizations</h2>
                    <p class="section-subtitle">Connect with PLV student organizations</p>
                </div>
                <div class="orgs-slider-container">
                    <button class="slider-btn prev" onclick="slideOrgPrev()" aria-label="Previous organizations">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="orgs-slider" id="orgsSlider">
                    <?php foreach ($featuredOrgs as $org): ?>
                        <a href="organization-product.php?id=<?= $org['id'] ?>" class="org-card">
                            <div class="org-logo">
                                <?php
                                $logo_found = false;
                                $logo_src = '';

                                if (!empty($org['logo_path'])) {
                                    $possible_paths = [
                                        $org['logo_path'],
                                        'seller/uploads/' . basename($org['logo_path']),
                                        'uploads/' . basename($org['logo_path']),
                                        '../seller/uploads/' . basename($org['logo_path']),
                                    ];

                                    foreach ($possible_paths as $path) {
                                        if (file_exists($path)) {
                                            $logo_src = $path;
                                            $logo_found = true;
                                            break;
                                        }
                                    }
                                }

                                if ($logo_found): ?>
                                    <img src="<?= htmlspecialchars($logo_src) ?>" alt="<?= htmlspecialchars($org['organization_name'] ?: $org['organization']) ?> Logo" loading="lazy">
                                <?php else: ?>
                                    <i class="fas fa-users"></i>
                                <?php endif; ?>
                            </div>
                            <div class="org-info">
                                <h3 class="org-name"><?= htmlspecialchars($org['organization_name'] ?: $org['organization'] ?: 'Student Organization') ?></h3>
                                <p class="org-description"><?= htmlspecialchars(substr($org['description'] ?? 'Student organization at PLV', 0, 100)) ?>...</p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                    <button class="slider-btn next" onclick="slideOrgNext()" aria-label="Next organizations">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="view-all-container">
                    <a href="organizations.php" class="btn-hero btn-secondary">
                        <i class="fas fa-building"></i>
                        <span>View All Organizations</span>
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <p>&copy; 2025 Phirse. All rights reserved.</p>
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
    <script src="js/nav-bar-transparent.js"></script>
    <script src="js/product-image-carousel.js"></script>
    
    <script>
        // Product slider functionality
        const slider = document.getElementById('productSlider');
        
        function slideNext() {
            if (slider) {
                const cardWidth = 280;
                slider.scrollBy({ left: cardWidth, behavior: 'smooth' });
            }
        }
        
        function slidePrev() {
            if (slider) {
                const cardWidth = 280;
                slider.scrollBy({ left: -cardWidth, behavior: 'smooth' });
            }
        }

        // Organization slider functions
        const orgsSlider = document.getElementById('orgsSlider');

        function slideOrgNext() {
            if (orgsSlider) {
                const cardWidth = 340;
                orgsSlider.scrollBy({ left: cardWidth, behavior: 'smooth' });
            }
        }

        function slideOrgPrev() {
            if (orgsSlider) {
                const cardWidth = 340;
                orgsSlider.scrollBy({ left: -cardWidth, behavior: 'smooth' });
            }
        }
        
        // Quick view functionality
        function showQuickView(productId) {
            if (window.StudentSite && window.StudentSite.showQuickView) {
                window.StudentSite.showQuickView(productId);
            } else {
                window.location.href = `product-details.php?id=${productId}`;
            }
        }
        
        // Scroll reveal animation
        function revealOnScroll() {
            const elements = document.querySelectorAll('.fade-in');
            
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('visible');
                }
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Initial reveal check
            revealOnScroll();
            
            // Animate statistics counters
            const statNumbers = document.querySelectorAll('.stat-number');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            statNumbers.forEach(stat => observer.observe(stat));
            
            // Auto-scroll products slider (desktop only)
            if (slider && window.innerWidth > 768) {
                let autoScrollInterval = setInterval(() => {
                    if (slider.scrollLeft >= slider.scrollWidth - slider.clientWidth - 10) {
                        slider.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        slideNext();
                    }
                }, 5000);

                // Pause auto-scroll on hover
                slider.addEventListener('mouseenter', () => {
                    clearInterval(autoScrollInterval);
                });

                slider.addEventListener('mouseleave', () => {
                    autoScrollInterval = setInterval(() => {
                        if (slider.scrollLeft >= slider.scrollWidth - slider.clientWidth - 10) {
                            slider.scrollTo({ left: 0, behavior: 'smooth' });
                        } else {
                            slideNext();
                        }
                    }, 5000);
                });
            }

            // Keyboard navigation for sliders
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    slidePrev();
                    slideOrgPrev();
                } else if (e.key === 'ArrowRight') {
                    slideNext();
                    slideOrgNext();
                }
            });
        });

        // Scroll event listener
        window.addEventListener('scroll', revealOnScroll);
        
        function animateCounter(element) {
            const originalText = element.textContent;
            const target = parseInt(originalText.replace(/[^0-9]/g, ''));
            const suffix = originalText.replace(/[0-9]/g, '');
            
            if (target === 0 || isNaN(target)) {
                return;
            }
            
            let current = 0;
            const increment = Math.ceil(target / 60);
            const duration = 1500;
            const stepTime = duration / (target / increment);
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current) + suffix;
            }, stepTime);
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation to cards
        const cards = document.querySelectorAll('.product-card, .org-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
    <!-- mobile nav include contains its own small script to set active link -->
</body>
</html>