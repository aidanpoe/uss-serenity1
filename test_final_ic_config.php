<?php
require_once 'includes/config.php';

echo "<h2>IC Date System - Final Configuration Test</h2>";
echo "<p>Current Date: " . date('Y-m-d') . " (OOC) | " . formatICDateOnly(date('Y-m-d')) . " (IC)</p>";

try {
    $pdo = getConnection();
    
    echo "<h3>✅ Changes Made:</h3>";
    echo "<ul>";
    echo "<li>✅ Removed 'Completed: [date]' from crew card training displays</li>";
    echo "<li>✅ Changed award forms to default to IC years (" . formatICDateOnly(date('Y-m-d')) . ")</li>";
    echo "<li>✅ Changed criminal record forms to default to IC years</li>";
    echo "<li>✅ Updated APIs to pass dates through without formatting (since forms submit IC dates)</li>";
    echo "</ul>";
    
    echo "<h3>📝 How It Now Works:</h3>";
    echo "<div style='background-color: #e8f4f8; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0;'>";
    echo "<h4>Form Behavior:</h4>";
    echo "<ul>";
    echo "<li><strong>Awards Form:</strong> Defaults to <strong>" . formatICDateOnly(date('Y-m-d')) . "</strong> (IC date)</li>";
    echo "<li><strong>Criminal Records Form:</strong> Defaults to <strong>" . formatICDateOnly(date('Y-m-d')) . "</strong> (IC date)</li>";
    echo "<li><strong>User Input:</strong> Users see and enter IC dates (2385)</li>";
    echo "<li><strong>Database Storage:</strong> IC dates stored directly in database</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background-color: #e8f5e8; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0;'>";
    echo "<h4>Display Behavior:</h4>";
    echo "<ul>";
    echo "<li><strong>Awards:</strong> Show IC dates as entered (2385-XX-XX)</li>";
    echo "<li><strong>Training:</strong> Only show assigned dates, NO completion dates</li>";
    echo "<li><strong>APIs:</strong> Return dates as-is from database</li>";
    echo "<li><strong>No Double Conversion:</strong> No more 2745 or 0359 date errors</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test Current Data:</h3>";
    
    // Test awards
    echo "<h4>Recent Awards:</h4>";
    $stmt = $pdo->query("
        SELECT ca.date_awarded, r.first_name, r.last_name, a.name as award_name
        FROM crew_awards ca 
        JOIN roster r ON ca.roster_id = r.id 
        JOIN awards a ON ca.award_id = a.id 
        ORDER BY ca.date_awarded DESC
        LIMIT 3
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>Award</th>";
    echo "<th style='padding: 8px;'>Recipient</th>";
    echo "<th style='padding: 8px;'>Date (will display as-is)</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['award_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px; font-weight: bold;'>" . $row['date_awarded'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test training
    echo "<h4>Recent Training (Completion Dates Hidden):</h4>";
    $stmt = $pdo->query("
        SELECT cc.assigned_date, cc.completion_date, r.first_name, r.last_name, tm.module_name
        FROM crew_competencies cc 
        JOIN roster r ON cc.roster_id = r.id 
        JOIN training_modules tm ON cc.module_id = tm.id 
        ORDER BY cc.assigned_date DESC
        LIMIT 3
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>Training</th>";
    echo "<th style='padding: 8px;'>Trainee</th>";
    echo "<th style='padding: 8px;'>Assigned (will show)</th>";
    echo "<th style='padding: 8px;'>Completed (HIDDEN from display)</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['module_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px; color: green;'>" . $row['assigned_date'] . "</td>";
        echo "<td style='padding: 8px; color: #888; text-decoration: line-through;'>" . ($row['completion_date'] ?: 'NULL') . " (not displayed)</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎯 Expected Results:</h3>";
    echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
    echo "<ul>";
    echo "<li><strong>Awards:</strong> Should now show proper IC years (2385) instead of 2745</li>";
    echo "<li><strong>Training Cards:</strong> No more 'Completed: 0359-12-03' text appearing</li>";
    echo "<li><strong>New Awards:</strong> Form will default to " . formatICDateOnly(date('Y-m-d')) . " for easy IC date entry</li>";
    echo "<li><strong>Consistent System:</strong> All dates handled as IC dates throughout</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
table { width: 100%; margin: 10px 0; }
th, td { text-align: left; }
.success { color: #28a745; }
.warning { color: #ffc107; }
.error { color: #dc3545; }
</style>
