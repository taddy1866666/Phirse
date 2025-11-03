<?php
// Common header template for student section
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../uploads/images/Plogo.png">
    <?php if (isset($pageTitle)): ?>
        <title>Phirse - <?php echo htmlspecialchars($pageTitle); ?></title>
    <?php else: ?>
        <title>Phirse Student Portal</title>
    <?php endif; ?>
    <!-- Common CSS -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/additional-styles.css">
    <link rel="stylesheet" href="css/bottom-links.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($additionalCss)): ?>
        <?php echo $additionalCss; ?>
    <?php endif; ?>
    
    <!-- Touch interaction styles -->
    <style>
        /* Base styles to prevent highlighting */
        html, body {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        /* Navigation items */
        .nav-menu a,
        .nav-link,
        .nav-brand,
        .navbar a,
        #homeLink,
        #organizationsLink,
        #productsLink {
            -webkit-tap-highlight-color: transparent !important;
            -webkit-touch-callout: none !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            touch-action: manipulation;
        }
        
        /* Product cards */
        .product-card,
        .product-card-link,
        .product-card * {
            -webkit-tap-highlight-color: transparent !important;
            -webkit-touch-callout: none !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            touch-action: manipulation;
        }
        
        /* Allow text selection for content */
        .product-description,
        .product-name,
        input,
        textarea,
        p {
            user-select: text !important;
            -webkit-user-select: text !important;
            -webkit-touch-callout: default !important;
        }
        
        /* Remove outline but keep it for keyboard navigation */
        a:focus:not(:focus-visible),
        button:focus:not(:focus-visible) {
            outline: none;
        }
        
        /* Preserve focus outline for keyboard navigation */
        a:focus-visible,
        button:focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
    </style>