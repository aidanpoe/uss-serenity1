<?php
// Minimal test version of index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html><head><title>USS-Serenity Test</title></head><body>";
echo "<h1>USS-Serenity Main Computer - Test Mode</h1>";
echo "<p>If you can see this, basic PHP is working.</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "</body></html>";
?>
