<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Add deceased status to roster table
    $pdo->exec("ALTER TABLE roster ADD COLUMN IF NOT EXISTS status ENUM('Active', 'Deceased', 'Missing', 'Transferred') DEFAULT 'Active'");
    
    // Add date_of_death column for deceased crew members
    $pdo->exec("ALTER TABLE roster ADD COLUMN IF NOT EXISTS date_of_death DATE NULL");
    
    // Add cause_of_death column
    $pdo->exec("ALTER TABLE roster ADD COLUMN IF NOT EXISTS cause_of_death TEXT NULL");
    
    // Update medical_records table to include deceased as a status
    $pdo->exec("ALTER TABLE medical_records MODIFY COLUMN status ENUM('Open', 'In Progress', 'Resolved', 'Deceased') DEFAULT 'Open'");
    
    echo "✅ Database updated successfully!\n";
    echo "Added the following columns to roster table:\n";
    echo "- status (Active, Deceased, Missing, Transferred)\n";
    echo "- date_of_death\n";
    echo "- cause_of_death\n";
    echo "Updated medical_records status to include 'Deceased'\n";
    
} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
