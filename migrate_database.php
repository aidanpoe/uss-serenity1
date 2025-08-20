<?php
/**
 * Database Migration Script for USS Serenity
 * Run this after uploading to production server
 * Safe to run multiple times
 */

require_once 'includes/config.php';

echo "<h2>USS Serenity Database Migration</h2>";
echo "<p>Checking and applying database updates...</p>";

try {
    $pdo = getConnection();
    $updates_applied = [];
    $errors = [];
    
    // Migration 1: Add last_active field to roster table
    try {
        // Check if field exists
        $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'last_active'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE roster ADD COLUMN last_active TIMESTAMP NULL DEFAULT NULL");
            $updates_applied[] = "✅ Added 'last_active' field to roster table";
        } else {
            $updates_applied[] = "ℹ️ Field 'last_active' already exists in roster table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding last_active field: " . $e->getMessage();
    }
    
    // Migration 2: Ensure user_id field exists in roster table (for character assignments)
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'user_id'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE roster ADD COLUMN user_id INT NULL, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
            $updates_applied[] = "✅ Added 'user_id' field to roster table with foreign key";
        } else {
            $updates_applied[] = "ℹ️ Field 'user_id' already exists in roster table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding user_id field: " . $e->getMessage();
    }
    
    // Migration 3: Ensure is_active field exists in roster table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'is_active'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE roster ADD COLUMN is_active TINYINT(1) DEFAULT 1");
            $updates_applied[] = "✅ Added 'is_active' field to roster table";
        } else {
            $updates_applied[] = "ℹ️ Field 'is_active' already exists in roster table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding is_active field: " . $e->getMessage();
    }
    
    // Migration 4: Ensure character_name field exists in roster table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'character_name'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE roster ADD COLUMN character_name VARCHAR(100) NULL");
            $updates_applied[] = "✅ Added 'character_name' field to roster table";
        } else {
            $updates_applied[] = "ℹ️ Field 'character_name' already exists in roster table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding character_name field: " . $e->getMessage();
    }
    
    // Migration 5: Ensure users table has required fields for Steam authentication
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'steam_id'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN steam_id VARCHAR(20) UNIQUE NULL");
            $updates_applied[] = "✅ Added 'steam_id' field to users table";
        } else {
            $updates_applied[] = "ℹ️ Field 'steam_id' already exists in users table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding steam_id field: " . $e->getMessage();
    }
    
    // Migration 6: Ensure active_character_id field exists in users table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'active_character_id'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN active_character_id INT NULL, ADD FOREIGN KEY (active_character_id) REFERENCES roster(id) ON DELETE SET NULL");
            $updates_applied[] = "✅ Added 'active_character_id' field to users table with foreign key";
        } else {
            $updates_applied[] = "ℹ️ Field 'active_character_id' already exists in users table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding active_character_id field: " . $e->getMessage();
    }
    
    // Migration 7: Ensure active field exists in users table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
            $updates_applied[] = "✅ Added 'active' field to users table";
        } else {
            $updates_applied[] = "ℹ️ Field 'active' already exists in users table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding active field: " . $e->getMessage();
    }
    
    // Migration 8: Ensure last_login field exists in users table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
        $field_exists = $stmt->rowCount() > 0;
        
        if (!$field_exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL");
            $updates_applied[] = "✅ Added 'last_login' field to users table";
        } else {
            $updates_applied[] = "ℹ️ Field 'last_login' already exists in users table";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Error adding last_login field: " . $e->getMessage();
    }
    
    // Display results
    echo "<h3>Migration Results:</h3>";
    
    if (!empty($updates_applied)) {
        echo "<div style='background: #1a4a2e; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
        echo "<h4>✅ Updates Applied:</h4>";
        foreach ($updates_applied as $update) {
            echo "<p>$update</p>";
        }
        echo "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div style='background: #4a1a1a; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0;'>";
        echo "<h4>❌ Errors Encountered:</h4>";
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
        echo "</div>";
    }
    
    if (empty($errors)) {
        echo "<div style='background: #1a4a2e; padding: 15px; border: 2px solid #4caf50; margin: 20px 0; text-align: center;'>";
        echo "<h3>🎉 Database Migration Completed Successfully!</h3>";
        echo "<p>Your USS Serenity database is now up to date with all required fields.</p>";
        echo "<p><strong>Last Active tracking is now fully operational!</strong></p>";
        echo "</div>";
        
        echo "<div style='background: #2a2a2a; padding: 15px; border: 1px solid #666; margin: 10px 0;'>";
        echo "<h4>Security Recommendation:</h4>";
        echo "<p>⚠️ For security, consider removing or restricting access to this migration script after running it on production.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #4a1a1a; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in includes/config.php</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    background: #000;
    color: #fff;
    margin: 20px;
}
h2, h3, h4 {
    color: #ffcc00;
}
p {
    margin: 5px 0;
}
</style>
