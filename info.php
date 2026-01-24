<?php
// Basic PHP information and error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Server Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";

echo "<h2>Environment Variables</h2>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h2>Database Configuration</h2>";
echo "<p><strong>DB_HOST:</strong> " . (defined('DB_HOST') ? constant('DB_HOST') : getenv('DB_HOST') ?: 'Not defined') . "</p>";
echo "<p><strong>DB_NAME:</strong> " . (defined('DB_NAME') ? constant('DB_NAME') : getenv('DB_NAME') ?: 'Not defined') . "</p>";
echo "<p><strong>DB_USER:</strong> " . (defined('DB_USER') ? constant('DB_USER') : getenv('DB_USER') ?: 'Not defined') . "</p>";

// Try to include config and test database
if(file_exists('config.php')) {
    require_once 'config.php';
    
    if(function_exists('getConnection')) {
        echo "<h2>Database Test</h2>";
        try {
            $pdo = getConnection();
            echo "<p style='color: green;'>✓ Database connection successful!</p>";
            
            // Test a simple query
            $result = $pdo->query("SELECT 1 as test");
            if($result) {
                echo "<p style='color: green;'>✓ Simple query executed successfully!</p>";
            }
        } catch(Exception $e) {
            echo "<p style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>config.php not found!</p>";
}

echo "<h2>File Permissions</h2>";
$files = ['config.php', 'includes/auth.php', 'login.php', 'index.php'];
foreach($files as $file) {
    if(file_exists($file)) {
        echo "<p><strong>$file:</strong> Readable: " . (is_readable($file) ? 'Yes' : 'No') . ", Writable: " . (is_writable($file) ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p><strong>$file:</strong> Does not exist</p>";
    }
}
?>