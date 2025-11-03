<?php
include __DIR__ . '/db/config.php';
include __DIR__ . '/includes/product-image-helper.php';

$pageTitle = 'Search Results';
include 'includes/header.php';

// Get search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Get logged-in student's affiliations
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

// Search Products
$products = [];
if (!empty($search)) {
$product_sql = "SELECT 
                        p.id,
                        p.seller_id,
                        p.name,
                        p.category,
                        p.price,
                        p.stock,
                        COALESCE(p.image_path, '') as images,
                        COALESCE(s.organization_name, s.organization, 'Student Organization') as organization_name,
                        p.created_at
                    FROM products p
                    LEFT JOIN sellers s ON p.seller_id = s.id
                    WHERE (p.status = 'approved' OR p.status IS NULL)
                    AND (p.name LIKE ? OR p.description LIKE ? OR COALESCE(s.organization_name, s.organization) LIKE ?)
                    ORDER BY p.created_at DESC
                    LIMIT 12";
    
    $search_param = "%{$search}%";
    $products = fetchAll($product_sql, [$search_param, $search_param, $search_param], 'sss');
}

// Search Organizations
$organizations = [];
if (!empty($search)) {
    $org_sql = "SELECT 
                    id,
                    seller_name,
                    organization_name,
                    organization,
                    logo_path,
                    description,
                    created_at
                FROM sellers
                WHERE (COALESCE(organization_name, organization) LIKE ? 
                    OR seller_name LIKE ? 
                    OR email LIKE ?)
                ORDER BY COALESCE(organization_name, organization) ASC
                LIMIT 12";
    
    $organizations = fetchAll($org_sql, [$search_param, $search_param, $search_param], 'sss');
}

$total_results = count($products) + count($organizations);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?= htmlspecialchars($search) ?> - PHIRSE</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <link rel="stylesheet" href="css/product-image-carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 120px 2rem 2rem;
        }
        
        .search-results-container {
            width: 100%;
        }
        
        .search-header {
            margin-bottom: 2rem;
        }
        
        .search-title {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .search-query {
            color: #667eea;
        }
        
        .search-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .section-title {
            font-size: clamp(1.25rem, 2.5vw, 1.5rem);
            font-weight: 700;
            color: #333;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 3rem;
            opacity: 1 !important;
            visibility: visible !important;
            width: 100%;
        }
        
        .product-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
            position: relative;
        }

        .product-image-container {
            position: relative;
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
            min-width: 0;
        }
        
        .product-image-container {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
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
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
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
        
        .product-category {
            font-size: 0.7rem;
            color: #999999;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            margin-bottom: 0.5rem;
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
        }
        
        .product-stock {
            font-size: 0.8rem;
            color: #28a745;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .product-stock.out-of-stock {
            color: #dc3545;
        }
        
        .product-stock.member-only {
            color: #4a5568;
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
        
        /* Organizations Grid - Always 1 column */
        .organizations-grid {
            display: grid;
            grid-template-columns: 1fr !important;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .org-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .org-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .org-card-link {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 1.5rem;
            text-decoration: none;
            color: inherit;
            width: 100%;
            min-width: 0;
        }
        
        .org-logo-container {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .org-logo {
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }
        
        .org-logo-placeholder {
            width: 70px;
            height: 70px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 2rem;
        }
        
        .org-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .org-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            word-break: break-word;
        }
        
        .org-description {
            color: #666;
            font-size: 0.875rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .no-results i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .no-results h3 {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .no-results p {
            color: #999;
        }
        
        @media (max-width: 1440px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            main {
                padding: 100px 1.5rem 2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .product-card {
                width: 100%;
                max-width: 100%;
            }
            
            .product-info {
                padding: 0.8rem 0.7rem;
            }
            
            .product-category {
                font-size: 0.65rem;
            }
            
            .product-organization {
                font-size: 0.7rem;
            }
            
            .organizations-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .org-logo-container {
                height: 140px;
            }
            
            .org-logo,
            .org-logo-placeholder {
                max-width: 80px;
                max-height: 80px;
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            main {
                padding: 90px 0.75rem 1.5rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }
            
            .product-card {
                border-radius: 8px;
                width: 100%;
                max-width: 100%;
            }
            
            .product-info {
                padding: 0.6rem 0.5rem;
            }
            
            .product-category {
                font-size: 0.6rem;
                margin-bottom: 0.3rem;
            }
            
            .product-name {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
                line-height: 1.3;
            }
            
            .product-organization {
                font-size: 0.65rem;
                margin-bottom: 0.4rem;
                gap: 0.3rem;
            }
            
            .product-organization i {
                font-size: 0.65rem;
            }
            
            .product-price {
                font-size: 0.85rem;
            }
            
            .product-stock {
                font-size: 0.7rem;
                margin-top: 0.4rem;
            }
            
            .new-arrival-badge {
                padding: 0.3rem 0.5rem;
                font-size: 0.6rem;
                top: 8px;
                right: 8px;
            }
            
            .organizations-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .org-logo-container {
                height: 120px;
            }
            
            .org-info {
                padding: 1rem;
            }
            
            .org-title {
                font-size: 1rem;
            }
            
            .org-description {
                font-size: 0.85rem;
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
    
    <main>
        <div class="search-results-container">
            <div class="search-header">
                <h1 class="search-title">
                    Search Results for <span class="search-query">"<?= htmlspecialchars($search) ?>"</span>
                </h1>
                <p class="search-meta"><?= $total_results ?> result<?= $total_results != 1 ? 's' : '' ?> found</p>
            </div>
        
        <?php if ($total_results > 0): ?>
            
            <!-- Products Section -->
            <?php if (!empty($products)): ?>
                <h2 class="section-title">Products (<?= count($products) ?>)</h2>
                <div class="products-grid">
                    <?php foreach ($products as $product): 
                        $allImages = getAllProductImages(
                            (int)$product['seller_id'],
                            $product['organization_name'],
                            $product['images']
                        );
                        $imagePath = $allImages[0];
                        $imagesData = htmlspecialchars(implode(',', $allImages));
                        $is_org_shirt = (stripos($product['category'], 'shirt') !== false || 
                                         stripos($product['name'], 'shirt') !== false ||
                                         stripos($product['category'], 'uniform') !== false);
                        $is_affiliated = in_array($product['seller_id'], $affiliated_seller_ids);
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
                                            <?php if ($rank !== false): ?>
                                                <div class="best-seller-badge"><i class="fas fa-crown"></i> <?= $rank_labels[$rank] ?></div>
                                            <?php endif; ?>
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
                </div>
            <?php endif; ?>
            
            <!-- Organizations Section -->
            <?php if (!empty($organizations)): ?>
                <h2 class="section-title">Organizations (<?= count($organizations) ?>)</h2>
                <div class="organizations-grid">
                    <?php foreach ($organizations as $org): ?>
                        <div class="org-card">
                            <a href="organization-product.php?id=<?= $org['id'] ?>" class="org-card-link">
                                <div class="org-logo-container">
                                    <?php
                                    $logo_found = false;
                                    $logo_src = '';
                                    
                                    if (!empty($org['logo_path'])) {
                                        $possible_paths = [
                                            $org['logo_path'],
                                            '../' . $org['logo_path'],
                                            'uploads/' . basename($org['logo_path']),
                                        ];
                                        
                                        foreach ($possible_paths as $path) {
                                            if (file_exists(__DIR__ . '/' . ltrim($path, '/'))) {
                                                $logo_src = $path;
                                                $logo_found = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    if ($logo_found): ?>
                                        <img src="<?= htmlspecialchars($logo_src) ?>" 
                                             alt="<?= htmlspecialchars($org['organization_name'] ?: $org['organization']) ?> Logo" 
                                             class="org-logo">
                                    <?php else: ?>
                                        <div class="org-logo-placeholder">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="org-info">
                                    <h3 class="org-title">
                                        <?= htmlspecialchars($org['organization_name'] ?: $org['organization'] ?: 'Unnamed Organization') ?>
                                    </h3>
                                    <p class="org-description">
                                        <?= htmlspecialchars($org['description'] ?: 'Student organization at PLV') ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p>We couldn't find any products or organizations matching "<?= htmlspecialchars($search) ?>"</p>
                <p>Try searching with different keywords</p>
            </div>
        <?php endif; ?>
        </div>
    </main>
    
    <script src="js/script.js"></script>
    <script>
        // Manually initialize carousel AFTER everything is loaded
        class ProductImageCarousel {
            constructor() {
                this.init();
            }

            init() {
                const productCards = document.querySelectorAll('.product-card');
                
                productCards.forEach(card => {
                    const imageContainer = card.querySelector('.product-image-container');
                    const mainImage = card.querySelector('.product-image');
                    
                    if (!imageContainer || !mainImage) return;
                    
                    const imagesData = mainImage.getAttribute('data-images');
                    if (!imagesData) return;
                    
                    const images = imagesData.split(',').map(img => img.trim()).filter(img => img);
                    
                    if (images.length <= 1) return;
                    
                    let currentIndex = 0;
                    let intervalId = null;
                    let isHovering = false;
                    let touchStartY = 0;
                    let isScrolling = false;
                    
                    const preloadedImages = [];
                    images.forEach(src => {
                        const img = new Image();
                        img.src = src;
                        preloadedImages.push(img);
                    });
                    
                    const imageWrapper = mainImage.parentElement;
                    const fadeOverlay = document.createElement('div');
                    fadeOverlay.style.position = 'absolute';
                    fadeOverlay.style.top = '0';
                    fadeOverlay.style.left = '0';
                    fadeOverlay.style.width = '100%';
                    fadeOverlay.style.height = '100%';
                    fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0)';
                    fadeOverlay.style.transition = 'background-color 0.1s ease-in-out';
                    fadeOverlay.style.pointerEvents = 'none';
                    fadeOverlay.style.zIndex = '1';
                    imageWrapper.style.position = 'relative';
                    imageWrapper.appendChild(fadeOverlay);
                    
                    const cycleImage = () => {
                        currentIndex = (currentIndex + 1) % images.length;
                        fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                        
                        setTimeout(() => {
                            mainImage.src = images[currentIndex];
                            setTimeout(() => {
                                fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0)';
                            }, 30);
                        }, 100);
                    };
                    
                    const startCycling = () => {
                        if (intervalId) return;
                        isHovering = true;
                        cycleImage();
                        intervalId = setInterval(cycleImage, 1000);
                    };
                    
                    const stopCycling = () => {
                        isHovering = false;
                        if (intervalId) {
                            clearInterval(intervalId);
                            intervalId = null;
                        }
                        
                        if (currentIndex !== 0) {
                            currentIndex = 0;
                            fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                            
                            setTimeout(() => {
                                mainImage.src = images[0];
                                setTimeout(() => {
                                    fadeOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0)';
                                }, 30);
                            }, 100);
                        }
                    };
                    
                    card.addEventListener('mouseenter', startCycling);
                    card.addEventListener('mouseleave', stopCycling);
                    
                    // Mobile: Touch events (cycle on tap, not hold)
                    card.addEventListener('touchstart', (e) => {
                        touchStartY = e.touches[0].clientY;
                        isScrolling = false;
                    });
                    
                    card.addEventListener('touchmove', (e) => {
                        const touchCurrentY = e.touches[0].clientY;
                        const deltaY = Math.abs(touchCurrentY - touchStartY);
                        
                        if (deltaY > 10) {
                            isScrolling = true;
                        }
                    });
                    
                    card.addEventListener('touchend', (e) => {
                        if (!isScrolling && !e.target.closest('a')) {
                            cycleImage();
                        }
                        isScrolling = false;
                    });
                    
                    mainImage.style.transition = 'none';
                    mainImage.style.display = 'block';
                });
            }
        }
    </script>
    <script>
        // Initialize carousel when page loads
        window.addEventListener('load', function() {
            // Initialize the carousel
            console.log('Initializing carousel...');
            new ProductImageCarousel();
            console.log('Carousel initialized!');
            
            // Debug info
            console.log('=== SEARCH PAGE DEBUG ===');
            console.log('Page loaded at:', new Date().toLocaleTimeString());
            
            const productCards = document.querySelectorAll('.product-card');
            console.log('Product cards found:', productCards.length);
            
            let hasMultipleImages = false;
            
            productCards.forEach((card, index) => {
                const img = card.querySelector('.product-image');
                const container = card.querySelector('.product-image-container');
                
                console.log(`\nCard ${index + 1}:`);
                console.log('  - Has image element:', !!img);
                console.log('  - Has container:', !!container);
                
                if (img) {
                    const dataImages = img.getAttribute('data-images');
                    console.log('  - data-images attribute:', dataImages ? 'Present' : 'MISSING');
                    
                    if (dataImages) {
                        const imageArray = dataImages.split(',').map(s => s.trim()).filter(s => s);
                        console.log('  - Image count:', imageArray.length);
                        console.log('  - Images:', imageArray);
                        
                        if (imageArray.length > 1) {
                            hasMultipleImages = true;
                            console.log('  - ✓ MULTIPLE IMAGES - Carousel should work!');
                        } else {
                            console.log('  - ✗ Only 1 image - Carousel will not activate');
                        }
                    }
                }
            });
            
            console.log('\n=== SUMMARY ===');
            console.log('Has products with multiple images:', hasMultipleImages);
            console.log('Carousel JS loaded:', typeof ProductImageCarousel !== 'undefined');
            console.log('==================\n');
            
            if (!hasMultipleImages) {
                console.warn('⚠️ WARNING: No products with multiple images found!');
                console.warn('The carousel needs products with 2+ images to work.');
            }
        });
    </script>
    <?php include __DIR__ . '/includes/mobile-bottom-nav.php'; ?>
</body>
</html>
