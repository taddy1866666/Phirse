<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';

$seller_id = $_SESSION['seller_id'];
$success_message = '';
$error_message = '';
$password_error = '';
$password_success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters long.";
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM sellers WHERE id = ?");
            $stmt->execute([$seller_id]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $seller['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_stmt = $pdo->prepare("UPDATE sellers SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $seller_id]);
                $password_success = "Password changed successfully!";
            } else {
                $password_error = "Current password is incorrect.";
            }
        } catch(PDOException $e) {
            $password_error = "Error changing password: " . $e->getMessage();
        }
    }
}

// Get current seller info
try {
    $stmt = $pdo->prepare("SELECT seller_name, organization, contact_number, logo_path, adviser FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set variables for sidebar
    $seller_logo = !empty($seller_info['logo_path']) ? $seller_info['logo_path'] : null;
    $organization_name = $seller_info['organization'] ?? $_SESSION['organization'] ?? '';
    $seller_name = $seller_info['seller_name'] ?? $_SESSION['seller_name'] ?? '';
} catch(PDOException $e) {
    $error_message = "Error fetching seller info: " . $e->getMessage();
}

// Handle form submission (only for profile updates, not password changes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $seller_name = trim($_POST['seller_name'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $adviser = trim($_POST['adviser'] ?? '');
    
    // Validation
    if (empty($seller_name)) {
        $error_message = "Seller name is required.";
    } elseif (empty($organization)) {
        $error_message = "Organization name is required.";
    } elseif (strlen($organization) < 3) {
        $error_message = "Organization name must be at least 3 characters long.";
    } elseif (empty($contact_number)) {
        $error_message = "Contact number is required.";
    } elseif (strlen($contact_number) !== 11 || !preg_match('/^09[0-9]{9}$/', $contact_number)) {
        $error_message = "Contact number must start with 09 and be exactly 11 digits.";
    } else {
        try {
            $logo_path = $seller_info['logo_path']; // Keep existing logo by default
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/logos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    // Delete old logo if exists
                    if ($seller_info['logo_path'] && file_exists($seller_info['logo_path'])) {
                        unlink($seller_info['logo_path']);
                    }
                    
                    $logo_filename = uniqid() . '.' . $file_extension;
                    $logo_path = $upload_dir . $logo_filename;
                    move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path);
                } else {
                    $error_message = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                }
            }
            
            if (empty($error_message)) {
                $stmt = $pdo->prepare("UPDATE sellers SET seller_name = ?, organization = ?, contact_number = ?, logo_path = ?, adviser = ? WHERE id = ?");
                $stmt->execute([$seller_name, $organization, $contact_number, $logo_path, $adviser, $seller_id]);
                
                // Update session
                $_SESSION['seller_name'] = $seller_name;
                $_SESSION['organization'] = $organization;
                
                $success_message = "Profile updated successfully!";
                
                // Refresh seller info
                $seller_info['seller_name'] = $seller_name;
                $seller_info['organization'] = $organization;
                $seller_info['contact_number'] = $contact_number;
                $seller_info['logo_path'] = $logo_path;
                $seller_info['adviser'] = $adviser;
            }
        } catch(PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Profile';
include 'includes/seller-header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Hide sidebar for full-width centered layout */
    .sidebar {
        display: none;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        display: flex;
    }

    .main-content {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 40px);
        width: 100%;
    }

    .container {
        max-width: 900px;
        width: 100%;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        padding: 50px;
        animation: slideUp 0.5s ease-out;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: start;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .header {
        display: flex;
        align-items: center;
        margin-bottom: 36px;
        gap: 16px;
        padding-bottom: 24px;
        border-bottom: 2px solid #f0f0f0;
    }

    .header i {
        font-size: 32px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .header h1 {
        font-size: 28px;
        color: #0f172a;
        font-weight: 700;
    }

    .alert {
        padding: 16px 18px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-weight: 500;
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

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: 1px solid #b1dfbb;
        border-left: 4px solid #28a745;
    }

    .alert-error {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border: 1px solid #f1b0b7;
        border-left: 4px solid #dc3545;
    }

    .form-section {
        margin-bottom: 40px;
    }

    .form-section:last-of-type {
        margin-bottom: 0;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
    }

    .form-section-title i {
        color: #667eea;
        font-size: 18px;
    }

    form {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px 50px;
    }

    form > .form-section:nth-child(1) {
        grid-column: 1;
    }

    form > .form-section:nth-child(2) {
        grid-column: 2;
    }

    form > .form-actions {
        grid-column: 1 / -1;
        margin-top: 20px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #1e293b;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #f8fafc;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="file"]:focus,
    .form-group input[type="password"]:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }

    .form-group input[type="password"] {
        font-family: monospace;
        letter-spacing: 0.1em;
    }

    .logo-preview {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 20px 0;
        padding: 24px;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        min-height: 140px;
    }

    .logo-preview img {
        max-width: 120px;
        max-height: 120px;
        border-radius: 12px;
        object-fit: contain;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 14px;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    .btn {
        flex: 1;
        min-width: 140px;
        padding: 14px 24px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .btn:active {
        transform: translateY(0);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        flex: 2;
    }

    .btn-primary:hover {
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .btn-secondary:hover {
        background: #f8fafc;
    }

    .btn-secondary.danger {
        color: #dc3545;
        border-color: #dc3545;
    }

    .btn-secondary.danger:hover {
        background: #fff5f5;
    }

    .info-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
        font-weight: 400;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            width: 100%;
            padding: 20px;
        }

        .container {
            padding: 30px 24px;
            max-width: 100%;
        }

        .header {
            margin-bottom: 28px;
        }

        .header h1 {
            font-size: 24px;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <i class="fas fa-user-edit"></i>
                <h1>Edit Profile</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Profile Information Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-building"></i>
                        Basic Information
                    </div>

                    <div class="form-group">
                        <label for="seller_name">
                            <i class="fas fa-user"></i> Seller Name <span style="color: #dc3545;">*</span>
                        </label>
                        <input type="text" id="seller_name" name="seller_name" value="<?php echo htmlspecialchars($seller_info['seller_name'] ?? ''); ?>" required placeholder="Enter your name">
                    </div>

                    <div class="form-group">
                        <label for="organization">
                            <i class="fas fa-store"></i> Organization Name <span style="color: #dc3545;">*</span>
                        </label>
                        <input type="text" id="organization" name="organization" value="<?php echo htmlspecialchars($seller_info['organization'] ?? ''); ?>" required minlength="3" placeholder="Enter organization name">
                        <p class="info-text">Minimum 3 characters</p>
                    </div>

                    <div class="form-group">
                        <label for="adviser">
                            <i class="fas fa-user-tie"></i> Adviser
                        </label>
                        <input type="text" id="adviser" name="adviser" value="<?php echo htmlspecialchars($seller_info['adviser'] ?? ''); ?>" placeholder="Enter adviser name">
                    </div>

                    <div class="form-group">
                        <label for="contact_number">
                            <i class="fas fa-phone"></i> Contact Number <span style="color: #dc3545;">*</span>
                        </label>
                        <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($seller_info['contact_number'] ?? ''); ?>" placeholder="09XXXXXXXXX" required maxlength="11" pattern="09[0-9]{9}">
                        <p class="info-text">Format: 09XXXXXXXXX (11 digits)</p>
                    </div>
                </div>

                <!-- Logo Upload Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-image"></i>
                        Organization Logo
                    </div>

                    <?php if (!empty($seller_info['logo_path']) && file_exists($seller_info['logo_path'])): ?>
                        <div class="logo-preview">
                            <img src="<?php echo htmlspecialchars($seller_info['logo_path']); ?>" alt="Current Logo">
                        </div>
                        <p class="info-text" style="text-align: center; margin-bottom: 20px;">Current logo displayed above</p>
                    <?php else: ?>
                        <div class="logo-preview">
                            <div style="text-align: center; color: #94a3b8;">
                                <i class="fas fa-image" style="font-size: 40px; margin-bottom: 10px;"></i>
                                <p>No logo uploaded</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="logo">
                            <i class="fas fa-upload"></i> Upload New Logo
                        </label>
                        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif">
                        <p class="info-text">Supported formats: JPG, PNG, GIF (Max size: 2MB)</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="showChangePasswordModal()">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                    <a href="seller-dashboard.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 16px; padding: 50px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.25); animation: modalSlideUp 0.3s ease-out;">
            <div style="display: flex; align-items: center; margin-bottom: 32px; gap: 14px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-lock" style="font-size: 24px; color: white;"></i>
                </div>
                <div>
                    <h2 style="font-size: 22px; color: #0f172a; margin: 0; font-weight: 700;">Change Password</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">Update your security credentials</p>
                </div>
            </div>

            <?php if (!empty($password_error)): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($password_error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($password_success)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($password_success); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="current_password">
                        <i class="fas fa-key"></i> Current Password <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-lock"></i> New Password <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Create a new password">
                    <p class="info-text">Minimum 6 characters</p>
                </div>

                <div class="form-group">
                    <label for="confirm_new_password">
                        <i class="fas fa-lock"></i> Confirm Password <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="6" placeholder="Confirm your new password">
                </div>

                <div class="form-actions" style="margin-top: 32px;">
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-check"></i> Update Password
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="hideChangePasswordModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        function showChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'flex';
        }

        function hideChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
            // Clear form fields
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_new_password').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('changePasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideChangePasswordModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideChangePasswordModal();
            }
        });

        <?php if (!empty($password_error) || !empty($password_success)): ?>
            // Show modal if there's a password message
            showChangePasswordModal();
        <?php endif; ?>
    </script>

    <script>
        // Auto-capitalize seller name to UPPERCASE
        const sellerNameInput = document.getElementById('seller_name');
        if (sellerNameInput) {
            sellerNameInput.addEventListener('input', function() {
                // Convert to uppercase
                this.value = this.value.toUpperCase();
            });
        }

        // Contact number validation - only allow numbers
        const contactNumberInput = document.getElementById('contact_number');
        if (contactNumberInput) {
            contactNumberInput.addEventListener('input', function(e) {
                // Remove any non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limit to 11 digits
                if (this.value.length > 11) {
                    this.value = this.value.substring(0, 11);
                }
            });
        }
    </script>
</body>
</html>
<?php
// Close seller-header if needed
?>