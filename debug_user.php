<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "Not logged in. Please log in first.";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<h1>Debug User Status</h1>";
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

try {
    $pdo = getConnection();
    
    // Check user table
    echo "<h2>User Table Data:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    echo "<pre>";
    print_r($user_data);
    echo "</pre>";
    
    // Check roster table
    echo "<h2>Roster Table Data:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $roster_data = $stmt->fetchAll();
    echo "<pre>";
    print_r($roster_data);
    echo "</pre>";
    
    // Check active character
    echo "<h2>Active Character Data:</h2>";
    if ($user_data['active_character_id']) {
        $stmt = $pdo->prepare("SELECT * FROM roster WHERE id = ?");
        $stmt->execute([$user_data['active_character_id']]);
        $active_char = $stmt->fetch();
        echo "<pre>";
        print_r($active_char);
        echo "</pre>";
    } else {
        echo "No active character set.";
    }
    
    // Test getCurrentCharacter function
    echo "<h2>getCurrentCharacter() Result:</h2>";
    $current_char = getCurrentCharacter();
    echo "<pre>";
    print_r($current_char);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
