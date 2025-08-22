<?php
echo "Testing with department_training include...<br>";

session_start();
echo "Session started<br>";

// Test config include
include '../includes/config.php';
echo "Config included successfully<br>";

// Test database connection
try {
    $stmt = $pdo->query('SELECT 1');
    echo "Database connection working<br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "About to include department_training.php...<br>";

// Test department_training include
include '../includes/department_training.php';
echo "Department training included successfully<br>";

echo "All includes working!";
?>
