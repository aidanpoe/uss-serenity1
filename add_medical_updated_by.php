<?php
/**
 * Add "updated_by" tracking to medical records
 * This will track who last updated each medical record
 */

require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Adding 'updated_by' tracking to medical records</h2>";
    
    // Check if updated_by column already exists
    $stmt = $pdo->query("DESCRIBE medical_records");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('updated_by', $columns)) {
        echo "Adding 'updated_by' column to medical_records table...<br>";
        
        // Add the updated_by column
        $sql = "ALTER TABLE medical_records ADD COLUMN updated_by VARCHAR(255) DEFAULT NULL AFTER treatment";
        $pdo->exec($sql);
        
        echo "✅ Added 'updated_by' column to medical_records table<br>";
        
        // Update existing records to show 'System' as updater for historical records
        $sql = "UPDATE medical_records SET updated_by = 'System' WHERE updated_by IS NULL";
        $stmt = $pdo->exec($sql);
        
        echo "✅ Updated {$stmt} existing records with 'System' as updater<br>";
    } else {
        echo "✅ 'updated_by' column already exists in medical_records table<br>";
    }
    
    echo "<h3>Current medical_records table structure:</h3>";
    $stmt = $pdo->query("DESCRIBE medical_records");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Sample record with new field:</h3>";
    $stmt = $pdo->query("SELECT * FROM medical_records LIMIT 1");
    $record = $stmt->fetch();
    
    if ($record) {
        echo "<pre>";
        print_r($record);
        echo "</pre>";
    } else {
        echo "No records found in medical_records table.";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
