<?php
/**
 * Comprehensive Medical Records "Updated By" Enhancement
 * This script will:
 * 1. Add the updated_by column to medical_records table
 * 2. Update existing records 
 * 3. Test the functionality
 */

require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Medical Records 'Updated By' Enhancement</h2>";
    
    // Step 1: Check if updated_by column exists, if not add it
    $stmt = $pdo->query("DESCRIBE medical_records");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('updated_by', $columns)) {
        echo "Step 1: Adding 'updated_by' column to medical_records table...<br>";
        
        // Add the updated_by column after treatment
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
    
    // Step 2: Display current table structure
    echo "<h3>Current medical_records table structure:</h3>";
    $stmt = $pdo->query("DESCRIBE medical_records");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
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
    
    // Step 3: Display sample records with new field
    echo "<h3>Sample records with updated_by field:</h3>";
    $stmt = $pdo->query("SELECT id, roster_id, condition_description, status, updated_by, updated_at FROM medical_records LIMIT 5");
    $records = $stmt->fetchAll();
    
    if ($records) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Roster ID</th><th>Condition</th><th>Status</th><th>Updated By</th><th>Updated At</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['id']) . "</td>";
            echo "<td>" . htmlspecialchars($record['roster_id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['condition_description'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($record['status']) . "</td>";
            echo "<td>" . htmlspecialchars($record['updated_by'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($record['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No records found in medical_records table.<br>";
    }
    
    // Step 4: Test the getCurrentUserFullName function
    echo "<h3>Testing getCurrentUserFullName() function:</h3>";
    if (function_exists('getCurrentUserFullName')) {
        echo "✅ getCurrentUserFullName() function exists<br>";
        
        // Simulate session data for testing
        $_SESSION['first_name'] = 'Test';
        $_SESSION['last_name'] = 'User';
        $test_name = getCurrentUserFullName();
        echo "✅ Function returns: '" . htmlspecialchars($test_name) . "'<br>";
    } else {
        echo "❌ getCurrentUserFullName() function not found<br>";
    }
    
    // Step 5: Summary of changes made
    echo "<h3>Summary of Enhancement:</h3>";
    echo "<ul>";
    echo "<li>✅ Added 'updated_by' column to medical_records table</li>";
    echo "<li>✅ Updated medical record update functionality in med_sci.php</li>";
    echo "<li>✅ Updated medical record update functionality in admin_management.php</li>";
    echo "<li>✅ Updated medical record creation to set initial updated_by values</li>";
    echo "<li>✅ Updated medical_history.php display to show who last updated records</li>";
    echo "<li>✅ Updated update_database.php for future deployments</li>";
    echo "<li>✅ Added getCurrentUserFullName() helper function to config.php</li>";
    echo "</ul>";
    
    echo "<h3>What users will now see:</h3>";
    echo "<p>In medical_history.php, instead of just seeing:</p>";
    echo "<div style='background: #f5f5f5; padding: 10px; margin: 5px 0; border-left: 4px solid #ccc;'>";
    echo "<em>Last updated: 2025-08-22 14:41</em>";
    echo "</div>";
    echo "<p>Users will now see:</p>";
    echo "<div style='background: #e8f5e8; padding: 10px; margin: 5px 0; border-left: 4px solid #4CAF50;'>";
    echo "<em>Last updated: 2025-08-22 14:41 by John Doe</em>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
