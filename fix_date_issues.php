<?php
require_once 'includes/config.php';

echo "<h2>Fix Date Issues - Processing...</h2>";

try {
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    echo "<h3>Fixing Issues:</h3>";
    
    // Fix the invalid completion dates (0000-00-00)
    echo "<h4>1. Fixing Invalid Training Completion Dates:</h4>";
    $stmt = $pdo->prepare("UPDATE crew_competencies SET completion_date = NULL WHERE completion_date = '0000-00-00' OR completion_date = '0000-00-00 00:00:00'");
    $result = $stmt->execute();
    $affected = $stmt->rowCount();
    echo "<p style='color: green;'>✅ Fixed $affected training records with invalid completion dates (set to NULL)</p>";
    
    // Fix IC dates in awards (convert 2385 back to 2025)
    echo "<h4>2. Fixing IC Dates in Awards Database:</h4>";
    $stmt = $pdo->query("SELECT id, date_awarded FROM crew_awards WHERE date_awarded > '2100-01-01'");
    $ic_awards = $stmt->fetchAll();
    
    foreach ($ic_awards as $award) {
        // Convert IC date back to OOC date (subtract 360 years)
        $ic_date = new DateTime($award['date_awarded']);
        $ooc_date = $ic_date->sub(new DateInterval('P360Y'));
        $ooc_date_str = $ooc_date->format('Y-m-d H:i:s');
        
        $update_stmt = $pdo->prepare("UPDATE crew_awards SET date_awarded = ? WHERE id = ?");
        $update_stmt->execute([$ooc_date_str, $award['id']]);
        
        echo "<p style='color: blue;'>📅 Award ID {$award['id']}: {$award['date_awarded']} → $ooc_date_str</p>";
    }
    
    // Check for any training dates that might have IC dates
    echo "<h4>3. Checking Training for IC Dates:</h4>";
    $stmt = $pdo->query("SELECT id, assigned_date FROM crew_competencies WHERE assigned_date > '2100-01-01'");
    $ic_training = $stmt->fetchAll();
    
    if (empty($ic_training)) {
        echo "<p style='color: green;'>✅ No IC dates found in training records</p>";
    } else {
        foreach ($ic_training as $training) {
            // Convert IC date back to OOC date (subtract 360 years)
            $ic_date = new DateTime($training['assigned_date']);
            $ooc_date = $ic_date->sub(new DateInterval('P360Y'));
            $ooc_date_str = $ooc_date->format('Y-m-d H:i:s');
            
            $update_stmt = $pdo->prepare("UPDATE crew_competencies SET assigned_date = ? WHERE id = ?");
            $update_stmt->execute([$ooc_date_str, $training['id']]);
            
            echo "<p style='color: blue;'>📅 Training ID {$training['id']}: {$training['assigned_date']} → $ooc_date_str</p>";
        }
    }
    
    $pdo->commit();
    echo "<h3>✅ All Date Issues Fixed!</h3>";
    
    echo "<h3>Verification:</h3>";
    echo "<p>Let's check the fixed data:</p>";
    
    // Verify awards
    echo "<h4>Awards (should now show OOC dates in DB, IC dates in display):</h4>";
    $stmt = $pdo->query("
        SELECT ca.id, ca.date_awarded, r.first_name, r.last_name, a.name as award_name
        FROM crew_awards ca 
        JOIN roster r ON ca.roster_id = r.id 
        JOIN awards a ON ca.award_id = a.id 
        ORDER BY ca.date_awarded DESC
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #e6f3ff;'>";
    echo "<th style='padding: 8px;'>Award</th>";
    echo "<th style='padding: 8px;'>Recipient</th>";
    echo "<th style='padding: 8px;'>DB Date (OOC)</th>";
    echo "<th style='padding: 8px;'>Display Date (IC)</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        $ic_display = formatICDateOnly($row['date_awarded']);
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['award_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px; color: blue;'>" . $row['date_awarded'] . "</td>";
        echo "<td style='padding: 8px; color: green;'>" . $ic_display . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verify training
    echo "<h4>Training (should show proper completion status):</h4>";
    $stmt = $pdo->query("
        SELECT cc.id, cc.assigned_date, cc.completion_date, r.first_name, r.last_name, tm.module_name
        FROM crew_competencies cc 
        JOIN roster r ON cc.roster_id = r.id 
        JOIN training_modules tm ON cc.module_id = tm.id 
        ORDER BY cc.assigned_date DESC
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #e6f3ff;'>";
    echo "<th style='padding: 8px;'>Training</th>";
    echo "<th style='padding: 8px;'>Trainee</th>";
    echo "<th style='padding: 8px;'>DB Assigned (OOC)</th>";
    echo "<th style='padding: 8px;'>IC Assigned</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch()) {
        $ic_assigned = formatICDateOnly($row['assigned_date']);
        $status = $row['completion_date'] ? 'Completed: ' . formatICDateOnly($row['completion_date']) : 'In Progress';
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['module_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px; color: blue;'>" . $row['assigned_date'] . "</td>";
        echo "<td style='padding: 8px; color: green;'>" . $ic_assigned . "</td>";
        echo "<td style='padding: 8px;'>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='background-color: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<strong>✅ Database Cleanup Complete!</strong><br>";
    echo "• Awards now store OOC dates and display as IC dates<br>";
    echo "• Training completion dates fixed (NULL instead of 0000-00-00)<br>";
    echo "• No more double-conversion issues<br>";
    echo "• Awards should now show 2385 instead of 2745<br>";
    echo "• Training should show proper IC dates instead of 0359 errors";
    echo "</p>";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
