<?php
// Quick diagnostic test
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP Status Check<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

try {
    echo "Testing includes...<br>";
    require_once 'includes/secure_config.php';
    echo "✓ secure_config.php loaded<br>";
    
    echo "Testing constants...<br>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'UNDEFINED') . "<br>";
    
    echo "✓ All basic tests passed!<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
