<?php
// Common header template for admin section
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../uploads/images/Plogo.png">
    <?php if (isset($pageTitle)): ?>
        <title>Phirse Admin - <?php echo htmlspecialchars($pageTitle); ?></title>
    <?php else: ?>
        <title>Phirse Admin Portal</title>
    <?php endif; ?>
    <!-- Common CSS -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($additionalCss)): ?>
        <?php echo $additionalCss; ?>
    <?php endif; ?>