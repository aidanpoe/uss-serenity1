<?php
// Minimal command.php test to isolate the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Minimal Command.php Test</h2>";

try {
    echo "<p>Step 1: Loading config...</p>";
    require_once '../includes/config.php';
    echo "<p>✅ Config loaded</p>";
    
    echo "<p>Step 2: Testing basic functions...</p>";
    updateLastActive();
    echo "<p>✅ updateLastActive called</p>";
    
    echo "<p>Step 3: Loading department training...</p>";
    require_once '../includes/department_training.php';
    echo "<p>✅ Department training loaded</p>";
    
    echo "<p>Step 4: Testing hasPermission...</p>";
    $hasCommand = hasPermission('Command');
    echo "<p>✅ hasPermission result: " . ($hasCommand ? 'true' : 'false') . "</p>";
    
    echo "<p>Step 5: Testing database...</p>";
    $pdo = getConnection();
    echo "<p>✅ Database connected</p>";
    
    echo "<p>Step 6: Testing POST handling...</p>";
    if ($_POST && isset($_POST['action'])) {
        echo "<p>POST action: " . htmlspecialchars($_POST['action']) . "</p>";
    } else {
        echo "<p>No POST data</p>";
    }
    
    echo "<p>Step 7: Testing basic queries...</p>";
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Commanding Officer', 'First Officer') LIMIT 1");
    $stmt->execute();
    $officer = $stmt->fetch();
    echo "<p>✅ Command officer query successful</p>";
    
    if ($hasCommand) {
        echo "<p>Step 8: Testing command queries...</p>";
        
        // Test suggestions
        try {
            $stmt = $pdo->prepare("SELECT * FROM command_suggestions ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $suggestions = $stmt->fetchAll();
            echo "<p>✅ Suggestions query successful (" . count($suggestions) . " found)</p>";
        } catch (Exception $e) {
            echo "<p>❌ Suggestions query failed: " . $e->getMessage() . "</p>";
        }
        
        // Test award recommendations
        try {
            $stmt = $pdo->prepare("SELECT * FROM award_recommendations ORDER BY submitted_at DESC LIMIT 1");
            $stmt->execute();
            $award_recommendations = $stmt->fetchAll();
            echo "<p>✅ Award recommendations query successful (" . count($award_recommendations) . " found)</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Award recommendations query failed: " . $e->getMessage() . "</p>";
            echo "<p>This is expected if table doesn't exist yet.</p>";
        }
    }
    
    echo "<p>✅ All tests passed! The issue might be in the HTML/PHP mixing.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
