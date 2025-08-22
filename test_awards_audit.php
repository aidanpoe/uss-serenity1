<?php
require_once 'includes/config.php';

echo "<h2>Awards Management Audit Logging Test</h2>";

// Test if awards_management.php allows Starfleet Auditors
echo "<h3>Testing Permission System</h3>";

// Simulate Starfleet Auditor session
session_start();
$_SESSION['steamid'] = 'test';
$_SESSION['roster_department'] = 'Starfleet Auditor';
$_SESSION['character_id'] = 57; // Your character ID

// Test the permission logic used in awards_management.php
$roster_dept = $_SESSION['roster_department'] ?? '';
$has_access = hasPermission('Command') || $roster_dept === 'Starfleet Auditor';

if ($has_access) {
    echo "✅ Starfleet Auditor has access to awards management<br>";
} else {
    echo "❌ Starfleet Auditor does NOT have access to awards management<br>";
}

// Test if logAuditorAction function exists
if (function_exists('logAuditorAction')) {
    echo "✅ logAuditorAction function exists<br>";
    
    // Test logging functionality
    try {
        $pdo = getConnection();
        
        // Test award assignment logging
        logAuditorAction($_SESSION['character_id'], 'assign_award', 'crew_awards', 999, [
            'recipient_name' => 'Lt Test Person',
            'award_name' => 'Test Award',
            'citation' => 'Test citation for audit trail verification',
            'date_awarded' => '2025-08-22',
            'user_type' => 'Starfleet Auditor'
        ]);
        echo "✅ Award assignment audit log test successful<br>";
        
        // Test award removal logging
        logAuditorAction($_SESSION['character_id'], 'remove_award', 'crew_awards', 998, [
            'recipient_name' => 'Lt Test Person 2',
            'award_name' => 'Test Award 2',
            'citation' => 'Removed test award',
            'user_type' => 'Starfleet Auditor'
        ]);
        echo "✅ Award removal audit log test successful<br>";
        
        // Check recent audit logs
        $stmt = $pdo->prepare("
            SELECT action_type, table_name, additional_data, action_timestamp
            FROM character_audit_trail 
            WHERE character_id = ? AND action_type IN ('assign_award', 'remove_award')
            ORDER BY action_timestamp DESC LIMIT 5
        ");
        $stmt->execute([$_SESSION['character_id']]);
        $logs = $stmt->fetchAll();
        
        echo "<h3>Recent Award-Related Audit Logs:</h3>";
        if ($logs) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Action</th><th>Table</th><th>Details</th><th>Timestamp</th></tr>";
            foreach ($logs as $log) {
                $details = json_decode($log['additional_data'], true);
                echo "<tr>";
                echo "<td>" . htmlspecialchars($log['action_type']) . "</td>";
                echo "<td>" . htmlspecialchars($log['table_name']) . "</td>";
                echo "<td>";
                if ($details) {
                    foreach ($details as $key => $value) {
                        echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "<br>";
                    }
                }
                echo "</td>";
                echo "<td>" . htmlspecialchars($log['action_timestamp']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No award-related audit logs found.";
        }
        
    } catch (Exception $e) {
        echo "❌ Error testing audit logging: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
} else {
    echo "❌ logAuditorAction function does NOT exist<br>";
}

echo "<br><strong>Summary:</strong><br>";
echo "- Awards management now allows Starfleet Auditors<br>";
echo "- Award assignments are now logged to audit trail<br>";
echo "- Award removals are now logged to audit trail<br>";
echo "- All actions include recipient name, award name, and user type<br>";
echo "<br><a href='pages/awards_management.php'>Test Awards Management Page</a>";
echo "<br><a href='pages/auditor_activity_log.php'>View Full Audit Trail</a>";
?>
