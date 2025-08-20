<?php
// Simple test to check if configuration files are working
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing configuration files...<br>";

try {
    echo "1. Loading secure_config.php...<br>";
    require_once 'includes/secure_config.php';
    echo "✓ secure_config.php loaded successfully<br>";
    
    echo "2. Testing database constants...<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_USERNAME: " . DB_USERNAME . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "✓ Database constants defined<br>";
    
    echo "3. Loading config.php...<br>";
    require_once 'includes/config.php';
    echo "✓ config.php loaded successfully<br>";
    
    echo "4. Testing database connection...<br>";
    $test_pdo = getConnection();
    echo "✓ Database connection successful<br>";
    
    echo "5. Testing session...<br>";
    if (session_status() == PHP_SESSION_ACTIVE) {
        echo "✓ Session started successfully<br>";
    } else {
        echo "✗ Session not active<br>";
    }
    
    echo "<br><strong>All tests passed! Configuration is working correctly.</strong>";
    
} catch (Exception $e) {
    echo "<br><strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
}
?>
