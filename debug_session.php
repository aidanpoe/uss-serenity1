<?php
session_start();
require_once 'includes/config.php';

echo "<h1>Debug Session Information</h1>";
echo "<h2>Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Functions:</h2>";
echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";
echo "isset(\$_SESSION['steamid']): " . (isset($_SESSION['steamid']) ? 'true' : 'false') . "<br>";
echo "isset(\$_SESSION['user_id']): " . (isset($_SESSION['user_id']) ? 'true' : 'false') . "<br>";

echo "<h2>Condition for Steam Button:</h2>";
$condition = !isLoggedIn() && !isset($_SESSION['steamid']);
echo "!isLoggedIn() && !isset(\$_SESSION['steamid']): " . ($condition ? 'true (BUTTON SHOULD SHOW)' : 'false (BUTTON SHOULD NOT SHOW)') . "<br>";

echo "<h2>Steam Files Check:</h2>";
if (file_exists('steamauth/steamauth.php')) {
    echo "steamauth/steamauth.php: EXISTS<br>";
} else {
    echo "steamauth/steamauth.php: MISSING<br>";
}

if (file_exists('steamauth/SteamConfig.php')) {
    echo "steamauth/SteamConfig.php: EXISTS<br>";
} else {
    echo "steamauth/SteamConfig.php: MISSING<br>";
}
?>
