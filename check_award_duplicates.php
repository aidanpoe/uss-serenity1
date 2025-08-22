<?php
require_once 'includes/config.php';

echo "<h3>Checking for duplicate awards in database...</h3>\n";

try {
    // Check for duplicate awards by name
    $duplicate_stmt = $pdo->query("
        SELECT name, type, COUNT(*) as count 
        FROM awards 
        GROUP BY name, type 
        HAVING COUNT(*) > 1
        ORDER BY count DESC, name
    ");
    $duplicates = $duplicate_stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✅ No duplicate awards found!</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Found duplicate awards:</p>\n";
        echo "<table border='1' style='border-collapse: collapse; margin: 1rem 0;'>\n";
        echo "<tr><th>Award Name</th><th>Type</th><th>Count</th></tr>\n";
        foreach ($duplicates as $dup) {
            echo "<tr><td>" . htmlspecialchars($dup['name']) . "</td><td>" . htmlspecialchars($dup['type']) . "</td><td>" . $dup['count'] . "</td></tr>\n";
        }
        echo "</table>\n";
    }
    
    // Show total count
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM awards");
    $total = $total_stmt->fetchColumn();
    echo "<p>Total awards in database: " . $total . "</p>\n";
    
    // Show all awards
    echo "<h4>All awards in database:</h4>\n";
    $all_stmt = $pdo->query("SELECT id, name, type, specialization FROM awards ORDER BY type, name");
    $all_awards = $all_stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 1rem 0;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Specialization</th></tr>\n";
    foreach ($all_awards as $award) {
        echo "<tr><td>" . $award['id'] . "</td><td>" . htmlspecialchars($award['name']) . "</td><td>" . htmlspecialchars($award['type']) . "</td><td>" . htmlspecialchars($award['specialization']) . "</td></tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
