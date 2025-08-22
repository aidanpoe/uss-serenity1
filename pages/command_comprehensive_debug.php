<?php
// Comprehensive error checking for command.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Command.php Diagnostic Report</h2>";

try {
    echo "<h3>Step 1: Basic PHP Test</h3>";
    echo "✓ PHP is working<br>";
    
    echo "<h3>Step 2: Session Test</h3>";
    session_start();
    echo "✓ Session started<br>";
    
    echo "<h3>Step 3: Config File Test</h3>";
    if (file_exists('../includes/config.php')) {
        echo "✓ Config file exists<br>";
        require_once '../includes/config.php';
        echo "✓ Config included<br>";
    } else {
        throw new Exception("Config file not found");
    }
    
    echo "<h3>Step 4: Database Connection Test</h3>";
    if (function_exists('getConnection')) {
        $pdo = getConnection();
        echo "✓ Database connection established<br>";
        
        // Test a simple query
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        echo "✓ Database query successful: " . $result['test'] . "<br>";
    } else {
        throw new Exception("getConnection function not found");
    }
    
    echo "<h3>Step 5: Department Training File Test</h3>";
    if (file_exists('../includes/department_training.php')) {
        echo "✓ Department training file exists<br>";
        require_once '../includes/department_training.php';
        echo "✓ Department training included<br>";
    } else {
        throw new Exception("Department training file not found");
    }
    
    echo "<h3>Step 6: Function Tests</h3>";
    if (function_exists('updateLastActive')) {
        echo "✓ updateLastActive function exists<br>";
        updateLastActive();
        echo "✓ updateLastActive executed<br>";
    } else {
        echo "⚠ updateLastActive function not found<br>";
    }
    
    if (function_exists('hasPermission')) {
        echo "✓ hasPermission function exists<br>";
        $perm = hasPermission('Command');
        echo "✓ hasPermission executed, result: " . ($perm ? 'true' : 'false') . "<br>";
    } else {
        echo "⚠ hasPermission function not found<br>";
    }
    
    if (function_exists('isLoggedIn')) {
        echo "✓ isLoggedIn function exists<br>";
        $logged = isLoggedIn();
        echo "✓ isLoggedIn executed, result: " . ($logged ? 'true' : 'false') . "<br>";
    } else {
        echo "⚠ isLoggedIn function not found<br>";
    }
    
    if (function_exists('handleDepartmentTraining')) {
        echo "✓ handleDepartmentTraining function exists<br>";
        if (hasPermission('Command')) {
            handleDepartmentTraining('Command');
            echo "✓ handleDepartmentTraining executed<br>";
        } else {
            echo "✓ handleDepartmentTraining skipped (no permission)<br>";
        }
    } else {
        echo "⚠ handleDepartmentTraining function not found<br>";
    }
    
    echo "<h3>Step 7: Database Table Tests</h3>";
    $tables_to_check = ['roster', 'command_suggestions', 'award_recommendations', 'medical_records', 'fault_reports', 'security_reports'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists<br>";
                
                // Try a basic count query
                $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_stmt->fetch()['count'];
                echo "&nbsp;&nbsp;→ Records: $count<br>";
            } else {
                echo "⚠ Table '$table' does not exist<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>Step 8: Query Tests</h3>";
    
    // Test command officers query
    try {
        $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer') ORDER BY FIELD(position, 'Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer')");
        $stmt->execute();
        $command_officers = $stmt->fetchAll();
        echo "✓ Command officers query successful, found " . count($command_officers) . " officers<br>";
    } catch (Exception $e) {
        echo "❌ Command officers query failed: " . $e->getMessage() . "<br>";
    }
    
    // Test summary query
    try {
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM medical_records WHERE status != 'Resolved') as open_medical,
                (SELECT COUNT(*) FROM fault_reports WHERE status != 'Resolved') as open_faults,
                (SELECT COUNT(*) FROM security_reports WHERE status != 'Resolved') as open_security,
                (SELECT COUNT(*) FROM roster) as total_crew
        ");
        $stmt->execute();
        $summary = $stmt->fetch();
        echo "✓ Summary query successful<br>";
        echo "&nbsp;&nbsp;→ Medical: " . ($summary['open_medical'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;→ Faults: " . ($summary['open_faults'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;→ Security: " . ($summary['open_security'] ?? 'N/A') . "<br>";
        echo "&nbsp;&nbsp;→ Crew: " . ($summary['total_crew'] ?? 'N/A') . "<br>";
    } catch (Exception $e) {
        echo "❌ Summary query failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>✅ All Tests Completed Successfully!</h3>";
    echo "<p style='color: green;'>The issue is likely in the HTML output section or a specific interaction between components.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Fatal Error Detected</h3>";
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>PHP Error Log Check</h3>";
if (ini_get('log_errors')) {
    echo "✓ Error logging is enabled<br>";
    $error_log = ini_get('error_log');
    echo "Error log location: " . ($error_log ?: 'system default') . "<br>";
} else {
    echo "⚠ Error logging is disabled<br>";
}
?>
