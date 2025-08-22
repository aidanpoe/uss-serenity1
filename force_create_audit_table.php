<?php
require_once 'includes/config.php';

// Force create the audit trail table
try {
    $pdo = getConnection();
    
    // Drop table if exists (for clean setup)
    $pdo->exec("DROP TABLE IF EXISTS character_audit_trail");
    echo "Dropped existing table if it existed<br>";
    
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
        INDEX idx_action_timestamp (action_timestamp)
    )";
    
    $pdo->exec($create_sql);
    echo "✅ Created character_audit_trail table<br>";
    
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
    
    if ($roster_id) {
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
            json_encode(['offense' => 'Test Offense', 'person_name' => 'Test Person']])
        ]);
        echo "✅ Inserted test criminal record deletion<br>";
        
        $stmt->execute([
            $roster_id,
            'delete_medical_record',
            'medical_records',
            789,
            json_encode(['condition' => 'Test Condition', 'patient_name' => 'Test Patient']])
        ]);
        echo "✅ Inserted test medical record deletion<br>";
    } else {
        echo "❌ No roster entries found for testing<br>";
    }
    
    // Check the data
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM character_audit_trail");
    $stmt->execute();
    $count = $stmt->fetch();
    
    echo "<br><strong>Total audit log records: " . $count['count'] . "</strong><br>";
    
    echo "<br><a href='pages/auditor_activity_log.php' style='background: #0066cc; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>View Auditor Activity Log</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
