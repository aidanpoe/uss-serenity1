<?php
session_start();
require_once 'includes/config.php';

echo "<h1>Permission Debug</h1>";

// Check if user is logged in
echo "<h2>Login Status:</h2>";
echo "<p>isLoggedIn(): " . (isLoggedIn() ? 'YES' : 'NO') . "</p>";

// Display all session variables
echo "<h2>Session Variables:</h2>";
echo "<pre>";
foreach ($_SESSION as $key => $value) {
    echo "$key: " . htmlspecialchars($value) . "\n";
}
echo "</pre>";

// Check if user has a character
if (isLoggedIn()) {
    $character = getCurrentCharacter();
    echo "<h2>Current Character:</h2>";
    if ($character) {
        echo "<pre>";
        print_r($character);
        echo "</pre>";
    } else {
        echo "<p>No active character found</p>";
    }
    
    // Check user's department permissions
    echo "<h2>Department Access:</h2>";
    echo "<p>Session department: " . ($_SESSION['department'] ?? 'Not set') . "</p>";
    echo "<p>Session roster_department: " . ($_SESSION['roster_department'] ?? 'Not set') . "</p>";
    
    // Test department access
    $userDept = $_SESSION['department'] ?? '';
    $userRank = $_SESSION['rank'] ?? '';
    $rosterDept = $_SESSION['roster_department'] ?? '';
    echo "<h3>Access Rights:</h3>";
    echo "<p>User Department: " . htmlspecialchars($userDept) . "</p>";
    echo "<p>User Rank: " . htmlspecialchars($userRank) . "</p>";
    echo "<p>Roster Department: " . htmlspecialchars($rosterDept) . "</p>";
    echo "<p><strong>Permission Results:</strong></p>";
    echo "<p>MED/SCI Access: " . (hasPermission('MED/SCI') ? 'YES' : 'NO') . "</p>";
    echo "<p>ENG/OPS Access: " . (hasPermission('ENG/OPS') ? 'YES' : 'NO') . "</p>";
    echo "<p>SEC/TAC Access: " . (hasPermission('SEC/TAC') ? 'YES' : 'NO') . "</p>";
    echo "<p>Command Access: " . (hasPermission('Command') ? 'YES' : 'NO') . "</p>";
    
    // Show command override logic
    if ($userRank === 'Captain' || $userRank === 'Commander' || $userDept === 'Command' || $rosterDept === 'Command') {
        echo "<p style='color: gold;'><strong>✅ COMMAND OVERRIDE: Full access granted due to rank/department</strong></p>";
    }
}

echo '<p><a href="index.php">Back to Homepage</a></p>';
?>
