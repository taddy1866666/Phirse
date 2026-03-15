<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Phirse - Database Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .step h3 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .step p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .success h3 {
            color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .error h3 {
            color: #dc3545;
        }
        .error code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            display: block;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        .status-icon {
            display: inline-block;
            margin-right: 8px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span class="status-icon">⚙️</span>Phirse Database Setup</h1>
        <p class="subtitle">Initialize cancellation feature for orders</p>

        <?php
        try {
            echo '<div class="step success">';
            echo '<h3><span class="status-icon">✓</span>Database Connection</h3>';
            echo '<p>Successfully connected to <strong>phirse_db</strong></p>';
            echo '</div>';

            // Check if cancellation_reason column exists
            $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'cancellation_reason'");
            $reasonColumnExists = $result && $result->rowCount() > 0;

            // Check if cancelled status exists
            $statusResult = $pdo->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
            $statusInfo = $statusResult->fetch(PDO::FETCH_ASSOC);
            $hasCancelledStatus = isset($statusInfo['Type']) && strpos($statusInfo['Type'], 'cancelled') !== false;

            if ($reasonColumnExists && $hasCancelledStatus) {
                echo '<div class="step success">';
                echo '<h3><span class="status-icon">✓</span>All Features Already Installed</h3>';
                echo '<p>The database is already set up for order cancellations!</p>';
                echo '<ul style="margin-left: 20px; margin-top: 10px;">';
                echo '<li><code>cancellation_reason</code> column exists</li>';
                echo '<li><code>cancelled</code> status is available</li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="step">';
                echo '<h3>🔧 Migrations Needed</h3>';
                echo '<p>The following changes need to be made:</p>';
                
                if (!$reasonColumnExists) {
                    echo '<p style="margin-top: 10px;"><strong>❌ Missing:</strong> <code>cancellation_reason</code> column</p>';
                }
                
                if (!$hasCancelledStatus) {
                    echo '<p style="margin-top: 10px;"><strong>❌ Missing:</strong> <code>cancelled</code> status in enum</p>';
                }
                
                echo '</div>';

                // Run migrations if needed
                if (isset($_POST['run_migrations'])) {
                    try {
                        if (!$reasonColumnExists) {
                            $pdo->exec("ALTER TABLE orders ADD COLUMN cancellation_reason TEXT DEFAULT NULL AFTER claiming_datetime");
                            echo '<div class="step success">';
                            echo '<h3><span class="status-icon">✓</span>Added cancellation_reason Column</h3>';
                            echo '<p>The column was successfully added to the orders table.</p>';
                            echo '</div>';
                        }

                        if (!$hasCancelledStatus) {
                            $pdo->exec("ALTER TABLE orders MODIFY COLUMN status enum('pending','paid','confirmed','claiming','completed','cancelled') DEFAULT 'pending'");
                            echo '<div class="step success">';
                            echo '<h3><span class="status-icon">✓</span>Updated Status Enum</h3>';
                            echo '<p>Added <code>cancelled</code> to the status enum.</p>';
                            echo '</div>';
                        }

                        echo '<div class="step success">';
                        echo '<h3><span class="status-icon">✓</span>Setup Complete!</h3>';
                        echo '<p>All database changes have been applied successfully.</p>';
                        echo '<p style="margin-top: 10px;">You can now use the cancel order feature with reasons and student notifications.</p>';
                        echo '</div>';

                        echo '<div class="button-group">';
                        echo '<a href="../seller/seller-orders.php" style="flex: 1;"><button class="btn-primary" style="width: 100%; cursor: pointer;">Go to Seller Orders →</button></a>';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="step error">';
                        echo '<h3><span class="status-icon">✗</span>Migration Error</h3>';
                        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '</div>';
                    }
                } else if (!$reasonColumnExists || !$hasCancelledStatus) {
                    echo '<form method="POST">';
                    echo '<div class="button-group">';
                    echo '<button type="submit" name="run_migrations" value="1" class="btn-primary">Run Setup →</button>';
                    echo '<a href="../seller/seller-orders.php"><button type="button" class="btn-secondary">Skip for Now</button></a>';
                    echo '</div>';
                    echo '</form>';
                }
            }

        } catch (Exception $e) {
            echo '<div class="step error">';
            echo '<h3><span class="status-icon">✗</span>Database Error</h3>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p style="margin-top: 10px;"><strong>Troubleshooting:</strong></p>';
            echo '<ul style="margin-left: 20px; margin-top: 10px;">';
            echo '<li>Make sure XAMPP MySQL is running</li>';
            echo '<li>Check that the database <code>phirse_db</code> exists</li>';
            echo '<li>Verify the database credentials in <code>config.php</code></li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
