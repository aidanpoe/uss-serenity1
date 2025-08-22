<?php
// PHP syntax checker
echo "<h2>PHP Syntax Check</h2>";

$file = 'pages/command.php';
if (!file_exists($file)) {
    echo "File not found: $file";
    exit;
}

// Use PHP -l to check syntax
$output = shell_exec("php -l \"$file\" 2>&1");
echo "<h3>Syntax Check Output:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Check for common issues
$content = file_get_contents($file);
$lines = explode("\n", $content);

echo "<h3>Manual Analysis:</h3>";

// Count braces, parentheses, etc.
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');
$open_parens = substr_count($content, '(');
$close_parens = substr_count($content, ')');
$open_brackets = substr_count($content, '[');
$close_brackets = substr_count($content, ']');

echo "<p>Braces: $open_braces open, $close_braces close (" . ($open_braces - $close_braces) . " difference)</p>";
echo "<p>Parentheses: $open_parens open, $close_parens close (" . ($open_parens - $close_parens) . " difference)</p>";
echo "<p>Brackets: $open_brackets open, $close_brackets close (" . ($open_brackets - $close_brackets) . " difference)</p>";

// Check for <?php and ?> tags
$php_opens = substr_count($content, '<?php');
$php_closes = substr_count($content, '?>');
echo "<p>PHP tags: $php_opens opens, $php_closes closes</p>";

// Look for potential issues
$issues = [];
foreach ($lines as $num => $line) {
    $num++; // 1-based line numbers
    
    // Check for common syntax errors
    if (preg_match('/\$\w+\s*=\s*$/', trim($line))) {
        $issues[] = "Line $num: Incomplete assignment";
    }
    
    if (strpos($line, '<?php') !== false && strpos($line, '<?php') > 0) {
        $issues[] = "Line $num: PHP tag not at beginning of line";
    }
    
    // Check for unmatched quotes
    $single_quotes = substr_count($line, "'");
    $double_quotes = substr_count($line, '"');
    if ($single_quotes % 2 !== 0) {
        $issues[] = "Line $num: Unmatched single quotes";
    }
    if ($double_quotes % 2 !== 0) {
        $issues[] = "Line $num: Unmatched double quotes";
    }
}

if (empty($issues)) {
    echo "<p>✅ No obvious syntax issues found</p>";
} else {
    echo "<h4>Potential Issues:</h4>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ul>";
}
?>
