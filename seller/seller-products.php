<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';
$pageTitle = 'Products';
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
        // Seller doesn't exist in database, clear session and redirect
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
}

// Get seller's products
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['seller_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process images for each product
    foreach ($products as &$product) {
        if (!empty($product['image_path'])) {
            $imagePaths = array_filter(array_map('trim', explode(',', $product['image_path'])));
            $firstImage = $imagePaths[0] ?? '';
            if (!empty($firstImage) && file_exists($firstImage)) {
                $product['display_image'] = $firstImage;
            } else {
                $product['display_image'] = '../uploads/products/default.jpg';
            }
        } else {
            $product['display_image'] = '../uploads/products/default.jpg';
        }
    }
    unset($product); // Break the reference
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
    <title>Product Management - Seller Dashboard</title>
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
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .products-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .products-table td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }
        
        .products-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .reason-text {
            font-size: 12px;
            color: #666;
            font-style: italic;
            max-width: 200px;
            word-wrap: break-word;
        }
        
        .reason-text.rejected {
            color: #dc3545;
            font-weight: 500;
        }
        
        .reason-text.approved {
            color: #28a745;
            font-weight: 500;
        }
        
        .reason-text.pending {
            color: #ffc107;
            font-weight: 500;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .no-image {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .no-products {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
        
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .windows-activation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #ccc;
            font-size: 12px;
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
                padding: 15px;
            }

            .page-title {
                font-size: 20px;
            }

            .products-container {
                overflow-x: auto;
            }

            table {
                min-width: 700px;
            }

            th, td {
                padding: 10px;
                font-size: 13px;
            }

            .status-badge {
                font-size: 11px;
                padding: 4px 8px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 10px 10px;
            }

            .page-title {
                font-size: 18px;
            }

            .page-header {
                padding: 12px;
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
        <div class="page-header">
            <h1 class="page-title">Product Management</h1>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="message"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>

        <div class="products-table-container">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    No products found. <a href="seller-add-product.php">Add your first product</a>
                </div>
            <?php else: ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>
                                    <?php if (!empty($product['display_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                             alt="Product Image" class="product-image">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $product['status']; ?>">
                                        <?php 
                                        if ($product['status'] === 'pending') {
                                            echo 'In Progress';
                                        } else {
                                            echo ucfirst($product['status']);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <?php if ($product['status'] === 'rejected' && !empty($product['rejection_reason'])): ?>
                                        <div class="reason-text rejected">
                                            <?php echo htmlspecialchars($product['rejection_reason']); ?>
                                        </div>
                                    <?php elseif ($product['status'] === 'approved'): ?>
                                        <div class="reason-text approved">
                                            Product approved by admin
                                        </div>
                                    <?php elseif ($product['status'] === 'pending'): ?>
                                        <div class="reason-text pending">
                                            Under review by admin
                                        </div>
                                    <?php else: ?>
                                        <div class="reason-text">
                                            —
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['status'] === 'approved'): ?>
                                        <button onclick="openUpdateStockModal(<?php echo $product['id']; ?>, <?php echo $product['stock']; ?>)" class="update-stock-btn">
                                            <i class="fas fa-edit"></i> Update Stock
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div id="updateStockModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Update Stock</h2>
            <form id="updateStockForm" method="POST" action="update-stock.php">
                <input type="hidden" id="product_id" name="product_id">
                <div class="form-group">
                    <label for="current_stock">Current Stock:</label>
                    <input type="number" id="current_stock" disabled>
                </div>
                <div class="form-group">
                    <label for="new_stock">New Stock:</label>
                    <input type="number" id="new_stock" name="new_stock" required min="0">
                </div>
                <button type="submit" class="submit-btn">Update Stock</button>
            </form>
        </div>
    </div>

    <style>
        .update-stock-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .update-stock-btn:hover {
            background-color: #0056b3;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close-modal:hover {
            color: #333;
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .modal .form-group {
            margin-bottom: 15px;
        }

        .modal label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .modal input[type="number"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .modal input[disabled] {
            background-color: #f5f5f5;
        }

        .modal .submit-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            margin-top: 10px;
        }

        .modal .submit-btn:hover {
            background-color: #218838;
        }
    </style>

    <script>
        function openUpdateStockModal(productId, currentStock) {
            const modal = document.getElementById('updateStockModal');
            const productIdInput = document.getElementById('product_id');
            const currentStockInput = document.getElementById('current_stock');
            const newStockInput = document.getElementById('new_stock');

            productIdInput.value = productId;
            currentStockInput.value = currentStock;
            newStockInput.value = currentStock;
            newStockInput.min = 0;

            modal.style.display = 'block';
        }

        // Close modal when clicking the X button
        document.querySelector('.close-modal').onclick = function() {
            document.getElementById('updateStockModal').style.display = 'none';
        }

        // Close modal when clicking outside the modal
        window.onclick = function(event) {
            const modal = document.getElementById('updateStockModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Form validation and submission
        document.getElementById('updateStockForm').onsubmit = function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const newStock = document.getElementById('new_stock').value;
            const productId = document.getElementById('product_id').value;
            
            if (newStock < 0) {
                alert('Stock cannot be negative');
                return false;
            }

            // Create FormData object
            const formData = new FormData(this);
            
            // Submit form using fetch
            fetch('update-stock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Server response:', text); // Debug response
                try {
                    // Try to parse as JSON in case it's JSON
                    const result = JSON.parse(text);
                    if (result.error) {
                        alert(result.error);
                    } else {
                        location.reload(); // Reload on success
                    }
                } catch (e) {
                    // If not JSON, just reload
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error updating stock:', error);
                alert('Failed to update stock. Please try again.');
            });

            return false;
        }
    </script>
</body>
</html>