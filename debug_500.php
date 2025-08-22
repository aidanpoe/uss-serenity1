<?php
// Debug script to find the exact 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Command.php Error Debug</h2>";

try {
    echo "<p>Step 1: Testing basic PHP...</p>";
    echo "<p>✅ PHP is working</p>";
    
    echo "<p>Step 2: Testing config include...</p>";
    require_once 'includes/config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    echo "<p>Step 3: Testing database connection...</p>";
    $pdo = getConnection();
    echo "<p>✅ Database connected</p>";
    
    echo "<p>Step 4: Testing basic functions...</p>";
    if (function_exists('updateLastActive')) {
        updateLastActive();
        echo "<p>✅ updateLastActive works</p>";
    } else {
        echo "<p>❌ updateLastActive missing</p>";
    }
    
    if (function_exists('isLoggedIn')) {
        $loggedIn = isLoggedIn();
        echo "<p>✅ isLoggedIn works: " . ($loggedIn ? 'true' : 'false') . "</p>";
    } else {
        echo "<p>❌ isLoggedIn missing</p>";
    }
    
    if (function_exists('hasPermission')) {
        $hasCommand = hasPermission('Command');
        echo "<p>✅ hasPermission works: " . ($hasCommand ? 'true' : 'false') . "</p>";
    } else {
        echo "<p>❌ hasPermission missing</p>";
    }
    
    echo "<p>Step 5: Testing department training include...</p>";
    require_once 'includes/department_training.php';
    echo "<p>✅ Department training loaded</p>";
    
    echo "<p>Step 6: Testing command.php include directly...</p>";
    ob_start();
    try {
        include 'pages/command.php';
        $output = ob_get_contents();
        ob_end_clean();
        echo "<p>✅ Command.php loaded successfully!</p>";
        echo "<p>Output length: " . strlen($output) . " characters</p>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p>❌ Command.php failed: " . $e->getMessage() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error at step: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Check for any fatal errors in error log
if (function_exists('error_get_last')) {
    $lastError = error_get_last();
    if ($lastError && $lastError['type'] === E_ERROR) {
        echo "<h3>Last Fatal Error:</h3>";
        echo "<p>Message: " . $lastError['message'] . "</p>";
        echo "<p>File: " . $lastError['file'] . "</p>";
        echo "<p>Line: " . $lastError['line'] . "</p>";
    }
}
?>
