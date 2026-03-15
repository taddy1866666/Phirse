<?php
session_start();
require_once '../database/config.php';

// Only allow sellers
if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}

$seller_id = $_SESSION['seller_id'];

try {
    // Fetch seller info
    $stmt = $pdo->prepare("SELECT seller_name, organization FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller_info) {
        session_destroy();
        header('Location: ../index.html');
        exit();
    }

    // Check if cancellation_reason column exists
    $hasReasonColumn = false;
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'cancellation_reason'");
        $hasReasonColumn = $testStmt && $testStmt->rowCount() > 0;
    } catch(Exception $e) {
        $hasReasonColumn = false;
    }

    // Fetch seller orders
    if ($hasReasonColumn) {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.quantity, o.total_price, o.order_date, o.status,
                o.payment_method, o.reference_number, o.product_size, 
                o.cancellation_reason,
                p.name AS product_name, p.category,
                s.student_number, s.student_name, s.organization AS student_organization,
                s.course_section, s.contact_number
            FROM orders o
            JOIN products p ON o.product_id = p.id
            LEFT JOIN students s ON o.student_id = s.id
            WHERE p.seller_id = ?
            ORDER BY o.order_date DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.quantity, o.total_price, o.order_date, o.status,
                o.payment_method, o.reference_number, o.product_size,
                p.name AS product_name, p.category,
                s.student_number, s.student_name, s.organization AS student_organization,
                s.course_section, s.contact_number
            FROM orders o
            JOIN products p ON o.product_id = p.id
            LEFT JOIN students s ON o.student_id = s.id
            WHERE p.seller_id = ?
            ORDER BY o.order_date DESC
        ");
    }
    $stmt->execute([$seller_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_revenue = 0;
    $total_orders = count($orders);

    foreach ($orders as $order) {
        $total_revenue += $order['total_price'];
    }

} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Seller Orders</title>
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
                <th>Reference No.</th>
                <th>Student #</th>
                <th>Student Name</th>
                <th>Organization</th>
                <th>Course</th>
                <th>Contact</th>
                <th>Product</th>
                <th>Category</th>
                <th>Size</th>
                <th>Qty</th>
                <th>Price (₱)</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Order Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['reference_number']); ?></td>
                    <td><?php echo htmlspecialchars($order['student_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['student_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['student_organization'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['course_section'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['contact_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['category'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($order['product_size'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                    <td><?php echo number_format($order['total_price'], 2); ?></td>
                    <td><?php echo ucfirst($order['status']); ?></td>
                    <td><?php echo htmlspecialchars($order['payment_method'] ?: 'CASH ON HAND'); ?></td>
                    <td><?php echo substr($order['order_date'], 0, 16); ?></td>
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
            doc.text('PHIRSE SELLER ORDERS REPORT', pageWidth / 2, 13, { align: 'center' });

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

            // Key Metrics Section
            doc.setTextColor(26, 32, 44);
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Key Metrics', 15, yPosition);
            yPosition += 10;

            const metrics = [
                { label: 'Total Orders', value: totalOrders.toString(), bgColor: [30, 64, 175] },
                { label: 'Total Revenue', value: '₱' + totalRevenue.toFixed(2), bgColor: [22, 163, 74] },
                { label: 'Average Order', value: '₱' + (totalOrders > 0 ? (totalRevenue / totalOrders).toFixed(2) : '0.00'), bgColor: [4, 120, 87] }
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
                    ['Reference No.:', rowData[0]],
                    ['Student #:', rowData[1]],
                    ['Student Name:', rowData[2]],
                    ['Organization:', rowData[3]],
                    ['Course & Section:', rowData[4]],
                    ['Contact Number:', rowData[5]],
                    ['Product:', rowData[6]],
                    ['Category:', rowData[7]],
                    ['Size:', rowData[8]],
                    ['Quantity:', rowData[9]],
                    ['Price:', rowData[10]],
                    ['Status:', rowData[11]],
                    ['Payment Method:', rowData[12] || 'CASH ON HAND'],
                    ['Order Date:', rowData[13]]
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
            const fileName = 'Seller_Orders_Report_' + new Date().getTime() + '.pdf';
            doc.save(fileName);

            // Close window or redirect after download
            setTimeout(() => {
                window.location.href = 'seller-orders.php';
            }, 500);
        }
    </script>
</body>
</html>
