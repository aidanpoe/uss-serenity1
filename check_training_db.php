<?php
require_once 'includes/config.php';

echo "=== Database Training System Structure Check ===\n\n";

try {
    $pdo = getConnection();
    
    // Check crew_competencies table structure
    echo "=== crew_competencies table structure ===\n";
    $stmt = $pdo->query('DESCRIBE crew_competencies');
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Null'] . ' - ' . $col['Key'] . "\n";
    }
    
    echo "\n=== training_modules table structure ===\n";
    $stmt = $pdo->query('DESCRIBE training_modules');
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Null'] . ' - ' . $col['Key'] . "\n";
    }
    
    // Check if the new training system columns exist
    echo "\n=== Checking required columns for new training system ===\n";
    
    $required_crew_competencies_columns = [
        'id', 'roster_id', 'module_id', 'assigned_by', 'assigned_date', 'status', 'notes'
    ];
    
    $required_training_modules_columns = [
        'id', 'module_name', 'module_code', 'certification_level', 'description', 'department', 'is_active'
    ];
    
    // Get actual columns
    $crew_comp_cols = array_column($columns, 'Field');
    
    $stmt = $pdo->query('DESCRIBE crew_competencies');
    $crew_comp_actual = array_column($stmt->fetchAll(), 'Field');
    
    $missing_crew_comp = array_diff($required_crew_competencies_columns, $crew_comp_actual);
    $missing_training_modules = array_diff($required_training_modules_columns, $crew_comp_cols);
    
    if (empty($missing_crew_comp) && empty($missing_training_modules)) {
        echo "✅ All required columns exist for the new training system!\n";
    } else {
        echo "❌ Missing columns detected:\n";
        if (!empty($missing_crew_comp)) {
            echo "crew_competencies missing: " . implode(', ', $missing_crew_comp) . "\n";
        }
        if (!empty($missing_training_modules)) {
            echo "training_modules missing: " . implode(', ', $missing_training_modules) . "\n";
        }
    }
    
    // Test the department functions
    echo "\n=== Testing department functions ===\n";
    
    // Test getting modules for Engineering
    $stmt = $pdo->prepare("
        SELECT id, module_name, module_code, certification_level, description
        FROM training_modules 
        WHERE (department = ? OR department = 'Universal') AND is_active = 1
        ORDER BY department, certification_level, module_name
    ");
    $stmt->execute(['Engineering']);
    $modules = $stmt->fetchAll();
    echo "Engineering modules available: " . count($modules) . "\n";
    
    // Test getting staff for Engineering
    $stmt = $pdo->prepare("
        SELECT r.id as roster_id, r.first_name, r.last_name, r.rank, r.department
        FROM roster r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE (r.department = ? OR r.department = 'Command') 
        AND r.is_active = 1 
        AND (u.active = 1 OR u.active IS NULL)
        ORDER BY r.department, r.rank, r.last_name, r.first_name
    ");
    $stmt->execute(['Engineering']);
    $staff = $stmt->fetchAll();
    echo "Engineering + Command staff available: " . count($staff) . "\n";
    
    echo "\n✅ Training system database structure is ready!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
