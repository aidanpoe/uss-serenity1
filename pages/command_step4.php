<?php
echo "Testing function calls...<br>";

session_start();
echo "Session started<br>";

// Test config include
include '../includes/config.php';
echo "Config included successfully<br>";

// Test department_training include
include '../includes/department_training.php';
echo "Department training included successfully<br>";

echo "About to call updateLastActive()...<br>";

// Test the function calls that command.php makes
try {
    updateLastActive();
    echo "updateLastActive() called successfully<br>";
} catch (Exception $e) {
    echo "Error in updateLastActive(): " . $e->getMessage() . "<br>";
}

echo "About to test hasPermission()...<br>";

try {
    $hasCommand = hasPermission('Command');
    echo "hasPermission('Command') returned: " . ($hasCommand ? 'true' : 'false') . "<br>";
} catch (Exception $e) {
    echo "Error in hasPermission(): " . $e->getMessage() . "<br>";
}

echo "Function tests complete!";
?>
