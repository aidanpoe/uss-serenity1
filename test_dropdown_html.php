<?php
require_once 'includes/config.php';
require_once 'includes/department_training.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Dropdown</title>
    <style>
        body { background: black; color: white; font-family: Arial; padding: 2rem; }
        select { width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid orange; border-radius: 3px; }
        optgroup { color: orange; font-weight: bold; }
        option { color: white; }
    </style>
</head>
<body>
    <h1>Training Module Dropdown Test</h1>
    
    <h2>Medical Department</h2>
    <select>
        <option value="">Select Training Module</option>
        <?php
        $modules = getDepartmentModules('Medical');
        $current_dept = '';
        foreach ($modules as $module) {
            $module_dept = $module['department'] === 'All' ? 'Universal' : $module['department'];
            if ($module_dept !== $current_dept) {
                if ($current_dept !== '') echo '</optgroup>';
                echo '<optgroup label="' . htmlspecialchars($module_dept) . '">';
                $current_dept = $module_dept;
            }
            echo '<option value="' . $module['id'] . '">';
            echo htmlspecialchars($module['module_name']) . ' (' . htmlspecialchars($module['certification_level']) . ')';
            echo '</option>';
        }
        if ($current_dept !== '') echo '</optgroup>';
        ?>
    </select>

    <h2>Engineering Department</h2>
    <select>
        <option value="">Select Training Module</option>
        <?php
        $modules = getDepartmentModules('Engineering');
        $current_dept = '';
        foreach ($modules as $module) {
            $module_dept = $module['department'] === 'All' ? 'Universal' : $module['department'];
            if ($module_dept !== $current_dept) {
                if ($current_dept !== '') echo '</optgroup>';
                echo '<optgroup label="' . htmlspecialchars($module_dept) . '">';
                $current_dept = $module_dept;
            }
            echo '<option value="' . $module['id'] . '">';
            echo htmlspecialchars($module['module_name']) . ' (' . htmlspecialchars($module['certification_level']) . ')';
            echo '</option>';
        }
        if ($current_dept !== '') echo '</optgroup>';
        ?>
    </select>
</body>
</html>
