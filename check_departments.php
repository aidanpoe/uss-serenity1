<?php
require_once 'includes/config.php';

echo "=== Training Modules Department Check ===\n\n";

try {
    $pdo = getConnection();
    
    // Check what departments are actually in the training_modules table
    echo "Current departments in training_modules:\n";
    $stmt = $pdo->query("SELECT DISTINCT department FROM training_modules ORDER BY department");
    $departments = $stmt->fetchAll();
    foreach ($departments as $dept) {
        echo "- " . $dept['department'] . "\n";
    }
    
    echo "\nAll training modules:\n";
    $stmt = $pdo->query("SELECT module_name, module_code, department, certification_level FROM training_modules ORDER BY department, module_name");
    $modules = $stmt->fetchAll();
    foreach ($modules as $module) {
        echo "- {$module['module_name']} ({$module['module_code']}) - {$module['department']} - {$module['certification_level']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
