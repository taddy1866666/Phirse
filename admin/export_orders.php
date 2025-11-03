<?php
session_start();
require_once '../database/config.php';

// Only allow admins
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

date_default_timezone_set('Asia/Manila');

// Get selected status filter (if any)
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

header('Content-Type: text/csv; charset=utf-8');
$filename = 'orders_export_' . ($status ? $status . '_' : '') . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, [
    'Reference Number',
    'Student Number',
    'Student Name',
    'Organization',
    'Course & Section',
    'Contact Number',
    'Seller Organization',
    'Product Name',
    'Category',
    'Size',
    'Quantity',
    'Total Price (₱)',
    'Status',
    'Payment Method',
    'Order Date & Time'
]);

// Build base query
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
        p.category AS product_category,
        o.product_size,
        o.quantity, 
        o.total_price, 
        o.status, 
        o.payment_method, 
        o.order_date
    FROM orders o
    LEFT JOIN students s ON o.student_id = s.id
    LEFT JOIN products p ON o.product_id = p.id
    LEFT JOIN sellers sl ON o.seller_id = sl.id
";

// Add filter condition if a status was selected
if (!empty($status)) {
    $sql .= " WHERE o.status = :status";
}

$sql .= " ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($sql);

// Bind status parameter if needed
if (!empty($status)) {
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
}

$stmt->execute();

// Write each row
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $payment_method = !empty($row['payment_method']) ? strtoupper($row['payment_method']) : 'ONHAND';

    if (!empty($row['order_date']) && $row['order_date'] !== '0000-00-00 00:00:00') {
        $dt = new DateTime($row['order_date'], new DateTimeZone('Asia/Manila'));
        // Excel-safe text formatting (avoid ####### issue)
        $formatted_date = "\t" . $dt->format('M d, Y h:i A');
    } else {
        $formatted_date = 'N/A';
    }

    fputcsv($output, [
        $row['reference_number'] ?? 'N/A',
        $row['student_number'] ?? 'N/A',
        $row['student_name'] ?? 'N/A',
        $row['student_org'] ?? 'N/A',
        $row['course_section'] ?? 'N/A',
        $row['contact_number'] ?? 'N/A',
        $row['seller_org'] ?? 'N/A',
        $row['product_name'] ?? 'N/A',
        $row['product_category'] ?? 'N/A',
        in_array($row['product_category'], ['Organization Shirt', 'Merchandise']) ? ($row['product_size'] ?? 'N/A') : '-',
        $row['quantity'] ?? 0,
        number_format((float)$row['total_price'], 2),
        ucfirst($row['status'] ?? 'Pending'),
        $payment_method,
        $formatted_date
    ]);
}

fclose($output);
exit;
?>
