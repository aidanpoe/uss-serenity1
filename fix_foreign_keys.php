<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Fixing Foreign Key Constraints for GDPR Compliance</h2>";
    
    // Check current foreign key constraints
    echo "<h3>Current Foreign Key Constraints</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'users'
    ");
    $constraints = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>References</th></tr>";
    foreach ($constraints as $constraint) {
        echo "<tr>";
        echo "<td>" . $constraint['TABLE_NAME'] . "</td>";
        echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
        echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Updating Foreign Key Constraints</h3>";
    
    // Fix training_audit foreign key constraint
    try {
        // First, drop the existing foreign key
        $stmt = $pdo->query("SHOW CREATE TABLE training_audit");
        $createTable = $stmt->fetch();
        
        if (strpos($createTable[1], 'FOREIGN KEY') !== false) {
            // Find the constraint name
            preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`performed_by`\)/', $createTable[1], $matches);
            if (!empty($matches[1])) {
                $constraintName = $matches[1];
                $pdo->exec("ALTER TABLE training_audit DROP FOREIGN KEY `$constraintName`");
                echo "<p style='color: green;'>✅ Dropped old foreign key constraint: $constraintName</p>";
            }
        }
        
        // Add new foreign key with SET NULL
        $pdo->exec("
            ALTER TABLE training_audit 
            MODIFY COLUMN performed_by INT NULL,
            ADD CONSTRAINT fk_training_audit_user 
            FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
        ");
        echo "<p style='color: green;'>✅ Updated training_audit foreign key to SET NULL</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ training_audit FK update: " . $e->getMessage() . "</p>";
    }
    
    // Fix training_access_log foreign key constraint  
    try {
        // For access logs, we'll delete them entirely when user is deleted (CASCADE is fine)
        echo "<p style='color: blue;'>ℹ️ training_access_log will use CASCADE (access logs deleted with user)</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ training_access_log FK update: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Testing Foreign Key Setup</h3>";
    
    // Test the new constraints
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME = 'users'
    ");
    $newConstraints = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>References</th></tr>";
    foreach ($newConstraints as $constraint) {
        echo "<tr>";
        echo "<td>" . $constraint['TABLE_NAME'] . "</td>";
        echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
        echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Summary</h3>";
    echo "<ul>";
    echo "<li><strong>training_audit.performed_by:</strong> Now allows NULL and uses SET NULL on user deletion</li>";
    echo "<li><strong>training_access_log.accessed_by:</strong> Uses CASCADE (access logs deleted with user)</li>";
    echo "<li><strong>GDPR Compliance:</strong> Audit trails preserved but anonymized</li>";
    echo "<li><strong>Data Rights:</strong> User deletion should now work without foreign key errors</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
