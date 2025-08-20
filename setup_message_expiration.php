<?php
require_once 'includes/config.php';

// Check if user is logged in and has admin privileges
if (!isLoggedIn()) {
    die("Access denied. Please login first.");
}

try {
    $pdo = getConnection();
    
    echo "<h2>Setting up Message Expiration System...</h2>";
    
    // Check if expires_at column already exists
    $result = $pdo->query("SHOW COLUMNS FROM crew_messages LIKE 'expires_at'");
    if ($result->rowCount() == 0) {
        // Add expires_at column to crew_messages table
        $sql = "ALTER TABLE crew_messages 
                ADD COLUMN expires_at DATETIME NOT NULL DEFAULT (DATE_ADD(NOW(), INTERVAL 7 DAY))";
        $pdo->exec($sql);
        echo "✅ Added expires_at column to crew_messages table.<br>";
        
        // Update existing messages to expire 7 days from their creation
        $sql = "UPDATE crew_messages SET expires_at = DATE_ADD(timestamp, INTERVAL 7 DAY) WHERE expires_at IS NULL";
        $pdo->exec($sql);
        echo "✅ Updated existing messages with expiration dates.<br>";
    } else {
        echo "ℹ️ expires_at column already exists.<br>";
    }
    
    // Add index for efficient cleanup queries
    $result = $pdo->query("SHOW INDEX FROM crew_messages WHERE Key_name = 'idx_expires_at'");
    if ($result->rowCount() == 0) {
        $sql = "ALTER TABLE crew_messages ADD INDEX idx_expires_at (expires_at)";
        $pdo->exec($sql);
        echo "✅ Added index for expires_at column.<br>";
    } else {
        echo "ℹ️ expires_at index already exists.<br>";
    }
    
    // Create a cleanup procedure
    $sql = "DROP PROCEDURE IF EXISTS CleanupExpiredMessages";
    $pdo->exec($sql);
    
    $sql = "CREATE PROCEDURE CleanupExpiredMessages()
    BEGIN
        DELETE FROM crew_messages WHERE expires_at < NOW();
        SELECT ROW_COUNT() as deleted_count;
    END";
    $pdo->exec($sql);
    echo "✅ Created CleanupExpiredMessages procedure.<br>";
    
    // Perform initial cleanup
    $stmt = $pdo->query("CALL CleanupExpiredMessages()");
    $result = $stmt->fetch();
    $deletedCount = $result['deleted_count'] ?? 0;
    echo "✅ Initial cleanup completed. Deleted {$deletedCount} expired messages.<br>";
    
    echo "<br><h3>✅ Message Expiration System Setup Complete!</h3>";
    echo "<p>Messages will now automatically expire and be cleaned up after 7 days.</p>";
    echo "<p><strong>Note:</strong> You should set up a cron job to run the cleanup regularly:</p>";
    echo "<code>0 2 * * * /usr/bin/php /path/to/cleanup_messages.php</code>";
    echo "<br><br><a href='index.php'>← Return to Main Page</a>";
    
} catch (PDOException $e) {
    echo "❌ Error setting up message expiration: " . $e->getMessage();
}
?>
