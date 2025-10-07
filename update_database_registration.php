<?php
require_once 'includes/config.php';

echo "<h1>USS-VOYAGER Database Update - User Registration System</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    $pdo = getConnection();
    
    echo "<h2>Updating Users Table Structure</h2>";
    
    // Check if columns exist and add them if they don't
    $columns_to_add = [
        'position' => 'VARCHAR(100) NULL',
        'rank' => 'VARCHAR(50) NULL',
        'roster_id' => 'INT NULL'
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        try {
            // Try to select the column - if it fails, the column doesn't exist
            $pdo->query("SELECT $column FROM users LIMIT 1");
            echo "<p class='info'>Column '$column' already exists in users table.</p>";
        } catch (Exception $e) {
            // Column doesn't exist, add it
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                echo "<p class='success'>✓ Added column '$column' to users table.</p>";
            } catch (Exception $e2) {
                echo "<p class='error'>✗ Failed to add column '$column': " . $e2->getMessage() . "</p>";
            }
        }
    }
    
    // Add foreign key constraint for roster_id if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_roster FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE SET NULL");
        echo "<p class='success'>✓ Added foreign key constraint for roster_id.</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "<p class='info'>Foreign key constraint already exists.</p>";
        } else {
            echo "<p class='error'>✗ Failed to add foreign key constraint: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Database Update Complete</h2>";
    echo "<p class='success'>The database has been updated to support user registration with roster integration.</p>";
    echo "<p><a href='index.php'>Return to Main Site</a> | <a href='pages/register.php'>Test Registration</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
