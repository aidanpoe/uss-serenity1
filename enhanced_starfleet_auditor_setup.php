<?php
// Enhanced Starfleet Auditor System - Database Migration
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Enhanced Starfleet Auditor Setup</title></head><body>";
echo "<h1>Enhanced Starfleet Auditor System Migration</h1>";

try {
    $pdo = getConnection();
    
    // Step 1: Add Starfleet Auditor to users department ENUM
    echo "<h2>Step 1: Database Structure Updates</h2>";
    
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN department ENUM('Captain', 'Command', 'MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Starfleet Auditor') NOT NULL");
        echo "✓ Added 'Starfleet Auditor' to users department enum.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Starfleet Auditor") !== false) {
            echo "ℹ 'Starfleet Auditor' already exists in users department enum.<br>";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Add invisible flag to users table
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_invisible TINYINT(1) DEFAULT 0");
        echo "✓ Added is_invisible column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ is_invisible column already exists in users table.<br>";
        } else {
            throw $e;
        }
    }
    
    // Step 3: Add invisible flag to roster table
    try {
        $pdo->exec("ALTER TABLE roster ADD COLUMN is_invisible TINYINT(1) DEFAULT 0");
        echo "✓ Added is_invisible column to roster table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ is_invisible column already exists in roster table.<br>";
        } else {
            throw $e;
        }
    }
    
    // Step 4: Create auditor management table
    $pdo->exec("CREATE TABLE IF NOT EXISTS auditor_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        assigned_by_user_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        notes TEXT,
        is_active TINYINT(1) DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✓ Created auditor_assignments tracking table.<br>";
    
    // Step 5: Verify all report tables exist and have proper indexes for deletion
    echo "<h2>Step 2: Report Management Setup</h2>";
    
    // Check medical_records table
    $stmt = $pdo->query("SHOW TABLES LIKE 'medical_records'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Medical records table exists - deletion capability enabled.<br>";
    } else {
        echo "⚠ Medical records table not found - create it first.<br>";
    }
    
    // Check science_reports table
    $stmt = $pdo->query("SHOW TABLES LIKE 'science_reports'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Science reports table exists - deletion capability enabled.<br>";
    } else {
        echo "⚠ Science reports table not found - create it first.<br>";
    }
    
    // Check fault_reports table
    $stmt = $pdo->query("SHOW TABLES LIKE 'fault_reports'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Fault reports table exists - deletion capability enabled.<br>";
    } else {
        echo "⚠ Fault reports table not found - create it first.<br>";
    }
    
    // Step 6: Add audit trail for deletions
    echo "<h2>Step 3: Audit Trail Setup</h2>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS deletion_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deleted_by_user_id INT NOT NULL,
        table_name VARCHAR(50) NOT NULL,
        record_id INT NOT NULL,
        record_data JSON,
        deletion_reason TEXT,
        deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (deleted_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_table_record (table_name, record_id),
        INDEX idx_deleted_by (deleted_by_user_id),
        INDEX idx_deleted_at (deleted_at)
    )");
    echo "✓ Created deletion_audit table for tracking all deletions.<br>";
    
    echo "<h2>✅ Enhanced Starfleet Auditor System Setup Complete!</h2>";
    
    echo "<h3>🛡️ Starfleet Auditor Capabilities</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #ff9900;'>";
    echo "<h4 style='color: #ff9900;'>Full System Access:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>✅ Bypass all permission checks (hasPermission always returns true)</li>";
    echo "<li>✅ Access all restricted areas and interfaces</li>";
    echo "<li>✅ View all content regardless of department restrictions</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #ff9900;'>Report Management:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>🗑️ Delete medical records from MED/SCI department</li>";
    echo "<li>🗑️ Delete science reports from research database</li>";
    echo "<li>🗑️ Delete fault reports from ENG/OPS department</li>";
    echo "<li>📊 All deletions are logged in audit trail</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #ff9900;'>Character Management:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>✏️ Edit any personnel file in the roster</li>";
    echo "<li>🗑️ Delete any character from the roster</li>";
    echo "<li>👤 Modify character details, ranks, departments</li>";
    echo "<li>🖼️ Manage character images and profiles</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #ff9900;'>Account Administration:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>🗑️ Delete user accounts through admin management</li>";
    echo "<li>⚙️ Access all administrative functions</li>";
    echo "<li>📋 View and manage all user data</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #ff9900;'>Invisibility Features:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>👁️ Completely invisible from all public rosters</li>";
    echo "<li>🚫 Hidden from crew listings and department displays</li>";
    echo "<li>🔒 Not shown in award recommendation dropdowns</li>";
    echo "<li>🎭 Perfect for OOC moderation without IC disruption</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>👨‍✈️ Captain Management</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #0099ff;'>";
    echo "<ul style='color: white;'>";
    echo "<li>🔑 Only Captains can assign/revoke Starfleet Auditor status</li>";
    echo "<li>📋 Access management through Command Center interface</li>";
    echo "<li>📝 Assignment tracking with notes and timestamps</li>";
    echo "<li>🔄 Easy revocation with department restoration</li>";
    echo "<li>📊 Full audit trail of all assignments</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>⚠️ Important Notes</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #ff3366;'>";
    echo "<ul style='color: white;'>";
    echo "<li>🛡️ This is an OOC (Out of Character) moderation role</li>";
    echo "<li>⚖️ Use responsibly for server administration only</li>";
    echo "<li>🔍 All actions are logged for accountability</li>";
    echo "<li>👥 Auditors remain invisible to maintain roleplay immersion</li>";
    echo "<li>🚨 Deletion actions cannot be undone - use with caution</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 System Ready</h3>";
    echo "<p><strong>The Enhanced Starfleet Auditor system is now ready for use!</strong></p>";
    echo "<p>Captains can now assign Starfleet Auditor roles through the Command Center interface.</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #330000; padding: 20px; border-radius: 10px;'>";
    echo "<h3>❌ Error During Setup</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
