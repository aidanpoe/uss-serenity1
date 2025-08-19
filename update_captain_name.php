<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting update script...<br>";

try {
    echo "Including config file...<br>";
    require_once 'includes/config.php';
    echo "Config loaded successfully.<br>";
    
    echo "Database connection status: ";
    if (isset($pdo)) {
        echo "Connected<br>";
    } else {
        echo "Not connected<br>";
        die("Database connection failed");
    }

    echo "Updating Captain's name from James to Aidan...<br>";
    
    // Update the users table
    echo "Updating users table...<br>";
    $stmt = $pdo->prepare("UPDATE users SET first_name = 'Aidan' WHERE username = 'Poe' AND department = 'Captain'");
    $result1 = $stmt->execute();
    $affected1 = $stmt->rowCount();
    echo "Users table update: " . ($result1 ? "Success" : "Failed") . " - Rows affected: $affected1<br>";
    
    // Update the roster table
    echo "Updating roster table...<br>";
    $stmt = $pdo->prepare("UPDATE roster SET first_name = 'Aidan' WHERE last_name = 'Poe' AND rank = 'Captain'");
    $result2 = $stmt->execute();
    $affected2 = $stmt->rowCount();
    echo "Roster table update: " . ($result2 ? "Success" : "Failed") . " - Rows affected: $affected2<br>";
    
    echo "<br><strong>Successfully updated Captain's name from James to Aidan Poe in both tables.</strong>";
    
} catch (PDOException $e) {
    echo "<br><strong>Database Error:</strong> " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "<br><strong>General Error:</strong> " . $e->getMessage() . "<br>";
}
?>
