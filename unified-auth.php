<?php
session_start();
require_once 'database/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    
    if (empty($user_id) || empty($password)) {
        header('Location: index.html?error=Please fill in all fields');
        exit();
    }
    
    try {
        // First, try to authenticate as admin
        $stmt = $pdo->prepare("SELECT id, user_id, password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login successful
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['user_id'];
            $_SESSION['role'] = 'admin';
            header('Location: admin/dashboard.php');
            exit();
        }
        
        // If admin authentication failed, try seller authentication using organization name
        $stmt = $pdo->prepare("SELECT id, seller_name, password, organization FROM sellers WHERE organization = ?");
        $stmt->execute([$user_id]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seller && password_verify($password, $seller['password'])) {
            // Seller login successful
            $_SESSION['seller_id'] = $seller['id'];
            $_SESSION['seller_name'] = $seller['seller_name'];
            $_SESSION['organization'] = $seller['organization'];
            $_SESSION['role'] = 'seller';
            header('Location: seller/seller-dashboard.php');
            exit();
        }
        
        // Both authentication attempts failed
        header('Location: index.html?error=Invalid User ID/Seller Name or Password');
        exit();
        
    } catch(PDOException $e) {
        header('Location: index.html?error=Login failed. Please try again.');
        exit();
    }
} else {
    header('Location: index.html');
    exit();
}
?>