<?php
// Syntax and structure checker for command.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Command.php Syntax Check</h2>";

$file_path = 'command.php';

if (!file_exists($file_path)) {
    echo "<p style='color: red;'>❌ File command.php not found!</p>";
    exit;
}

echo "<h3>File Information</h3>";
echo "✓ File exists<br>";
echo "File size: " . filesize($file_path) . " bytes<br>";
echo "Last modified: " . date('Y-m-d H:i:s', filemtime($file_path)) . "<br>";

echo "<h3>Reading File Contents</h3>";
$content = file_get_contents($file_path);
$lines = explode("\n", $content);
echo "Total lines: " . count($lines) . "<br>";

echo "<h3>Checking for Common Issues</h3>";

// Check for UTF-8 BOM
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
    echo "⚠ UTF-8 BOM detected at start of file<br>";
} else {
    echo "✓ No UTF-8 BOM<br>";
}

// Check PHP opening tag
if (strpos(trim($content), '<?php') === 0) {
    echo "✓ Proper PHP opening tag<br>";
} else {
    echo "❌ Missing or incorrect PHP opening tag<br>";
}

// Check for basic syntax issues
$php_errors = [];
$bracket_count = 0;
$paren_count = 0;
$brace_count = 0;
$in_string = false;
$string_char = '';

for ($i = 0; $i < strlen($content); $i++) {
    $char = $content[$i];
    
    if (!$in_string) {
        switch ($char) {
            case '"':
            case "'":
                $in_string = true;
                $string_char = $char;
                break;
            case '(':
                $paren_count++;
                break;
            case ')':
                $paren_count--;
                break;
            case '[':
                $bracket_count++;
                break;
            case ']':
                $bracket_count--;
                break;
            case '{':
                $brace_count++;
                break;
            case '}':
                $brace_count--;
                break;
        }
    } else {
        if ($char === $string_char && ($i === 0 || $content[$i-1] !== '\\')) {
            $in_string = false;
            $string_char = '';
        }
    }
}

echo "Parentheses balance: " . ($paren_count === 0 ? "✓ Balanced" : "❌ Unbalanced ($paren_count)") . "<br>";
echo "Brackets balance: " . ($bracket_count === 0 ? "✓ Balanced" : "❌ Unbalanced ($bracket_count)") . "<br>";
echo "Braces balance: " . ($brace_count === 0 ? "✓ Balanced" : "❌ Unbalanced ($brace_count)") . "<br>";

// Check for common PHP errors
$common_issues = [
    '/\$[a-zA-Z_][a-zA-Z0-9_]*\[.*\](?!\s*[\?\:])/m' => 'Potential undefined array access',
    '/\$_POST\[.*\](?!\s*\?\?)/m' => 'Unsafe $_POST access without null coalescing',
    '/\$_SESSION\[.*\](?!\s*\?\?)/m' => 'Unsafe $_SESSION access without null coalescing',
    '/echo\s+[^;]*\$[^;]*;/m' => 'Potential XSS - unescaped echo',
];

foreach ($common_issues as $pattern => $description) {
    if (preg_match($pattern, $content)) {
        echo "⚠ $description detected<br>";
    }
}

echo "<h3>Testing File Execution</h3>";

// Try to include and execute the file in a controlled way
ob_start();
$execution_error = null;

try {
    // Set up minimal environment
    session_start();
    
    // Capture any output
    include $file_path;
    
} catch (ParseError $e) {
    $execution_error = "Parse Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
} catch (Error $e) {
    $execution_error = "Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
} catch (Exception $e) {
    $execution_error = "Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}

$output = ob_get_clean();

if ($execution_error) {
    echo "<p style='color: red;'>❌ Execution failed: $execution_error</p>";
} else {
    echo "<p style='color: green;'>✓ File executed without fatal errors</p>";
    if (!empty($output)) {
        echo "<h4>Output Preview (first 500 chars):</h4>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
    }
}

echo "<h3>File Structure Analysis</h3>";

// Count key elements
$function_count = preg_match_all('/function\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\(/m', $content);
$class_count = preg_match_all('/class\s+[a-zA-Z_][a-zA-Z0-9_]*\s*{/m', $content);
$include_count = preg_match_all('/(require|include)(_once)?\s*[\'"].*[\'"];/m', $content);

echo "Functions defined: $function_count<br>";
echo "Classes defined: $class_count<br>";
echo "Includes/requires: $include_count<br>";

// Check for HTML
if (strpos($content, '<!DOCTYPE') !== false || strpos($content, '<html') !== false) {
    echo "✓ Contains HTML structure<br>";
} else {
    echo "⚠ No HTML structure detected<br>";
}

echo "<h3>Summary</h3>";
if ($execution_error) {
    echo "<p style='color: red;'>The file has execution errors that need to be fixed.</p>";
} else {
    echo "<p style='color: green;'>The file appears to be syntactically correct. The 500 error might be due to runtime conditions.</p>";
}
?>
