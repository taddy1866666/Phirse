<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';
$pageTitle = 'Add Product';
include 'includes/seller-header.php';

// Get seller info for header
try {
    $seller_id = $_SESSION['seller_id'] ?? null;

    if (!$seller_id) {
        header('Location: ../index.html');
        exit();
    }

    $stmt = $pdo->prepare("SELECT seller_name, organization, logo_path FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller_info) {
        session_destroy();
        header('Location: ../index.html');
        exit();
    }

    $seller_logo = $seller_info['logo_path'] ?? null;
    $organization_name = $seller_info['organization'] ?? $_SESSION['organization'] ?? '';
    $seller_name = $seller_info['seller_name'] ?? $_SESSION['seller_name'] ?? '';

} catch(PDOException $e) {
    $seller_logo = null;
    $organization_name = $_SESSION['organization'] ?? '';
    $seller_name = $_SESSION['seller_name'] ?? '';
    $debug_info = "Database connection error: " . $e->getMessage();
}

// Handle form submission
if ($_POST && isset($_POST['add_product'])) {
    error_log("Form submitted with data: " . print_r($_POST, true));
    
    try {
        // Verify the seller
        $stmt = $pdo->prepare("SELECT id FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);
        $seller_exists = $stmt->fetch();
        
        if (!$seller_exists) {
            throw new Exception("Invalid seller session. Please log in again.");
        }
        
        $category = $_POST['category'];
        
        // Handle different form fields based on category
        if ($category === 'Organization Shirt') {
            $event_name = $_POST['shirt_name'] ?? '';
            $description = $_POST['shirt_description'] ?? '';
            // allow 0 price explicitly, so use null default to distinguish missing
            $price = isset($_POST['shirt_price']) && $_POST['shirt_price'] !== '' ? $_POST['shirt_price'] : null;
            $stock = isset($_POST['shirt_stock']) && $_POST['shirt_stock'] !== '' ? $_POST['shirt_stock'] : null;
            $max_order = isset($_POST['shirt_max_order']) && $_POST['shirt_max_order'] !== '' ? $_POST['shirt_max_order'] : null;

            $shirt_category = $_POST['shirt_category'] ?? '';
            $shirt_sizes = $_POST['shirt_sizes'] ?? [];

            // Build description with seller's organization
            $description = "Organization: " . $organization_name . "\n";
            $description .= "Type: " . $shirt_category . "\n";
            if (!empty($shirt_sizes)) {
                $description .= "Sizes: " . implode(', ', $shirt_sizes) . "\n";
            }
            $description .= "\n" . ($_POST['shirt_description'] ?? '');

        } elseif ($category === 'Merchandise') {
            $event_name = $_POST['merch_name'] ?? '';
            $description = $_POST['merch_description'] ?? '';
            $price = isset($_POST['merch_price']) && $_POST['merch_price'] !== '' ? $_POST['merch_price'] : null;
            $stock = isset($_POST['merch_stock']) && $_POST['merch_stock'] !== '' ? $_POST['merch_stock'] : null;
            $max_order = isset($_POST['merch_max_order']) && $_POST['merch_max_order'] !== '' ? $_POST['merch_max_order'] : null;

            $merch_type = $_POST['merch_type'] ?? '';
            $merch_sizes = $_POST['merch_sizes'] ?? [];

            // Build description with seller's organization
            $description = "Organization: " . $organization_name . "\n";
            $description .= "Type: " . $merch_type . "\n";
            if (!empty($merch_sizes)) {
                $description .= "Sizes: " . implode(', ', $merch_sizes) . "\n";
            }
            $description .= "\n" . ($_POST['merch_description'] ?? '');

        } else {
            $event_name = $_POST['event_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = isset($_POST['price']) && $_POST['price'] !== '' ? $_POST['price'] : null;
            $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? $_POST['stock'] : null;
            $max_order = isset($_POST['max_order']) && $_POST['max_order'] !== '' ? $_POST['max_order'] : null;
            
            // Handle ticket type for Event Ticket category
            if ($category === 'Event Ticket') {
                $ticket_type = $_POST['ticket_type'] ?? '';
                
                $description = "Ticket Type: " . ($ticket_type === 'for_organization' ? 'For Organization' : 'For Public') . "\n\n";
                
                $description .= $_POST['description'] ?? '';
            }
        }

        // Validate required fields. Use strict checks so '0' is accepted as valid input.
        $missing = [];
        if (empty($category)) $missing[] = 'category';
        if (empty($event_name)) $missing[] = ($category === 'Organization Shirt' ? 'shirt name / design title' : 'name');
        if ($price === null || $price === '') $missing[] = 'price';

        // stock required for non-Organization Shirt categories
        if ($category !== 'Organization Shirt' && ($stock === null || $stock === '')) {
            $missing[] = 'stock';
        }

        if (!empty($missing)) {
            throw new Exception('Please fill in all required fields: ' . implode(', ', $missing) . '.');
        }
        
        if (!is_numeric($price) || $price < 0) {
            throw new Exception("Price must be a valid positive number.");
        }
        
        if (!empty($stock) && (!is_numeric($stock) || $stock < 0)) {
            throw new Exception("Stock must be a valid positive number.");
        }
        
        if ($category === 'Organization Shirt' && ($stock === null || $stock === '')) {
            $stock = 0;
        }
        
        if ($category === 'Organization Shirt') {
            if (empty($_POST['shirt_category'])) {
                throw new Exception("Please fill in all required fields for Organization Shirt.");
            }
            if (empty($_POST['shirt_sizes'])) {
                throw new Exception("Please select at least one size for the shirt.");
            }
        } elseif ($category === 'Merchandise') {
            if (empty($_POST['merch_type'])) {
                throw new Exception("Please fill in all required fields for Merchandise.");
            }
        }
        
        $image_path = null;

        $image_field = '';
        if ($category === 'Organization Shirt') {
            $image_field = 'shirt_image';
        } elseif ($category === 'Merchandise') {
            $image_field = 'merch_image';
        } else {
            $image_field = 'images';
        }

        $upload_dir = '../uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Handle multiple image uploads
        $uploaded_images = [];
        if (isset($_FILES[$image_field])) {
            $files = $_FILES[$image_field];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            // Handle single file upload (backward compatibility)
            if (!is_array($files['name'])) {
                $files = [
                    'name' => [$files['name']],
                    'type' => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error' => [$files['error']],
                    'size' => [$files['size']]
                ];
            }
            
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] == 0) {
                    $file_type = $files['type'][$i];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("Only JPG, PNG and GIF images are allowed.");
                    }
                    
                    if ($files['size'][$i] > 5 * 1024 * 1024) {
                        throw new Exception("Image file size must be less than 5MB.");
                    }
                    
                    $file_extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_product_' . $i . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
                        $uploaded_images[] = $upload_path;
                    } else {
                        throw new Exception("Failed to upload image file: " . $files['name'][$i]);
                    }
                }
            }
            
            if (!empty($uploaded_images)) {
                $image_path = implode(',', $uploaded_images);
            }
        }
        
        // Set ticket specific fields if it's an event ticket
        $ticket_type = null;
        
        if ($category === 'Event Ticket') {
            $ticket_type = $_POST['ticket_type'] ?? null;
        }

        $stmt = $pdo->prepare("INSERT INTO products (seller_id, category, name, description, price, stock, image_path, ticket_type, max_order, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$seller_id, $category, $event_name, $description, $price, $stock, $image_path, $ticket_type, $max_order]);

        $product_id = $pdo->lastInsertId();

        // Insert admin notification row
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                icon VARCHAR(50) DEFAULT 'fa-bell',
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $title = 'New Product Submitted';
            $sellerLabel = $organization_name ?: $seller_name ?: 'Seller';
            $msg = sprintf('%s (%s) • Category: %s', $event_name, $sellerLabel, $category);
            $icon = 'fa-hourglass-half';
            $stmtNotif = $pdo->prepare("INSERT INTO admin_notifications (type, title, message, icon) VALUES (?, ?, ?, ?)");
            $stmtNotif->execute(['product', $title, $msg, $icon]);
        } catch (PDOException $e) {
            // Non-fatal: log and continue
            error_log('Failed to insert admin notification: ' . $e->getMessage());
        }

        $success_message = "Product added successfully!";
    } catch (PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        error_log("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Phirse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
        }
        
        .breadcrumb i {
            margin-right: 8px;
        }
        
        .welcome-text {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            width: 100%;
            max-width: none;
        }
        
        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.3s;
            background-color: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .form-control select {
            cursor: pointer;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .file-input-wrapper {
            display: block;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background-color: #f8f9fa;
            cursor: pointer;
            display: inline-block;
            font-size: 15px;
            color: #666;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
        }
        
        .file-input-label:hover {
            background-color: #e9ecef;
            border-color: #007bff;
        }
        
        .submit-btn {
            background-color: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background-color: #218838;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .form-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        
        .form-section.active {
            display: block;
        }
        
        .form-section h3 {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .checkbox-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
        <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                Home > Add product
            </div>
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($seller_name ?: 'Bitcoin'); ?>!
            </div>
        </div>

        <div class="form-container">
            <h1 class="form-title">Add Product</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category" class="form-label">Category: <span class="required">*</span></label>
                    <select name="category" id="category" class="form-control" required onchange="toggleFormSections()">
                        <option value="">Select a category</option>
                        <option value="Event Ticket">Event Ticket</option>
                        <option value="Merchandise">Merchandise</option>
                        <option value="Organization Shirt">Organization Shirt</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <!-- Organization Shirt Form Section -->
                <div id="organization-shirt-section" class="form-section">
                    <h3>👕 Organization Shirt Details</h3>

                    <div class="form-group">
                        <label for="shirt_name" class="form-label">Shirt Name / Design Title: <span class="required">*</span></label>
                        <input type="text" name="shirt_name" id="shirt_name" class="form-control" placeholder="e.g., Computer Science Department Shirt 2025" required>
                    </div>

                    <div class="form-group">
                        <label for="shirt_description" class="form-label">Description:</label>
                        <textarea name="shirt_description" id="shirt_description" class="form-control" rows="3" placeholder="Short details about the shirt (e.g., event, purpose, or theme)"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="shirt_category" class="form-label">Product Type / Category: <span class="required">*</span></label>
                            <input type="text" name="shirt_category" id="shirt_category" class="form-control" placeholder="e.g., T-Shirt, Polo Shirt, Hoodie, Tank Top, Long Sleeve" required>
                            <small class="form-text text-muted">Enter the product type or category (e.g., T-Shirt, Polo Shirt, Hoodie, etc.)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Available Sizes: <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="shirt_sizes[]" value="XS" id="size_xs">
                                <label for="size_xs">XS</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="shirt_sizes[]" value="S" id="size_s">
                                <label for="size_s">S</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="shirt_sizes[]" value="M" id="size_m">
                                <label for="size_m">M</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="shirt_sizes[]" value="L" id="size_l">
                                <label for="size_l">L</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="shirt_sizes[]" value="XL" id="size_xl">
                                <label for="size_xl">XL</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="shirt_sizes[]" value="2XL" id="size_2xl">
                                <label for="size_2xl">2XL</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shirt_image" class="form-label">Product Images:</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="shirt_image[]" id="shirt_image" class="file-input" accept="image/*" multiple>
                            <label for="shirt_image" class="file-input-label">
                                <i class="fas fa-upload"></i> Choose images (multiple allowed)
                            </label>
                            <span id="shirt-file-name" style="margin-left: 10px; color: #666;">No files chosen</span>
                        </div>
                        <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                            You can select multiple images. First image will be the main display image.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="shirt_price" class="form-label">Price per Shirt: <span class="required">*</span></label>
                            <input type="number" name="shirt_price" id="shirt_price" class="form-control" step="0.01" min="0" placeholder="₱350.00" required>
                        </div>
                        <div class="form-group">
                            <label for="shirt_stock" class="form-label">Available Stock (optional):</label>
                            <input type="number" name="shirt_stock" id="shirt_stock" class="form-control" min="0" placeholder="Only if producing in advance">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shirt_max_order" class="form-label">Maximum Order Per Student:</label>
                        <input type="number" name="shirt_max_order" id="shirt_max_order" class="form-control" min="1" placeholder="Leave empty for no limit">
                        <small class="form-text text-muted">Maximum number of items a student can order</small>
                    </div>

                    <button type="submit" name="add_product" class="submit-btn">
                        <i class="fas fa-plus"></i> Add Organization Shirt
                    </button>
                </div>

                <!-- Merchandise Form Section -->
                <div id="merchandise-section" class="form-section">
                    <h3>🎨 Merchandise Details</h3>

                    <div class="form-group">
                        <label for="merch_name" class="form-label">Merchandise Name / Title: <span class="required">*</span></label>
                        <input type="text" name="merch_name" id="merch_name" class="form-control" placeholder="e.g., IT Society Tote Bag 2025" required>
                    </div>

                    <div class="form-group">
                        <label for="merch_description" class="form-label">Product Description:</label>
                        <textarea name="merch_description" id="merch_description" class="form-control" rows="3" placeholder="Brief info, e.g., 'Commemorative tote bag for IT Week'"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="merch_type" class="form-label">Product Type / Category: <span class="required">*</span></label>
                            <input type="text" name="merch_type" id="merch_type" class="form-control" placeholder="e.g., Tote Bag, Mug, Cap, Hoodie, Lanyard, Sticker, Keychain, Notebook" required>
                            <small class="form-text text-muted">Enter the product type or category (e.g., Tote Bag, Mug, Cap, etc.)</small>
                        </div>
                    </div>

                    <div class="form-group" id="merch_sizes_group" style="display: none;">
                        <label class="form-label">Size Options (for wearables):</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="merch_sizes[]" value="XS" id="merch_size_xs">
                                <label for="merch_size_xs">XS</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="merch_sizes[]" value="S" id="merch_size_s">
                                <label for="merch_size_s">S</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="merch_sizes[]" value="M" id="merch_size_m">
                                <label for="merch_size_m">M</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="merch_sizes[]" value="L" id="merch_size_l">
                                <label for="merch_size_l">L</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="merch_sizes[]" value="XL" id="merch_size_xl">
                                <label for="merch_size_xl">XL</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="merch_image" class="form-label">Product Images:</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="merch_image[]" id="merch_image" class="file-input" accept="image/*" multiple>
                            <label for="merch_image" class="file-input-label">
                                <i class="fas fa-upload"></i> Choose images (multiple allowed)
                            </label>
                            <span id="merch-file-name" style="margin-left: 10px; color: #666;">No files chosen</span>
                        </div>
                        <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                            You can select multiple images. First image will be the main display image.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="merch_price" class="form-label">Price per Unit: <span class="required">*</span></label>
                            <input type="number" name="merch_price" id="merch_price" class="form-control" step="0.01" min="0" placeholder="₱150.00" required>
                        </div>
                        <div class="form-group">
                            <label for="merch_stock" class="form-label">Available Stock (optional):</label>
                            <input type="number" name="merch_stock" id="merch_stock" class="form-control" min="0" placeholder="If tracking inventory">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="merch_max_order" class="form-label">Maximum Order Per Student:</label>
                        <input type="number" name="merch_max_order" id="merch_max_order" class="form-control" min="1" placeholder="Leave empty for no limit">
                        <small class="form-text text-muted">Maximum number of items a student can order</small>
                    </div>

                    <button type="submit" name="add_product" class="submit-btn">
                        <i class="fas fa-plus"></i> Add Merchandise
                    </button>
                </div>

                <!-- Default form fields (for Event Ticket and Others) -->
                <div id="default-section" class="form-section">
                    <h3>🎫 Event / Product Details</h3>

                    <div class="form-group">
                        <label for="event_name" class="form-label">Event Name: <span class="required">*</span></label>
                        <input type="text" name="event_name" id="event_name" class="form-control" required>
                    </div>

                    <div id="ticket-type-section" style="display: none;" class="form-group">
                        <label for="ticket_type" class="form-label">Ticket Type: <span class="required">*</span></label>
                        <select name="ticket_type" id="ticket_type" class="form-control">
                            <option value="for_public">For Public</option>
                            <option value="for_organization">For Organization</option>
                        </select>
                    </div>



                    <div class="form-group">
                        <label for="description" class="form-label">Description:</label>
                        <textarea name="description" id="description" class="form-control" rows="5"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Price: <span class="required">*</span></label>
                        <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="stock" class="form-label">Stock: <span class="required">*</span></label>
                        <input type="number" name="stock" id="stock" class="form-control" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="max_order" class="form-label">Maximum Order Per Student:</label>
                        <input type="number" name="max_order" id="max_order" class="form-control" min="1" placeholder="Leave empty for no limit">
                        <small class="form-text text-muted">Maximum number of items a student can order</small>
                    </div>

                    <div class="form-group">
                        <label for="images" class="form-label">Product Images:</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="images[]" id="images" class="file-input" accept="image/*" multiple>
                            <label for="images" class="file-input-label">
                                <i class="fas fa-upload"></i> Choose images (multiple allowed)
                            </label>
                            <span id="file-name" style="margin-left: 10px; color: #666;">No files chosen</span>
                        </div>
                        <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                            You can select multiple images. First image will be the main display image.
                        </small>
                    </div>

                    <button type="submit" name="add_product" class="submit-btn">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('images').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                const fileNames = Array.from(files).map(file => file.name).join(', ');
                document.getElementById('file-name').textContent = `${files.length} file(s) chosen: ${fileNames}`;
            } else {
                document.getElementById('file-name').textContent = 'No files chosen';
            }
        });
        
        function toggleFormSections() {
            const category = document.getElementById('category').value;
            const orgShirtSection = document.getElementById('organization-shirt-section');
            const merchandiseSection = document.getElementById('merchandise-section');
            const defaultSection = document.getElementById('default-section');

            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
                const inputs = section.querySelectorAll('input[required], select[required]');
                inputs.forEach(input => {
                    if (input.hasAttribute('data-originally-required')) {
                        input.removeAttribute('required');
                    } else if (input.hasAttribute('required')) {
                        input.setAttribute('data-originally-required', 'true');
                        input.removeAttribute('required');
                    }
                });
            });

            let activeSection = null;
            if (category === 'Organization Shirt') {
                activeSection = orgShirtSection;
            } else if (category === 'Merchandise') {
                activeSection = merchandiseSection;
            } else if (category === 'Event Ticket' || category === 'Others') {
                activeSection = defaultSection;
                // Show/hide ticket type section based on category
                const ticketTypeSection = document.getElementById('ticket-type-section');
                if (category === 'Event Ticket') {
                    ticketTypeSection.style.display = 'block';
                    document.getElementById('ticket_type').setAttribute('required', 'required');
                } else {
                    ticketTypeSection.style.display = 'none';
                    document.getElementById('ticket_type').removeAttribute('required');
                }
            }

            if (activeSection) {
                activeSection.classList.add('active');
                const inputs = activeSection.querySelectorAll('input[data-originally-required], select[data-originally-required]');
                inputs.forEach(input => {
                    input.setAttribute('required', 'required');
                });
            }
        }
        
        function toggleMerchSizes() {
            const merchType = document.getElementById('merch_type').value.toLowerCase();
            const sizesGroup = document.getElementById('merch_sizes_group');
            const wearableTypes = ['shirt', 'hoodie', 'cap', 'jacket', 'sweater', 'polo'];
            
            if (wearableTypes.some(type => merchType.includes(type))) {
                sizesGroup.style.display = 'block';
            } else {
                sizesGroup.style.display = 'none';
                document.querySelectorAll('#merch_sizes_group input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
        }
        


        document.addEventListener('DOMContentLoaded', function() {
            const merchTypeInput = document.getElementById('merch_type');
            if (merchTypeInput) {
                merchTypeInput.addEventListener('input', toggleMerchSizes);
                merchTypeInput.addEventListener('change', toggleMerchSizes);
            }

            const categorySelect = document.getElementById('category');
            if (categorySelect) {
                categorySelect.addEventListener('change', toggleFormSections);
                toggleFormSections();
            }

            const defaultFileInput = document.getElementById('images');
            if (defaultFileInput) {
                defaultFileInput.addEventListener('change', function(e) {
                    const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
                    document.getElementById('file-name').textContent = fileName;
                });
            }

            const shirtFileInput = document.getElementById('shirt_image');
            if (shirtFileInput) {
                shirtFileInput.addEventListener('change', function(e) {
                    const files = e.target.files;
                    if (files.length > 0) {
                        const fileNames = Array.from(files).map(file => file.name).join(', ');
                        document.getElementById('shirt-file-name').textContent = `${files.length} file(s) chosen: ${fileNames}`;
                    } else {
                        document.getElementById('shirt-file-name').textContent = 'No files chosen';
                    }
                });
            }

            const merchFileInput = document.getElementById('merch_image');
            if (merchFileInput) {
                merchFileInput.addEventListener('change', function(e) {
                    const files = e.target.files;
                    if (files.length > 0) {
                        const fileNames = Array.from(files).map(file => file.name).join(', ');
                        document.getElementById('merch-file-name').textContent = `${files.length} file(s) chosen: ${fileNames}`;
                    } else {
                        document.getElementById('merch-file-name').textContent = 'No files chosen';
                    }
                });
            }

            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const category = document.getElementById('category').value;

                    if (!category) {
                        alert('Please select a category first.');
                        e.preventDefault();
                        return false;
                    }

                    if (category === 'Organization Shirt') {
                        const sizes = document.querySelectorAll('input[name="shirt_sizes[]"]:checked');
                        if (sizes.length === 0) {
                            alert('Please select at least one shirt size.');
                            e.preventDefault();
                            return false;
                        }
                    } else if (category === 'Merchandise') {
                        const merchType = document.getElementById('merch_type').value;
                        const wearableTypes = ['Shirt', 'Hoodie', 'Cap'];
                        
                        if (wearableTypes.includes(merchType)) {
                            const sizes = document.querySelectorAll('input[name="merch_sizes[]"]:checked');
                            if (sizes.length === 0) {
                                alert('Please select at least one size for ' + merchType + '.');
                                e.preventDefault();
                                return false;
                            }
                        }
                    }

                    const activeSection = document.querySelector('.form-section.active');
                    if (activeSection) {
                        const requiredFields = activeSection.querySelectorAll('input[required], select[required]');
                        for (let field of requiredFields) {
                            if (!field.value.trim()) {
                                alert('Please fill in all required fields.');
                                field.focus();
                                e.preventDefault();
                                return false;
                            }
                        }
                    }
                });
            }
        })
    </script>
</body>
</html>

