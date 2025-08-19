<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Profile Debug</h1>";

try {
    require_once '../includes/config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    // Check if logged in
    if (!isLoggedIn()) {
        echo "<p>❌ User not logged in</p>";
        echo "<p>Session data:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        echo '<p><a href="login.php">Go to Login</a></p>';
        exit();
    }
    
    echo "<p>✅ User is logged in</p>";
    echo "<p>Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "</p>";
    
    $pdo = getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if steam_id column exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'steam_id'");
        if ($stmt->fetch()) {
            echo "<p>✅ steam_id column exists</p>";
        } else {
            echo "<p>❌ steam_id column does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking steam_id column: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Check if roster_id column exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'roster_id'");
        if ($stmt->fetch()) {
            echo "<p>✅ roster_id column exists</p>";
        } else {
            echo "<p>❌ roster_id column does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking roster_id column: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Try to get user data
    try {
        $stmt = $pdo->prepare("SELECT u.*, r.id as roster_id, r.rank, r.first_name, r.last_name, r.species, r.department, r.position, r.image_path 
                              FROM users u 
                              LEFT JOIN roster r ON (u.roster_id = r.id OR (r.first_name = u.first_name AND r.last_name = u.last_name))
                              WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user = $stmt->fetch();
        
        if ($current_user) {
            echo "<p>✅ User data retrieved successfully</p>";
            echo "<pre>" . print_r($current_user, true) . "</pre>";
        } else {
            echo "<p>❌ User not found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error retrieving user data: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        // Try simpler query
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "<p>✅ Basic user data retrieved:</p>";
                echo "<pre>" . print_r($user, true) . "</pre>";
            } else {
                echo "<p>❌ User not found with simple query</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error with simple user query: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo '<p><a href="../index.php">Return to Homepage</a></p>';
echo '<p><a href="../setup_steam.php">Setup Steam Integration</a></p>';
?>
