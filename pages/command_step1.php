<?php
echo "Testing includes...<br>";

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

echo "All basic includes working!";
?>
