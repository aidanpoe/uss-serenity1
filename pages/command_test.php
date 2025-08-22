<?php
// Minimal command.php test
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';

echo "Basic PHP works<br>";

updateLastActive();
echo "updateLastActive works<br>";

$hasCommand = hasPermission('Command');
echo "hasPermission works: " . ($hasCommand ? 'true' : 'false') . "<br>";

$pdo = getConnection();
echo "Database works<br>";

echo "<h1>Command Center</h1>";
echo "<p>This is a minimal test version</p>";

if (isLoggedIn()) {
    $user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    echo "<p>Logged in as: " . htmlspecialchars($user) . "</p>";
} else {
    echo "<p>Not logged in</p>";
}

echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='test'>";
echo "<button type='submit'>Test Form</button>";
echo "</form>";

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'test') {
    echo "<p>Form submission works!</p>";
}

echo "<p><a href='../index.php'>Back to Home</a></p>";
?>
