<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Create the character_audit_trail table
    $sql = "
    CREATE TABLE IF NOT EXISTS character_audit_trail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        character_id INT NOT NULL,
        action_type VARCHAR(100) NOT NULL,
        table_name VARCHAR(100) NOT NULL,
        record_id INT NOT NULL,
        additional_data TEXT,
        action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_character_id (character_id),
        INDEX idx_action_timestamp (action_timestamp)
    )";
    
    $pdo->exec($sql);
    echo "character_audit_trail table created successfully.\n";
    
    // Insert some test data for your character
    $test_data = [
        [57, 'assign_award', 'crew_awards', 100, '{"recipient_name":"Lt Commander Test","award_name":"Starfleet Medal of Honor","citation":"Test award assignment","user_type":"Starfleet Auditor"}'],
        [57, 'remove_award', 'crew_awards', 101, '{"recipient_name":"Lt Test Person","award_name":"Service Medal","citation":"Test award removal","user_type":"Starfleet Auditor"}'],
        [57, 'delete_record', 'award_recommendations', 50, '{"deleted_by":"Starfleet Auditor","reason":"Test deletion"}'],
        [57, 'delete_record', 'medical_records', 75, '{"patient_name":"Test Patient","condition":"Test Condition","deleted_by":"Starfleet Auditor"}'],
        [57, 'delete_record', 'criminal_records', 25, '{"suspect_name":"Test Suspect","offense":"Test Offense","deleted_by":"Starfleet Auditor"}']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO character_audit_trail (character_id, action_type, table_name, record_id, additional_data)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($test_data as $data) {
        $stmt->execute($data);
    }
    
    echo "Test audit data inserted successfully.\n";
    echo "Total entries created: " . count($test_data) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
