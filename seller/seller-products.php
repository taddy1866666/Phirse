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

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_multiple'])) {
    try {
        $ids = json_decode($_POST['delete_multiple'], true);
        
        if (!is_array($ids) || empty($ids)) {
            throw new Exception("Invalid selection");
        }
        
        // Validate all IDs are numeric and belong to the seller
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                throw new Exception("Invalid product ID");
            }
        }
        
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Get product info before deleting for notifications
        $getProductsStmt = $pdo->prepare("SELECT id, name, category FROM products WHERE id IN ($placeholders) AND seller_id = ?");
        $productsToDelete = array_merge($ids, [$seller_id]);
        $getProductsStmt->execute($productsToDelete);
        $productsInfo = $getProductsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete all selected products that belong to this seller
        $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders) AND seller_id = ?");
        $ids_with_seller = array_merge($ids, [$seller_id]);
        $stmt->execute($ids_with_seller);
        
        // Create admin notifications for each deleted product
        foreach ($productsInfo as $product) {
            $notif_message = 'Organization: ' . htmlspecialchars($organization_name) . "\nSeller: " . htmlspecialchars($seller_name) . "\nCategory: " . $product['category'] . "\nProduct: " . $product['name'] . "\nAction: Product has been deleted by the seller.";
            $notifStmt = $pdo->prepare("
                INSERT INTO admin_notifications (type, title, message, seller_id)
                VALUES (?, ?, ?, ?)
            ");
            $notifStmt->execute([
                'product_deleted',
                'Product Deleted: ' . $product['name'],
                $notif_message,
                $seller_id
            ]);
        }
        
        $message = "Successfully deleted " . count($ids) . " product(s)!";
        header('Location: seller-products.php?message=' . urlencode($message));
        exit();
    } catch(Exception $e) {
        $error = "Error deleting products: " . $e->getMessage();
    }
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
        
        .bulk-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .select-all-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #333;
        }

        .select-all-label input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        .delete-selected-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delete-selected-btn:hover {
            background-color: #c82333;
        }

        .product-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
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
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="product-row" data-product-id="<?php echo $product['id']; ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" onchange="updateSelectAllState()">
                                </td>
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

        .delete-product-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .delete-product-btn:hover {
            background-color: #c82333;
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

        function confirmDeleteProduct(productId, productName) {
            if (confirm(`Are you sure you want to delete the product "${productName}"? This action cannot be undone.`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const input1 = document.createElement('input');
                input1.type = 'hidden';
                input1.name = 'delete_product';
                input1.value = '1';
                
                const input2 = document.createElement('input');
                input2.type = 'hidden';
                input2.name = 'product_id';
                input2.value = productId;
                
                form.appendChild(input1);
                form.appendChild(input2);
                document.body.appendChild(form);
                form.submit();
            }
        }

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
                form.action = 'seller-products.php';
                
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