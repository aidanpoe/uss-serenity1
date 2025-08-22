<?php
require_once 'includes/config.php';
require_once 'includes/department_training.php';

echo "=== Debug Training Module Grouping ===\n\n";

// Test Medical department
echo "Testing Medical department modules:\n";
$modules = getDepartmentModules('Medical');
echo "Found " . count($modules) . " modules:\n";
foreach ($modules as $module) {
    echo "- {$module['department']}: {$module['module_name']} ({$module['certification_level']})\n";
}

echo "\n=== Simulating dropdown generation ===\n";
$current_dept = '';
foreach ($modules as $module) {
    $module_dept = $module['department'] === 'All' ? 'Universal' : $module['department'];
    if ($module_dept !== $current_dept) {
        if ($current_dept !== '') echo "<!-- End optgroup: {$current_dept} -->\n";
        echo "<!-- Start optgroup: {$module_dept} -->\n";
        $current_dept = $module_dept;
    }
    echo "  Option: {$module['module_name']} ({$module['certification_level']})\n";
}
if ($current_dept !== '') echo "<!-- End optgroup: {$current_dept} -->\n";
?>
