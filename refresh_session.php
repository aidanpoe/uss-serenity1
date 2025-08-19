<?php
session_start();
require_once 'includes/config.php';

echo "<h1>Session Refresh</h1>";

if (isLoggedIn()) {
    // Get current character data
    $character = getCurrentCharacter();
    
    if ($character) {
        echo "<h2>Updating Session Variables</h2>";
        
        // Update session with current character data
        $_SESSION['rank'] = $character['rank'];
        $_SESSION['first_name'] = $character['first_name'];
        $_SESSION['last_name'] = $character['last_name'];
        $_SESSION['position'] = $character['position'];
        $_SESSION['image_path'] = $character['image_path'];
        $_SESSION['roster_department'] = $character['roster_department'];
        
        // Map department to permission group
        $permission_dept = '';
        switch($character['roster_department']) {
            case 'Medical':
            case 'Science':
                $permission_dept = 'MED/SCI';
                break;
            case 'Engineering':
            case 'Operations':
                $permission_dept = 'ENG/OPS';
                break;
            case 'Security':
            case 'Tactical':
                $permission_dept = 'SEC/TAC';
                break;
            case 'Command':
                $permission_dept = 'Command';
                break;
            default:
                $permission_dept = 'SEC/TAC';
                break;
        }
        
        $_SESSION['department'] = $permission_dept;
        
        // Update database
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE users SET department = ? WHERE id = ?");
        $stmt->execute([$permission_dept, $_SESSION['user_id']]);
        
        echo "<p>✅ Session refreshed successfully!</p>";
        echo "<p><strong>Updated Data:</strong></p>";
        echo "<p>Rank: " . htmlspecialchars($character['rank']) . "</p>";
        echo "<p>Name: " . htmlspecialchars($character['first_name'] . ' ' . $character['last_name']) . "</p>";
        echo "<p>Roster Department: " . htmlspecialchars($character['roster_department']) . "</p>";
        echo "<p>Permission Group: " . htmlspecialchars($permission_dept) . "</p>";
        
    } else {
        echo "<p>❌ No active character found</p>";
    }
} else {
    echo "<p>❌ Not logged in</p>";
}

echo '<p><a href="debug_permissions.php">Check Permissions</a></p>';
echo '<p><a href="index.php">Return to Homepage</a></p>';
?>
