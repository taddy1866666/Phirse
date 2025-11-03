<?php
require_once '../database/config.php';
header('Content-Type: application/json');

$range = $_GET['range'] ?? '7days';

if ($range === 'month') {
    $start = date('Y-m-01');
    $end = date('Y-m-t');
} elseif ($range === '30days') {
    $start = date('Y-m-d', strtotime('-29 days'));
    $end = date('Y-m-d');
} else {
    $start = date('Y-m-d', strtotime('-6 days'));
    $end = date('Y-m-d');
}

// collect per-product totals
$stmt = $pdo->prepare("
    SELECT p.id, p.name, SUM(o.quantity) AS total_sold, ROUND(SUM(o.total_price),2) AS revenue
    FROM orders o
    JOIN products p ON p.id = o.product_id
    WHERE o.status='completed' AND o.order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->execute([$start,$end]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_revenue = array_sum(array_map(fn($r)=> (float)$r['revenue'], $products));
$total_sold = array_sum(array_map(fn($r)=> (int)$r['total_sold'], $products));

echo json_encode(['products'=>$products, 'total_revenue'=>$total_revenue, 'total_sold'=>$total_sold]);
