<?php
require_once '../includes/config.php';

echo "<h2>Debugging Auditor Activity Log</h2>";

try {
    $pdo = getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'character_audit_trail'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ character_audit_trail table exists</p>";
        
        // Check table structure
        $stmt = $pdo->prepare("DESCRIBE character_audit_trail");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<h3>Table Structure:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . htmlspecialchars($column['Field']) . " - " . htmlspecialchars($column['Type']) . "</li>";
        }
        echo "</ul>";
        
        // Check for any data
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM character_audit_trail");
        $stmt->execute();
        $count = $stmt->fetch();
        
        echo "<p><strong>Total records in table: " . $count['count'] . "</strong></p>";
        
        if ($count['count'] > 0) {
            // Show recent entries
            $stmt = $pdo->prepare("
                SELECT 
                    cat.*,
                    r.first_name,
                    r.last_name,
                    r.rank
                FROM character_audit_trail cat
                LEFT JOIN roster r ON cat.character_id = r.id
                ORDER BY cat.action_timestamp DESC
                LIMIT 10
            ");
            $stmt->execute();
            $recent_logs = $stmt->fetchAll();
            
            echo "<h3>Recent Log Entries:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Character</th><th>Action</th><th>Table</th><th>Record ID</th><th>Timestamp</th></tr>";
            foreach ($recent_logs as $log) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($log['id']) . "</td>";
                echo "<td>" . htmlspecialchars(($log['rank'] ?? 'Unknown') . ' ' . ($log['first_name'] ?? 'Unknown') . ' ' . ($log['last_name'] ?? 'User')) . "</td>";
                echo "<td>" . htmlspecialchars($log['action_type']) . "</td>";
                echo "<td>" . htmlspecialchars($log['table_name']) . "</td>";
                echo "<td>" . htmlspecialchars($log['record_id']) . "</td>";
                echo "<td>" . htmlspecialchars($log['action_timestamp']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No audit log entries found. This means:</p>";
            echo "<ul>";
            echo "<li>No Starfleet Auditor has performed any logged actions yet</li>";
            echo "<li>The logging system is working but no actions have been performed</li>";
            echo "</ul>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ character_audit_trail table does not exist</p>";
        echo "<p>Creating the table now...</p>";
        
        // Create the table
        $create_sql = "
        CREATE TABLE character_audit_trail (
            id INT AUTO_INCREMENT PRIMARY KEY,
            character_id INT NOT NULL,
            action_type VARCHAR(100) NOT NULL,
            table_name VARCHAR(100) NOT NULL,
            record_id INT NOT NULL,
            additional_data TEXT,
            action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_character_id (character_id),
            INDEX idx_action_timestamp (action_timestamp),
            FOREIGN KEY (character_id) REFERENCES roster(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_sql);
        echo "<p style='color: green;'>✅ character_audit_trail table created successfully</p>";
    }
    
    // Check current session info
    echo "<h3>Current Session Info:</h3>";
    echo "<ul>";
    echo "<li>Character ID: " . ($_SESSION['character_id'] ?? 'Not set') . "</li>";
    echo "<li>Roster Department: " . ($_SESSION['roster_department'] ?? 'Not set') . "</li>";
    echo "<li>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</li>";
    echo "</ul>";
    
    // Test the logAuditorAction function
    if (function_exists('logAuditorAction')) {
        echo "<p style='color: green;'>✅ logAuditorAction function exists</p>";
    } else {
        echo "<p style='color: red;'>❌ logAuditorAction function does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
