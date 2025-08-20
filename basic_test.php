<?php
// Basic PHP test - no includes
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Basic PHP Test<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test if we can start a session
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "✓ Session started successfully<br>";
    }
} catch (Exception $e) {
    echo "✗ Session error: " . $e->getMessage() . "<br>";
}

echo "Test completed successfully!";
?>
