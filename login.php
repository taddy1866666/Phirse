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
        $stmt = $pdo->prepare("SELECT id, user_id, password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['user_id'];
            header('Location: admin/dashboard.php');
            exit();
        } else {
            header('Location: index.html?error=Invalid User ID or Password');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: index.html?error=Login failed. Please try again.');
        exit();
    }
} else {
    header('Location: index.html');
    exit();
}
?>