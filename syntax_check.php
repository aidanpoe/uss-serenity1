<?php
// PHP Syntax Checker for command.php
echo "<h2>PHP Syntax Check for command.php</h2>";

$file = 'pages/command.php';

// Check if file exists
if (!file_exists($file)) {
    echo "<p>❌ File not found: $file</p>";
    exit;
}

echo "<p>📁 File found: $file</p>";

// Check PHP syntax
$output = [];
$return_var = 0;
exec("php -l \"$file\" 2>&1", $output, $return_var);

echo "<h3>Syntax Check Results:</h3>";
if ($return_var === 0) {
    echo "<p style='color: green;'>✅ No syntax errors detected</p>";
} else {
    echo "<p style='color: red;'>❌ Syntax errors found:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre>";
}

// Try to include the file with error reporting
echo "<h3>Include Test with Error Reporting:</h3>";
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
try {
    // Don't actually include, just check for obvious issues
    $content = file_get_contents($file);
    
    // Check for common issues
    $lines = explode("\n", $content);
    $errors = [];
    
    $in_php = false;
    $brace_count = 0;
    $paren_count = 0;
    
    foreach ($lines as $line_num => $line) {
        $line_num++; // 1-based line numbers
        
        // Check for PHP tags
        if (strpos($line, '<?php') !== false) {
            $in_php = true;
        }
        if (strpos($line, '?>') !== false) {
            $in_php = false;
        }
        
        if ($in_php) {
            // Count braces and parentheses
            $brace_count += substr_count($line, '{') - substr_count($line, '}');
            $paren_count += substr_count($line, '(') - substr_count($line, ')');
            
            // Check for common syntax issues
            if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*$/', trim($line))) {
                $errors[] = "Line $line_num: Incomplete assignment";
            }
            
            if (preg_match('/\?\s*:/', $line) && !preg_match('/\?\s*[^:]+\s*:/', $line)) {
                $errors[] = "Line $line_num: Malformed ternary operator";
            }
        }
    }
    
    if ($brace_count != 0) {
        $errors[] = "Mismatched braces: " . ($brace_count > 0 ? "$brace_count unclosed" : abs($brace_count) . " extra closing");
    }
    
    if ($paren_count != 0) {
        $errors[] = "Mismatched parentheses: " . ($paren_count > 0 ? "$paren_count unclosed" : abs($paren_count) . " extra closing");
    }
    
    if (empty($errors)) {
        echo "<p style='color: green;'>✅ No obvious syntax issues found</p>";
    } else {
        echo "<p style='color: red;'>❌ Potential issues found:</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error during file analysis: " . htmlspecialchars($e->getMessage()) . "</p>";
}
ob_end_clean();

echo "<p><a href='command.php'>Try Command.php</a> | <a href='debug_command.php'>Run Debug Script</a></p>";
?>
