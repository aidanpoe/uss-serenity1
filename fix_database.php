<?php
require_once 'includes/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Update</title></head><body>";
echo "<h1>USS-Serenity Database Update</h1>";
echo "<style>body { font-family: Arial; margin: 20px; background: #000; color: #ff9900; } .success { color: #00ff00; } .error { color: #ff0000; } .info { color: #0099ff; }</style>";

try {
    $pdo = getConnection();
    
    echo "<h2>Updating Users Table for Registration System</h2>";
    
    // Add position column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN position VARCHAR(100) NULL");
        echo "<p class='success'>✓ Added 'position' column to users table.</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>Column 'position' already exists.</p>";
        } else {
            echo "<p class='error'>Error adding position column: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add rank column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN rank VARCHAR(50) NULL");
        echo "<p class='success'>✓ Added 'rank' column to users table.</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>Column 'rank' already exists.</p>";
        } else {
            echo "<p class='error'>Error adding rank column: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add roster_id column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN roster_id INT NULL");
        echo "<p class='success'>✓ Added 'roster_id' column to users table.</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>Column 'roster_id' already exists.</p>";
        } else {
            echo "<p class='error'>Error adding roster_id column: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add foreign key constraint
    try {
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_roster FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE SET NULL");
        echo "<p class='success'>✓ Added foreign key constraint.</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p class='info'>Foreign key constraint already exists.</p>";
        } else {
            echo "<p class='error'>Note: Foreign key constraint not added: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2 class='success'>Database Update Complete!</h2>";
    echo "<p>The users table has been updated to support the new registration system.</p>";
    echo "<p><a href='index.php' style='color: #00ff00;'>← Return to Main Site</a></p>";
    echo "<p><a href='pages/register.php' style='color: #0099ff;'>Test Registration System →</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Database connection error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in includes/config.php</p>";
}

echo "</body></html>";
?>
