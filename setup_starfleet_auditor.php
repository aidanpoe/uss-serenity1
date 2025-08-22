<?php
// Add Starfleet Auditor usergroup and invisibility system
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Starfleet Auditor Setup</title></head><body>";
echo "<h1>Setting up Starfleet Auditor Usergroup</h1>";

try {
    $pdo = getConnection();
    
    // Step 1: Add Starfleet Auditor to users department ENUM
    echo "<h2>Step 1: Adding Starfleet Auditor to users table</h2>";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN department ENUM('Captain', 'Command', 'MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Starfleet Auditor') NOT NULL");
    echo "✓ Added 'Starfleet Auditor' to users department enum.<br>";
    
    // Step 2: Add invisible flag to users table
    echo "<h2>Step 2: Adding invisibility system</h2>";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_invisible TINYINT(1) DEFAULT 0");
        echo "✓ Added is_invisible column to users table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ is_invisible column already exists.<br>";
        } else {
            throw $e;
        }
    }
    
    // Step 3: Add invisible flag to roster table
    echo "<h2>Step 3: Adding invisibility to roster</h2>";
    try {
        $pdo->exec("ALTER TABLE roster ADD COLUMN is_invisible TINYINT(1) DEFAULT 0");
        echo "✓ Added is_invisible column to roster table.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ is_invisible column already exists in roster.<br>";
        } else {
            throw $e;
        }
    }
    
    // Step 4: Create auditor management table for Captain assignments
    echo "<h2>Step 4: Creating auditor management system</h2>";
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
    
    echo "<h2>✅ Starfleet Auditor System Setup Complete!</h2>";
    echo "<p><strong>Features implemented:</strong></p>";
    echo "<ul>";
    echo "<li>Starfleet Auditor usergroup with full system access</li>";
    echo "<li>Invisibility system for OOC moderation characters</li>";
    echo "<li>Captain-only assignment and management interface</li>";
    echo "<li>Assignment tracking and audit trail</li>";
    echo "<li>Automatic roster exclusion for invisible users</li>";
    echo "<li><strong>Report Deletion:</strong> Can delete medical records, science reports, and fault reports</li>";
    echo "<li><strong>Character Management:</strong> Can delete and edit any personnel file</li>";
    echo "<li><strong>Account Management:</strong> Can delete user accounts through admin interface</li>";
    echo "</ul>";
    
    echo "<p><strong>Starfleet Auditor Capabilities:</strong></p>";
    echo "<ul>";
    echo "<li>🗑️ Delete medical records from MED/SCI department</li>";
    echo "<li>🗑️ Delete science reports from research database</li>";
    echo "<li>🗑️ Delete fault reports from ENG/OPS department</li>";
    echo "<li>✏️ Edit any personnel file in the roster</li>";
    echo "<li>🗑️ Delete any character from the roster</li>";
    echo "<li>🗑️ Delete user accounts through admin management</li>";
    echo "<li>👁️ Remain completely invisible from all public areas</li>";
    echo "<li>🔑 Full access to all restricted sections</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Update hasPermission() function to grant full access to Starfleet Auditors ✅</li>";
    echo "<li>Modify roster queries to exclude invisible users ✅</li>";
    echo "<li>Add Captain management interface for auditor assignments ✅</li>";
    echo "<li>Update all user-facing lists to respect invisibility ✅</li>";
    echo "<li>Add report deletion capabilities to department pages ✅</li>";
    echo "<li>Grant character management permissions to auditors ✅</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
