<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Create some test character assignments to demonstrate multi-character last_active tracking
    
    // First, let's see what users exist
    $stmt = $pdo->query("SELECT id, username, steam_id FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<h2>Setting up Multi-Character Test Data</h2>";
    
    if (count($users) > 0) {
        $test_user = $users[0]; // Use first user for testing
        echo "<p>Using user: " . htmlspecialchars($test_user['username']) . " (ID: " . $test_user['id'] . ")</p>";
        
        // Check if this user has any roster characters assigned
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, department FROM roster WHERE user_id = ?");
        $stmt->execute([$test_user['id']]);
        $existing_chars = $stmt->fetchAll();
        
        if (count($existing_chars) > 0) {
            echo "<p>User already has " . count($existing_chars) . " character(s):</p>";
            foreach ($existing_chars as $char) {
                echo "<li>" . htmlspecialchars($char['first_name'] . ' ' . $char['last_name']) . " (" . $char['department'] . ")</li>";
            }
        } else {
            // Assign some unassigned characters to this user for testing
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, department FROM roster WHERE user_id IS NULL LIMIT 3");
            $stmt->execute();
            $available_chars = $stmt->fetchAll();
            
            if (count($available_chars) > 0) {
                echo "<p>Assigning characters to user for testing:</p>";
                
                foreach ($available_chars as $i => $char) {
                    // Assign character to user
                    $update_stmt = $pdo->prepare("UPDATE roster SET user_id = ?, is_active = 1 WHERE id = ?");
                    $update_stmt->execute([$test_user['id'], $char['id']]);
                    
                    // Set different last_active times to show variety
                    $times = [
                        0 => date('Y-m-d H:i:s'), // Now (online)
                        1 => date('Y-m-d H:i:s', strtotime('-3 hours')), // 3 hours ago
                        2 => date('Y-m-d H:i:s', strtotime('-2 days')), // 2 days ago
                    ];
                    
                    if (isset($times[$i])) {
                        $time_stmt = $pdo->prepare("UPDATE roster SET last_active = ? WHERE id = ?");
                        $time_stmt->execute([$times[$i], $char['id']]);
                    }
                    
                    echo "<li>" . htmlspecialchars($char['first_name'] . ' ' . $char['last_name']) . " (" . $char['department'] . ") - Last active: " . ($times[$i] ?? 'Never') . "</li>";
                }
                
                // Set the first character as the active one
                if (count($available_chars) > 0) {
                    $active_char_id = $available_chars[0]['id'];
                    $stmt = $pdo->prepare("UPDATE users SET active_character_id = ? WHERE id = ?");
                    $stmt->execute([$active_char_id, $test_user['id']]);
                    echo "<p><strong>Set " . htmlspecialchars($available_chars[0]['first_name'] . ' ' . $available_chars[0]['last_name']) . " as active character.</strong></p>";
                }
                
                echo "<p><strong>Multi-character test setup complete!</strong></p>";
                echo "<p>Now when you switch between these characters, each will have their own last_active timestamp.</p>";
                
            } else {
                echo "<p>No unassigned characters available for testing.</p>";
            }
        }
    } else {
        echo "<p>No users found in the database. Please log in via Steam first.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Current Character Status Overview</h3>";
    
    // Show all characters with their user assignments and last_active status
    $stmt = $pdo->query("
        SELECT r.id, r.first_name, r.last_name, r.department, r.last_active, r.user_id,
               u.username, u.steam_id, 
               CASE WHEN u.active_character_id = r.id THEN 'ACTIVE' ELSE 'Inactive' END as status
        FROM roster r 
        LEFT JOIN users u ON r.user_id = u.id 
        ORDER BY r.user_id, r.id
    ");
    $all_roster = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Character</th><th>Department</th><th>Assigned User</th><th>Status</th><th>Last Active</th></tr>";
    
    foreach ($all_roster as $char) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($char['first_name'] . ' ' . $char['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($char['department']) . "</td>";
        echo "<td>" . ($char['username'] ? htmlspecialchars($char['username']) : '<em>Unassigned</em>') . "</td>";
        echo "<td>" . $char['status'] . "</td>";
        echo "<td>" . ($char['last_active'] ? $char['last_active'] : '<em>Never</em>') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
