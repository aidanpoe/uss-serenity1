<?php
echo "<h1>USS-VOYAGER LCARS Website Installation Check</h1>";
echo "<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .warning { color: orange; }</style>";

echo "<h2>System Requirements Check</h2>";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '>=')) {
    echo "<p class='success'>✓ PHP Version: " . PHP_VERSION . " (Required: 7.4+)</p>";
} else {
    echo "<p class='error'>✗ PHP Version: " . PHP_VERSION . " (Required: 7.4+)</p>";
}

// Check PDO MySQL extension
if (extension_loaded('pdo_mysql')) {
    echo "<p class='success'>✓ PDO MySQL Extension: Available</p>";
} else {
    echo "<p class='error'>✗ PDO MySQL Extension: Not available</p>";
}

// Check session support
if (function_exists('session_start')) {
    echo "<p class='success'>✓ Session Support: Available</p>";
} else {
    echo "<p class='error'>✗ Session Support: Not available</p>";
}

echo "<h2>File Structure Check</h2>";

$required_files = [
    'includes/config.php',
    'setup_database.php',
    'add_training_docs.php',
    'index.php',
    'pages/login.php',
    'pages/logout.php',
    'pages/roster.php',
    'pages/med_sci.php',
    'pages/eng_ops.php',
    'pages/sec_tac.php',
    'pages/command.php',
    'pages/training.php',
    'pages/reports.php',
    'TEMPLATE/assets/classic.css',
    'TEMPLATE/assets/lcars.js'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $file</p>";
    } else {
        echo "<p class='error'>✗ $file (Missing)</p>";
    }
}

echo "<h2>Database Connection Test</h2>";

try {
    require_once 'includes/config.php';
    $pdo = getConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['users', 'roster', 'medical_reports', 'fault_reports', 'security_incidents', 'suggestions', 'training_documents', 'phaser_training'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<p class='success'>✓ Table '$table' exists</p>";
        } else {
            echo "<p class='warning'>! Table '$table' missing (Run setup_database.php)</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p class='warning'>Make sure MySQL is running and database credentials are correct in includes/config.php</p>";
}

echo "<h2>Installation Instructions</h2>";
echo "<ol>";
echo "<li>If any files are missing, make sure all files were uploaded correctly</li>";
echo "<li>If database connection fails, check MySQL service and credentials</li>";
echo "<li>If tables are missing, run: <strong>setup_database.php</strong></li>";
echo "<li>To add default training documents, run: <strong>add_training_docs.php</strong></li>";
echo "<li>Default login: Username = <strong>Poe</strong>, Password = <strong>Class390</strong></li>";
echo "</ol>";

echo "<h2>Next Steps</h2>";
echo "<p>Once all checks pass, visit <a href='index.php'>index.php</a> to access the USS-VOYAGER LCARS website!</p>";
?>
