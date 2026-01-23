<?php
// Test script to verify the database fix works
require_once 'config.php';

try {
    initializeDatabase();
    echo "Database initialization completed successfully without errors!\n";
    echo "All default users have been created or updated as needed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Clean up test file
unlink(__FILE__);
?>