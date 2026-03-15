<?php
session_start();

if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

require_once '../database/config.php';
$pageTitle = 'Registered Students';
include 'includes/seller-header.php';

// Get seller info
try {
    $seller_id = $_SESSION['seller_id'];
    
    // Get seller information including logo
    $stmt = $pdo->prepare("SELECT seller_name, organization, logo_path FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $seller_logo = $seller_info['logo_path'] ?? null;
    $organization_name = $seller_info['organization'] ?? $_SESSION['organization'] ?? '';
    $seller_name = $seller_info['seller_name'] ?? $_SESSION['seller_name'] ?? '';
    
} catch(PDOException $e) {
    $seller_logo = null;
    $organization_name = $_SESSION['organization'] ?? '';
    $seller_name = $_SESSION['seller_name'] ?? '';
}

// Handle add student request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $student_number = trim($_POST['student_number']);
    $student_name = trim($_POST['student_name']);
    $organization = $organization_name; // Use logged-in seller's organization
    $course_section = trim($_POST['course_section']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($student_number) && !empty($student_name) && !empty($organization) && !empty($password)) {
        try {
            // Check if student number already exists
            $check_stmt = $pdo->prepare("SELECT id, contact_number FROM students WHERE student_number = ?");
            $check_stmt->execute([$student_number]);
            $existing_student = $check_stmt->fetch(PDO::FETCH_ASSOC);

            // Check if contact number is provided and already used by another student
            if (!empty($contact_number)) {
                $contact_check = $pdo->prepare("SELECT id FROM students WHERE contact_number = ?");
                $contact_check->execute([$contact_number]);
                $contact_conflict = $contact_check->fetch(PDO::FETCH_ASSOC);
                if ($contact_conflict && (!$existing_student || $contact_conflict['id'] != $existing_student['id'])) {
                    $error = "Contact number already exists for another student.";
                }
            }

            if (isset($error) && $error !== '') {
                // contact conflict detected
            } elseif ($existing_student) {
                // Student already exists. Create affiliation if not exists.
                $existing_id = $existing_student['id'];
                $aff_check = $pdo->prepare("SELECT id FROM student_seller_affiliations WHERE student_id = ? AND seller_id = ?");
                $aff_check->execute([$existing_id, $seller_id]);

                if ($aff_check->fetch()) {
                    $error = "Student number already exists and is already registered with your organization.";
                } else {
                    // Create affiliation
                    $affiliation_stmt = $pdo->prepare("INSERT INTO student_seller_affiliations (student_id, seller_id) VALUES (?, ?)");
                    $affiliation_stmt->execute([$existing_id, $seller_id]);
                    header('Location: registered-students.php?message=Student linked to your organization');
                    exit();
                }
            } else {
                // New student - ensure contact number is not used
                if (!empty($contact_number)) {
                    $contact_check2 = $pdo->prepare("SELECT id FROM students WHERE contact_number = ?");
                    $contact_check2->execute([$contact_number]);
                    if ($contact_check2->fetch()) {
                        $error = "Contact number already exists for another student.";
                    }
                }
            
                if (empty($error)) {
                    $stmt = $pdo->prepare("INSERT INTO students (student_number, student_name, organization, course_section, contact_number, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->execute([$student_number, $student_name, $organization, $course_section, $contact_number, $email, $hashed_password]);
                    
                    // Get the inserted student ID
                    $student_id = $pdo->lastInsertId();
                    
                    // Create affiliation with current seller
                    $affiliation_stmt = $pdo->prepare("INSERT INTO student_seller_affiliations (student_id, seller_id) VALUES (?, ?)");
                    $affiliation_stmt->execute([$student_id, $seller_id]);
                    
                    header('Location: registered-students.php?message=Student added successfully');
                    exit();
                }
            }
        } catch(PDOException $e) {
            $error = "Error adding student: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $student_id = $_GET['delete'];
    try {
        // Delete affiliation first
        $stmt_aff = $pdo->prepare("DELETE FROM student_seller_affiliations WHERE student_id = ? AND seller_id = ?");
        $stmt_aff->execute([$student_id, $seller_id]);
        
        // Check if student has other affiliations
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM student_seller_affiliations WHERE student_id = ?");
        $check_stmt->execute([$student_id]);
        $affiliation_count = $check_stmt->fetchColumn();
        
        // Only delete student if no other affiliations exist
        if ($affiliation_count == 0) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
        }
        
        header('Location: registered-students.php?message=Student deleted successfully');
        exit();
    } catch(PDOException $e) {
        $error = "Error deleting student: " . $e->getMessage();
    }
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
                throw new Exception("Invalid student ID");
            }
        }
        
        // Delete affiliations for all selected students
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt_aff = $pdo->prepare("DELETE FROM student_seller_affiliations WHERE student_id IN ($placeholders) AND seller_id = ?");
        $ids_with_seller = array_merge($ids, [$seller_id]);
        $stmt_aff->execute($ids_with_seller);
        
        // Delete students that have no other affiliations
        foreach ($ids as $student_id) {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM student_seller_affiliations WHERE student_id = ?");
            $check_stmt->execute([$student_id]);
            $affiliation_count = $check_stmt->fetchColumn();
            
            if ($affiliation_count == 0) {
                $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$student_id]);
            }
        }
        
        $_SESSION['message'] = "Successfully deleted " . count($ids) . " student(s)!";
        header('Location: registered-students.php');
        exit();
    } catch(Exception $e) {
        $_SESSION['error'] = "Error deleting students: " . $e->getMessage();
        header('Location: registered-students.php');
        exit();
    }
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $reset_error = '';
    $reset_success = '';
    
    if ($student_id && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $reset_error = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $reset_error = "Password must be at least 6 characters long.";
        } else {
            try {
                // Verify student belongs to this seller
                $check_stmt = $pdo->prepare("
                    SELECT s.id FROM students s
                    JOIN student_seller_affiliations ssa ON s.id = ssa.student_id
                    WHERE s.id = ? AND ssa.seller_id = ?
                ");
                $check_stmt->execute([$student_id, $seller_id]);
                
                if ($check_stmt->fetch()) {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_password, $student_id]);
                    $reset_success = "Password reset successfully!";
                } else {
                    $reset_error = "Unauthorized: Student not found in your organization.";
                }
            } catch(PDOException $e) {
                $reset_error = "Error resetting password: " . $e->getMessage();
            }
        }
    } else {
        $reset_error = "Please fill in all fields.";
    }
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$year_level_filter = isset($_GET['year_level']) ? trim($_GET['year_level']) : '';
$section_filter = isset($_GET['section']) ? trim($_GET['section']) : '';
$where_conditions = [];
$params = [];

// Always filter by seller ID using the affiliations table
$where_conditions[] = "ssa.seller_id = ?";
$params[] = $seller_id;

// Add search filter if provided
if (!empty($search)) {
    $where_conditions[] = "(s.student_number LIKE ? OR s.student_name LIKE ? OR s.course_section LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add year level filter if provided
if (!empty($year_level_filter)) {
    $where_conditions[] = "s.course_section LIKE ?";
    $params[] = $year_level_filter . "-%";
}

// Add section filter if provided
if (!empty($section_filter)) {
    $where_conditions[] = "s.course_section LIKE ?";
    $params[] = "%-" . $section_filter;
}

// Build WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get students from database using JOIN with affiliations table
try {
    $sql = "SELECT s.*
            FROM students s
            INNER JOIN student_seller_affiliations ssa ON s.id = ssa.student_id
            $where_clause
            ORDER BY s.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $students = [];
    $error = "Error fetching students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Students - Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f7;
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
            padding: 20px 30px;
            border-bottom: 1px solid #e5e5e7;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            font-size: 16px;
            color: #333;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 30px;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .register-btn,
        .upload-btn,
        .template-btn {
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .register-btn {
            background-color: #000;
        }

        .register-btn:hover {
            background-color: #333;
        }

        .upload-btn {
            background-color: #16a34a;
        }

        .upload-btn:hover {
            background-color: #15803d;
        }

        .template-btn {
            background-color: #2563eb;
        }

        .template-btn:hover {
            background-color: #1d4ed8;
        }

        .register-btn i,
        .upload-btn i,
        .template-btn i {
            margin-right: 8px;
        }
        
        .search-container {
            margin-bottom: 30px;
        }
        
        .search-input {
            width: 100%;
            max-width: 500px;
            padding: 12px 16px;
            border: 1px solid #d2d2d7;
            border-radius: 8px;
            font-size: 16px;
            background-color: white;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        }
        
        .students-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e5e5e7;
        }
        
        .select-all-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #424245;
            user-select: none;
        }
        
        .select-all-label input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .delete-selected-btn {
            background: #ff3b30;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
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
            background: #d70015;
            transform: translateY(-1px);
        }
        
        .student-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table th {
            background-color: #f8f9fa;
            padding: 20px 16px;
            text-align: left;
            font-weight: 600;
            color: #1d1d1f;
            font-size: 14px;
            border-bottom: 1px solid #e5e5e7;
        }
        
        .students-table td {
            padding: 20px 16px;
            border-bottom: 1px solid #f5f5f7;
            color: #424245;
            font-size: 14px;
        }
        
        .students-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-btn {
            background-color: #ff3b30;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .action-btn:hover {
            background-color: #d70015;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #86868b;
            font-size: 16px;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .message i {
            font-size: 1.4em;
        }
        
        .message.error i {
            color: #dc2626;
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
            backdrop-filter: blur(10px);
            overflow-y: auto;
            padding: 20px 0;
        }
        
        .modal.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 100vh;
        }
        
        .modal-content {
            background-color: white;
            padding: 40px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            margin: auto 0;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #86868b;
            border: none;
            background: none;
            padding: 5px;
        }
        
        .modal-close:hover {
            color: #1d1d1f;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #1d1d1f;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1d1d1f;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d2d2d7;
            border-radius: 8px;
            font-size: 16px;
            background-color: white;
            transition: border-color 0.3s;
        }

        .form-group input:disabled,
        .form-group select:disabled {
            background-color: #f5f5f7;
            cursor: not-allowed;
            color: #6c757d;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        }
        
        .register-cancel-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        
        .register-btn-modal {
            flex: 1;
            background-color: #34c759;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .cancel-btn-modal {
            flex: 1;
            background-color: #8e8e93;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .register-btn-modal:hover {
            background-color: #30b454;
        }
        
        .cancel-btn-modal:hover {
            background-color: #7d7d82;
        }

        .upload-instructions {
            background: #f0f9ff;
            border-left: 4px solid #2563eb;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
        }

        .upload-instructions h3 {
            color: #1e40af;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .upload-instructions ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .upload-instructions li {
            margin-bottom: 8px;
            color: #374151;
            line-height: 1.6;
        }

        .form-group small {
            display: block;
            margin-top: 8px;
            color: #6b7280;
            font-size: 13px;
        }

        .org-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
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

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px 15px;
            }

            .students-table-container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
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
        <div class="top-bar">
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                Home > Registered Students
            </div>
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($seller_name ?: 'Seller'); ?>!
            </div>
        </div>

        <div class="content-area">
            <?php if (isset($_GET['message'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="message error">
                    <i class="fas fa-times"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error">
                    <i class="fas fa-times"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <h1 class="page-title">Registered Students</h1>

            <div class="action-buttons">
                <button class="register-btn" onclick="showModal()">
                    <i class="fas fa-user-plus"></i>
                    Register New Student
                </button>

                <button class="upload-btn" onclick="showUploadModal()">
                    <i class="fas fa-file-excel"></i>
                    Upload CSV
                </button>

                <a href="download-student-template.php" class="template-btn">
                    <i class="fas fa-download"></i>
                    Download Template
                </a>
            </div>
            
            <div class="search-container">
                <form method="GET" id="filterForm" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Search by student number, name, or course & section"
                        value="<?php echo htmlspecialchars($search); ?>"
                        style="flex: 1; min-width: 250px;"
                        onkeyup="document.getElementById('filterForm').submit();"
                    >
                    <select name="year_level" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; cursor: pointer; background: white;" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Year Levels</option>
                        <?php 
                        // Get unique year levels for this seller's students
                        try {
                            $year_sql = "SELECT DISTINCT SUBSTRING_INDEX(s.course_section, '-', 1) as year_level 
                                        FROM students s 
                                        JOIN student_seller_affiliations ssa ON s.id = ssa.student_id 
                                        WHERE ssa.seller_id = ? AND s.course_section IS NOT NULL AND s.course_section != '' 
                                        ORDER BY year_level";
                            $year_stmt = $pdo->prepare($year_sql);
                            $year_stmt->execute([$seller_id]);
                            $years = $year_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($years as $year) {
                                $selected = ($year_level_filter === $year['year_level']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($year['year_level']) . '" ' . $selected . '>Year ' . htmlspecialchars($year['year_level']) . '</option>';
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                    <select name="section" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; cursor: pointer; background: white;" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Sections</option>
                        <?php 
                        // Get unique sections for this seller's students
                        try {
                            $section_sql = "SELECT DISTINCT SUBSTRING_INDEX(s.course_section, '-', -1) as section 
                                           FROM students s 
                                           JOIN student_seller_affiliations ssa ON s.id = ssa.student_id 
                                           WHERE ssa.seller_id = ? AND s.course_section IS NOT NULL AND s.course_section != '' 
                                           ORDER BY section";
                            $section_stmt = $pdo->prepare($section_sql);
                            $section_stmt->execute([$seller_id]);
                            $sections = $section_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($sections as $section) {
                                $selected = ($section_filter === $section['section']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($section['section']) . '" ' . $selected . '>Section ' . htmlspecialchars($section['section']) . '</option>';
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                </form>
            </div>

            <div class="students-table-container">
                <?php if (empty($students)): ?>
                    <div class="no-data">
                        No students found<?php echo !empty($search) ? ' for your search criteria' : ''; ?>.
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
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;"></th>
                                <th>ID</th>
                                <th>Student Number</th>
                                <th>Name</th>
                                <th>Course & Section</th>
                                <th>Contact Number</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr class="student-row" data-student-id="<?php echo $student['id']; ?>">
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="student-checkbox" value="<?php echo $student['id']; ?>" onchange="updateSelectAllState()">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course_section'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button 
                                            class="action-btn" 
                                            onclick="showResetPasswordModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['student_name']); ?>')"
                                            style="background: #667eea;"
                                        >
                                            Reset Password
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Excel Upload Modal -->
    <div id="uploadExcelModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideUploadModal()">&times;</button>
            <div class="modal-title">Upload CSV File</div>

            <div class="upload-instructions">
                <h3><i class="fas fa-info-circle"></i> Instructions:</h3>
                <ol>
                    <li>Click "Download Template" button to get the CSV template</li>
                    <li>Open the template in Excel or any spreadsheet program</li>
                    <li>Fill in student information (Student Number, Name, Organization, Course & Section, Password)</li>
                    <li><strong>IMPORTANT:</strong> Save as CSV format</li>
                    <li>Upload the CSV file below</li>
                </ol>
            </div>

            <form method="POST" action="upload-students-excel.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="excel_file">
                        <i class="fas fa-file-csv"></i> Select CSV File (.csv)
                    </label>
                    <input type="file" name="excel_file" accept=".csv" required>
                    <small>Maximum file size: 5MB</small>
                </div>

                <div class="register-cancel-buttons">
                    <button type="submit" class="register-btn-modal">
                        <i class="fas fa-upload"></i> Upload & Register
                    </button>
                    <button type="button" class="cancel-btn-modal" onclick="hideUploadModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideResetPasswordModal()">&times;</button>
            <div class="modal-title">Reset Student Password</div>
            <?php if (!empty($reset_error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #dc3545;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($reset_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($reset_success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #28a745;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($reset_success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="student_id" id="resetStudentId" value="">
                
                <div class="form-group">
                    <label>Student: <span id="resetStudentName" style="font-weight: bold; color: #667eea;"></span></label>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="Enter new password (minimum 6 characters)">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="Confirm new password">
                </div>
                
                <div class="register-cancel-buttons">
                    <button type="submit" name="reset_password" class="register-btn-modal">Reset Password</button>
                    <button type="button" class="cancel-btn-modal" onclick="hideResetPasswordModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="hideModal()">&times;</button>
            <div class="modal-title">Register New Student</div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="student_number">Student Number *</label>
                    <input type="text" name="student_number" id="student_number" required>
                </div>
                
                <div class="form-group">
                    <label for="student_name">Name *</label>
                    <input type="text" name="student_name" id="student_name" required>
                </div>
                
                <div class="form-group">
                    <label for="organization">Organization</label>
                    <div style="text-align: center; padding: 12px;">
                        <span class="org-badge">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($organization_name); ?>
                        </span>
                    </div>
                    <small style="text-align: center; display: block; color: #6c757d;">
                        Students will be registered under your organization
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="course_section">Course & Section</label>
                    <input type="text" name="course_section" id="course_section" placeholder="e.g., BSCS-4-1">
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" placeholder="e.g., 09123456789">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="student@plv.edu.ph">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" name="password" id="password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="register-cancel-buttons">
                    <button type="submit" name="add_student" class="register-btn-modal">Register</button>
                    <button type="button" class="cancel-btn-modal" onclick="hideModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete student "${name}"?`)) {
                window.location.href = `registered-students.php?delete=${id}`;
            }
        }
        
        function toggleSelectAll(checkbox) {
            const allCheckboxes = document.querySelectorAll('.student-checkbox');
            allCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateDeleteButtonState();
        }
        
        function updateSelectAllState() {
            const allCheckboxes = document.querySelectorAll('.student-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            
            selectAllCheckbox.checked = checkedCount === allCheckboxes.length && allCheckboxes.length > 0;
            updateDeleteButtonState();
        }
        
        function updateDeleteButtonState() {
            const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            
            if (checkedCheckboxes.length > 0) {
                deleteBtn.style.display = 'flex';
            } else {
                deleteBtn.style.display = 'none';
            }
        }
        
        function deleteSelected() {
            const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            if (checkedCheckboxes.length === 0) {
                alert('Please select at least one student to delete.');
                return;
            }
            
            const selectedIds = Array.from(checkedCheckboxes).map(cb => cb.value);
            const count = selectedIds.length;
            
            if (confirm(`Are you sure you want to delete ${count} student(s)? This action cannot be undone.`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'registered-students.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_multiple';
                input.value = JSON.stringify(selectedIds);
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showModal() {
            document.getElementById('addStudentModal').classList.add('show');
        }

        function hideModal() {
            document.getElementById('addStudentModal').classList.remove('show');
        }

        function showUploadModal() {
            document.getElementById('uploadExcelModal').classList.add('show');
        }

        function hideUploadModal() {
            document.getElementById('uploadExcelModal').classList.remove('show');
        }

        function showResetPasswordModal(studentId, studentName) {
            document.getElementById('resetStudentId').value = studentId;
            document.getElementById('resetStudentName').textContent = studentName;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('resetPasswordModal').classList.add('show');
        }

        function hideResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('show');
        }

        window.onclick = function(event) {
            var modal = document.getElementById('addStudentModal');
            var uploadModal = document.getElementById('uploadExcelModal');
            var resetPasswordModal = document.getElementById('resetPasswordModal');
            if (event.target == modal) {
                hideModal();
            }
            if (event.target == uploadModal) {
                hideUploadModal();
            }
            if (event.target == resetPasswordModal) {
                hideResetPasswordModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideModal();
                hideUploadModal();
                hideResetPasswordModal();
            }
        });
    </script>
</body>
</html>