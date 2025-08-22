<?php
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Training Debug</title></head><body>";
echo "<h1>Training Assignment Debug</h1>";

try {
    $pdo = getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test modules query
    echo "<h2>Testing Modules Query...</h2>";
    $modulesStmt = $pdo->query("
        SELECT id, module_name, module_code, department, certification_level 
        FROM training_modules 
        WHERE is_active = 1 
        ORDER BY department, module_name
    ");
    $modules = $modulesStmt->fetchAll();
    echo "<p>✅ Modules query successful - found " . count($modules) . " modules</p>";
    
    // Test crew query
    echo "<h2>Testing Crew Query...</h2>";
    $crewStmt = $pdo->query("
        SELECT r.id as roster_id, r.first_name, r.last_name, r.rank, r.department,
               u.id as user_id, u.username
        FROM roster r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.is_active = 1 AND (u.active = 1 OR u.active IS NULL)
        ORDER BY r.department, r.rank, r.last_name, r.first_name
    ");
    $crew = $crewStmt->fetchAll();
    echo "<p>✅ Crew query successful - found " . count($crew) . " crew members</p>";
    
    // Test assignments query
    echo "<h2>Testing Assignments Query...</h2>";
    $assignmentsStmt = $pdo->query("
        SELECT cc.*, 
               tm.module_name, tm.module_code, tm.department as module_department,
               tm.certification_level,
               r.first_name, r.last_name, r.rank as current_rank, r.department as current_department,
               u.username,
               assigner.username as assigned_by_name
        FROM crew_competencies cc
        JOIN training_modules tm ON cc.module_id = tm.id
        JOIN roster r ON cc.roster_id = r.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users assigner ON cc.assigned_by = assigner.id
        ORDER BY cc.assigned_date DESC, tm.department, r.department
        LIMIT 5
    ");
    $assignments = $assignmentsStmt->fetchAll();
    echo "<p>✅ Assignments query successful - found " . count($assignments) . " assignments</p>";
    
    echo "<h2>Sample Data:</h2>";
    echo "<h3>Modules:</h3>";
    foreach ($modules as $module) {
        echo "<p>- " . htmlspecialchars($module['module_name']) . " (" . htmlspecialchars($module['department']) . ")</p>";
    }
    
    echo "<h3>Crew:</h3>";
    foreach ($crew as $member) {
        echo "<p>- " . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . " (" . htmlspecialchars($member['department'] ?? 'Unknown') . ")</p>";
    }
    
    echo "<h3>Assignments:</h3>";
    foreach ($assignments as $assignment) {
        echo "<p>- " . htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) . " -> " . htmlspecialchars($assignment['module_name']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Error details:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
