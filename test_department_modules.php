<?php
require_once 'includes/config.php';
require_once 'includes/department_training.php';

echo "=== Testing Department Training Module Loading ===\n\n";

$departments = ['Medical', 'Engineering', 'Security', 'Command'];

foreach ($departments as $dept) {
    echo "Testing {$dept} department:\n";
    $modules = getDepartmentModules($dept);
    echo "Found " . count($modules) . " modules:\n";
    foreach ($modules as $module) {
        echo "- {$module['module_name']} ({$module['module_code']}) - {$module['certification_level']}\n";
    }
    echo "\n";
}
?>
