<?php
require_once 'includes/config.php';

echo "<h2>Setting up Auditor Activity Log System</h2>";

try {
    $pdo = getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'character_audit_trail'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "<p style='color: orange;'>⚠️ character_audit_trail table does not exist. Creating it now...</p>";
        
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
    } else {
        echo "<p style='color: green;'>✅ character_audit_trail table already exists</p>";
    }
    
    // Check table structure
    $stmt = $pdo->prepare("DESCRIBE character_audit_trail");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Insert a test record if logged in as Starfleet Auditor
    session_start();
    if (isset($_SESSION['character_id']) && isset($_SESSION['roster_department']) && $_SESSION['roster_department'] === 'Starfleet Auditor') {
        echo "<h3>Testing Audit Logging</h3>";
        
        // Test the logAuditorAction function
        if (function_exists('logAuditorAction')) {
            logAuditorAction($_SESSION['character_id'], 'test_action', 'test_table', 999, [
                'test_field' => 'test_value',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            echo "<p style='color: green;'>✅ Test audit log entry created</p>";
        } else {
            echo "<p style='color: red;'>❌ logAuditorAction function not found</p>";
        }
    } else {
        echo "<p style='color: orange;'>ℹ️ To test audit logging, log in as a Starfleet Auditor character</p>";
    }
    
    // Check current record count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM character_audit_trail");
    $stmt->execute();
    $count = $stmt->fetch();
    
    echo "<p><strong>Total audit log records: " . $count['count'] . "</strong></p>";
    
    if ($count['count'] > 0) {
        echo "<p style='color: green;'>✅ Audit log system is working and has data</p>";
        echo "<p><a href='pages/auditor_activity_log.php' style='background: #0066cc; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>View Auditor Activity Log</a></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No audit log entries yet. Data will appear when Starfleet Auditors perform logged actions.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>";
}
?>
