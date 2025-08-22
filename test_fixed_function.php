<?php
require_once 'includes/config.php';

echo "<h2>Testing Fixed logAuditorAction Function</h2>";

try {
    // Test the function with your character ID
    echo "Testing with character ID 57...<br>";
    
    $result = logAuditorAction(57, 'test_award_action', 'crew_awards', 123, [
        'recipient_name' => 'Test Recipient',
        'award_name' => 'Test Award',
        'citation' => 'Test citation for function testing',
        'user_type' => 'Starfleet Auditor'
    ]);
    
    if ($result) {
        echo "✅ logAuditorAction function worked successfully!<br>";
    } else {
        echo "❌ logAuditorAction function failed<br>";
    }
    
    // Check the results
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM character_audit_trail 
        WHERE character_id = ? AND action_type = 'test_award_action'
    ");
    $stmt->execute([57]);
    $test_count = $stmt->fetch();
    
    echo "Test entries for character 57: " . $test_count['count'] . "<br>";
    
    // Show recent entries for your character
    $stmt = $pdo->prepare("
        SELECT * FROM character_audit_trail 
        WHERE character_id = ? 
        ORDER BY action_timestamp DESC 
        LIMIT 5
    ");
    $stmt->execute([57]);
    $your_entries = $stmt->fetchAll();
    
    if ($your_entries) {
        echo "<h3>Your Recent Audit Entries:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Action</th><th>Table</th><th>Record ID</th><th>Timestamp</th><th>Details</th></tr>";
        foreach ($your_entries as $entry) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($entry['action_type']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['table_name']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['record_id']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['action_timestamp']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($entry['additional_data'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No entries found for your character.<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='pages/auditor_activity_log.php'>View Audit Activity Log</a><br>";
echo "<a href='pages/awards_management.php'>Test Awards Management</a>";
?>
