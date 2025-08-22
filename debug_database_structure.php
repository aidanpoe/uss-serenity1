<?php
require_once 'includes/config.php';

echo "<h1>Database Table Analysis</h1>";

try {
    $pdo = getConnection();
    
    // Show all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Existing Tables:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
    // Check for users table structure
    if (in_array('users', $tables)) {
        echo "<h2>Users Table Structure:</h2>";
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
    background: #000;
    color: #00ff00;
}
h1, h2 {
    color: #00ffff;
}
table {
    border-collapse: collapse;
    width: 100%;
    color: #00ff00;
}
th, td {
    border: 1px solid #00ffff;
    padding: 8px;
    text-align: left;
}
th {
    background-color: #003333;
}
</style>
