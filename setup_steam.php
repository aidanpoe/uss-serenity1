<!DOCTYPE html>
<html>
<head>
    <title>USS-Serenity Steam Integration Setup</title>
    <link rel="stylesheet" href="assets/classic.css">
</head>
<body>
    <div style="padding: 2rem; background: black; color: white; min-height: 100vh;">
        <h1 style="color: var(--gold);">USS-Serenity Steam Integration Setup</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
            try {
                // Database connection
                $pdo = new PDO(
                    "mysql:host=localhost;port=3306;dbname=serenity;charset=utf8mb4", 
                    "serenity", 
                    "Os~886go4",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                
                echo "<div style='background: rgba(0,255,0,0.1); padding: 1rem; border: 1px solid green; margin: 1rem 0;'>";
                echo "<h3 style='color: green;'>Setting up Steam Integration...</h3>";
                
                // Check if steam_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'steam_id'");
                if (!$stmt->fetch()) {
                    echo "<p>Adding steam_id column...</p>";
                    $pdo->exec("ALTER TABLE users ADD COLUMN steam_id VARCHAR(20) NULL UNIQUE");
                } else {
                    echo "<p>steam_id column already exists.</p>";
                }
                
                // Check if roster_id column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'roster_id'");
                if (!$stmt->fetch()) {
                    echo "<p>Adding roster_id column...</p>";
                    $pdo->exec("ALTER TABLE users ADD COLUMN roster_id INT NULL");
                } else {
                    echo "<p>roster_id column already exists.</p>";
                }
                
                // Check if user management columns exist
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'force_password_change'");
                if (!$stmt->fetch()) {
                    echo "<p>Adding user management columns...</p>";
                    $pdo->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0");
                    $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
                } else {
                    echo "<p>User management columns already exist.</p>";
                }
                
                // Check if user_id column exists in roster
                $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'user_id'");
                if (!$stmt->fetch()) {
                    echo "<p>Adding user_id column to roster...</p>";
                    $pdo->exec("ALTER TABLE roster ADD COLUMN user_id INT NULL");
                } else {
                    echo "<p>user_id column in roster already exists.</p>";
                }
                
                echo "<h3 style='color: green;'>✅ Steam Integration Setup Complete!</h3>";
                echo "<p>Your database is now ready for Steam authentication.</p>";
                echo "<p><a href='index.php' style='color: var(--bluey);'>Return to Homepage</a></p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div style='background: rgba(255,0,0,0.1); padding: 1rem; border: 1px solid red; margin: 1rem 0;'>";
                echo "<h3 style='color: red;'>❌ Setup Failed</h3>";
                echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        } else {
        ?>
        
        <div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
            <h2>Steam Integration Database Setup</h2>
            <p>This will update your database to support Steam authentication and enhanced user management.</p>
            
            <div style="background: rgba(255,136,0,0.1); padding: 1rem; border: 1px solid orange; margin: 1rem 0;">
                <h3 style="color: orange;">⚠️ Important</h3>
                <p>If you're getting 500 errors on Steam login or profile pages, you need to run this setup first.</p>
            </div>
            
            <h3>Changes that will be made:</h3>
            <ul>
                <li>Add <code>steam_id</code> column to users table</li>
                <li>Add <code>roster_id</code> column to users table</li>
                <li>Add <code>force_password_change</code>, <code>active</code>, and <code>last_login</code> columns</li>
                <li>Add <code>user_id</code> column to roster table</li>
            </ul>
            
            <form method="POST">
                <button type="submit" name="setup" style="background-color: var(--gold); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; font-size: 1.2rem;">
                    Setup Steam Integration
                </button>
            </form>
            
            <p style="margin-top: 2rem;">
                <strong>Debug Tools:</strong><br>
                <a href="steamauth/steamauth_debug.php?login" style="color: var(--bluey);">Test Steam Login (Debug)</a><br>
                <a href="pages/profile_debug.php" style="color: var(--bluey);">Test Profile Page (Debug)</a>
            </p>
        </div>
        
        <?php } ?>
    </div>
</body>
</html>
