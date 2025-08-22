<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Force Create Audit Table</h2>";

try {
    require_once 'includes/config.php';
    echo "✅ Config loaded successfully<br>";
    
    $pdo = getConnection();
    echo "✅ Database connection successful<br>";
    
    // Drop table if exists (for clean setup)
    try {
        $pdo->exec("DROP TABLE IF EXISTS character_audit_trail");
        echo "✅ Dropped existing table if it existed<br>";
    } catch (Exception $e) {
        echo "⚠️ Could not drop table (may not exist): " . $e->getMessage() . "<br>";
    }
    
    // Create the table without foreign key constraint initially
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
        INDEX idx_action_timestamp (action_timestamp)
    )";
    
    $pdo->exec($create_sql);
    echo "✅ Created character_audit_trail table<br>";
    
    // Check if roster table exists and has data
    $roster_check = $pdo->prepare("SELECT COUNT(*) as count FROM roster");
    $roster_check->execute();
    $roster_count = $roster_check->fetch();
    echo "ℹ️ Found " . $roster_count['count'] . " roster entries<br>";
    
    if ($roster_count['count'] > 0) {
        // Insert some test data
        $stmt = $pdo->prepare("
            INSERT INTO character_audit_trail 
            (character_id, action_type, table_name, record_id, additional_data) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        // Get a roster ID for testing
        $roster_stmt = $pdo->prepare("SELECT id FROM roster LIMIT 1");
        $roster_stmt->execute();
        $roster_id = $roster_stmt->fetchColumn();
        
        echo "ℹ️ Using roster ID " . $roster_id . " for test data<br>";
        
        $stmt->execute([
            $roster_id,
            'delete_test_record',
            'test_table',
            123,
            json_encode(['test_field' => 'test_value', 'deleted_at' => date('Y-m-d H:i:s')])
        ]);
        echo "✅ Inserted test audit log entry<br>";
        
        $stmt->execute([
            $roster_id,
            'delete_criminal_record',
            'criminal_records',
            456,
            json_encode(['offense' => 'Test Offense', 'person_name' => 'Test Person'])
        ]);
        echo "✅ Inserted test criminal record deletion<br>";
        
        $stmt->execute([
            $roster_id,
            'delete_medical_record',
            'medical_records',
            789,
            json_encode(['condition' => 'Test Condition', 'patient_name' => 'Test Patient'])
        ]);
        echo "✅ Inserted test medical record deletion<br>";
    } else {
        echo "⚠️ No roster entries found - inserting with dummy character ID<br>";
        
        $stmt = $pdo->prepare("
            INSERT INTO character_audit_trail 
            (character_id, action_type, table_name, record_id, additional_data) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            1, // Dummy ID
            'delete_test_record',
            'test_table',
            123,
            json_encode(['test_field' => 'test_value', 'deleted_at' => date('Y-m-d H:i:s')])
        ]);
        echo "✅ Inserted test audit log entry with dummy ID<br>";
    }
    
    // Check the data
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM character_audit_trail");
    $stmt->execute();
    $count = $stmt->fetch();
    
    echo "<br><strong>Total audit log records: " . $count['count'] . "</strong><br>";
    
    // Show recent entries
    $stmt = $pdo->prepare("SELECT * FROM character_audit_trail ORDER BY action_timestamp DESC LIMIT 5");
    $stmt->execute();
    $recent = $stmt->fetchAll();
    
    if ($recent) {
        echo "<h3>Recent Audit Log Entries:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Character ID</th><th>Action</th><th>Table</th><th>Record ID</th><th>Timestamp</th></tr>";
        foreach ($recent as $entry) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($entry['id']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['character_id']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['action_type']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['table_name']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['record_id']) . "</td>";
            echo "<td>" . htmlspecialchars($entry['action_timestamp']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><a href='pages/auditor_activity_log.php' style='background: #0066cc; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>View Auditor Activity Log</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack trace:</strong><br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
