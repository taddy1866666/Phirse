<?php
// Temporary migration runner - delete after running
require_once 'database/config.php';

try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM sellers LIKE 'adviser'");
    
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sellers ADD COLUMN adviser VARCHAR(255) NULL AFTER organization");
        echo "✓ Successfully added 'adviser' column to sellers table";
    } else {
        echo "✓ 'adviser' column already exists in sellers table";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
