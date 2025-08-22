<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Checking IP Address Tables Status</h2>";
    
    // Check if training_audit table exists and has ip_address column
    echo "<h3>Training Audit Table</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE training_audit");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $has_ip_column = false;
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'ip_address') {
                $has_ip_column = true;
            }
        }
        echo "</table>";
        
        if ($has_ip_column) {
            echo "<p style='color: green;'>✅ training_audit table has ip_address column</p>";
            
            // Check if there are any IP addresses to anonymize
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_audit WHERE ip_address IS NOT NULL AND ip_address != '' AND ip_address != 'xxx.xxx.xxx.xxx'");
            $result = $stmt->fetch();
            echo "<p>IP addresses in training_audit table: " . $result['count'] . "</p>";
            
            // Check old IPs (older than 7 days)
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_audit WHERE action_date < DATE_SUB(NOW(), INTERVAL 7 DAY) AND ip_address IS NOT NULL AND ip_address != '' AND ip_address != 'xxx.xxx.xxx.xxx'");
            $result = $stmt->fetch();
            echo "<p>IP addresses older than 7 days that need anonymizing: " . $result['count'] . "</p>";
            
        } else {
            echo "<p style='color: red;'>❌ training_audit table missing ip_address column</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ training_audit table does not exist: " . $e->getMessage() . "</p>";
    }
    
    // Check if training_access_log table exists and has ip_address column
    echo "<h3>Training Access Log Table</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE training_access_log");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $has_ip_column = false;
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'ip_address') {
                $has_ip_column = true;
            }
        }
        echo "</table>";
        
        if ($has_ip_column) {
            echo "<p style='color: green;'>✅ training_access_log table has ip_address column</p>";
            
            // Check if there are any IP addresses to anonymize
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_access_log WHERE ip_address IS NOT NULL AND ip_address != '' AND ip_address != 'xxx.xxx.xxx.xxx'");
            $result = $stmt->fetch();
            echo "<p>IP addresses in training_access_log table: " . $result['count'] . "</p>";
            
            // Check old IPs (older than 7 days)
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_access_log WHERE access_date < DATE_SUB(NOW(), INTERVAL 7 DAY) AND ip_address IS NOT NULL AND ip_address != '' AND ip_address != 'xxx.xxx.xxx.xxx'");
            $result = $stmt->fetch();
            echo "<p>IP addresses older than 7 days that need anonymizing: " . $result['count'] . "</p>";
            
        } else {
            echo "<p style='color: red;'>❌ training_access_log table missing ip_address column</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ training_access_log table does not exist: " . $e->getMessage() . "</p>";
    }
    
    // Check for other tables that might store IP addresses
    echo "<h3>Other Potential IP Storage Tables</h3>";
    
    $tables_to_check = ['users', 'login_logs', 'user_sessions', 'crew_messages'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ip_columns = [];
            foreach ($columns as $column) {
                if (stripos($column['Field'], 'ip') !== false || stripos($column['Field'], 'addr') !== false) {
                    $ip_columns[] = $column['Field'];
                }
            }
            
            if (!empty($ip_columns)) {
                echo "<p style='color: orange;'>⚠️ Table '$table' has potential IP columns: " . implode(', ', $ip_columns) . "</p>";
            } else {
                echo "<p>Table '$table' - no IP columns found</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p>Table '$table' does not exist or cannot be accessed</p>";
        }
    }
    
    echo "<h3>Recommendations</h3>";
    echo "<ul>";
    echo "<li>If tables don't exist, run <strong>setup_training_system.php</strong> first</li>";
    echo "<li>If tables exist but missing IP columns, run a database migration script</li>";
    echo "<li>If everything looks good, you can run <strong>gdpr_cleanup.php</strong> to start anonymizing old IP addresses</li>";
    echo "<li>Set up the cron job to run gdpr_cleanup.php daily</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
