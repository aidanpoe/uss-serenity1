<?php
// Simple test to verify basic Steam authentication without all the extras
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Simple Steam Login Test</h1>";

// For testing - just redirect to Steam without database checks
if (isset($_GET['simple_test'])) {
    require_once 'SteamConfig.php';
    require_once 'openid.php';
    
    try {
        $openid = new LightOpenID($steamauth['domainname']);
        $openid->identity = 'https://steamcommunity.com/openid';
        
        echo "<p>Steam Auth URL Generated Successfully!</p>";
        echo "<p>Redirecting to Steam...</p>";
        
        header('Location: ' . $openid->authUrl());
        exit;
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}

// Handle return from Steam
if (isset($_GET['openid_mode'])) {
    require_once 'SteamConfig.php';
    require_once 'openid.php';
    
    try {
        $openid = new LightOpenID($steamauth['domainname']);
        
        if ($openid->mode == 'cancel') {
            echo '<h2>Steam Authentication Cancelled</h2>';
            echo '<p>You cancelled the Steam login process.</p>';
        } else {
            if($openid->validate()) {
                $id = $openid->identity;
                $ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
                preg_match($ptn, $id, $matches);
                
                if (isset($matches[1])) {
                    echo '<h2>✅ Steam Authentication Successful!</h2>';
                    echo '<p><strong>Steam ID:</strong> ' . htmlspecialchars($matches[1]) . '</p>';
                    echo '<p>The Steam authentication process is working correctly.</p>';
                    echo '<p>You can now integrate this with your user database.</p>';
                } else {
                    echo '<h2>❌ Steam ID Extraction Failed</h2>';
                    echo '<p>Authentication succeeded but could not extract Steam ID.</p>';
                }
            } else {
                echo '<h2>❌ Steam Authentication Failed</h2>';
                echo '<p>Steam authentication validation failed.</p>';
            }
        }
    } catch (Exception $e) {
        echo "<h2>❌ Error</h2>";
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Steam Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: white; }
        a { color: #5599ff; }
        .button { background: #gold; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>

<?php if (!isset($_GET['openid_mode']) && !isset($_GET['simple_test'])): ?>
<h2>Test Steam Authentication</h2>
<p>This is a simple test to verify that Steam authentication works correctly.</p>

<a href="?simple_test=1" class="button">Test Steam Login</a>

<p><a href="../index.php">Return to Homepage</a></p>
<?php endif; ?>

<?php if (isset($_GET['openid_mode'])): ?>
<p><a href="?" class="button">Test Again</a></p>
<p><a href="../index.php">Return to Homepage</a></p>
<?php endif; ?>

</body>
</html>
