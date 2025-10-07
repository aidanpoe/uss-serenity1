<?php
require_once 'includes/config.php';

// Check if user is logged in and has admin privileges
if (!isLoggedIn()) {
    die("Access denied. Please login first.");
}

try {
    $pdo = getConnection();
    
    echo "<h2>Setting up USS Voyager Messaging System...</h2>";
    
    // Create crew_messages table
    $sql = "CREATE TABLE IF NOT EXISTS crew_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        sender_name VARCHAR(255) NOT NULL,
        sender_rank VARCHAR(100),
        sender_department VARCHAR(100),
        message TEXT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_deleted TINYINT(1) DEFAULT 0,
        INDEX idx_timestamp (timestamp),
        INDEX idx_sender (sender_id),
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ crew_messages table created successfully.<br>";
    
    // Create message_reactions table for future enhancements
    $sql = "CREATE TABLE IF NOT EXISTS message_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type VARCHAR(50) NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_reaction (message_id, user_id, reaction_type),
        FOREIGN KEY (message_id) REFERENCES crew_messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ message_reactions table created successfully.<br>";
    
    // Create online_status table for tracking who's online
    $sql = "CREATE TABLE IF NOT EXISTS crew_online_status (
        user_id INT PRIMARY KEY,
        character_name VARCHAR(255),
        rank_name VARCHAR(100),
        department VARCHAR(100),
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_online TINYINT(1) DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ crew_online_status table created successfully.<br>";
    
    echo "<br><h3>✅ Messaging System Setup Complete!</h3>";
    echo "<p>You can now use the crew messaging system on the main page.</p>";
    echo "<a href='index.php'>← Return to Main Page</a>";
    
} catch (PDOException $e) {
    echo "❌ Error setting up messaging system: " . $e->getMessage();
}
?>
