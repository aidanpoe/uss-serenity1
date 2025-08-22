<?php
require_once 'includes/config.php';

echo "<h2>🔍 Database & System Verification</h2>";
echo "<p>This script verifies all systems are working correctly after the database update.</p>";

try {
    $pdo = getConnection();
    
    echo "<h3>📊 Database Tables Status</h3>";
    
    // List of required tables
    $required_tables = [
        'roster' => 'Main crew roster and character data',
        'users' => 'User authentication system',
        'character_audit_trail' => 'Audit logging for all administrative actions',
        'crew_awards' => 'Awarded decorations and commendations',
        'award_recommendations' => 'Pending award recommendations',
        'medical_records' => 'Medical department patient records',
        'science_reports' => 'Science department research reports',
        'security_reports' => 'Security department incident reports',
        'fault_reports' => 'Engineering department system faults',
        'criminal_records' => 'Security department criminal database'
    ];
    
    $table_status = [];
    foreach ($required_tables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $table_status[$table] = ['exists' => true, 'count' => $count, 'description' => $description];
            echo "✅ <strong>$table</strong>: $count records - $description<br>";
        } catch (Exception $e) {
            $table_status[$table] = ['exists' => false, 'error' => $e->getMessage()];
            echo "❌ <strong>$table</strong>: MISSING - $description<br>";
        }
    }
    
    echo "<h3>🎖️ Permission System Test</h3>";
    
    if (isLoggedIn()) {
        $character_id = $_SESSION['character_id'] ?? 'Not set';
        $roster_dept = $_SESSION['roster_department'] ?? 'Not set';
        $user_dept = getUserDepartment() ?? 'Not set';
        
        echo "<strong>Current User Status:</strong><br>";
        echo "Character ID: " . htmlspecialchars($character_id) . "<br>";
        echo "Roster Department: " . htmlspecialchars($roster_dept) . "<br>";
        echo "User Department: " . htmlspecialchars($user_dept) . "<br><br>";
        
        echo "<strong>Permission Tests:</strong><br>";
        echo "Has Command Permission: " . (hasPermission('Command') ? '✅ Yes' : '❌ No') . "<br>";
        echo "Can Edit Personnel Files: " . (canEditPersonnelFiles() ? '✅ Yes' : '❌ No') . "<br>";
        echo "Can Promote MED/SCI: " . (canPromoteDemote('MED/SCI') ? '✅ Yes' : '❌ No') . "<br>";
        echo "Can Promote ENG/OPS: " . (canPromoteDemote('ENG/OPS') ? '✅ Yes' : '❌ No') . "<br>";
        echo "Can Promote SEC/TAC: " . (canPromoteDemote('SEC/TAC') ? '✅ Yes' : '❌ No') . "<br>";
        
        // Test department head detection
        echo "<br><strong>Department Head Status:</strong><br>";
        echo "Is Head of MED/SCI: " . (isDepartmentHead('MED/SCI') ? '✅ Yes' : '❌ No') . "<br>";
        echo "Is Head of ENG/OPS: " . (isDepartmentHead('ENG/OPS') ? '✅ Yes' : '❌ No') . "<br>";
        echo "Is Head of SEC/TAC: " . (isDepartmentHead('SEC/TAC') ? '✅ Yes' : '❌ No') . "<br>";
        
    } else {
        echo "❌ Not logged in - cannot test user permissions<br>";
        echo "<a href='index.php'>Please log in to test permissions</a><br>";
    }
    
    echo "<h3>🔧 Function Availability Test</h3>";
    
    $functions_to_test = [
        'logAuditorAction' => 'Audit trail logging',
        'isDepartmentHead' => 'Department head detection',
        'canPromoteDemote' => 'Promotion/demotion permissions',
        'getPromotableRanks' => 'Available ranks for promotion',
        'renderPromotionForm' => 'Promotion form rendering'
    ];
    
    foreach ($functions_to_test as $function => $description) {
        if (function_exists($function)) {
            echo "✅ <strong>$function()</strong>: Available - $description<br>";
        } else {
            echo "❌ <strong>$function()</strong>: MISSING - $description<br>";
        }
    }
    
    echo "<h3>📁 File System Check</h3>";
    
    $required_files = [
        'includes/config.php' => 'Core configuration and functions',
        'includes/promotion_system.php' => 'Promotion/demotion system',
        'pages/personnel_edit.php' => 'Personnel editor interface',
        'pages/auditor_activity_log.php' => 'Audit trail viewer',
        'pages/awards_management.php' => 'Awards management system',
        'pages/promotion_guide.php' => 'Department head guide'
    ];
    
    foreach ($required_files as $file => $description) {
        if (file_exists($file)) {
            echo "✅ <strong>$file</strong>: Exists - $description<br>";
        } else {
            echo "❌ <strong>$file</strong>: MISSING - $description<br>";
        }
    }
    
    echo "<h3>🧪 Quick System Tests</h3>";
    
    // Test audit logging
    if (function_exists('logAuditorAction') && isLoggedIn() && isset($_SESSION['character_id'])) {
        try {
            $test_result = logAuditorAction($_SESSION['character_id'], 'system_test', 'verification', 0, [
                'test_type' => 'System verification test',
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'success'
            ]);
            
            if ($test_result) {
                echo "✅ <strong>Audit Logging:</strong> Test successful<br>";
            } else {
                echo "❌ <strong>Audit Logging:</strong> Test failed<br>";
            }
        } catch (Exception $e) {
            echo "❌ <strong>Audit Logging:</strong> Error - " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    } else {
        echo "⚠️ <strong>Audit Logging:</strong> Cannot test (not logged in or function missing)<br>";
    }
    
    // Test database connectivity
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM roster");
        $roster_count = $stmt->fetch()['total'];
        echo "✅ <strong>Database Connection:</strong> Working ($roster_count crew members)<br>";
    } catch (Exception $e) {
        echo "❌ <strong>Database Connection:</strong> Error - " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    
    echo "<h3>📋 Summary & Recommendations</h3>";
    
    $missing_tables = array_filter($table_status, function($status) { return !$status['exists']; });
    $total_records = array_sum(array_column(array_filter($table_status, function($status) { return $status['exists']; }), 'count'));
    
    if (empty($missing_tables)) {
        echo "<div style='background: rgba(0, 255, 0, 0.2); border: 2px solid var(--green); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
        echo "<p style='color: var(--green);'><strong>✅ System Status: OPERATIONAL</strong></p>";
        echo "<p style='color: var(--bluey);'>All required database tables exist with $total_records total records.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: rgba(255, 165, 0, 0.3); border: 2px solid var(--orange); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
        echo "<p style='color: var(--orange);'><strong>⚠️ System Status: NEEDS ATTENTION</strong></p>";
        echo "<p style='color: var(--bluey);'>Missing tables: " . implode(', ', array_keys($missing_tables)) . "</p>";
        echo "<p style='color: var(--bluey);'>Please run the database update script again.</p>";
        echo "</div>";
    }
    
    echo "<div style='text-align: center; margin: 2rem 0;'>";
    echo "<a href='update_database.php' style='background-color: var(--orange); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;'>🔄 Run Database Update</a>";
    echo "<a href='pages/command.php' style='background-color: var(--red); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;'>🎖️ Command Center</a>";
    echo "<a href='test_promotion_system.php' style='background-color: var(--blue); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;'>🧪 Test Promotion System</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
    echo "<p style='color: var(--red);'><strong>❌ Critical Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
