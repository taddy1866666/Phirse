<?php
session_start();
require_once '../database/config.php';

// Only allow admins
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

// Build query - get all orders
$sql = "
    SELECT 
        o.reference_number, 
        s.student_number, 
        s.student_name, 
        s.organization AS student_org, 
        s.course_section, 
        s.contact_number,
        sl.organization AS seller_org, 
        p.name AS product_name, 
        p.category, 
        o.product_size, 
        o.quantity, 
        o.total_price, 
        o.status, 
        o.claiming_datetime,
        o.payment_method,
        o.order_date
    FROM orders o
    LEFT JOIN students s ON o.student_id = s.id
    LEFT JOIN products p ON o.product_id = p.id
    LEFT JOIN sellers sl ON p.seller_id = sl.id
    ORDER BY o.order_date DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}

// Calculate totals
$total_revenue = 0;
$total_orders = count($orders);

foreach ($orders as $order) {
    $total_revenue += $order['total_price'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Orders</title>
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
    <table id="ordersTable" style="display:none;">
        <thead>
            <tr>
                <th>Reference Number</th>
                <th>Student Number</th>
                <th>Student Name</th>
                <th>Organization</th>
                <th>Course & Section</th>
                <th>Contact Number</th>
                <th>Seller Organization</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Claiming Date & Time</th>
                <th>Payment Method</th>
                <th>Order Date & Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['reference_number']); ?></td>
                    <td><?php echo htmlspecialchars($order['student_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['student_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['student_org'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['course_section'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['contact_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['seller_org'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                    <td><?php echo ucfirst($order['status']); ?></td>
                    <td><?php echo !empty($order['claiming_datetime']) ? htmlspecialchars($order['claiming_datetime']) : 'N/A'; ?></td>
                    <td><?php echo ($order['payment_method'] !== null && $order['payment_method'] !== '') ? htmlspecialchars($order['payment_method']) : 'CASH ON HAND'; ?></td>
                    <td><?php echo htmlspecialchars($order['order_date']); ?></td>
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
            doc.text('PHIRSE ORDERS REPORT', pageWidth / 2, 13, { align: 'center' });

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
            const tableElement = document.getElementById('ordersTable');
            const rows = [];
            
            const headerCells = tableElement.querySelectorAll('thead th');
            const headers = Array.from(headerCells).map(h => h.innerText);

            // Get data from table
            const bodyRows = tableElement.querySelectorAll('tbody tr');
            const totalOrders = <?php echo $total_orders; ?>;
            const totalRevenue = <?php echo $total_revenue; ?>;

            bodyRows.forEach((row) => {
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).map(cell => cell.innerText);
                rows.push(rowData);
            });

            // Calculate completed and pending orders
            let completedCount = 0;
            let pendingCount = 0;
            rows.forEach(row => {
                const status = row[10]; // Status column index
                if (status.toLowerCase().includes('complet')) {
                    completedCount++;
                } else if (['pending', 'paid', 'confirmed', 'claiming'].some(s => status.toLowerCase().includes(s))) {
                    pendingCount++;
                }
            });

            // Key Metrics Section
            doc.setTextColor(26, 32, 44);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Key Metrics', 15, yPosition);
            yPosition += 10;

            const metrics = [
                { label: 'Total Revenue', value: '₱' + totalRevenue.toFixed(2), bgColor: [4, 120, 87] },
                { label: 'Total Orders', value: totalOrders.toString(), bgColor: [30, 64, 175] },
                { label: 'Completed', value: completedCount.toString(), bgColor: [22, 163, 74] },
                { label: 'Pending', value: pendingCount.toString(), bgColor: [234, 179, 8] }
            ];

            const metricWidth = (pageWidth - 30) / 2;
            const metricHeight = 14;
            let xPos = 15;
            let row = 0;

            metrics.forEach((metric, index) => {
                if (index % 2 === 0 && index > 0) {
                    row++;
                    xPos = 15;
                    yPosition += metricHeight + 2;
                } else if (index > 0) {
                    xPos = 15 + metricWidth + 5;
                }

                // Metric box background
                doc.setFillColor(...metric.bgColor);
                doc.rect(xPos, yPosition, metricWidth, metricHeight, 'F');

                // Metric label
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');
                doc.text(metric.label.toUpperCase(), xPos + 3, yPosition + 4);

                // Metric value
                doc.setFontSize(10);
                doc.setFont(undefined, 'bold');
                doc.text(metric.value, xPos + 3, yPosition + 11);
            });

            yPosition += metricHeight + 8;

            // Orders detail section
            doc.setTextColor(26, 32, 44);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Order Details', 15, yPosition);
            yPosition += 8;

            // Display each order in detailed card format
            rows.forEach((rowData, orderIdx) => {
                // Check if need new page
                if (yPosition > pageHeight - 40) {
                    doc.addPage();
                    yPosition = 15;
                }

                // Order card background
                doc.setFillColor(245, 245, 245);
                doc.rect(15, yPosition - 2, pageWidth - 30, 2, 'F');
                yPosition += 4;

                // Order details in label: value format
                doc.setFontSize(9);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(102, 126, 234);

                const details = [
                    ['Reference Number:', rowData[0]],
                    ['Student Number:', rowData[1]],
                    ['Student Name:', rowData[2]],
                    ['Organization:', rowData[3]],
                    ['Course & Section:', rowData[4]],
                    ['Contact Number:', rowData[5]],
                    ['Seller Organization:', rowData[6]],
                    ['Product Name:', rowData[7]],
                    ['Quantity:', rowData[8]],
                    ['Total Price:', rowData[9]],
                    ['Status:', rowData[10]],
                    ['Claiming Date & Time:', rowData[11]],
                    ['Payment Method:', rowData[12] || 'CASH ON HAND'],
                    ['Order Date & Time:', rowData[13]]
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
            const fileName = 'Orders_Report_' + new Date().getTime() + '.pdf';
            doc.save(fileName);

            // Close window or redirect after download
            setTimeout(() => {
                window.location.href = 'admin-orders.php';
            }, 500);
        }
    </script>
</body>
</html>
