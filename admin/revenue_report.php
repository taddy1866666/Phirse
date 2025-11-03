<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../database/config.php';

$range = $_GET['range'] ?? '7days';

try {
    $labels = [];
    $revenues = [];
    $dateCondition = '';
    
    switch ($range) {
        case '7days':
            // Last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('M d', strtotime($date));
                
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_price), 0) as revenue
                    FROM orders
                    WHERE status = 'completed' AND DATE(order_date) = ?
                ");
                $stmt->execute([$date]);
                $revenues[] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            }
            break;
            
        case 'month':
            // Current month by days
            $daysInMonth = date('t');
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = date('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $labels[] = date('M d', strtotime($date));
                
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_price), 0) as revenue
                    FROM orders
                    WHERE status = 'completed' AND DATE(order_date) = ?
                ");
                $stmt->execute([$date]);
                $revenues[] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            }
            break;
            
        case '30days':
            // Last 30 days
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('M d', strtotime($date));
                
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_price), 0) as revenue
                    FROM orders
                    WHERE status = 'completed' AND DATE(order_date) = ?
                ");
                $stmt->execute([$date]);
                $revenues[] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            }
            break;
            
        case '6months':
            // Last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M Y', strtotime($date . '-01'));
                
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_price), 0) as revenue
                    FROM orders
                    WHERE status = 'completed' 
                    AND YEAR(order_date) = ? 
                    AND MONTH(order_date) = ?
                ");
                $year = date('Y', strtotime($date . '-01'));
                $month = date('m', strtotime($date . '-01'));
                $stmt->execute([$year, $month]);
                $revenues[] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            }
            break;
    }
    
    // Calculate statistics
    $total = array_sum($revenues);
    $count = count($revenues);
    $average = $count > 0 ? $total / $count : 0;
    
    // Calculate change (compare first half vs second half)
    $halfPoint = floor($count / 2);
    $firstHalf = array_slice($revenues, 0, $halfPoint);
    $secondHalf = array_slice($revenues, $halfPoint);
    
    $firstHalfSum = array_sum($firstHalf);
    $secondHalfSum = array_sum($secondHalf);
    
    $change = 0;
    if ($firstHalfSum > 0) {
        $change = (($secondHalfSum - $firstHalfSum) / $firstHalfSum) * 100;
    } elseif ($secondHalfSum > 0) {
        $change = 100;
    }
    
    echo json_encode([
        'labels' => $labels,
        'revenues' => $revenues,
        'total' => $total,
        'average' => $average,
        'change' => $change
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>