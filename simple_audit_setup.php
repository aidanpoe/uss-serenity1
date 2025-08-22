<?php
// Simple audit table creation script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Audit Table Setup</h2>";

// Basic database connection test
try {
    include_once 'includes/config.php';
    echo "✅ Config included<br>";
    
    // Test if getConnection function exists
    if (function_exists('getConnection')) {
        echo "✅ getConnection function exists<br>";
        
        $pdo = getConnection();
        echo "✅ Database connected<br>";
        
        // Simple table creation
        $sql = "CREATE TABLE IF NOT EXISTS character_audit_trail (
            id INT AUTO_INCREMENT PRIMARY KEY,
            character_id INT NOT NULL,
            action_type VARCHAR(100) NOT NULL,
            table_name VARCHAR(100) NOT NULL,
            record_id INT NOT NULL,
            additional_data TEXT,
            action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "✅ Table created/verified<br>";
        
        // Insert one simple test record
        $test_sql = "INSERT INTO character_audit_trail 
                     (character_id, action_type, table_name, record_id, additional_data) 
                     VALUES (1, 'test_action', 'test_table', 999, ?)";
        $stmt = $pdo->prepare($test_sql);
        $stmt->execute([json_encode(['test' => 'data'])]);
        echo "✅ Test record inserted<br>";
        
        // Count records
        $count_sql = "SELECT COUNT(*) as total FROM character_audit_trail";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Total records: " . $result['total'] . "<br>";
        
        echo "<br><strong>Success! Table is ready.</strong><br>";
        echo "<a href='pages/auditor_activity_log.php'>View Auditor Activity Log</a>";
        
    } else {
        echo "❌ getConnection function not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}
?>
