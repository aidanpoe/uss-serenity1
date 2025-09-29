<?php
// Simple test page
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test</title></head><body>";
echo "<h1>Testing USS Serenity</h1>";

try {
    require_once 'includes/config.php';
    echo "<p>✅ Config loaded successfully!</p>";
    echo "<p>SHOWCASE_MODE: " . (defined('SHOWCASE_MODE') ? (SHOWCASE_MODE ? 'true' : 'false') : 'not defined') . "</p>";
    echo "<p>isLoggedIn(): " . (function_exists('isLoggedIn') ? (isLoggedIn() ? 'true' : 'false') : 'function not exists') . "</p>";
    echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (ParseError $e) {
    echo "<p>❌ Parse Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Error $e) {
    echo "<p>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>