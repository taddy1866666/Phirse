<?php
// Database migration runner
require_once 'database/config.php';

try {
    echo "Checking databases...\n";
    $result = $pdo->query("SHOW DATABASES");
    $databases = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Available databases: " . implode(", ", $databases) . "\n\n";
    
    // Check if railway database exists
    if (in_array('railway', $databases)) {
        echo "✓ 'railway' database already exists\n";
        
        // Check adviser column
        $checkColumn = $pdo->query("SHOW COLUMNS FROM sellers LIKE 'adviser'");
        if ($checkColumn->rowCount() === 0) {
            echo "Adding 'adviser' column...\n";
            $pdo->exec("ALTER TABLE sellers ADD COLUMN adviser VARCHAR(255) NULL AFTER organization");
            echo "✓ Successfully added 'adviser' column\n";
        } else {
            echo "✓ 'adviser' column already exists\n";
        }
    } else {
        echo "✗ 'railway' database does not exist\n";
        echo "Creating it now...\n";
        $pdo->exec("CREATE DATABASE railway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database 'railway' created\n";
        
        // Now import the schema
        echo "Importing schema...\n";
        $sql = file_get_contents(__DIR__ . '/database/phirse_db.sql');
        $pdo->exec("USE railway");
        $pdo->exec($sql);
        echo "✓ Schema imported successfully\n";
    }
    
    echo "\n✓ All checks completed!";
    
} catch(PDOException $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
