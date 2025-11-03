<?php
include __DIR__ . '/db/config.php';

$pageTitle = 'Organizations';
include 'includes/header.php';

// Get search and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name_asc';

// Build search query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(COALESCE(organization_name, organization) LIKE ? OR seller_name LIKE ? OR email LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM sellers {$where_clause}";
$total_result = !empty($params) ? fetchSingle($count_sql, $params, $types) : fetchSingle($count_sql);
$total_organizations = $total_result['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$total_pages = ceil($total_organizations / $per_page);
$offset = ($page - 1) * $per_page;

// Get organizations
$sql = "SELECT
            id,
            seller_name,
            organization_name,
            organization,
            contact_number,
            email,
            logo_path,
            description,
            created_at
        FROM sellers
        {$where_clause}
        ORDER BY " . match($sort_by) {
            'name_desc' => "COALESCE(organization_name, organization) DESC",
            'date_old' => "created_at ASC",
            'date_new' => "created_at DESC",
            default => "COALESCE(organization_name, organization) ASC, created_at DESC"
        } . "
        LIMIT {$per_page} OFFSET {$offset}";

$organizations = !empty($params) ? fetchAll($sql, $params, $types) : fetchAll($sql);

displayFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizations - PHIRSE</title>
    <link rel="icon" href="N/A" type="image/png">

    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav-bar-transparent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .organizations-container {
            max-width: 1200px;
            margin: 90px auto 0;
            padding: 2rem;
            width: 100%;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }


        /* Organizations Grid */
        .organizations-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
            width: 100%;
        }

        .org-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .org-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .org-card-link {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            height: 100%;
            min-width: 0;
        }

        .org-card-link:hover {
            text-decoration: none;
            color: inherit;
        }

        .org-logo-container {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .org-logo {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .org-card:hover .org-logo {
            transform: scale(1.05);
        }

        .org-logo-placeholder {
            width: 120px;
            height: 120px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
        }

        .org-info {
            padding: 1.5rem;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .org-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.75rem;
            min-height: 2.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.3;
            word-break: break-word;
        }

        .org-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            min-height: 3rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .org-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .join-date {
            color: #999;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 3rem;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination a,
        .pagination span {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #333;
            color: white;
            border-color: #333;
            text-decoration: none;
        }

        .pagination .current {
            background: #333;
            color: white;
            border-color: #333;
        }

        .pagination-info {
            text-align: center;
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .view-all-btn {
            background: #333;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .view-all-btn:hover {
            background: #555;
            color: white;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .organizations-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .organizations-container {
                padding: 1.5rem;
                margin-top: 70px;
            }

            .page-title {
                font-size: clamp(1.5rem, 4vw, 2rem);
            }

            .page-header {
                margin-bottom: 2rem;
            }


            .organizations-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .org-logo-container {
                height: 160px;
            }

            .org-logo,
            .org-logo-placeholder {
                max-width: 100px;
                max-height: 100px;
            }

            .org-info {
                padding: 1rem;
            }

            .org-title {
                font-size: 1.1rem;
                min-height: 2.2rem;
            }

            .org-description {
                font-size: 0.85rem;
                min-height: 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .organizations-container {
                padding: 1rem;
                margin-top: 65px;
            }

            .page-header {
                margin-bottom: 1.5rem;
            }


            .organizations-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .org-card {
                border-radius: 10px;
            }

            .org-logo-container {
                height: 120px;
            }

            .org-logo,
            .org-logo-placeholder {
                max-width: 70px;
                max-height: 70px;
                width: 70px;
                height: 70px;
                font-size: 1.75rem;
            }

            .org-info {
                padding: 0.75rem;
            }

            .org-title {
                font-size: 0.95rem;
                min-height: auto;
                margin-bottom: 0.5rem;
            }

            .org-description {
                font-size: 0.8rem;
                min-height: auto;
                -webkit-line-clamp: 2;
                margin-bottom: 0.75rem;
            }

            .org-meta {
                margin-top: 0.5rem;
            }

            .join-date {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 375px) {
            .organizations-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem;
            }

            .org-logo-container {
                height: 100px;
            }

            .org-logo,
            .org-logo-placeholder {
                max-width: 60px;
                max-height: 60px;
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .org-info {
                padding: 0.6rem;
            }

            .org-title {
                font-size: 0.875rem;
            }

            .org-description {
                font-size: 0.75rem;
            }

            .join-date {
                font-size: 0.65rem;
            }
        }

        @media (max-width: 320px) {
            .organizations-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .org-title {
                font-size: 0.8rem;
            }

            .org-description {
                font-size: 0.7rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'nav-bar-transparent.php'; ?>

    <div class="organizations-container">
        <div class="page-header">
            <h1 class="page-title">All Student Organizations</h1>
        </div>


        <!-- 🏫 Organizations Grid -->
        <?php if (!empty($organizations)): ?>
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
                                        '../seller/' . basename($org['logo_path']),
                                        '../uploads/' . basename($org['logo_path']),
                                        '../seller/uploads/' . basename($org['logo_path']),
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
                                         class="org-logo"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="org-logo-placeholder" style="display: none;">
                                        <i class="fas fa-users"></i>
                                    </div>
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
                                <div class="org-meta">
                                    <!-- 🗓 Only show date now -->
                                    <span class="join-date">
                                        <?= date('M Y', strtotime($org['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination (unchanged) -->
            <?php if ($total_pages > 1): ?>
                <!-- same pagination code -->
            <?php endif; ?>

        <?php else: ?>
            <!-- No Results (unchanged) -->
        <?php endif; ?>
    </div>

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
    <script src="js/nav-bar-transparent.js"></script>

</body>
</html>