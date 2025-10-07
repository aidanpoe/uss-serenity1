<?php
require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>USS-VOYAGER - Fix User Characters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" type="text/css" href="assets/classic.css">
</head>
<body>
    <section class="wrap-standard" id="column-3">
        <div class="wrap">
            <div class="left-frame-top">
                <button onclick="window.location.href='index.php'" class="panel-1-button">LCARS</button>
                <div class="panel-2">FIX<span class="hop">-CHAR</span></div>
            </div>
            <div class="right-frame-top">
                <div class="banner">USS-VOYAGER &#149; CHARACTER FIX</div>
            </div>
        </div>
        
        <div class="wrap" id="gap">
            <div class="left-frame">
                <button onclick="window.location.href='index.php'" id="topBtn"><span class="hop">main</span> menu</button>
                <div>
                    <div class="panel-3">FIX<span class="hop">-CHAR</span></div>
                    <div class="panel-4">SYS<span class="hop">-READY</span></div>
                </div>
            </div>
            
            <div class="right-frame">
                <main>
                    <h1>Fix User Character Assignments</h1>
                    <p>This script will fix users who have characters but no active character set.</p>
                    
                    <?php
                    try {
                        $pdo = getConnection();
                        
                        echo "<h3>Step 1: Finding users with characters but no active character...</h3>";
                        
                        // Find users who have characters but no active_character_id set
                        $stmt = $pdo->query("
                            SELECT u.id as user_id, u.username, u.active_character_id, 
                                   COUNT(r.id) as character_count
                            FROM users u 
                            LEFT JOIN roster r ON u.id = r.user_id AND r.is_active = 1
                            GROUP BY u.id
                            HAVING character_count > 0 AND (u.active_character_id IS NULL OR u.active_character_id NOT IN (SELECT id FROM roster WHERE user_id = u.id AND is_active = 1))
                        ");
                        $problematic_users = $stmt->fetchAll();
                        
                        if (empty($problematic_users)) {
                            echo "<p style='color: var(--blue);'>✅ No problematic users found. All users with characters have proper active character assignments.</p>";
                        } else {
                            echo "<p style='color: var(--orange);'>Found " . count($problematic_users) . " users with character assignment issues:</p>";
                            
                            foreach ($problematic_users as $user) {
                                echo "<div style='margin: 1rem 0; padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 5px;'>";
                                echo "<h4 style='color: var(--orange);'>User: {$user['username']} (ID: {$user['user_id']})</h4>";
                                echo "<p>Character count: {$user['character_count']}</p>";
                                echo "<p>Current active_character_id: " . ($user['active_character_id'] ?? 'NULL') . "</p>";
                                
                                // Get their first character
                                $char_stmt = $pdo->prepare("
                                    SELECT id, character_name, first_name, last_name, department 
                                    FROM roster 
                                    WHERE user_id = ? AND is_active = 1 
                                    ORDER BY created_at ASC 
                                    LIMIT 1
                                ");
                                $char_stmt->execute([$user['user_id']]);
                                $first_char = $char_stmt->fetch();
                                
                                if ($first_char) {
                                    echo "<p>First character: {$first_char['character_name']} ({$first_char['first_name']} {$first_char['last_name']}) - {$first_char['department']}</p>";
                                    
                                    // Update the user's active character
                                    $update_stmt = $pdo->prepare("UPDATE users SET active_character_id = ? WHERE id = ?");
                                    $update_stmt->execute([$first_char['id'], $user['user_id']]);
                                    
                                    echo "<p style='color: var(--green);'>✅ Fixed: Set active character to '{$first_char['character_name']}'</p>";
                                    
                                    // Also update their department permission based on character department
                                    $user_department = '';
                                    switch($first_char['department']) {
                                        case 'Medical':
                                        case 'Science':
                                        case 'MED/SCI':
                                            $user_department = 'MED/SCI';
                                            break;
                                        case 'Engineering':
                                        case 'Operations':
                                        case 'ENG/OPS':
                                            $user_department = 'ENG/OPS';
                                            break;
                                        case 'Security':
                                        case 'Tactical':
                                        case 'SEC/TAC':
                                            $user_department = 'SEC/TAC';
                                            break;
                                        case 'Command':
                                            $user_department = 'Command';
                                            break;
                                        default:
                                            $user_department = 'SEC/TAC'; // Default fallback
                                            break;
                                    }
                                    
                                    $dept_stmt = $pdo->prepare("UPDATE users SET department = ? WHERE id = ?");
                                    $dept_stmt->execute([$user_department, $user['user_id']]);
                                    
                                    echo "<p style='color: var(--blue);'>Updated department permission to: {$user_department}</p>";
                                    
                                    // If this is the current logged-in user, update their session
                                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['user_id']) {
                                        $_SESSION['department'] = $user_department;
                                        $_SESSION['first_name'] = $first_char['first_name'];
                                        $_SESSION['last_name'] = $first_char['last_name'];
                                        $_SESSION['roster_department'] = $first_char['department'];
                                        echo "<p style='color: var(--green);'>🔄 Updated your session data - refresh the page to see changes!</p>";
                                    }
                                } else {
                                    echo "<p style='color: var(--red);'>❌ No valid characters found for this user</p>";
                                }
                                
                                echo "</div>";
                            }
                        }
                        
                        echo "<h3 style='color: var(--blue);'>🎉 Character Assignment Fix Complete!</h3>";
                        echo "<p>Users with incomplete profiles should now be able to access their character data properly.</p>";
                        
                    } catch (PDOException $e) {
                        echo "<p style='color: var(--red);'>❌ Error fixing character assignments: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    ?>
                    
                    <div style="margin: 2rem 0;">
                        <a href="pages/profile.php" style="background: var(--blue); color: black; padding: 1rem 2rem; text-decoration: none; border-radius: 15px; font-weight: bold; margin-right: 1rem;">
                            Test Profile Page
                        </a>
                        <a href="index.php" style="background: var(--orange); color: black; padding: 1rem 2rem; text-decoration: none; border-radius: 15px; font-weight: bold;">
                            Return to Main Computer
                        </a>
                    </div>
                </main>
            </div>
        </div>
    </section>
</body>
</html>
