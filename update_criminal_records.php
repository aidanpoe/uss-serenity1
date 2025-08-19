<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Create criminal_records table
    $pdo->exec("CREATE TABLE IF NOT EXISTS criminal_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        roster_id INT NOT NULL,
        incident_type ENUM('Minor Infraction', 'Major Violation', 'Court Martial', 'Criminal Activity', 'Disciplinary Action') NOT NULL,
        incident_date DATE NOT NULL,
        incident_description TEXT NOT NULL,
        investigation_details TEXT,
        evidence_notes TEXT,
        punishment_type ENUM('Verbal Warning', 'Written Reprimand', 'Loss of Privileges', 'Demotion', 'Confinement', 'Court Martial', 'Dismissal', 'Other') DEFAULT 'Verbal Warning',
        punishment_details TEXT,
        punishment_duration VARCHAR(100),
        investigating_officer VARCHAR(100),
        reported_by VARCHAR(100),
        status ENUM('Under Investigation', 'Closed - Guilty', 'Closed - Not Guilty', 'Closed - Insufficient Evidence', 'Pending Review') DEFAULT 'Under Investigation',
        classification ENUM('Public', 'Restricted', 'Classified') DEFAULT 'Restricted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
    )");
    
    // Add criminal record count to roster for quick access
    $pdo->exec("ALTER TABLE roster ADD COLUMN IF NOT EXISTS criminal_record_count INT DEFAULT 0");
    
    echo "✅ Criminal Records database updated successfully!\n";
    echo "Created criminal_records table with the following features:\n";
    echo "- Incident tracking with types and classifications\n";
    echo "- Investigation details and evidence notes\n";
    echo "- Punishment tracking with duration\n";
    echo "- Security access controls\n";
    echo "- Case status management\n";
    echo "Added criminal_record_count column to roster table\n";
    
} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
