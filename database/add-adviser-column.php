<?php
require_once 'config.php';

try {
    // Check if the adviser column already exists
    $stmt = $pdo->query("DESCRIBE sellers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('adviser', $columns)) {
        // Add the adviser column after organization
        $pdo->exec("ALTER TABLE sellers ADD COLUMN adviser VARCHAR(100) DEFAULT NULL AFTER organization");
        echo "Adviser column added successfully to sellers table!";
    } else {
        echo "Adviser column already exists in sellers table.";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
