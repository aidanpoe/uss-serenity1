<?php
// Quick fix for Captain permissions
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Update users with Captain rank or Command department characters to have Command permissions
    $stmt = $pdo->prepare("
        UPDATE users u 
        JOIN roster r ON u.active_character_id = r.id 
        SET u.department = 'Command' 
        WHERE r.rank IN ('Captain', 'Commander') OR r.department = 'Command'
    ");
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    
    echo "<h1>Permission Fix Complete</h1>";
    echo "<p>Updated $affected user(s) to have Command permissions.</p>";
    echo "<p>Users with Captain/Commander ranks or Command department characters now have full system access.</p>";
    echo '<p><a href="debug_permissions.php">Check Your Permissions</a></p>';
    echo '<p><a href="index.php">Return to Homepage</a></p>';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
