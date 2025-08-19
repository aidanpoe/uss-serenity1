<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Debug</h1>";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "<p>✅ Session started</p>";
} else {
    echo "<p>✅ Session already active</p>";
}

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";

echo "<h2>Current Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Test traditional login
if (isset($_POST['test_login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['first_name'] = 'Test';
    $_SESSION['last_name'] = 'User';
    $_SESSION['department'] = 'Command';
    echo "<p>✅ Test session data set</p>";
    echo "<p><a href=''>Refresh page</a> to see session data</p>";
}

// Test isLoggedIn function
try {
    require_once '../includes/config.php';
    
    if (function_exists('isLoggedIn')) {
        $logged_in = isLoggedIn();
        echo "<p><strong>isLoggedIn() result:</strong> " . ($logged_in ? 'true' : 'false') . "</p>";
    } else {
        echo "<p>❌ isLoggedIn() function not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>PHP Configuration:</h2>";
echo "<p><strong>Session save path:</strong> " . session_save_path() . "</p>";
echo "<p><strong>Session name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session cookie params:</strong></p>";
echo "<pre>" . print_r(session_get_cookie_params(), true) . "</pre>";

echo "<h2>Test Traditional Login:</h2>";
echo '<form method="POST">';
echo '<button type="submit" name="test_login" style="background-color: #gold; color: black; border: none; padding: 1rem; border-radius: 5px;">Set Test Session Data</button>';
echo '</form>';

echo "<h2>Clear Session:</h2>";
if (isset($_POST['clear_session'])) {
    session_unset();
    session_destroy();
    echo "<p>✅ Session cleared</p>";
    echo "<p><a href=''>Refresh page</a></p>";
} else {
    echo '<form method="POST">';
    echo '<button type="submit" name="clear_session" style="background-color: red; color: white; border: none; padding: 1rem; border-radius: 5px;">Clear Session</button>';
    echo '</form>';
}

echo '<p><a href="../index.php">Return to Homepage</a></p>';
echo '<p><a href="login.php">Go to Login Page</a></p>';
?>
