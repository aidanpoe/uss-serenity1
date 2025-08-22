<?php
// Character-Based Starfleet Auditor System - Complete Migration
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Character-Based Starfleet Auditor Migration</title></head><body>";
echo "<h1>Character-Based Starfleet Auditor System Migration</h1>";

try {
    $pdo = getConnection();
    
    // Step 1: Add Starfleet Auditor to roster department ENUM
    echo "<h2>Step 1: Database Structure Updates</h2>";
    
    try {
        $pdo->exec("ALTER TABLE roster MODIFY COLUMN department ENUM('Command', 'MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Starfleet Auditor') NOT NULL");
        echo "✓ Added 'Starfleet Auditor' to roster department enum.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Starfleet Auditor") !== false) {
            echo "ℹ 'Starfleet Auditor' already exists in roster department enum.<br>";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Add invisible flag to roster table (for auditor characters)
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
    
    // Step 3: Create character auditor tracking table
    $pdo->exec("CREATE TABLE IF NOT EXISTS character_auditor_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        roster_id INT NOT NULL,
        assigned_by_user_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        notes TEXT,
        is_active TINYINT(1) DEFAULT 1,
        FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✓ Created character_auditor_assignments tracking table.<br>";
    
    // Step 4: Add audit trail for character actions
    $pdo->exec("CREATE TABLE IF NOT EXISTS character_audit_trail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        auditor_roster_id INT NOT NULL,
        action_type ENUM('delete_report', 'delete_character', 'edit_character', 'delete_account') NOT NULL,
        target_table VARCHAR(50) NOT NULL,
        target_id INT NOT NULL,
        action_details JSON,
        performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (auditor_roster_id) REFERENCES roster(id) ON DELETE CASCADE,
        INDEX idx_auditor (auditor_roster_id),
        INDEX idx_action_type (action_type),
        INDEX idx_performed_at (performed_at)
    )");
    echo "✓ Created character_audit_trail table for tracking auditor actions.<br>";
    
    // Step 5: Update existing Starfleet Auditor characters to be invisible
    echo "<h2>Step 2: Character Migration</h2>";
    $stmt = $pdo->prepare("UPDATE roster SET is_invisible = 1 WHERE department = 'Starfleet Auditor'");
    $stmt->execute();
    $updated_count = $stmt->rowCount();
    echo "✓ Set " . $updated_count . " existing Starfleet Auditor characters to invisible.<br>";
    
    // Step 6: Verify all report tables exist for deletion capabilities
    echo "<h2>Step 3: Report Management Verification</h2>";
    
    $tables_to_check = ['medical_records', 'science_reports', 'fault_reports'];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table table exists - deletion capability enabled.<br>";
        } else {
            echo "⚠ $table table not found - create it first for full functionality.<br>";
        }
    }
    
    echo "<h2>✅ Character-Based Starfleet Auditor System Migration Complete!</h2>";
    
    echo "<h3>🎭 Character-Based Auditor System Overview</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #ff9900;'>";
    echo "<h4 style='color: #ff9900;'>How the System Works:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>🧑‍🚀 Players create characters normally through character creation</li>";
    echo "<li>👨‍✈️ Captains can assign 'Starfleet Auditor' department to specific characters</li>";
    echo "<li>🔄 When a character has 'Starfleet Auditor' department, they gain full permissions</li>";
    echo "<li>👁️ Auditor characters are automatically invisible from public rosters</li>";
    echo "<li>🎭 Perfect for OOC moderation without breaking roleplay immersion</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #ff9900;'>Character Auditor Capabilities:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>🗑️ Delete medical records from MED/SCI department</li>";
    echo "<li>🗑️ Delete science reports from research database</li>";
    echo "<li>🗑️ Delete fault reports from ENG/OPS department</li>";
    echo "<li>✏️ Edit any personnel file in the roster</li>";
    echo "<li>🗑️ Delete any character from the roster</li>";
    echo "<li>🗑️ Delete user accounts through admin management</li>";
    echo "<li>👁️ Remain completely invisible from all public areas</li>";
    echo "<li>🔑 Full access to all restricted sections</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #ff9900;'>Management Process:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>1️⃣ Player creates a character normally (Captains can select 'Starfleet Auditor' department)</li>";
    echo "<li>2️⃣ OR Captain changes existing character's department to 'Starfleet Auditor'</li>";
    echo "<li>3️⃣ Character gains invisible status and full permissions automatically</li>";
    echo "<li>4️⃣ All actions logged to character_audit_trail</li>";
    echo "<li>5️⃣ Character can be reverted by changing department back to normal role</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🔧 Technical Implementation</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #0099ff;'>";
    echo "<h4 style='color: #0099ff;'>Updated Functions:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>✅ hasPermission() - Now checks character department first</li>";
    echo "<li>✅ canEditPersonnelFiles() - Includes character auditor checks</li>";
    echo "<li>✅ switchCharacter() - Handles Starfleet Auditor department mapping</li>";
    echo "<li>✅ create_character.php - Supports Starfleet Auditor creation (Captain-only)</li>";
    echo "<li>✅ character_auditor_management.php - New management interface</li>";
    echo "</ul>";
    
    echo "<h4 style='color: #0099ff;'>Permission Priority:</h4>";
    echo "<ol style='color: white;'>";
    echo "<li>Character department = 'Starfleet Auditor' → Full access</li>";
    echo "<li>User department = 'Starfleet Auditor' → Full access (legacy)</li>";
    echo "<li>Captain/Command rank → Full access</li>";
    echo "<li>Department-specific access</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>📋 Available Interfaces</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #00ff66;'>";
    echo "<ul style='color: white;'>";
    echo "<li>🎯 <strong>Create Character:</strong> Captains can create Starfleet Auditor characters directly</li>";
    echo "<li>🛡️ <strong>Character Auditor Management:</strong> Captains can assign/revoke auditor status for any character</li>";
    echo "<li>📊 <strong>Assignment History:</strong> Track all auditor assignments and revocations</li>";
    echo "<li>🔍 <strong>Audit Trail:</strong> Log all actions performed by auditor characters</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚨 Important Notes</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #ff3366;'>";
    echo "<ul style='color: white;'>";
    echo "<li>🎯 This system works with <strong>characters</strong>, not user accounts</li>";
    echo "<li>👤 Players can have regular characters AND auditor characters</li>";
    echo "<li>🔄 Switching between characters switches permissions automatically</li>";
    echo "<li>📊 All auditor actions are logged by character ID for accountability</li>";
    echo "<li>⚖️ Only Captains can assign/revoke Starfleet Auditor status</li>";
    echo "<li>👁️ Auditor characters are completely invisible from all public displays</li>";
    echo "<li>🚫 Invisible characters don't appear in rosters, award dropdowns, or crew listings</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 System Ready</h3>";
    echo "<p><strong>The Character-Based Starfleet Auditor system is now fully operational!</strong></p>";
    echo "<p>Access the management interface through Command Center → Character Auditor Management</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Captains can create Starfleet Auditor characters directly during character creation</li>";
    echo "<li>Existing characters can be converted using the Character Auditor Management interface</li>";
    echo "<li>All auditor characters gain immediate full access and invisibility</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #330000; padding: 20px; border-radius: 10px;'>";
    echo "<h3>❌ Error During Migration</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
