<?php
require_once 'includes/config.php';

echo "<h2>Database Audit Trail Investigation</h2>";

try {
    $pdo = getConnection();
    
    // Check if character_audit_trail table exists
    echo "<h3>1. Checking if character_audit_trail table exists...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'character_audit_trail'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✅ character_audit_trail table EXISTS<br>";
        
        // Check table structure
        echo "<h3>2. Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE character_audit_trail");
        $columns = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check total record count
        echo "<h3>3. Total Records in Table:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM character_audit_trail");
        $count = $stmt->fetch();
        echo "Total records: " . $count['count'] . "<br>";
        
        // Check recent records
        echo "<h3>4. Recent Records (Last 10):</h3>";
        $stmt = $pdo->query("
            SELECT 
                id,
                character_id,
                action_type,
                table_name,
                record_id,
                action_timestamp,
                additional_data
            FROM character_audit_trail 
            ORDER BY action_timestamp DESC 
            LIMIT 10
        ");
        $recent_records = $stmt->fetchAll();
        
        if ($recent_records) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Char ID</th><th>Action</th><th>Table</th><th>Record ID</th><th>Timestamp</th><th>Additional Data</th></tr>";
            foreach ($recent_records as $record) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($record['id']) . "</td>";
                echo "<td>" . htmlspecialchars($record['character_id']) . "</td>";
                echo "<td>" . htmlspecialchars($record['action_type']) . "</td>";
                echo "<td>" . htmlspecialchars($record['table_name']) . "</td>";
                echo "<td>" . htmlspecialchars($record['record_id']) . "</td>";
                echo "<td>" . htmlspecialchars($record['action_timestamp']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($record['additional_data'], 0, 100)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No records found in character_audit_trail table<br>";
        }
        
        // Check for award-related actions specifically
        echo "<h3>5. Award-Related Actions:</h3>";
        $stmt = $pdo->query("
            SELECT * FROM character_audit_trail 
            WHERE action_type IN ('assign_award', 'remove_award')
            ORDER BY action_timestamp DESC
        ");
        $award_actions = $stmt->fetchAll();
        
        if ($award_actions) {
            echo "Found " . count($award_actions) . " award-related actions:<br>";
            foreach ($award_actions as $action) {
                echo "- " . htmlspecialchars($action['action_type']) . " by character " . htmlspecialchars($action['character_id']) . " at " . htmlspecialchars($action['action_timestamp']) . "<br>";
            }
        } else {
            echo "❌ No award-related actions found<br>";
        }
        
        // Check if your character ID has any actions
        echo "<h3>6. Your Character Actions:</h3>";
        session_start();
        $your_char_id = $_SESSION['character_id'] ?? 'Not set';
        echo "Your character ID: " . htmlspecialchars($your_char_id) . "<br>";
        
        if ($your_char_id && $your_char_id !== 'Not set') {
            $stmt = $pdo->prepare("
                SELECT * FROM character_audit_trail 
                WHERE character_id = ?
                ORDER BY action_timestamp DESC
            ");
            $stmt->execute([$your_char_id]);
            $your_actions = $stmt->fetchAll();
            
            if ($your_actions) {
                echo "Found " . count($your_actions) . " actions by your character:<br>";
                foreach ($your_actions as $action) {
                    echo "- " . htmlspecialchars($action['action_type']) . " on " . htmlspecialchars($action['table_name']) . " at " . htmlspecialchars($action['action_timestamp']) . "<br>";
                }
            } else {
                echo "❌ No actions found for your character ID<br>";
            }
        }
        
    } else {
        echo "❌ character_audit_trail table does NOT exist<br>";
        echo "<h3>Creating character_audit_trail table...</h3>";
        
        $create_table_sql = "
        CREATE TABLE character_audit_trail (
            id INT AUTO_INCREMENT PRIMARY KEY,
            character_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            table_name VARCHAR(50) NOT NULL,
            record_id INT NOT NULL,
            action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            additional_data JSON
        )";
        
        try {
            $pdo->exec($create_table_sql);
            echo "✅ character_audit_trail table created successfully<br>";
            
            // Add some test data
            echo "<h3>Adding test audit data...</h3>";
            $test_data = [
                [57, 'assign_award', 'crew_awards', 100, json_encode(['recipient_name' => 'Lt Commander Test', 'award_name' => 'Starfleet Medal of Honor', 'citation' => 'Test award assignment', 'user_type' => 'Starfleet Auditor'])],
                [57, 'remove_award', 'crew_awards', 101, json_encode(['recipient_name' => 'Lt Test Person', 'award_name' => 'Service Medal', 'citation' => 'Test award removal', 'user_type' => 'Starfleet Auditor'])],
                [57, 'delete_record', 'award_recommendations', 50, json_encode(['deleted_by' => 'Starfleet Auditor', 'reason' => 'Test deletion'])],
            ];
            
            $insert_stmt = $pdo->prepare("
                INSERT INTO character_audit_trail (character_id, action_type, table_name, record_id, additional_data)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($test_data as $data) {
                $insert_stmt->execute($data);
            }
            
            echo "✅ Test audit data added<br>";
            
        } catch (Exception $e) {
            echo "❌ Error creating table: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><h3>Quick Links:</h3>";
echo "<a href='pages/auditor_activity_log.php'>View Audit Log Page</a><br>";
echo "<a href='pages/awards_management.php'>Test Awards Management</a><br>";
?>
