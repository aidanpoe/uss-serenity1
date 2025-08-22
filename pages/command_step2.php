<?php
echo "Testing sessions...<br>";

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

// Check session
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in - this is normal for testing<br>";
} else {
    echo "User logged in: " . $_SESSION['user_id'] . "<br>";
}

echo "Session test complete!";
?>
