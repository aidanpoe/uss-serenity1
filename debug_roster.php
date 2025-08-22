<?php
require_once '../includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h3>All positions in roster:</h3>";
    $stmt = $pdo->query('SELECT DISTINCT position FROM roster ORDER BY position');
    $positions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach($positions as $pos) {
        echo "<li>" . htmlspecialchars($pos) . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>Looking for command-related positions:</h3>";
    $stmt = $pdo->prepare('SELECT * FROM roster WHERE position LIKE "%command%" OR position LIKE "%officer%" OR position LIKE "%captain%" ORDER BY position');
    $stmt->execute();
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach($officers as $officer) {
        echo "<li>" . htmlspecialchars($officer['position']) . " : " . htmlspecialchars($officer['rank'] . " " . $officer['first_name'] . " " . $officer['last_name']) . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>Testing exact query from command.php:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer') ORDER BY FIELD(position, 'Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer')");
    $stmt->execute();
    $command_officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($command_officers) . " command officers:</p>";
    echo "<ul>";
    foreach($command_officers as $officer) {
        echo "<li>" . htmlspecialchars($officer['position']) . " : " . htmlspecialchars($officer['rank'] . " " . $officer['first_name'] . " " . $officer['last_name']) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
