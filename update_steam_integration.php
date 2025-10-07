<?php
/**
 * USS Voyager Steam Integration Database Update Script
 * 
 * This script updates the users table to support Steam authentication
 * Run this once to add Steam ID column to the existing users table
 */

require 'includes/config.php';

try {
    echo "<h2>USS Voyager Steam Integration Database Update</h2>";
    
    // Check if steam_id column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'steam_id'");
    if ($checkColumn->rowCount() == 0) {
        // Add steam_id column
        $pdo->exec("ALTER TABLE users ADD COLUMN steam_id VARCHAR(20) UNIQUE AFTER username");
        echo "✅ Added steam_id column to users table<br>";
    } else {
        echo "ℹ️ steam_id column already exists<br>";
    }
    
    // Check if users table has the new user management columns
    $checkForce = $pdo->query("SHOW COLUMNS FROM users LIKE 'force_password_change'");
    if ($checkForce->rowCount() == 0) {
        // Add user management columns
        $pdo->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0 AFTER password");
        echo "✅ Added force_password_change column to users table<br>";
        
        $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER force_password_change");
        echo "✅ Added active column to users table<br>";
        
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME AFTER active");
        echo "✅ Added last_login column to users table<br>";
    } else {
        echo "ℹ️ User management columns already exist<br>";
    }
    
    echo "<br><strong>Database update completed successfully!</strong><br>";
    echo "<br>You can now use Steam authentication with your USS Voyager website.<br>";
    echo "<br><strong>Next steps:</strong><br>";
    echo "1. Get a Steam API key from: <a href='https://steamcommunity.com/dev/apikey' target='_blank'>https://steamcommunity.com/dev/apikey</a><br>";
    echo "2. Edit steamauth/SteamConfig.php and add your API key and domain name<br>";
    echo "3. Users can now register with Steam and link their accounts to roster entries<br>";
    
} catch (PDOException $e) {
    echo "❌ Error updating database: " . $e->getMessage();
}
?>
