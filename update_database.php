<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    
    // Check if the 'city' column exists in the stores table
    $stmt = $pdo->query("SHOW COLUMNS FROM stores LIKE 'city'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Add the city column to the stores table
        $alterSql = "ALTER TABLE stores ADD COLUMN city VARCHAR(100)";
        $pdo->exec($alterSql);
        echo "Successfully added 'city' column to stores table.\n";
    } else {
        echo "'city' column already exists in stores table.\n";
    }
    
    // Also update the assignments.php page to show store name in the assignments table
    echo "Database update completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>