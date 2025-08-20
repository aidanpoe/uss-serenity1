<?php
// Emergency simple index.php - bypasses all security features temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic session start
session_start();

// Simple database connection (using original hardcoded values)
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=serenity;charset=utf8mb4", 
        "serenity", 
        "Os~886go4",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $db_status = "✓ Database connected";
} catch(PDOException $e) {
    $db_status = "✗ Database error: " . $e->getMessage();
}

// Steam login function
function loginbutton($buttonstyle = "square") {
    echo "<a href='steamauth/steamauth.php?login'>Sign in through Steam</a>";
}

// Check if logged in
$logged_in = isset($_SESSION['user_id']) && isset($_SESSION['steamid']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>USS-Serenity 74714 - Emergency Mode</title>
    <link rel="stylesheet" type="text/css" href="assets/classic.css">
</head>
<body>
    <div class="lcars-container">
        <div class="lcars-header">
            <h1>USS-SERENITY 74714 - EMERGENCY MODE</h1>
            <p><?php echo $db_status; ?></p>
        </div>
        
        <div class="lcars-content">
            <?php if (!$logged_in): ?>
                <h2>Authentication Required</h2>
                <?php loginbutton(); ?>
            <?php else: ?>
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h2>
                <p>You are logged in successfully.</p>
                <p><a href="pages/roster.php">View Roster</a></p>
                <p><a href="steamauth/steamauth.php?logout">Logout</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
