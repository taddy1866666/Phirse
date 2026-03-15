<?php
// Session and authentication check should be done in the main page
if (!isset($_SESSION['seller_id'])) {
    header('Location: ../index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../uploads/images/Plogo.png">
    <title>Phirse Seller Portal</title>
    <!-- Common CSS -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php

try {
    // Get seller information
    $stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
    $stmt->execute([$_SESSION['seller_id']]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller) {
        header('Location: ../index.html');
        exit();
    }

    $organization_name = $seller['organization'];
    // Check if logo path already contains the uploads directory
    $logo_path = $seller['logo_path'] ?? '';
    if (!empty($logo_path) && strpos($logo_path, 'uploads') === false) {
        // If path doesn't contain 'uploads', prepend the path
        $seller_logo = '../uploads/logos/' . $logo_path;
    } else {
        // Otherwise use as-is from database
        $seller_logo = !empty($logo_path) ? $logo_path : null;
    }
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    exit('An error occurred.');
}
?>
