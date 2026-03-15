<?php
require_once 'config.php';

try {
    // Check if pdf_path column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'pdf_path'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add the pdf_path column
        $sql = "ALTER TABLE products ADD COLUMN pdf_path VARCHAR(255) DEFAULT NULL AFTER image_path";
        $pdo->exec($sql);
        echo "✓ Successfully added 'pdf_path' column to products table";
    } else {
        echo "✓ 'pdf_path' column already exists in products table";
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
