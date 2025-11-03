<?php
session_start();
require_once '../database/config.php';

// Ensure seller is logged in
if (!isset($_SESSION['seller_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sellerId = $_SESSION['seller_id'];
$filter = $_GET['range'] ?? '7days';

// Determine date range
if ($filter === 'month') {
    $start = date('Y-m-01');
    $end = date('Y-m-t');
    $interval = new DateInterval('P1D');
} elseif ($filter === '30days') {
    $start = date('Y-m-d', strtotime('-29 days'));
    $end = date('Y-m-d');
    $interval = new DateInterval('P1D');
} else {
    // last 7 days default
    $start = date('Y-m-d', strtotime('-6 days'));
    $end = date('Y-m-d');
    $interval = new DateInterval('P1D');
}

// Generate all dates
$period = new DatePeriod(new DateTime($start), $interval, (new DateTime($end))->modify('+1 day'));
$dates = [];
foreach ($period as $date) {
    $dates[$date->format('Y-m-d')] = 0;
}

// Fetch revenue data for current period
$stmt = $pdo->prepare("SELECT DATE(order_date) as order_date, SUM(total_price) as total_sales
    FROM orders
    WHERE status = 'completed'
      AND seller_id = ?
      AND order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(order_date)
");
$stmt->execute([$sellerId, $start, $end]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Merge with full date list
foreach ($results as $row) {
    $dates[$row['order_date']] = (float)$row['total_sales'];
}

// Current period stats
$totalRevenue = array_sum($dates);
$average = count($dates) > 0 ? $totalRevenue / count($dates) : 0;

// Previous period for comparison
$prevStart = date('Y-m-d', strtotime($start . ' -' . count($dates) . ' days'));
$prevEnd = date('Y-m-d', strtotime($end . ' -' . count($dates) . ' days'));

$stmtPrev = $pdo->prepare("SELECT SUM(total_price) as prev_sales
    FROM orders
    WHERE status = 'completed'
      AND seller_id = ?
      AND order_date BETWEEN ? AND ?
");
$stmtPrev->execute([$sellerId, $prevStart, $prevEnd]);
$prevSales = (float)$stmtPrev->fetchColumn();

// Calculate change percentage
$change = $prevSales > 0 ? (($totalRevenue - $prevSales) / $prevSales) * 100 : 0;

// Find best/lowest day
$bestDay = array_keys($dates, max($dates))[0] ?? 'N/A';
$lowDay = array_keys($dates, min($dates))[0] ?? 'N/A';

// Return JSON
header('Content-Type: application/json');
echo json_encode([
    'labels' => array_map(fn($d) => date('M j', strtotime($d)), array_keys($dates)),
    'totals' => array_values($dates),
    'totalRevenue' => $totalRevenue,
    'averageRevenue' => $average,
    'change' => round($change, 1),
    'bestDay' => $bestDay !== 'N/A' ? date('M j', strtotime($bestDay)) : 'N/A',
    'lowDay' => $lowDay !== 'N/A' ? date('M j', strtotime($lowDay)) : 'N/A'
]);
