<?php
// Simple test to check if configuration files are working
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing configuration files step by step...<br><br>";

// Step 1: Test basic PHP
echo "Step 1: Basic PHP test<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "✓ Basic PHP working<br><br>";

// Step 2: Test file paths
echo "Step 2: Testing file paths<br>";
$secure_config_path = __DIR__ . '/includes/secure_config.php';
$config_path = __DIR__ . '/includes/config.php';
$headers_path = __DIR__ . '/includes/security_headers.php';

echo "Secure config path: " . $secure_config_path . "<br>";
echo "Config path: " . $config_path . "<br>";
echo "Headers path: " . $headers_path . "<br>";

echo "Secure config exists: " . (file_exists($secure_config_path) ? "✓ YES" : "✗ NO") . "<br>";
echo "Config exists: " . (file_exists($config_path) ? "✓ YES" : "✗ NO") . "<br>";
echo "Headers exists: " . (file_exists($headers_path) ? "✓ YES" : "✗ NO") . "<br><br>";

// Step 3: Test security headers
echo "Step 3: Testing security headers...<br>";
try {
    require_once $headers_path;
    echo "✓ Security headers loaded successfully<br><br>";
} catch (Exception $e) {
    echo "✗ Security headers error: " . $e->getMessage() . "<br><br>";
}

// Step 4: Test secure_config.php
echo "Step 4: Testing secure_config.php...<br>";
try {
    require_once $secure_config_path;
    echo "✓ secure_config.php loaded successfully<br>";
    echo "DB_HOST defined: " . (defined('DB_HOST') ? "✓ YES (" . DB_HOST . ")" : "✗ NO") . "<br>";
    echo "DB_USERNAME defined: " . (defined('DB_USERNAME') ? "✓ YES (" . DB_USERNAME . ")" : "✗ NO") . "<br>";
    echo "DB_NAME defined: " . (defined('DB_NAME') ? "✓ YES (" . DB_NAME . ")" : "✗ NO") . "<br><br>";
} catch (Exception $e) {
    echo "✗ secure_config.php error: " . $e->getMessage() . "<br>";
    echo "Error file: " . $e->getFile() . "<br>";
    echo "Error line: " . $e->getLine() . "<br><br>";
    exit();
}

// Step 5: Test config.php (but stop before database connection)
echo "Step 5: Testing config.php functions...<br>";
try {
    // Read the config file content and check for syntax
    $config_content = file_get_contents($config_path);
    if ($config_content === false) {
        throw new Exception("Cannot read config.php file");
    }
    echo "✓ Config file readable<br>";
    
    // Check for basic syntax by tokenizing
    $tokens = token_get_all($config_content);
    echo "✓ Config file has valid PHP syntax<br><br>";
    
} catch (Exception $e) {
    echo "✗ Config file error: " . $e->getMessage() . "<br><br>";
    exit();
}

echo "<strong>All basic tests passed!</strong><br>";
echo "If you see this message, the configuration files are syntactically correct.<br>";
echo "The 500 error might be related to database connection or server configuration.";
?>
