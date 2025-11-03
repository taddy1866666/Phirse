<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}
require_once '../database/config.php';
$pageTitle = 'Students List';
include 'includes/header.php';

// Fetch all students
$students = [];
$totalStudents = 0;
$activeStudents = 0;

try {
    // Get total count
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    
    // Get students with their order count
    $stmt = $pdo->query("
        SELECT 
            s.*,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_price ELSE 0 END), 0) as total_spent,
            MAX(o.order_date) as last_order_date
        FROM students s
        LEFT JOIN orders o ON s.id = o.student_id
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count active students (those who have placed orders)
    $activeStudents = $pdo->query("
        SELECT COUNT(DISTINCT student_id) 
        FROM orders 
        WHERE student_id IS NOT NULL
    ")->fetchColumn();
    
} catch (PDOException $e) {
    $error_message = "Error loading students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Admin Panel</title>
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
            flex: 1;
            padding: 32px 40px;
            width: calc(100% - 220px);
        }

        .page-header-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icon i {
            font-size: 24px;
            color: #4f46e5;
        }

        .header-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 4px 0;
        }

        .header-text p {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.students {
            background: #eff6ff;
            color: #1e40af;
        }

        .stat-icon.active {
            background: #d1fae5;
            color: #047857;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
        }

        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .filters-row {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
            color: #2d3748;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: #4a5568;
        }

        .filter-select:hover {
            border-color: #cbd5e0;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .export-btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .students-table-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .students-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e2e8f0;
        }

        .students-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            color: #1a202c;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .students-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #4a5568;
            font-size: 14px;
        }

        .students-table tbody tr {
            transition: all 0.2s;
        }

        .students-table tbody tr:hover {
            background: #f8f9fa;
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .student-info {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 2px;
        }

        .student-number {
            font-size: 12px;
            color: #718096;
        }

        .orders-count {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #667eea;
            font-weight: 600;
        }

        .spent-amount {
            font-weight: 700;
            color: #1a202c;
        }

        .btn-view {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            margin: 3% auto;
            border-radius: 16px;
            padding: 0;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 2px solid #e2e8f0;
            background: #f8f9fa;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
        }

        .close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #a0aec0;
            transition: all 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .close:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .modal-body {
            padding: 24px;
        }

        .detail-row {
            display: flex;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            min-width: 150px;
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
        }

        .detail-value {
            color: #1a202c;
            font-size: 14px;
            flex: 1;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #cbd5e0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 16px;
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: 100%;
            }

            .students-table-container {
                padding: 16px;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header-wrapper">
            <div class="page-header">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="header-text">
                        <h1>Students Management</h1>
                        <p>Monitor and manage all registered students</p>
                    </div>
                </div>
                <div class="header-actions">
                    <div class="dashboard-date">
                        <i class="far fa-calendar"></i>
                        <span><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Total Students</div>
                    <div class="stat-icon students">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalStudents ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-label">Active Students</div>
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $activeStudents ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name, student number, or organization...">
                    <i class="fas fa-search"></i>
                </div>
                
                <select id="orgFilter" class="filter-select">
                    <option value="">All Organizations</option>
                    <?php
                    try {
                        $orgs = $pdo->query("SELECT DISTINCT organization FROM students WHERE organization IS NOT NULL AND organization != '' ORDER BY organization")->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($orgs as $org) {
                            echo '<option value="' . htmlspecialchars($org) . '">' . htmlspecialchars($org) . '</option>';
                        }
                    } catch (PDOException $e) {}
                    ?>
                </select>

                <button class="export-btn" onclick="exportToCSV()">
                    <i class="fas fa-download"></i>
                    Export CSV
                </button>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Students Table -->
        <div class="students-table-container">
            <?php if (!empty($students)): ?>
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Organization</th>
                            <th>Course & Section</th>
                            <th>Contact</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr data-org="<?= htmlspecialchars($student['organization'] ?? '') ?>">
                                <td>
                                    <div class="student-profile">
                                        <div class="student-avatar">
                                            <?php if (!empty($student['profile_path']) && file_exists('../' . $student['profile_path'])): ?>
                                                <img src="../<?= htmlspecialchars($student['profile_path']) ?>" alt="Profile">
                                            <?php else: ?>
                                                <?= strtoupper(substr($student['student_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="student-info">
                                            <div class="student-name"><?= htmlspecialchars($student['student_name']) ?></div>
                                            <div class="student-number"><?= htmlspecialchars($student['student_number']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($student['organization'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($student['course_section'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($student['contact_number'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="orders-count">
                                        <i class="fas fa-shopping-cart"></i>
                                        <?= $student['total_orders'] ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="spent-amount">₱<?= number_format($student['total_spent'], 2) ?></span>
                                </td>
                                <td>
                                    <button class="btn-view" onclick='viewStudent(<?= json_encode($student) ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>No students found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Student Details</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('orgFilter').addEventListener('change', filterTable);

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const orgFilter = document.getElementById('orgFilter').value.toLowerCase();
            
            const rows = document.querySelectorAll('.students-table tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const org = row.dataset.org.toLowerCase();
                
                const matchesSearch = text.includes(search);
                const matchesOrg = !orgFilter || org === orgFilter;

                row.style.display = matchesSearch && matchesOrg ? '' : 'none';
            });
        }

        function viewStudent(student) {
            const modal = document.getElementById('studentModal');
            const modalBody = document.getElementById('modalBody');

            const lastOrder = student.last_order_date ? 
                new Date(student.last_order_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                }) : 'No orders yet';

            const joinDate = new Date(student.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            modalBody.innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Student Number:</div>
                    <div class="detail-value">${student.student_number || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Full Name:</div>
                    <div class="detail-value">${student.student_name || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Organization:</div>
                    <div class="detail-value">${student.organization || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Course & Section:</div>
                    <div class="detail-value">${student.course_section || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value">${student.email || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Contact Number:</div>
                    <div class="detail-value">${student.contact_number || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Orders:</div>
                    <div class="detail-value">${student.total_orders}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Spent:</div>
                    <div class="detail-value">₱${parseFloat(student.total_spent).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Last Order:</div>
                    <div class="detail-value">${lastOrder}</div>
                </div>
            `;

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('studentModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        function exportToCSV() {
            const rows = document.querySelectorAll('.students-table tbody tr');
            let csv = 'Student Number,Name,Organization,Course & Section,Contact,Email,Orders,Total Spent\n';
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    const studentName = cells[0].querySelector('.student-name').textContent;
                    const studentNumber = cells[0].querySelector('.student-number').textContent;
                    const org = cells[1].textContent;
                    const course = cells[2].textContent;
                    const contact = cells[3].textContent;
                    const ordersText = cells[4].textContent.trim();
                    const spentText = cells[5].textContent.trim();
                    
                    const viewButton = cells[6].querySelector('.btn-view');
                    const onclickAttr = viewButton.getAttribute('onclick');
                    const jsonMatch = onclickAttr.match(/viewStudent\((.*)\)/);
                    
                    if (jsonMatch) {
                        try {
                            const studentObj = JSON.parse(jsonMatch[1].replace(/'/g, '"'));
                            const email = studentObj.email || 'N/A';
                            const totalSpent = parseFloat(studentObj.total_spent || 0).toFixed(2);
                            
                            csv += `"${studentNumber}","${studentName}","${org}","${course}","${contact}","${email}","${ordersText}","${totalSpent}"\n`;
                        } catch (e) {
                            console.error('Error parsing student data:', e);
                        }
                    }
                }
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'students_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>