<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Add last_active column to roster table
    $pdo->exec("ALTER TABLE roster ADD COLUMN last_active TIMESTAMP NULL DEFAULT NULL");
    
    echo "Successfully added last_active column to roster table!\n";
    
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'last_active' already exists in roster table.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
