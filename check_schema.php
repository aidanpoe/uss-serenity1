<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "Roster table columns:\n";
    $stmt = $pdo->query('DESCRIBE roster');
    while ($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nUsers table columns:\n";
    $stmt = $pdo->query('DESCRIBE users');
    while ($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
