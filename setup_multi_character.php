<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h1>USS Voyager Multi-Character System Setup</h1>";
    echo "<p>Setting up database for multiple characters per Steam account...</p>";
    
    // Add active_character_id to users table to track which character is currently selected
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN active_character_id INT DEFAULT NULL");
        echo "<p>✅ Added active_character_id column to users table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p>ℹ️ active_character_id column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Add character_name to roster table to distinguish between characters
    try {
        $pdo->exec("ALTER TABLE roster ADD COLUMN character_name VARCHAR(100) DEFAULT NULL");
        echo "<p>✅ Added character_name column to roster table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p>ℹ️ character_name column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Add is_active to roster table to track active/inactive characters
    try {
        $pdo->exec("ALTER TABLE roster ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "<p>✅ Added is_active column to roster table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p>ℹ️ is_active column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Add created_at to roster table for character creation tracking
    try {
        $pdo->exec("ALTER TABLE roster ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p>✅ Added created_at column to roster table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p>ℹ️ created_at column already exists</p>";
        } else {
            throw $e;
        }
    }
    
    // Update existing roster entries to have character names based on their current names
    $stmt = $pdo->prepare("UPDATE roster SET character_name = CONCAT(first_name, ' ', last_name) WHERE character_name IS NULL AND first_name IS NOT NULL");
    $updated = $stmt->execute();
    $rowCount = $stmt->rowCount();
    echo "<p>✅ Updated $rowCount existing roster entries with character names</p>";
    
    // Set active character for users who don't have one set
    $stmt = $pdo->prepare("
        UPDATE users u 
        SET active_character_id = (
            SELECT r.id 
            FROM roster r 
            WHERE r.user_id = u.id 
            LIMIT 1
        ) 
        WHERE u.active_character_id IS NULL 
        AND EXISTS (SELECT 1 FROM roster r WHERE r.user_id = u.id)
    ");
    $updated = $stmt->execute();
    $rowCount = $stmt->rowCount();
    echo "<p>✅ Set active character for $rowCount users</p>";
    
    echo "<h2>Multi-Character System Setup Complete!</h2>";
    echo "<p><strong>New Features Available:</strong></p>";
    echo "<ul>";
    echo "<li>Steam accounts can now have up to 5 different crew roster profiles</li>";
    echo "<li>Users can switch between their characters from their profile page</li>";
    echo "<li>Each character has independent rank, department, position, and species</li>";
    echo "<li>Character creation page allows adding new personas</li>";
    echo "<li>Session tracks the currently active character</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>Return to Main Site</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
