<?php
session_start();
include __DIR__ . '/db/config.php';

$pageTitle = 'Profile';
include 'includes/header.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $query = "SELECT password FROM students WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($confirm_password, $user['password'])) {
        $delete_affiliations = "DELETE FROM student_seller_affiliations WHERE student_id = ?";
        $stmt_aff = $conn->prepare($delete_affiliations);
        $stmt_aff->bind_param("i", $student_id);
        $stmt_aff->execute();
        
        $delete_student = "DELETE FROM students WHERE id = ?";
        $stmt_del = $conn->prepare($delete_student);
        $stmt_del->bind_param("i", $student_id);
        
        if ($stmt_del->execute()) {
            session_destroy();
            header("Location: login.php?message=Account deleted successfully");
            exit();
        } else {
            $error = "Failed to delete account. Please try again.";
        }
    } else {
        $error = "Incorrect password. Account deletion cancelled.";
    }
}

$query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Student not found.";
    exit();
}

$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - PHIRSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .profile-container {
            max-width: 650px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease-out;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-header .icon-wrapper {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        .profile-header .icon-wrapper:hover {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .profile-header .icon-wrapper i {
            font-size: 56px;
            color: white;
        }

        .profile-header h2 {
            font-size: 2rem;
            color: #1a1a1a;
            margin-bottom: 10px;
            font-weight: 700;
            animation: fadeIn 0.8s ease-out 0.3s both;
        }

        .profile-header .student-number {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 10px 24px;
            border-radius: 50px;
            color: #667eea;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            animation: fadeIn 0.8s ease-out 0.4s both;
        }

        .profile-header .student-number:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .profile-details {
            margin-bottom: 32px;
        }

        .detail-item {
            display: flex;
            padding: 18px 0;
            border-bottom: 1px solid #f0f0f0;
            animation: fadeIn 0.6s ease-out both;
        }

        .detail-item:nth-child(1) { animation-delay: 0.5s; }
        .detail-item:nth-child(2) { animation-delay: 0.6s; }
        .detail-item:nth-child(3) { animation-delay: 0.7s; }
        .detail-item:nth-child(4) { animation-delay: 0.8s; }
        .detail-item:nth-child(5) { animation-delay: 0.9s; }

        .detail-item:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            margin: 0 -20px;
            padding: 18px 20px;
            border-radius: 12px;
            transform: translateX(5px);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            flex: 0 0 200px;
            font-weight: 700;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .detail-label i {
            width: 24px;
            color: #667eea;
            font-size: 1.1rem;
        }

        .detail-value {
            flex: 1;
            color: #1a1a1a;
            font-size: 1rem;
            word-break: break-word;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 14px;
            margin-top: 32px;
            animation: fadeIn 0.8s ease-out 1s both;
        }

        .btn {
            flex: 1;
            padding: 16px 28px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
        }

        .btn-danger:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.4);
        }

        .btn-secondary:active {
            transform: translateY(-1px);
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
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: white;
            padding: 36px;
            border-radius: 20px;
            width: 90%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: modalSlide 0.4s ease-out;
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .modal-header i {
            font-size: 36px;
            color: #dc3545;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: #1a1a1a;
            font-weight: 700;
        }

        .modal-body {
            margin-bottom: 28px;
        }

        .modal-body p {
            color: #6c757d;
            line-height: 1.7;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
            transform: translateY(-1px);
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
        }

        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Page Transition */
        .page-transition {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .page-transition.active {
            opacity: 1;
            visibility: visible;
        }

        .page-transition-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 36px 28px;
            }

            .profile-header h2 {
                font-size: 1.75rem;
            }

            .detail-label {
                flex: 0 0 160px;
                font-size: 0.9rem;
            }

            .detail-value {
                font-size: 0.95rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .modal-content {
                padding: 28px 24px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .profile-container {
                padding: 28px 20px;
                border-radius: 20px;
            }

            .profile-header .icon-wrapper {
                width: 100px;
                height: 100px;
            }

            .profile-header .icon-wrapper i {
                font-size: 48px;
            }

            .profile-header h2 {
                font-size: 1.5rem;
            }

            .profile-header .student-number {
                padding: 8px 18px;
                font-size: 0.85rem;
            }

            .detail-item {
                flex-direction: column;
                gap: 8px;
                padding: 14px 0;
            }

            .detail-label {
                flex: none;
                font-size: 0.85rem;
            }

            .detail-value {
                font-size: 0.9rem;
            }

            .btn {
                padding: 14px 24px;
                font-size: 0.95rem;
            }

            .modal-content {
                padding: 24px 20px;
            }

            .modal-header h3 {
                font-size: 1.3rem;
            }

            .modal-header i {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>

<!-- Page Transition Overlay -->
<div class="page-transition" id="pageTransition">
    <div class="page-transition-spinner"></div>
</div>

<div class="profile-container">
    <div class="profile-header">
        <div class="icon-wrapper">
            <i class="fas fa-user-circle"></i>
        </div>
        <h2><?= htmlspecialchars($student['student_name']); ?></h2>
        <span class="student-number">
            <i class="fas fa-id-card"></i>
            <?= htmlspecialchars($student['student_number']); ?>
        </span>
    </div>

    <div class="profile-details">
        <div class="detail-item">
            <div class="detail-label">
                <i class="fas fa-building"></i>
                Organization
            </div>
            <div class="detail-value">
                <?= !empty($student['organization']) ? htmlspecialchars($student['organization']) : 'N/A'; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">
                <i class="fas fa-graduation-cap"></i>
                Course & Section
            </div>
            <div class="detail-value">
                <?= !empty($student['course_section']) ? htmlspecialchars($student['course_section']) : 'N/A'; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">
                <i class="fas fa-phone"></i>
                Contact Number
            </div>
            <div class="detail-value">
                <?= !empty($student['contact_number']) ? htmlspecialchars($student['contact_number']) : 'N/A'; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">
                <i class="fas fa-envelope"></i>
                Email
            </div>
            <div class="detail-value">
                <?= !empty($student['email']) ? htmlspecialchars($student['email']) : 'N/A'; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">
                <i class="fas fa-calendar"></i>
                Member Since
            </div>
            <div class="detail-value">
                <?= htmlspecialchars(date('F j, Y', strtotime($student['created_at']))); ?>
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="index.php" class="btn btn-secondary" onclick="pageTransition(event, 'index.php')">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
        <button class="btn btn-danger" onclick="showDeleteModal()">
            <i class="fas fa-trash-alt"></i>
            Delete Account
        </button>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Delete Account</h3>
        </div>
        <div class="modal-body">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <p><strong>Warning:</strong> This action cannot be undone. Your account and all associated data will be permanently deleted.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Enter your password to confirm
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Enter your password">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" name="delete_account" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i>
                        Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Page transition function
    function pageTransition(event, url) {
        event.preventDefault();
        const transition = document.getElementById('pageTransition');
        transition.classList.add('active');
        
        setTimeout(() => {
            window.location.href = url;
        }, 500);
    }

    function showDeleteModal() {
        document.getElementById('deleteModal').classList.add('show');
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('deleteModal');
        if (event.target == modal) {
            hideDeleteModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideDeleteModal();
        }
    });

    <?php if (isset($error)): ?>
        // Show modal if there's an error
        showDeleteModal();
    <?php endif; ?>

    // Add smooth scroll behavior
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
</script>

</body>
</html>