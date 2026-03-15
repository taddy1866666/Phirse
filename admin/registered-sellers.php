<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';
$pageTitle = 'Registered Sellers';
include 'includes/header.php';

$message = '';
$error = '';

// Get message from session and clear it
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get error from session and clear it
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $seller_id = $_POST['seller_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';
    
    if (empty($seller_id)) {
        $_SESSION['error'] = "Invalid seller ID.";
    } elseif (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters long.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE sellers SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $seller_id]);
            $_SESSION['message'] = "Password reset successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error resetting password: " . $e->getMessage();
        }
    }
    header('Location: registered-sellers.php');
    exit();
}

// Handle add seller request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_seller'])) {
    $seller_name = trim($_POST['seller_name']);
    $organization = trim($_POST['organization']);
    $adviser = trim($_POST['adviser']);
    $contact_number = trim($_POST['contact_number']);
    $password = $_POST['password'];
    
    if (!empty($seller_name) && !empty($organization) && !empty($adviser) && !empty($contact_number) && !empty($password)) {
        // Validate minimum length requirements
        if (strlen($seller_name) < 4) {
            $_SESSION['error'] = "Seller name must be at least 4 characters long.";
        } elseif (strlen($organization) < 3) {
            $_SESSION['error'] = "Organization name must be at least 3 characters long.";
        } elseif (strlen($adviser) < 3) {
            $_SESSION['error'] = "Adviser name must be at least 3 characters long.";
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = "Password must be at least 6 characters long.";
        } elseif (strlen($contact_number) !== 12 || !preg_match('/^09[0-9]{10}$/', $contact_number)) {
            $_SESSION['error'] = "Contact number must start with 09 and be exactly 12 digits.";
        } else {
        try {
            // Handle logo upload
            $logo_path = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/logos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $logo_filename = uniqid() . '.' . $file_extension;
                    $logo_path = $upload_dir . $logo_filename;
                    move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO sellers (seller_name, organization, adviser, contact_number, password, logo_path) VALUES (?, ?, ?, ?, ?, ?)");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$seller_name, $organization, $adviser, $contact_number, $hashed_password, $logo_path]);
            
            $_SESSION['message'] = "Seller added successfully!";
            header('Location: registered-sellers.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error adding seller: " . $e->getMessage();
        }
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
    header('Location: registered-sellers.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $seller_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);
        $_SESSION['message'] = "Seller deleted successfully!";
        header('Location: registered-sellers.php');
        exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error deleting seller: " . $e->getMessage();
        }
        header('Location: registered-sellers.php');
        exit();
    }

// Handle bulk delete request
if (isset($_POST['delete_multiple'])) {
    try {
        $ids = json_decode($_POST['delete_multiple'], true);
        
        if (!is_array($ids) || empty($ids)) {
            throw new Exception("Invalid selection");
        }
        
        // Validate all IDs are numeric
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                throw new Exception("Invalid seller ID");
            }
        }
        
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Delete all selected sellers
        $stmt = $pdo->prepare("DELETE FROM sellers WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $_SESSION['message'] = "Successfully deleted " . count($ids) . " seller(s)!";
        header('Location: registered-sellers.php');
        exit();
    } catch(Exception $e) {
        $_SESSION['error'] = "Error deleting sellers: " . $e->getMessage();
        header('Location: registered-sellers.php');
        exit();
    }
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE seller_name LIKE ? OR organization LIKE ? OR contact_number LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Get sellers from database
try {
    $sql = "SELECT * FROM sellers $where_clause ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $sellers = [];
    $error = "Error fetching sellers: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Organization - Admin Dashboard</title>
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
            align-items: flex-start;
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
            align-items: center;
            gap: 12px;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            width: 300px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .search-wrapper:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: none;
            font-size: 14px;
            background: transparent;
            color: #1a202c;
            outline: none;
            width: 100%;
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

        .export-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .notification-bell {
            position: relative;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            color: #4a5568;
        }

        .notification-bell:hover {
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 600;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
        }
        
        .sellers-table-container {
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
        
        .seller-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .sellers-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .sellers-table th {
            background-color: #f8fafc;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .sellers-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .sellers-table tr:hover {
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        
        .delete-btn {
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
        }
        
        .delete-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        
        .reset-password-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            margin-right: 8px;
        }
        
        .reset-password-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .seller-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .logo-placeholder {
            width: 50px;
            height: 50px;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .logo-placeholder i {
            font-size: 16px;
            margin-bottom: 2px;
        }
        
        .logo-placeholder span {
            font-size: 8px;
            text-align: center;
            line-height: 1;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
        
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
        
        .message-close {
            background: none;
            border: none;
            color: #155724;
            cursor: pointer;
            font-size: 20px;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.2s;
        }
        
        .message-close:hover {
            opacity: 0.7;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-left-color: #dc3545;
        }
        
        .error .message-close {
            color: #721c24;
        }
        
        .windows-activation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #ccc;
            font-size: 12px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-content h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            border: none;
            background: none;
            padding: 5px;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: none;
            border-bottom: 2px solid #333;
            font-size: 16px;
            background: transparent;
            outline: none;
        }
        
        .form-group input:focus {
            border-bottom-color: #555;
        }
        
        .file-upload-group {
            margin: 20px 0;
        }
        
        .file-upload-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
        }
        
        .file-upload-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .image-preview-container {
            margin-top: 12px;
            display: none;
            text-align: center;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 8px;
            border: 2px dashed #ccc;
        }
        
        .image-preview-container.show {
            display: block;
        }
        
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            width: 150px;
            height: 150px;
            border-radius: 8px;
            border: 2px solid #ddd;
            padding: 4px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            object-fit: contain;
            background: white;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .image-preview-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .clear-preview-btn {
            background: #e8e8e8;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            color: #333;
            transition: all 0.2s;
        }
        
        .clear-preview-btn:hover {
            background: #d0d0d0;
        }
        
        .register-btn {
            width: 100%;
            background-color: #333;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .register-btn:hover {
            background-color: #555;
            transform: translateY(-2px);
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        .submit-btn,
        .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn {
            background-color: #3b82f6;
            color: white;
        }
        
        .submit-btn:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }
        
        .cancel-btn {
            background-color: #e5e7eb;
            color: #333;
        }
        
        .cancel-btn:hover {
            background-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        .register-btn:hover {
            background-color: #555;
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

            .search-input {
                width: 100%;
            }

            .sellers-table-container {
                overflow-x: auto;
            }

            .sellers-table {
                min-width: 600px;
            }

            .sellers-table th,
            .sellers-table td {
                padding: 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 10px 10px;
            }

            .page-title {
                font-size: 18px;
            }

            .sellers-table th,
            .sellers-table td {
                padding: 10px;
                font-size: 12px;
            }

            .seller-logo,
            .logo-placeholder {
                width: 40px;
                height: 40px;
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
                <h1>Organizations</h1>
                <div class="dashboard-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <div class="search-wrapper">
                    <form method="GET" style="display: flex; width: 100%;">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search organizations..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <button class="export-btn" onclick="showModal()">
                    <i class="fas fa-plus"></i>
                    Add Organization
                </button>
            </div>
        </div>
            
        <?php if (!empty($message)): ?>
            <div class="message" id="messageAlert">
                <span><?php echo htmlspecialchars($message); ?></span>
                <button type="button" class="message-close" onclick="closeMessage()">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error" id="errorAlert">
                <span><?php echo htmlspecialchars($error); ?></span>
                <button type="button" class="message-close" onclick="closeMessage(event)">&times;</button>
            </div>
        <?php endif; ?>

        <div class="sellers-table-container">
            <?php if (empty($sellers)): ?>
                <div class="no-data">
                    No sellers found<?php echo !empty($search) ? ' for your search criteria' : ''; ?>.
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
                <table class="sellers-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"></th>
                            <th>ID</th>
                            <th>Seller Name</th>
                            <th>Organization</th>
                            <th>Logo</th>
                            <th>Contact Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers as $seller): ?>
                            <tr class="seller-row" data-seller-id="<?php echo $seller['id']; ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" class="seller-checkbox" value="<?php echo $seller['id']; ?>" onchange="updateSelectAllState()">
                                </td>
                                <td><?php echo htmlspecialchars($seller['id']); ?></td>
                                <td><?php echo htmlspecialchars($seller['seller_name']); ?></td>
                                <td><?php echo htmlspecialchars($seller['organization']); ?></td>
                                <td>
                                    <?php if (!empty($seller['logo_path']) && file_exists($seller['logo_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($seller['logo_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($seller['organization']); ?> Logo" 
                                             class="seller-logo">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <i class="fas fa-building"></i>
                                            <span>No Logo</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($seller['contact_number']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideResetPasswordModal()">&times;</button>
            <h2>Reset Seller Password</h2>
            <form method="POST">
                <input type="hidden" name="seller_id" id="resetSellerIdInput">
                <input type="hidden" name="reset_password" value="1">
                
                <div class="form-group">
                    <label>Organization:</label>
                    <input type="text" id="resetOrganization" disabled style="background-color: #f0f0f0; cursor: not-allowed;">
                </div>
                
                <div class="form-group">
                    <label>Seller Name:</label>
                    <input type="text" id="resetSellerName" disabled style="background-color: #f0f0f0; cursor: not-allowed;">
                </div>
                
                <div class="form-group">
                    <label for="resetNewPassword">New Password:</label>
                    <input type="password" name="new_password" id="resetNewPassword" required minlength="6" title="Password must be at least 6 characters long">
                </div>
                
                <div class="form-group">
                    <label for="resetConfirmPassword">Confirm Password:</label>
                    <input type="password" name="confirm_new_password" id="resetConfirmPassword" required minlength="6" title="Password must be at least 6 characters long">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="hideResetPasswordModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Reset Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Seller Modal -->
    <div id="addSellerModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideModal()">&times;</button>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="organization">Organization Name:</label>
                    <input type="text" name="organization" id="organization" required minlength="3" title="Organization name must be at least 3 characters long">
                </div>
                
                <div class="form-group">
                    <label for="adviser">Adviser:</label>
                    <input type="text" name="adviser" id="adviser" required minlength="3" title="Adviser name must be at least 3 characters long">
                </div>
                
                <div class="form-group">
                    <label for="seller_name">Seller Name:</label>
                    <input type="text" name="seller_name" id="seller_name" required minlength="4" title="Seller name must be at least 4 characters long">
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number:</label>
                    <input type="tel" name="contact_number" id="contact_number" required pattern="09[0-9]{10}" title="Contact number must start with 09 and be exactly 12 digits" inputmode="numeric" placeholder="09123456789" maxlength="12">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required minlength="6" title="Password must be at least 6 characters long">
                </div>
                
                <div class="file-upload-group">
                    <label for="logo">Upload Organization Logo:</label>
                    <input type="file" name="logo" id="logo" class="file-upload-input" accept="image/*" onchange="previewImage(event)">
                    <div class="image-preview-container" id="previewContainer">
                        <img id="previewImage" class="image-preview" alt="Logo preview">
                        <div class="image-preview-info" id="previewInfo"></div>
                        <button type="button" class="clear-preview-btn" onclick="clearPreview()">Clear Preview</button>
                    </div>
                </div>
                
                <button type="submit" name="add_seller" class="register-btn">Register</button>
            </form>
        </div>
    </div>

    <script>
        // Image preview function
        function previewImage(event) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById('previewContainer');
            const previewImage = document.getElementById('previewImage');
            const previewInfo = document.getElementById('previewInfo');
            
            if (file) {
                // Check if file is an image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewContainer.classList.add('show');
                        
                        // Display file info
                        const sizeInKB = (file.size / 1024).toFixed(2);
                        previewInfo.textContent = `${file.name} (${sizeInKB} KB)`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.classList.remove('show');
                    previewInfo.textContent = 'Please select an image file';
                    alert('Please select an image file (JPG, PNG, GIF)');
                }
            } else {
                previewContainer.classList.remove('show');
            }
        }
        
        // Clear preview function
        function clearPreview() {
            document.getElementById('logo').value = '';
            document.getElementById('previewContainer').classList.remove('show');
            document.getElementById('previewImage').src = '';
            document.getElementById('previewInfo').textContent = '';
        }
        
        // Message auto-dismiss
        function closeMessage(event) {
            if (event) event.target.closest('.message').remove();
            else {
                const messageAlert = document.getElementById('messageAlert');
                if (messageAlert) messageAlert.remove();
            }
        }
        
        // Auto-close messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messageAlert = document.getElementById('messageAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (messageAlert) {
                setTimeout(() => {
                    messageAlert.style.animation = 'slideUp 0.3s ease-out forwards';
                    setTimeout(() => messageAlert.remove(), 300);
                }, 5000);
            }
            
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.style.animation = 'slideUp 0.3s ease-out forwards';
                    setTimeout(() => errorAlert.remove(), 300);
                }, 7000);
            }
        });
        
        function showResetPasswordModal(sellerId, sellerName, organization) {
            document.getElementById('resetSellerIdInput').value = sellerId;
            document.getElementById('resetOrganization').value = organization;
            document.getElementById('resetSellerName').value = sellerName;
            document.getElementById('resetNewPassword').value = '';
            document.getElementById('resetConfirmPassword').value = '';
            document.getElementById('resetPasswordModal').classList.add('show');
        }
        
        function hideResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('show');
        }
        
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete seller "${name}"? This action cannot be undone.`)) {
                window.location.href = `registered-sellers.php?delete=${id}`;
            }
        }
        
        function toggleSelectAll(checkbox) {
            const allCheckboxes = document.querySelectorAll('.seller-checkbox');
            allCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateDeleteButtonState();
        }
        
        function updateSelectAllState() {
            const allCheckboxes = document.querySelectorAll('.seller-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkedCount = document.querySelectorAll('.seller-checkbox:checked').length;
            
            selectAllCheckbox.checked = checkedCount === allCheckboxes.length && allCheckboxes.length > 0;
            updateDeleteButtonState();
        }
        
        function updateDeleteButtonState() {
            const checkedCheckboxes = document.querySelectorAll('.seller-checkbox:checked');
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            
            if (checkedCheckboxes.length > 0) {
                deleteBtn.style.display = 'flex';
            } else {
                deleteBtn.style.display = 'none';
            }
        }
        
        function deleteSelected() {
            const checkedCheckboxes = document.querySelectorAll('.seller-checkbox:checked');
            if (checkedCheckboxes.length === 0) {
                alert('Please select at least one seller to delete.');
                return;
            }
            
            const selectedIds = Array.from(checkedCheckboxes).map(cb => cb.value);
            const count = selectedIds.length;
            
            if (confirm(`Are you sure you want to delete ${count} seller(s)? This action cannot be undone.`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'registered-sellers.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_multiple';
                input.value = JSON.stringify(selectedIds);
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function hideModal() {
            document.getElementById('addSellerModal').classList.remove('show');
            // Don't clear the form - let user keep their selections
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var addSellerModal = document.getElementById('addSellerModal');
            var resetPasswordModal = document.getElementById('resetPasswordModal');
            if (event.target == addSellerModal) {
                hideModal();
            }
            if (event.target == resetPasswordModal) {
                hideResetPasswordModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideModal();
                hideResetPasswordModal();
            }
        });
        
        function setupFormValidation() {
            const contactNumber = document.getElementById('contact_number');
            const password = document.getElementById('password');
            const organization = document.getElementById('organization');
            const sellerName = document.getElementById('seller_name');
            const form = document.querySelector('form[method="POST"]');
            
            // Organization name validation
            if (organization) {
                organization.addEventListener('input', function(e) {
                    if (this.value.length > 0 && this.value.length < 3) {
                        this.setCustomValidity('Organization name must be at least 3 characters long');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            // Seller name validation
            if (sellerName) {
                sellerName.addEventListener('input', function(e) {
                    if (this.value.length > 0 && this.value.length < 4) {
                        this.setCustomValidity('Seller name must be at least 4 characters long');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            if (contactNumber) {
                // Contact number validation - must start with 09 and be exactly 12 digits
                contactNumber.addEventListener('input', function(e) {
                    // Remove any non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limit to 12 digits
                    if (this.value.length > 12) {
                        this.value = this.value.substring(0, 12);
                    }
                    
                    // Validate format
                    if (this.value.length > 0 && this.value.length < 12) {
                        this.setCustomValidity('Contact number must be exactly 12 digits');
                    } else if (this.value.length === 12 && !this.value.startsWith('09')) {
                        this.setCustomValidity('Contact number must start with 09');
                    } else if (this.value.length === 12 && this.value.startsWith('09')) {
                        this.setCustomValidity('');
                    } else if (this.value.length === 0) {
                        this.setCustomValidity('');
                    }
                });
                
                // Prevent pasting non-numeric content
                contactNumber.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 12) {
                            this.value = this.value.substring(0, 12);
                        }
                    }, 10);
                });
                
                // Prevent typing non-numeric characters
                contactNumber.addEventListener('keypress', function(e) {
                    // Allow backspace, delete, tab, escape, enter
                    if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                        // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.keyCode === 65 && e.ctrlKey === true) ||
                        (e.keyCode === 67 && e.ctrlKey === true) ||
                        (e.keyCode === 86 && e.ctrlKey === true) ||
                        (e.keyCode === 88 && e.ctrlKey === true)) {
                        return;
                    }
                    // Ensure that it is a number and stop the keypress
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            }
            
            if (password) {
                // Password validation
                password.addEventListener('input', function(e) {
                    if (this.value.length < 6 && this.value.length > 0) {
                        this.setCustomValidity('Password must be at least 6 characters long');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            if (form) {
                // Form validation before submit - only for the add seller form
                form.addEventListener('submit', function(e) {
                    let hasError = false;
                    let errorMessage = '';
                    
                    // Check organization name
                    if (organization && organization.value.length < 3) {
                        hasError = true;
                        errorMessage = 'Organization name must be at least 3 characters long';
                        organization.focus();
                    }
                    // Check seller name
                    else if (sellerName && sellerName.value.length < 4) {
                        hasError = true;
                        errorMessage = 'Seller name must be at least 4 characters long';
                        sellerName.focus();
                    }
                    // Check contact number
                    else if (contactNumber && (contactNumber.value.length !== 12 || !contactNumber.value.startsWith('09'))) {
                        hasError = true;
                        errorMessage = 'Contact number must start with 09 and be exactly 12 digits';
                        contactNumber.focus();
                    }
                    // Check password
                    else if (password && password.value.length < 6) {
                        hasError = true;
                        errorMessage = 'Password must be at least 6 characters long';
                        password.focus();
                    }
                    
                    if (hasError) {
                        e.preventDefault();
                        alert(errorMessage);
                        return false;
                    }
                });
            }
        }
        
        // Setup form validation when modal is shown
        function showModal() {
            document.getElementById('addSellerModal').classList.add('show');
            setupFormValidation();
            
            // Show preview if file is already selected
            const logoInput = document.getElementById('logo');
            if (logoInput.files.length > 0) {
                const event = { target: logoInput };
                previewImage(event);
            }
        }
        
        // Auto-show reset password modal if there's an error
        <?php if (!empty($error)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const resetPasswordModal = document.getElementById('resetPasswordModal');
                if (resetPasswordModal && document.querySelector('.message.error')) {
                    // There's an error, show modal if it's related to password reset
                    const sellerIdInput = document.getElementById('resetSellerIdInput');
                    if (sellerIdInput && sellerIdInput.value) {
                        showResetPasswordModal(sellerIdInput.value, document.getElementById('resetSellerName').value || 'Seller', document.getElementById('resetOrganization').value || '');
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>