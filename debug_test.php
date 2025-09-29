<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing config loading...\n";

try {
    require_once 'includes/config.php';
    echo "Config loaded successfully!\n";
    
    echo "Showcase mode: " . (defined('SHOWCASE_MODE') ? (SHOWCASE_MODE ? 'true' : 'false') : 'not defined') . "\n";
    echo "isLoggedIn: " . (isLoggedIn() ? 'true' : 'false') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} catch (ParseError $e) {
    echo "Parse Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>