<?php
// Debug script to find the exact error in command.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Command.php Debug</h2>";

try {
    echo "<p>1. Testing includes...</p>";
    require_once '../includes/config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    require_once '../includes/department_training.php';
    echo "<p>✅ Department training loaded successfully</p>";
    
    echo "<p>2. Testing functions...</p>";
    
    // Test updateLastActive function
    if (function_exists('updateLastActive')) {
        echo "<p>✅ updateLastActive function exists</p>";
        updateLastActive();
        echo "<p>✅ updateLastActive executed successfully</p>";
    } else {
        echo "<p>❌ updateLastActive function missing</p>";
    }
    
    // Test hasPermission function
    if (function_exists('hasPermission')) {
        echo "<p>✅ hasPermission function exists</p>";
        $hasCommand = hasPermission('Command');
        echo "<p>✅ hasPermission executed: " . ($hasCommand ? 'true' : 'false') . "</p>";
    } else {
        echo "<p>❌ hasPermission function missing</p>";
    }
    
    echo "<p>3. Testing database connection...</p>";
    $pdo = getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    echo "<p>4. Testing basic queries...</p>";
    $stmt = $pdo->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ Basic query successful: " . $result['test'] . "</p>";
    
    echo "<p>5. Testing command_suggestions table...</p>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM command_suggestions");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ Command suggestions table exists: " . $result['count'] . " records</p>";
    
    echo "<p>6. Testing award_recommendations table...</p>";
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM award_recommendations");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>✅ Award recommendations table exists: " . $result['count'] . " records</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Award recommendations table missing: " . $e->getMessage() . "</p>";
        echo "<p>Creating table...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS award_recommendations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            recommended_person VARCHAR(255) NOT NULL,
            recommended_award VARCHAR(255) NOT NULL,
            justification TEXT NOT NULL,
            submitted_by VARCHAR(255) NOT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            reviewed_by VARCHAR(255),
            review_notes TEXT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL
        )");
        echo "<p>✅ Award recommendations table created</p>";
    }
    
    echo "<p>7. All checks passed! The issue might be in the PHP syntax.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error found: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<p><a href='command.php'>Try Command.php again</a></p>";
?>
