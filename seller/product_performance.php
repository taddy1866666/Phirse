<?php
require_once '../database/config.php';
session_start();
$seller_id = $_SESSION['seller_id'];

$range = $_GET['range'] ?? '7days';
$dateFilter = '';

switch ($range) {
  case '7days': $dateFilter = "o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
  case '30days': $dateFilter = "o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"; break;
  case 'month': $dateFilter = "MONTH(o.order_date)=MONTH(CURDATE()) AND YEAR(o.order_date)=YEAR(CURDATE())"; break;
}

$query = "SELECT 
    p.name,
    p.category,
    SUM(o.quantity) AS total_sold,
    SUM(o.total_price) AS revenue
  FROM orders o
  JOIN products p ON o.product_id = p.id
  WHERE o.status='completed' AND o.seller_id=:seller_id AND $dateFilter
  GROUP BY p.id, p.name, p.category
  ORDER BY revenue DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['seller_id' => $seller_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRevenue = array_sum(array_column($products, 'revenue'));
$totalSold = array_sum(array_column($products, 'total_sold'));

echo json_encode([
  'products' => $products,
  'total_revenue' => $totalRevenue,
  'total_sold' => $totalSold
]);
?>
