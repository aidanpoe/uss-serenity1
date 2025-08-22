<?php
// Character-Based Starfleet Auditor System Setup
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Character-Based Starfleet Auditor Setup</title></head><body>";
echo "<h1>Setting up Character-Based Starfleet Auditor System</h1>";

try {
    $pdo = getConnection();
    
    // Step 1: Add Starfleet Auditor to roster department ENUM
    echo "<h2>Step 1: Adding Starfleet Auditor to roster characters</h2>";
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
    echo "<h2>Step 2: Adding invisibility system for characters</h2>";
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
    echo "<h2>Step 3: Creating character auditor management system</h2>";
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
    echo "<h2>Step 4: Setting up audit trail</h2>";
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
    
    echo "<h2>✅ Character-Based Starfleet Auditor System Setup Complete!</h2>";
    
    echo "<h3>🎭 Character-Based Auditor System</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #ff9900;'>";
    echo "<h4 style='color: #ff9900;'>How It Works:</h4>";
    echo "<ul style='color: white;'>";
    echo "<li>🧑‍🚀 Players create normal characters through character creation</li>";
    echo "<li>👨‍✈️ Captains can designate specific characters as 'Starfleet Auditor'</li>";
    echo "<li>🔄 When a character is set to Starfleet Auditor department, they gain full permissions</li>";
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
    echo "<li>1️⃣ Player creates a character normally</li>";
    echo "<li>2️⃣ Captain changes character's department to 'Starfleet Auditor'</li>";
    echo "<li>3️⃣ Character gains invisible status and full permissions</li>";
    echo "<li>4️⃣ All actions logged to character_audit_trail</li>";
    echo "<li>5️⃣ Character can be reverted by changing department back</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>📋 Next Steps</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #0099ff;'>";
    echo "<ul style='color: white;'>";
    echo "<li>✅ Database structure updated for character-based auditors</li>";
    echo "<li>⚠️ Update hasPermission() function to check character department</li>";
    echo "<li>⚠️ Modify command.php to manage character auditor assignments</li>";
    echo "<li>⚠️ Update all permission checks to use active character data</li>";
    echo "<li>⚠️ Add automatic invisibility for Starfleet Auditor characters</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚨 Important Notes</h3>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; border: 2px solid #ff3366;'>";
    echo "<ul style='color: white;'>";
    echo "<li>🎯 This system works with characters, not user accounts</li>";
    echo "<li>👤 Players can have regular characters AND auditor characters</li>";
    echo "<li>🔄 Switching between characters switches permissions</li>";
    echo "<li>📊 All auditor actions are logged by character ID</li>";
    echo "<li>⚖️ Captains control which characters become auditors</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #330000; padding: 20px; border-radius: 10px;'>";
    echo "<h3>❌ Error During Setup</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
