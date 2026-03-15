<?php
session_start();
require_once '../database/config.php';

// Only allow admins
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

// Build query - get all students with their stats
$sql = "
    SELECT 
        s.student_number,
        s.student_name,
        s.organization,
        s.course_section,
        s.contact_number,
        s.email,
        s.created_at,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_price ELSE 0 END), 0) as total_spent,
        MAX(o.order_date) as last_order_date
    FROM students s
    LEFT JOIN orders o ON s.id = o.student_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching students: " . $e->getMessage());
}

// Calculate totals
$total_students = count($students);
$total_spent = 0;
$active_students = 0;

foreach ($students as $student) {
    $total_spent += $student['total_spent'];
    if ($student['total_orders'] > 0) {
        $active_students++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Students</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .loading {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <p>Generating PDF Report...</p>
    </div>

    <!-- Hidden table for data extraction -->
    <table id="studentsTable" style="display:none;">
        <thead>
            <tr>
                <th>Student Number</th>
                <th>Student Name</th>
                <th>Organization</th>
                <th>Course & Section</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Total Orders</th>
                <th>Total Spent</th>
                <th>Last Order Date</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['organization'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['course_section'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                    <td><?php echo $student['total_orders']; ?></td>
                    <td><?php echo number_format($student['total_spent'], 2); ?></td>
                    <td><?php echo !empty($student['last_order_date']) ? date('M d, Y', strtotime($student['last_order_date'])) : 'N/A'; ?></td>
                    <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        const { jsPDF } = window.jspdf;

        window.addEventListener('load', function() {
            generateAndDownloadPDF();
        });

        function generateAndDownloadPDF() {
            const doc = new jsPDF('p', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            let yPosition = 20;

            // Header with gradient
            doc.setFillColor(102, 126, 234);
            doc.rect(0, 0, pageWidth, 20, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.text('PHIRSE STUDENTS REPORT', pageWidth / 2, 13, { align: 'center' });

            // Subheader with date
            yPosition = 25;
            doc.setTextColor(100, 100, 100);
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) + ' ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            doc.text('Generated on: ' + dateStr, pageWidth / 2, yPosition, { align: 'center' });

            yPosition = 32;

            // Get table data
            const tableElement = document.getElementById('studentsTable');
            const rows = [];
            
            const headerCells = tableElement.querySelectorAll('thead th');
            const headers = Array.from(headerCells).map(h => h.innerText);

            // Get data from table
            const bodyRows = tableElement.querySelectorAll('tbody tr');
            const totalStudents = <?php echo $total_students; ?>;
            const activeStudents = <?php echo $active_students; ?>;
            const totalSpent = <?php echo $total_spent; ?>;

            bodyRows.forEach((row) => {
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).map(cell => cell.innerText);
                rows.push(rowData);
            });

            // Key Metrics Section
            doc.setTextColor(26, 32, 44);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Key Metrics', 15, yPosition);
            yPosition += 10;

            const metrics = [
                { label: 'Total Students', value: totalStudents.toString(), bgColor: [30, 64, 175] },
                { label: 'Active Students', value: activeStudents.toString(), bgColor: [22, 163, 74] },
                { label: 'Total Spent', value: '₱' + totalSpent.toFixed(2), bgColor: [4, 120, 87] }
            ];

            const metricWidth = (pageWidth - 40) / 3;
            const metricHeight = 15;
            let xPos = 15;

            metrics.forEach((metric, index) => {
                // Metric box background
                doc.setFillColor(...metric.bgColor);
                doc.rect(xPos, yPosition, metricWidth, metricHeight, 'F');

                // Metric label
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');
                doc.text(metric.label.toUpperCase(), xPos + 3, yPosition + 5);

                // Metric value
                doc.setFontSize(11);
                doc.setFont(undefined, 'bold');
                doc.text(metric.value, xPos + 3, yPosition + 12);

                xPos += metricWidth + 5;
            });

            yPosition += 22;

            // Students detail section
            doc.setTextColor(26, 32, 44);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Student Details', 15, yPosition);
            yPosition += 8;

            // Display each student in detailed card format
            rows.forEach((rowData, studentIdx) => {
                // Check if need new page
                if (yPosition > pageHeight - 40) {
                    doc.addPage();
                    yPosition = 15;
                }

                // Student card background
                doc.setFillColor(245, 245, 245);
                doc.rect(15, yPosition - 2, pageWidth - 30, 2, 'F');
                yPosition += 4;

                // Student details in label: value format
                doc.setFontSize(9);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(102, 126, 234);

                const details = [
                    ['Student Number:', rowData[0]],
                    ['Student Name:', rowData[1]],
                    ['Organization:', rowData[2]],
                    ['Course & Section:', rowData[3]],
                    ['Contact Number:', rowData[4]],
                    ['Email:', rowData[5]],
                    ['Total Orders:', rowData[6]],
                    ['Total Spent:', rowData[7]],
                    ['Last Order Date:', rowData[8]],
                    ['Registration Date:', rowData[9]]
                ];

                doc.setFontSize(8.5);
                const lineHeight = 5;
                let leftX = 18;
                let rightX = pageWidth / 2;
                let colIndex = 0;

                details.forEach((detail, idx) => {
                    const label = detail[0];
                    const value = detail[1];

                    // Alternate between left and right columns
                    const currentX = (colIndex % 2 === 0) ? leftX : rightX;

                    if (colIndex % 2 === 0 && colIndex > 0) {
                        yPosition += lineHeight;
                    }

                    // Label
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(80, 100, 180);
                    doc.text(label, currentX, yPosition, { maxWidth: pageWidth / 2 - 25 });

                    // Value
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(50, 50, 50);
                    const valueX = currentX + 50;
                    const lines = doc.splitTextToSize(value, pageWidth / 2 - 70);
                    doc.text(lines, valueX, yPosition, { maxWidth: pageWidth / 2 - 70 });

                    colIndex++;
                });

                yPosition += lineHeight + 8;

                // Separator
                doc.setDrawColor(200, 200, 200);
                doc.line(15, yPosition, pageWidth - 15, yPosition);
                yPosition += 3;
            });

            // Footer
            yPosition = pageHeight - 5;
            doc.setFontSize(7);
            doc.setTextColor(150, 150, 150);
            doc.setFont(undefined, 'italic');
            doc.text('This is an automatically generated report from PHIRSE System', pageWidth / 2, yPosition, { align: 'center' });

            // Download PDF with timestamp
            const fileName = 'Students_Report_' + new Date().getTime() + '.pdf';
            doc.save(fileName);

            // Close window or redirect after download
            setTimeout(() => {
                window.location.href = 'students-list.php';
            }, 500);
        }
    </script>
</body>
</html>
