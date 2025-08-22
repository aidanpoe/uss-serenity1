<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Medical Records Table Structure</h2>";
    
    $stmt = $pdo->query("DESCRIBE medical_records");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Sample Records:</h3>";
    $stmt = $pdo->query("SELECT * FROM medical_records LIMIT 3");
    $records = $stmt->fetchAll();
    
    if ($records) {
        echo "<pre>";
        print_r($records);
        echo "</pre>";
    } else {
        echo "No records found.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
