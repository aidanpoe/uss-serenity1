<?php
/**
 * Database Migration Script for USS Voyager
 * Run this after uploading to production server
 * Safe to run multiple times
 */

require_once 'includes/config.php';

echo "<h2>USS Voyager Database Migration</h2>";
echo "<p>Checking and applying database updates...</p>";

try {
    $pdo = getConnection();
    $updates_applied = [];
    $errors = [];
    
    // Check current database status
    $updates_applied[] = "ℹ️ Checking database schema against requirements...";
    
    // Verify all required fields are present
    $required_fields = [
        'roster' => ['last_active', 'user_id', 'character_name', 'is_active'],
        'users' => ['steam_id', 'active_character_id', 'active', 'last_login']
    ];
    
    $missing_fields = [];
    
    foreach ($required_fields as $table => $fields) {
        foreach ($fields as $field) {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$field'");
            if ($stmt->rowCount() == 0) {
                $missing_fields[$table][] = $field;
            }
        }
    }
    
    // Apply any missing migrations
    if (empty($missing_fields)) {
        $updates_applied[] = "✅ All required database fields are already present!";
        $updates_applied[] = "📊 Database schema verification complete";
    } else {
        foreach ($missing_fields as $table => $fields) {
            foreach ($fields as $field) {
                try {
                    switch ($table . '.' . $field) {
                        case 'roster.last_active':
                            $pdo->exec("ALTER TABLE roster ADD COLUMN last_active TIMESTAMP NULL DEFAULT NULL");
                            $updates_applied[] = "✅ Added 'last_active' field to roster table";
                            break;
                        case 'roster.user_id':
                            $pdo->exec("ALTER TABLE roster ADD COLUMN user_id INT NULL");
                            $updates_applied[] = "✅ Added 'user_id' field to roster table";
                            break;
                        case 'roster.character_name':
                            $pdo->exec("ALTER TABLE roster ADD COLUMN character_name VARCHAR(100) NULL");
                            $updates_applied[] = "✅ Added 'character_name' field to roster table";
                            break;
                        case 'roster.is_active':
                            $pdo->exec("ALTER TABLE roster ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                            $updates_applied[] = "✅ Added 'is_active' field to roster table";
                            break;
                        case 'users.steam_id':
                            $pdo->exec("ALTER TABLE users ADD COLUMN steam_id VARCHAR(20) UNIQUE NULL");
                            $updates_applied[] = "✅ Added 'steam_id' field to users table";
                            break;
                        case 'users.active_character_id':
                            $pdo->exec("ALTER TABLE users ADD COLUMN active_character_id INT NULL");
                            $updates_applied[] = "✅ Added 'active_character_id' field to users table";
                            break;
                        case 'users.active':
                            $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
                            $updates_applied[] = "✅ Added 'active' field to users table";
                            break;
                        case 'users.last_login':
                            $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL DEFAULT NULL");
                            $updates_applied[] = "✅ Added 'last_login' field to users table";
                            break;
                    }
                } catch (PDOException $e) {
                    $errors[] = "❌ Error adding $field to $table: " . $e->getMessage();
                }
            }
        }
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
        echo "<p>Your USS Voyager database is now up to date with all required fields.</p>";
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
