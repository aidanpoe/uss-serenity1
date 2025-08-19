<?php
require_once 'includes/config.php';

// Update the Captain's name from James to Aidan
try {
    // Update the users table
    $stmt = $pdo->prepare("UPDATE users SET first_name = 'Aidan' WHERE username = 'Poe' AND department = 'Captain'");
    $stmt->execute();
    
    // Update the roster table
    $stmt = $pdo->prepare("UPDATE roster SET first_name = 'Aidan' WHERE last_name = 'Poe' AND rank = 'Captain'");
    $stmt->execute();
    
    echo "Successfully updated Captain's name from James to Aidan Poe in both users and roster tables.";
    
} catch (PDOException $e) {
    echo "Error updating name: " . $e->getMessage();
}
?>
