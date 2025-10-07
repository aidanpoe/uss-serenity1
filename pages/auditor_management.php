<?php
// Starfleet Auditor Management - Captain Only
require_once '../includes/config.php';

// Ensure only Captains can access this page
if (!hasPermission('Captain')) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

// Handle auditor assignment
if ($_POST['action'] === 'assign_auditor' && isset($_POST['user_id'])) {
    try {
        $pdo = getConnection();
        
        // Update user to Starfleet Auditor department
        $stmt = $pdo->prepare("UPDATE users SET department = 'Starfleet Auditor', is_invisible = 1 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        
        // Log the assignment
        $stmt = $pdo->prepare("INSERT INTO auditor_assignments (user_id, assigned_by_user_id, notes) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['user_id'],
            $_SESSION['user_id'],
            $_POST['notes'] ?? 'Assigned Starfleet Auditor role for moderation purposes'
        ]);
        
        $success = "User successfully assigned as Starfleet Auditor.";
    } catch (Exception $e) {
        $error = "Error assigning auditor: " . $e->getMessage();
    }
}

// Handle auditor removal
if ($_POST['action'] === 'revoke_auditor' && isset($_POST['user_id'])) {
    try {
        $pdo = getConnection();
        
        // Get user's original department (we'll default to Command for safety)
        $new_dept = $_POST['new_department'] ?? 'Command';
        
        // Update user back to normal department
        $stmt = $pdo->prepare("UPDATE users SET department = ?, is_invisible = 0 WHERE id = ?");
        $stmt->execute([$new_dept, $_POST['user_id']]);
        
        // Mark assignment as revoked
        $stmt = $pdo->prepare("UPDATE auditor_assignments SET is_active = 0, revoked_at = NOW() WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_POST['user_id']]);
        
        $success = "Starfleet Auditor role revoked successfully.";
    } catch (Exception $e) {
        $error = "Error revoking auditor: " . $e->getMessage();
    }
}

try {
    $pdo = getConnection();
    
    // Get all current Starfleet Auditors
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.first_name, u.last_name, 
               aa.assigned_at, aa.notes,
               assigned_by.username as assigned_by_username
        FROM users u
        LEFT JOIN auditor_assignments aa ON u.id = aa.user_id AND aa.is_active = 1
        LEFT JOIN users assigned_by ON aa.assigned_by_user_id = assigned_by.id
        WHERE u.department = 'Starfleet Auditor'
        ORDER BY aa.assigned_at DESC
    ");
    $stmt->execute();
    $current_auditors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all regular users who could become auditors
    $stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, department 
        FROM users 
        WHERE department != 'Starfleet Auditor' 
        ORDER BY username ASC
    ");
    $stmt->execute();
    $regular_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starfleet Auditor Management - USS Voyager</title>
    <link rel="stylesheet" href="../assets/classic.css">
</head>
<body>
    <audio id="beep1" src="../assets/beep1.mp3" preload="auto"></audio>
    <audio id="beep2" src="../assets/beep2.mp3" preload="auto"></audio>
    <audio id="beep3" src="../assets/beep3.mp3" preload="auto"></audio>
    <audio id="beep4" src="../assets/beep4.mp3" preload="auto"></audio>

    <header>
        <div class="header-content">
            <h1>🛡️ STARFLEET AUDITOR MANAGEMENT</h1>
            <nav>
                <a href="../index.php" onclick="playBeep()">Bridge</a>
                <a href="../pages/roster.php" onclick="playBeep()">Roster</a>
                <a href="../pages/command.php" onclick="playBeep()">Command</a>
                <a href="../pages/logout.php" onclick="playBeep()">Logout</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="wrap-standard" id="column-3">
            <?php if ($success): ?>
                <div style="background: rgba(0, 255, 0, 0.1); color: #00ff00; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid #00ff00;">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: rgba(255, 0, 0, 0.1); color: #ff6b6b; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid #ff6b6b;">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Current Starfleet Auditors -->
            <div style="background: rgba(255, 215, 0, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
                <h2 style="color: var(--gold); text-align: center;">🛡️ Current Starfleet Auditors</h2>
                <p style="color: var(--orange); text-align: center; margin-bottom: 2rem;">
                    OOC moderation accounts with full system access but invisible from all rosters
                </p>

                <?php if (!empty($current_auditors)): ?>
                    <?php foreach ($current_auditors as $auditor): ?>
                    <div style="background: rgba(255, 215, 0, 0.1); padding: 1.5rem; margin: 1rem 0; border-radius: 10px; border: 1px solid var(--gold);">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: center;">
                            <div>
                                <h3 style="color: var(--gold); margin: 0;">
                                    <?php echo htmlspecialchars($auditor['username'] ?? 'Unknown'); ?>
                                    <?php if ($auditor['first_name'] || $auditor['last_name']): ?>
                                        (<?php echo htmlspecialchars(trim(($auditor['first_name'] ?? '') . ' ' . ($auditor['last_name'] ?? ''))); ?>)
                                    <?php endif; ?>
                                </h3>
                                <p style="margin: 0.5rem 0; color: white;">
                                    <strong style="color: var(--blue);">Assigned:</strong> 
                                    <?php echo htmlspecialchars($auditor['assigned_at'] ?? 'Unknown'); ?>
                                    <?php if ($auditor['assigned_by_username']): ?>
                                        by <?php echo htmlspecialchars($auditor['assigned_by_username']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($auditor['notes']): ?>
                                    <p style="margin: 0.5rem 0; color: var(--orange); font-style: italic;">
                                        "<?php echo htmlspecialchars($auditor['notes']); ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
                                    <input type="hidden" name="action" value="revoke_auditor">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($auditor['id'] ?? ''); ?>">
                                    
                                    <select name="new_department" required style="padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;">
                                        <option value="">Restore to Department:</option>
                                        <option value="Command">Command</option>
                                        <option value="MED/SCI">MED/SCI</option>
                                        <option value="ENG/OPS">ENG/OPS</option>
                                        <option value="SEC/TAC">SEC/TAC</option>
                                    </select>
                                    
                                    <button type="submit" onclick="return confirm('Are you sure you want to revoke Starfleet Auditor access for this user?')" style="background-color: var(--red); color: white; border: none; padding: 0.5rem; border-radius: 5px; font-weight: bold;">
                                        🚫 Revoke Access
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem;">
                        <p style="color: var(--gold); font-size: 1.1rem;">No Starfleet Auditors currently assigned.</p>
                        <small style="color: var(--orange);">Use the form below to assign auditor privileges to users.</small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Assign New Auditor -->
            <div style="background: rgba(0, 155, 255, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
                <h2 style="color: var(--blue); text-align: center;">➕ Assign New Starfleet Auditor</h2>
                <p style="color: var(--blue); text-align: center; margin-bottom: 2rem;">
                    Grant full system access for OOC moderation (user will become invisible)
                </p>

                <form method="POST" action="" style="max-width: 600px; margin: 0 auto;">
                    <input type="hidden" name="action" value="assign_auditor">
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="color: var(--blue); display: block; margin-bottom: 0.5rem;">Select User:</label>
                        <select name="user_id" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--blue); border-radius: 5px;">
                            <option value="">-- Select a User --</option>
                            <?php if (!empty($regular_users)): ?>
                                <?php foreach ($regular_users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($user['username'] ?? 'Unknown'); ?>
                                        <?php if ($user['first_name'] || $user['last_name']): ?>
                                            (<?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?>)
                                        <?php endif; ?>
                                        - <?php echo htmlspecialchars($user['department'] ?? 'Unknown'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No users available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: var(--blue); display: block; margin-bottom: 0.5rem;">Assignment Notes:</label>
                        <textarea name="notes" placeholder="Reason for assignment (e.g., 'Assigned for moderation duties')" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--blue); border-radius: 5px; resize: vertical; min-height: 80px;"></textarea>
                    </div>
                    
                    <button type="submit" onclick="return confirm('This will grant the user full system access and make them invisible. Continue?')" style="background-color: var(--blue); color: black; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; width: 100%; font-weight: bold;">
                        🛡️ Assign Starfleet Auditor Role
                    </button>
                </form>
            </div>

            <!-- Information Panel -->
            <div style="background: rgba(204, 68, 68, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border: 2px solid var(--red);">
                <h3 style="color: var(--red); text-align: center;">⚠️ Starfleet Auditor Guidelines</h3>
                <ul style="color: white; margin: 1rem 0;">
                    <li><strong style="color: var(--gold);">Full Access:</strong> Auditors can access all areas of the website</li>
                    <li><strong style="color: var(--gold);">Invisible:</strong> They will not appear in rosters or crew listings</li>
                    <li><strong style="color: var(--gold);">OOC Role:</strong> This is for moderation, not roleplay characters</li>
                    <li><strong style="color: var(--gold);">Captain Only:</strong> Only Captains can assign/revoke this role</li>
                    <li><strong style="color: var(--gold);">Audit Trail:</strong> All assignments are logged for accountability</li>
                </ul>
                <p style="color: var(--orange); text-align: center; margin-top: 1rem;">
                    Use this role responsibly for server administration and moderation purposes only.
                </p>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <p>&copy; 2024 USS Voyager - Star Trek Roleplay Community</p>
        </div>
    </footer>

    <script src="../assets/lcars.js"></script>
</body>
</html>
