<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Removing IP Address Logging - Database Migration</h2>";
    
    // Remove IP address column from training_audit table
    echo "<h3>Updating training_audit table</h3>";
    try {
        // First check if the column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM training_audit LIKE 'ip_address'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("ALTER TABLE training_audit DROP COLUMN ip_address");
            echo "<p style='color: green;'>✅ Removed ip_address column from training_audit table</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ training_audit table already doesn't have ip_address column</p>";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "<p style='color: blue;'>ℹ️ training_audit table doesn't exist yet</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating training_audit: " . $e->getMessage() . "</p>";
        }
    }
    
    // Remove IP address column from training_access_log table
    echo "<h3>Updating training_access_log table</h3>";
    try {
        // First check if the column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM training_access_log LIKE 'ip_address'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("ALTER TABLE training_access_log DROP COLUMN ip_address");
            echo "<p style='color: green;'>✅ Removed ip_address column from training_access_log table</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ training_access_log table already doesn't have ip_address column</p>";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "<p style='color: blue;'>ℹ️ training_access_log table doesn't exist yet</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating training_access_log: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check for any other tables that might have IP columns
    echo "<h3>Checking for other IP address columns</h3>";
    
    $tables_to_check = ['users', 'login_logs', 'user_sessions', 'crew_messages', 'roster'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SHOW COLUMNS FROM $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $ip_columns = [];
                foreach ($columns as $column) {
                    if (stripos($column['Field'], 'ip') !== false && stripos($column['Field'], 'addr') !== false) {
                        $ip_columns[] = $column['Field'];
                    }
                }
                
                if (!empty($ip_columns)) {
                    echo "<p style='color: orange;'>⚠️ Table '$table' has IP-related columns: " . implode(', ', $ip_columns) . "</p>";
                    echo "<p>Please manually review if these should be removed.</p>";
                } else {
                    echo "<p style='color: green;'>✅ Table '$table' - no IP columns found</p>";
                }
            } else {
                echo "<p style='color: gray;'>Table '$table' doesn't exist</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: gray;'>Cannot check table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Summary</h3>";
    echo "<p style='color: green;'>✅ IP address logging has been removed from the training system</p>";
    echo "<p>Next steps:</p>";
    echo "<ul>";
    echo "<li>Run setup_training_system.php if you need to create the tables fresh</li>";
    echo "<li>Update your privacy policy to reflect that IP addresses are no longer logged</li>";
    echo "<li>Update the GDPR cleanup script to remove IP anonymization logic</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
