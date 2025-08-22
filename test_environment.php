<?php
// Test basic PHP functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Environment Test</h2>";

// Test basic PHP
echo "✅ PHP is working<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test file inclusion
echo "<h3>Testing includes/config.php</h3>";
if (file_exists('includes/config.php')) {
    echo "✅ config.php file exists<br>";
    
    try {
        include_once 'includes/config.php';
        echo "✅ config.php included successfully<br>";
        
        // Test if key functions exist
        $functions_to_check = ['getConnection', 'logAuditorAction', 'hasPermission'];
        foreach ($functions_to_check as $func) {
            if (function_exists($func)) {
                echo "✅ Function $func exists<br>";
            } else {
                echo "❌ Function $func missing<br>";
            }
        }
        
        // Test database connection
        if (function_exists('getConnection')) {
            try {
                $pdo = getConnection();
                echo "✅ Database connection successful<br>";
                
                // Test simple query
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                if ($result && $result['test'] == 1) {
                    echo "✅ Database query test passed<br>";
                } else {
                    echo "❌ Database query test failed<br>";
                }
                
            } catch (Exception $e) {
                echo "❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error including config.php: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "Error on line: " . $e->getLine() . "<br>";
    }
} else {
    echo "❌ includes/config.php file not found<br>";
}

echo "<h3>Session Information</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
if (isset($_SESSION)) {
    echo "Session variables: " . count($_SESSION) . "<br>";
    foreach ($_SESSION as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            echo "- $key: " . htmlspecialchars($value) . "<br>";
        } else {
            echo "- $key: " . gettype($value) . "<br>";
        }
    }
} else {
    echo "No session variables<br>";
}
?>
