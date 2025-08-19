<?php
require_once 'includes/config.php';

echo "<h1>Garry's Mod Server Test</h1>";
echo "<p>Testing connection to 46.4.12.78:27015...</p>";

$start_time = microtime(true);
$gmodData = getGmodPlayersOnline();
$end_time = microtime(true);
$query_time = round(($end_time - $start_time) * 1000, 2);

echo "<p><strong>Query Time:</strong> {$query_time}ms</p>";

if (isset($gmodData['error'])) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($gmodData['error']) . "</p>";
} else {
    echo "<p style='color: green;'><strong>Success!</strong> Found {$gmodData['count']} player(s) online</p>";
    
    if ($gmodData['count'] > 0) {
        echo "<h3>Players Online:</h3>";
        echo "<ul>";
        foreach ($gmodData['players'] as $player) {
            echo "<li>" . htmlspecialchars($player) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Server:</strong> " . htmlspecialchars($gmodData['server']) . "</p>";
}

echo '<p><a href="index.php">Return to Homepage</a></p>';
echo '<p><a href="">Refresh Test</a></p>';
?>
