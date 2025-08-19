<?php
require_once 'includes/config.php';

echo "<h1>Database Update: User Profile Management</h1>";

try {
    $pdo = getConnection();
    
    echo "<h2>Adding force_password_change column to users table...</h2>";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'force_password_change'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Add force_password_change column
        $pdo->exec("ALTER TABLE users ADD COLUMN force_password_change BOOLEAN DEFAULT 0 AFTER password");
        echo "<p style='color: green;'>✅ Added force_password_change column to users table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ force_password_change column already exists</p>";
    }
    
    // Check if active column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
    $active_exists = $stmt->fetch();
    
    if (!$active_exists) {
        // Add active column
        $pdo->exec("ALTER TABLE users ADD COLUMN active BOOLEAN DEFAULT 1 AFTER force_password_change");
        echo "<p style='color: green;'>✅ Added active column to users table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ active column already exists</p>";
    }
    
    // Check if last_login column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    $last_login_exists = $stmt->fetch();
    
    if (!$last_login_exists) {
        // Add last_login column
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER active");
        echo "<p style='color: green;'>✅ Added last_login column to users table</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ last_login column already exists</p>";
    }
    
    echo "<h2>Updated Users Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='color: white; background: #333; border-collapse: collapse;'>";
    echo "<tr><th style='padding: 8px;'>Column</th><th style='padding: 8px;'>Type</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$column['Field']}</td>";
        echo "<td style='padding: 8px;'>{$column['Type']}</td>";
        echo "<td style='padding: 8px;'>{$column['Null']}</td>";
        echo "<td style='padding: 8px;'>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Database update complete!</h2>";
    echo "<p>New features available:</p>";
    echo "<ul>";
    echo "<li><strong>User Profile Management:</strong> <a href='pages/profile.php' style='color: #66ccff;'>pages/profile.php</a></li>";
    echo "<li><strong>Captain User Management:</strong> <a href='pages/user_management.php' style='color: #66ccff;'>pages/user_management.php</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px; }
table { margin: 10px 0; }
a { color: #66ccff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
