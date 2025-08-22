<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Table Structure Check</h2>";
    
    // Check current table structure
    $stmt = $pdo->query("DESCRIBE character_audit_trail");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Current Table Columns:</h3>";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
    
    echo "<h3>Testing logAuditorAction function...</h3>";
    
    // Test the function
    $result = logAuditorAction(57, 'test_action', 'test_table', 999, ['test' => 'data']);
    
    if ($result) {
        echo "✅ logAuditorAction function executed successfully<br>";
    } else {
        echo "❌ logAuditorAction function failed<br>";
    }
    
    // Check if any new entries were added
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM character_audit_trail");
    $count = $stmt->fetch();
    echo "Total records after test: " . $count['count'] . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
