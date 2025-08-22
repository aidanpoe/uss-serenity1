<?php
require_once 'includes/config.php';

echo "<h1>Training Competency System Test</h1>";

try {
    $pdo = getConnection();
    
    // Check if training_modules table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'training_modules'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ training_modules table exists</p>";
        
        // Check module count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_modules");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Total training modules: {$count}</p>";
        
        if ($count > 0) {
            echo "<p style='color: green;'>✅ Training modules are populated</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No training modules found - need to run setup</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ training_modules table does not exist</p>";
    }
    
    // Check if crew_competencies table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'crew_competencies'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ crew_competencies table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ crew_competencies table does not exist</p>";
    }
    
    echo "<h2>Available Actions:</h2>";
    echo "<p><a href='setup_training_competencies.php'>🔧 Run Training Setup</a></p>";
    echo "<p><a href='pages/training_modules.php'>📚 Manage Training Modules</a></p>";
    echo "<p><a href='pages/training_assignment.php'>👥 Assign Training</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: #000;
    color: #00ff00;
}
h1, h2 {
    color: #00ffff;
}
a {
    color: #ffff00;
    text-decoration: none;
    font-weight: bold;
}
a:hover {
    text-decoration: underline;
}
</style>
