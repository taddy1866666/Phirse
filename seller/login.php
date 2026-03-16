<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    
    if (empty($user_id) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            // Try seller authentication using organization name
            $stmt = $pdo->prepare("SELECT id, seller_name, password, organization FROM sellers WHERE organization = ?");
            $stmt->execute([$user_id]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($seller && password_verify($password, $seller['password'])) {
                // Seller login successful
                $_SESSION['seller_id'] = $seller['id'];
                $_SESSION['seller_name'] = $seller['seller_name'];
                $_SESSION['organization'] = $seller['organization'];
                $_SESSION['role'] = 'seller';
                header('Location: seller-dashboard.php');
                exit();
            } else {
                $error = 'Invalid Organization Name or Password';
            }
        } catch(PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phirse - Seller Login</title>
    <link rel="icon" type="image/png" href="../uploads/images/Plogo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 400px; width: 100%; padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; color: #333; margin-bottom: 5px; }
        .header p { color: #666; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #f5576c; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn:hover { transform: scale(1.02); }
        .error { color: #ef4444; font-size: 14px; background: #fee2e2; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #f5576c; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Seller Login</h1>
            <p>Manage your organization's store</p>
        </div>
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="user_id">Organization Name</label>
                <input type="text" id="user_id" name="user_id" placeholder="e.g., COMELEC" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn">Login</button>
            <div class="back-link">
                <a href="./">← Back</a>
            </div>
        </form>
    </div>
</body>
</html>
