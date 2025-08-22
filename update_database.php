<?php
require_once 'includes/config.php';

echo "<h2>USS-Serenity Database Update Script</h2>";
echo "<p>This script will update your database with all the latest features and improvements.</p>";

$updates_performed = [];
$errors = [];

try {
    $pdo = getConnection();
    
    echo "<h3>🔍 Checking Database Structure...</h3>";
    
    // 1. Ensure character_audit_trail table exists with correct structure
    echo "<h4>1. Character Audit Trail System</h4>";
    
    $table_exists = false;
    try {
        $stmt = $pdo->query("DESCRIBE character_audit_trail");
        $table_exists = true;
        echo "✅ character_audit_trail table exists<br>";
    } catch (Exception $e) {
        echo "❌ character_audit_trail table missing<br>";
    }
    
    if (!$table_exists) {
        echo "Creating character_audit_trail table...<br>";
        $sql = "
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
            INDEX idx_action_type (action_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created character_audit_trail table";
        echo "✅ character_audit_trail table created<br>";
    }
    
    // 2. Check and update roster table for department head positions
    echo "<h4>2. Roster Table - Department Head Positions</h4>";
    
    // Check if position column exists and is properly sized
    $stmt = $pdo->query("DESCRIBE roster");
    $columns = $stmt->fetchAll();
    $position_column = null;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'position') {
            $position_column = $column;
            break;
        }
    }
    
    if (!$position_column) {
        echo "Adding position column to roster table...<br>";
        $pdo->exec("ALTER TABLE roster ADD COLUMN position VARCHAR(100) DEFAULT '' AFTER department");
        $updates_performed[] = "Added position column to roster table";
        echo "✅ Position column added<br>";
    } else {
        echo "✅ Position column exists<br>";
        
        // Check if it needs to be expanded for longer position names
        if (strpos($position_column['Type'], 'varchar(50)') !== false) {
            echo "Expanding position column size...<br>";
            $pdo->exec("ALTER TABLE roster MODIFY position VARCHAR(100) DEFAULT ''");
            $updates_performed[] = "Expanded position column to VARCHAR(100)";
            echo "✅ Position column expanded<br>";
        }
    }
    
    // 3. Ensure roster_department column exists
    echo "<h4>3. Roster Table - Department Column for Character-Based Permissions</h4>";
    
    $roster_dept_exists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'roster_department') {
            $roster_dept_exists = true;
            break;
        }
    }
    
    if (!$roster_dept_exists) {
        echo "Adding roster_department column...<br>";
        $pdo->exec("ALTER TABLE roster ADD COLUMN roster_department VARCHAR(50) DEFAULT NULL AFTER department");
        $updates_performed[] = "Added roster_department column to roster table";
        echo "✅ roster_department column added<br>";
    } else {
        echo "✅ roster_department column exists<br>";
    }
    
    // 4. Check crew_awards table for awards management
    echo "<h4>4. Awards Management System</h4>";
    
    try {
        $stmt = $pdo->query("DESCRIBE crew_awards");
        echo "✅ crew_awards table exists<br>";
    } catch (Exception $e) {
        echo "Creating crew_awards table...<br>";
        $sql = "
        CREATE TABLE crew_awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            roster_id INT NOT NULL,
            award_name VARCHAR(255) NOT NULL,
            citation TEXT,
            date_awarded DATE,
            awarded_by VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_roster_id (roster_id),
            INDEX idx_date_awarded (date_awarded),
            FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created crew_awards table";
        echo "✅ crew_awards table created<br>";
    }
    
    // 5. Check award_recommendations table
    echo "<h4>5. Award Recommendations System</h4>";
    
    try {
        $stmt = $pdo->query("DESCRIBE award_recommendations");
        echo "✅ award_recommendations table exists<br>";
    } catch (Exception $e) {
        echo "Creating award_recommendations table...<br>";
        $sql = "
        CREATE TABLE award_recommendations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nominee_name VARCHAR(255) NOT NULL,
            recommended_award VARCHAR(255) NOT NULL,
            citation TEXT NOT NULL,
            recommended_by VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            reviewed_by VARCHAR(255) NULL,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created award_recommendations table";
        echo "✅ award_recommendations table created<br>";
    }
    
    // 6. Ensure all department record tables exist
    echo "<h4>6. Department Record Tables</h4>";
    
    // Medical records
    try {
        $stmt = $pdo->query("DESCRIBE medical_records");
        echo "✅ medical_records table exists<br>";
    } catch (Exception $e) {
        echo "Creating medical_records table...<br>";
        $sql = "
        CREATE TABLE medical_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            roster_id INT NOT NULL,
            condition_description TEXT NOT NULL,
            reported_by VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Open',
            treatment TEXT,
            updated_by VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_roster_id (roster_id),
            INDEX idx_status (status),
            FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created medical_records table";
        echo "✅ medical_records table created<br>";
    }
    
    // Science reports
    try {
        $stmt = $pdo->query("DESCRIBE science_reports");
        echo "✅ science_reports table exists<br>";
    } catch (Exception $e) {
        echo "Creating science_reports table...<br>";
        $sql = "
        CREATE TABLE science_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            reported_by VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created science_reports table";
        echo "✅ science_reports table created<br>";
    }
    
    // Security reports
    try {
        $stmt = $pdo->query("DESCRIBE security_reports");
        echo "✅ security_reports table exists<br>";
    } catch (Exception $e) {
        echo "Creating security_reports table...<br>";
        $sql = "
        CREATE TABLE security_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            incident_type VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            reported_by VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Open',
            location VARCHAR(255),
            severity VARCHAR(50) DEFAULT 'Medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_severity (severity),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created security_reports table";
        echo "✅ security_reports table created<br>";
    }
    
    // Fault reports (engineering)
    try {
        $stmt = $pdo->query("DESCRIBE fault_reports");
        echo "✅ fault_reports table exists<br>";
    } catch (Exception $e) {
        echo "Creating fault_reports table...<br>";
        $sql = "
        CREATE TABLE fault_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            system_name VARCHAR(255) NOT NULL,
            fault_description TEXT NOT NULL,
            reported_by VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Open',
            priority VARCHAR(50) DEFAULT 'Medium',
            location VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_priority (priority),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created fault_reports table";
        echo "✅ fault_reports table created<br>";
    }
    
    // Criminal records
    try {
        $stmt = $pdo->query("DESCRIBE criminal_records");
        echo "✅ criminal_records table exists<br>";
    } catch (Exception $e) {
        echo "Creating criminal_records table...<br>";
        $sql = "
        CREATE TABLE criminal_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            suspect_name VARCHAR(255) NOT NULL,
            offense VARCHAR(255) NOT NULL,
            description TEXT,
            reported_by VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Active',
            date_of_offense DATE,
            location VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_suspect_name (suspect_name),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        $updates_performed[] = "Created criminal_records table";
        echo "✅ criminal_records table created<br>";
    }
    
    // 7. Add some sample department head positions if roster is empty of heads
    echo "<h4>7. Sample Department Head Data</h4>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM roster WHERE position LIKE '%Head of%'");
    $head_count = $stmt->fetch()['count'];
    
    if ($head_count == 0) {
        echo "No department heads found. You may want to assign department head positions manually.<br>";
        echo "🔧 <strong>Manual Action Required:</strong> Use Personnel Editor to assign 'Head of MED/SCI', 'Head of ENG/OPS', or 'Head of SEC/TAC' positions.<br>";
    } else {
        echo "✅ Found $head_count department head(s) in roster<br>";
    }
    
    // 8. Add some test audit data if the table is empty
    echo "<h4>8. Audit Trail Test Data</h4>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM character_audit_trail");
    $audit_count = $stmt->fetch()['count'];
    
    if ($audit_count < 3) {
        echo "Adding sample audit trail data...<br>";
        
        // Get a character ID for test data
        $stmt = $pdo->query("SELECT id FROM roster LIMIT 1");
        $char_id = $stmt->fetchColumn();
        
        if ($char_id) {
            $test_data = [
                [$char_id, 'database_update', 'system', 0, '{"update_type":"Database structure update","script_version":"2025-08-22"}'],
                [$char_id, 'system_initialization', 'character_audit_trail', 0, '{"action":"Initial audit system setup","features_added":"promotion_system,audit_logging"}']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO character_audit_trail (character_id, action_type, table_name, record_id, additional_data)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($test_data as $data) {
                $stmt->execute($data);
            }
            
            $updates_performed[] = "Added sample audit trail entries";
            echo "✅ Sample audit data added<br>";
        }
    } else {
        echo "✅ Audit trail has $audit_count entries<br>";
    }
    
    echo "<hr>";
    echo "<h3>📋 Update Summary</h3>";
    
    if (empty($updates_performed)) {
        echo "<div style='background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
        echo "<p style='color: var(--blue);'>✅ <strong>Database is up to date!</strong> No updates were needed.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: rgba(0, 255, 0, 0.2); border: 2px solid var(--green); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
        echo "<p style='color: var(--green);'><strong>✅ Database updated successfully!</strong></p>";
        echo "<p style='color: var(--bluey);'>The following updates were performed:</p>";
        echo "<ul style='color: var(--bluey);'>";
        foreach ($updates_performed as $update) {
            echo "<li>" . htmlspecialchars($update) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div style='background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
        echo "<p style='color: var(--red);'><strong>⚠️ Some errors occurred:</strong></p>";
        echo "<ul style='color: var(--red);'>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<h3>🎯 Next Steps</h3>";
    echo "<div style='background: rgba(0,0,0,0.5); padding: 1.5rem; border-radius: 10px; margin: 1rem 0;'>";
    echo "<ul style='color: var(--bluey);'>";
    echo "<li><strong>Department Heads:</strong> Use Personnel Editor to assign department head positions ('Head of MED/SCI', etc.)</li>";
    echo "<li><strong>Starfleet Auditors:</strong> Set roster_department to 'Starfleet Auditor' for audit characters</li>";
    echo "<li><strong>Test Features:</strong> Try the promotion system, awards management, and audit trail</li>";
    echo "<li><strong>Verify Permissions:</strong> Make sure all user groups have appropriate access</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 2rem 0;'>";
    echo "<a href='pages/command.php' style='background-color: var(--red); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;'>🎖️ Command Center</a>";
    echo "<a href='pages/personnel_edit.php' style='background-color: var(--blue); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;'>👥 Personnel Editor</a>";
    echo "<a href='pages/auditor_activity_log.php' style='background-color: var(--purple); color: white; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; margin: 0.5rem;'>🔍 Audit Trail</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;'>";
    echo "<p style='color: var(--red);'><strong>❌ Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
