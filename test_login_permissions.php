<?php
require_once '../includes/config.php';

echo "<h2>Permission Test for Login Logs Access</h2>";

if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Not logged in</p>";
    exit;
}

echo "<p style='color: green;'>✅ User is logged in</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
echo "<p>Department: " . (getUserDepartment() ?? 'Not set') . "</p>";
echo "<p>Rank: " . ($_SESSION['rank'] ?? 'Not set') . "</p>";

echo "<h3>Permission Checks:</h3>";

// Test hasPermission function
if (hasPermission('Command')) {
    echo "<p style='color: green;'>✅ hasPermission('Command') = TRUE</p>";
} else {
    echo "<p style='color: red;'>❌ hasPermission('Command') = FALSE</p>";
}

if (hasPermission('Captain')) {
    echo "<p style='color: green;'>✅ hasPermission('Captain') = TRUE</p>";
} else {
    echo "<p style='color: red;'>❌ hasPermission('Captain') = FALSE</p>";
}

// Test the combined check used in admin_login_logs.php
if (hasPermission('Command') || hasPermission('Captain')) {
    echo "<p style='color: green; font-weight: bold;'>✅ LOGIN LOGS ACCESS: GRANTED</p>";
    echo "<p>This user can access admin_login_logs.php</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ LOGIN LOGS ACCESS: DENIED</p>";
    echo "<p>This user cannot access admin_login_logs.php</p>";
}

echo "<h3>Session Data:</h3>";
echo "<pre>";
foreach ($_SESSION as $key => $value) {
    if (!in_array($key, ['password', 'steam_token'])) { // Don't show sensitive data
        echo $key . ": " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}
echo "</pre>";

echo "<h3>Additional Security Notes:</h3>";
echo "<ul>";
echo "<li><strong>Command Department:</strong> Users with department = 'Command'</li>";
echo "<li><strong>Captain Rank:</strong> Users with rank = 'Captain' or 'Commander'</li>";
echo "<li><strong>Double Check:</strong> Both conditions are checked with OR logic</li>";
echo "<li><strong>Function Used:</strong> hasPermission() from config.php</li>";
echo "</ul>";
?>
