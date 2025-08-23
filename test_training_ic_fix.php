<?php
require_once 'includes/config.php';

echo "<h2>Training Assignment IC Date Fix - Verification</h2>";
echo "<p>Current Date: " . date('Y-m-d H:i:s') . " (OOC)</p>";
echo "<p>IC Date: " . date('Y-m-d H:i:s', strtotime('+360 years')) . " (IC)</p>";

try {
    $pdo = getConnection();
    
    echo "<h3>✅ Changes Made to Training Assignment:</h3>";
    echo "<ul>";
    echo "<li>✅ Individual training assignment now uses IC dates</li>";
    echo "<li>✅ Bulk training assignment now uses IC dates</li>";
    echo "<li>✅ Training completion dates now use IC dates</li>";
    echo "</ul>";
    
    echo "<h3>📋 Recent Training Assignments:</h3>";
    $stmt = $pdo->query("
        SELECT cc.assigned_date, cc.completion_date, cc.status, 
               r.first_name, r.last_name, tm.module_name,
               u.username as assigned_by
        FROM crew_competencies cc 
        JOIN roster r ON cc.roster_id = r.id 
        JOIN training_modules tm ON cc.module_id = tm.id 
        LEFT JOIN users u ON cc.assigned_by = u.id
        ORDER BY cc.assigned_date DESC
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>Training</th>";
    echo "<th style='padding: 8px;'>Trainee</th>";
    echo "<th style='padding: 8px;'>Assigned Date</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Assigned By</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        $assigned_year = substr($row['assigned_date'], 0, 4);
        $is_ic_date = $assigned_year >= 2100; // Check if it's an IC date
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['module_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px; color: " . ($is_ic_date ? 'green' : 'red') . ";'>";
        echo $row['assigned_date'];
        echo " " . ($is_ic_date ? "(✅ IC)" : "(❌ OOC)");
        echo "</td>";
        echo "<td style='padding: 8px;'>" . ucfirst($row['status']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['assigned_by'] ?: 'Unknown') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🧪 Test IC Date Calculation:</h3>";
    echo "<div style='background-color: #e8f4f8; padding: 15px; border-left: 4px solid #2196F3;'>";
    echo "<p><strong>Current OOC Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>IC Date (OOC + 360 years):</strong> " . date('Y-m-d H:i:s', strtotime('+360 years')) . "</p>";
    echo "<p><strong>Expected IC Year:</strong> " . (date('Y') + 360) . "</p>";
    echo "</div>";
    
    echo "<h3>📝 What This Fixes:</h3>";
    echo "<div style='background-color: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<p><strong>Before:</strong> Training assignments showed OOC dates (2025)</p>";
    echo "<p><strong>Now:</strong> Training assignments will show IC dates (" . (date('Y') + 360) . ")</p>";
    echo "<p><strong>Result:</strong> Consistent IC date system across all features</p>";
    echo "</div>";
    
    echo "<h3>🎯 Next Steps:</h3>";
    echo "<p>Try assigning a new training to someone - it should now use the IC date " . date('Y-m-d', strtotime('+360 years')) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
table { width: 100%; margin: 10px 0; }
th, td { text-align: left; }
</style>
