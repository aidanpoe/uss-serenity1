<?php
require_once 'includes/config.php';

// Set a test last_active timestamp for some characters
try {
    $pdo = getConnection();
    
    // Update some characters with different last_active times for testing
    $test_updates = [
        1 => date('Y-m-d H:i:s'), // Current time (online)
        2 => date('Y-m-d H:i:s', strtotime('-2 hours')), // 2 hours ago
        3 => date('Y-m-d H:i:s', strtotime('-1 day')), // 1 day ago
        4 => date('Y-m-d H:i:s', strtotime('-5 days')), // 5 days ago
        5 => date('Y-m-d H:i:s', strtotime('-2 weeks')), // 2 weeks ago
    ];
    
    foreach ($test_updates as $id => $timestamp) {
        $stmt = $pdo->prepare("UPDATE roster SET last_active = ? WHERE id = ?");
        $stmt->execute([$timestamp, $id]);
    }
    
    echo "Test data updated successfully!\n";
    echo "- Character 1: Just now (should show as Online)\n";
    echo "- Character 2: 2 hours ago\n";
    echo "- Character 3: 1 day ago\n";
    echo "- Character 4: 5 days ago\n";
    echo "- Character 5: 2 weeks ago\n\n";
    echo "Visit the roster page to see the last active timestamps!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
