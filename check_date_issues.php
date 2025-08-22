<?php
require_once 'includes/config.php';

echo "<h2>IC Date System - Data Validation and Cleanup</h2>";

try {
    $pdo = getConnection();
    
    echo "<h3>Checking for Date Issues:</h3>";
    
    // Check for invalid training dates
    echo "<h4>Training Competencies Date Issues:</h4>";
    $stmt = $pdo->query("
        SELECT cc.id, cc.assigned_date, cc.completion_date, r.first_name, r.last_name, tm.module_name
        FROM crew_competencies cc 
        JOIN roster r ON cc.roster_id = r.id 
        JOIN training_modules tm ON cc.module_id = tm.id 
        WHERE cc.assigned_date < '1900-01-01' OR cc.assigned_date > '2030-01-01'
           OR (cc.completion_date IS NOT NULL AND (cc.completion_date < '1900-01-01' OR cc.completion_date > '2030-01-01'))
        ORDER BY cc.assigned_date
    ");
    
    $issues = $stmt->fetchAll();
    if (empty($issues)) {
        echo "<p style='color: green;'>✅ No date issues found in training competencies</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #ffcccc;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Training</th>";
        echo "<th style='padding: 10px;'>Trainee</th>";
        echo "<th style='padding: 10px;'>Assigned Date</th>";
        echo "<th style='padding: 10px;'>Completion Date</th>";
        echo "</tr>";
        foreach ($issues as $issue) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . $issue['id'] . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($issue['module_name']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) . "</td>";
            echo "<td style='padding: 10px; color: red;'>" . $issue['assigned_date'] . "</td>";
            echo "<td style='padding: 10px; color: red;'>" . ($issue['completion_date'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for invalid award dates
    echo "<h4>Awards Date Issues:</h4>";
    $stmt = $pdo->query("
        SELECT ca.id, ca.date_awarded, r.first_name, r.last_name, a.name as award_name
        FROM crew_awards ca 
        JOIN roster r ON ca.roster_id = r.id 
        JOIN awards a ON ca.award_id = a.id 
        WHERE ca.date_awarded < '1900-01-01' OR ca.date_awarded > '2030-01-01'
        ORDER BY ca.date_awarded
    ");
    
    $award_issues = $stmt->fetchAll();
    if (empty($award_issues)) {
        echo "<p style='color: green;'>✅ No date issues found in awards</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #ffcccc;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Award</th>";
        echo "<th style='padding: 10px;'>Recipient</th>";
        echo "<th style='padding: 10px;'>Date Awarded</th>";
        echo "</tr>";
        foreach ($award_issues as $issue) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . $issue['id'] . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($issue['award_name']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) . "</td>";
            echo "<td style='padding: 10px; color: red;'>" . $issue['date_awarded'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for very future dates (IC dates stored in database)
    echo "<h4>Future Dates (Possible IC Dates in Database):</h4>";
    $stmt = $pdo->query("
        SELECT 'award' as type, ca.id, ca.date_awarded as date_val, r.first_name, r.last_name, a.name as item_name
        FROM crew_awards ca 
        JOIN roster r ON ca.roster_id = r.id 
        JOIN awards a ON ca.award_id = a.id 
        WHERE ca.date_awarded > '2100-01-01'
        UNION ALL
        SELECT 'training' as type, cc.id, cc.assigned_date as date_val, r.first_name, r.last_name, tm.module_name as item_name
        FROM crew_competencies cc 
        JOIN roster r ON cc.roster_id = r.id 
        JOIN training_modules tm ON cc.module_id = tm.id 
        WHERE cc.assigned_date > '2100-01-01'
        ORDER BY date_val DESC
    ");
    
    $future_dates = $stmt->fetchAll();
    if (empty($future_dates)) {
        echo "<p style='color: green;'>✅ No future dates found (good - means IC dates aren't stored in DB)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Found dates in far future - these may be IC dates stored in database:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #ffffcc;'>";
        echo "<th style='padding: 10px;'>Type</th>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Item</th>";
        echo "<th style='padding: 10px;'>Person</th>";
        echo "<th style='padding: 10px;'>Date</th>";
        echo "<th style='padding: 10px;'>Action</th>";
        echo "</tr>";
        foreach ($future_dates as $date) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . ucfirst($date['type']) . "</td>";
            echo "<td style='padding: 10px;'>" . $date['id'] . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($date['item_name']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($date['first_name'] . ' ' . $date['last_name']) . "</td>";
            echo "<td style='padding: 10px; color: orange;'>" . $date['date_val'] . "</td>";
            echo "<td style='padding: 10px;'><button onclick='fixDate(\"" . $date['type'] . "\", " . $date['id'] . ", \"" . $date['date_val'] . "\")'>Fix Date</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Fix Options:</h3>";
    echo "<p>If you see future dates (2100+), these are likely IC dates that were stored in the database. They should be converted back to OOC dates.</p>";
    echo "<p>If you see very old dates (pre-1900), these are likely data corruption and should be set to current date.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<script>
function fixDate(type, id, currentDate) {
    var newDate = prompt("Enter the correct date (YYYY-MM-DD format):\nCurrent: " + currentDate, "2025-08-23");
    if (newDate && confirm("Change " + type + " #" + id + " date from '" + currentDate + "' to '" + newDate + "'?")) {
        // In a real implementation, this would make an AJAX call to update the database
        alert("Date fix feature not implemented yet. Please update manually in database:\n\n" + 
              "For " + type + " ID " + id + ":\n" +
              "UPDATE " + (type === 'award' ? 'crew_awards' : 'crew_competencies') + 
              " SET " + (type === 'award' ? 'date_awarded' : 'assigned_date') + 
              " = '" + newDate + "' WHERE id = " + id);
    }
}
</script>
