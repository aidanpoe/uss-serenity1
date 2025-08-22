<?php
require_once '../includes/config.php';

// Check if user is logged in and has Captain privileges
if (!isLoggedIn() || !hasPermission('Command')) {
    header('Location: login.php');
    exit();
}

$pdo = getConnection();

// Handle character department updates
if ($_POST['action'] ?? '' === 'update_character_department') {
    $character_id = $_POST['character_id'] ?? 0;
    $new_department = $_POST['new_department'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    try {
        // Get current character data
        $stmt = $pdo->prepare("SELECT * FROM roster WHERE id = ?");
        $stmt->execute([$character_id]);
        $character = $stmt->fetch();
        
        if ($character) {
            // Update character department
            $stmt = $pdo->prepare("UPDATE roster SET department = ?, is_invisible = ? WHERE id = ?");
            $is_invisible = ($new_department === 'Starfleet Auditor') ? 1 : 0;
            $stmt->execute([$new_department, $is_invisible, $character_id]);
            
            // Log the assignment/revocation
            $stmt = $pdo->prepare("INSERT INTO character_auditor_assignments (roster_id, assigned_by_user_id, notes, is_active) VALUES (?, ?, ?, ?)");
            $is_active = ($new_department === 'Starfleet Auditor') ? 1 : 0;
            $stmt->execute([$character_id, $_SESSION['user_id'], $notes, $is_active]);
            
            // If user is currently playing this character, update their session
            if (($_SESSION['character_id'] ?? 0) == $character_id) {
                switchCharacter($character_id); // Refresh session data
            }
            
            $success_message = ($new_department === 'Starfleet Auditor') 
                ? "Character promoted to Starfleet Auditor" 
                : "Character department updated to " . $new_department;
        }
    } catch (Exception $e) {
        $error_message = "Error updating character: " . $e->getMessage();
    }
}

// Get all characters (including invisible ones for management)
$stmt = $pdo->query("
    SELECT r.*, u.first_name as user_first_name, u.last_name as user_last_name 
    FROM roster r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.department, r.rank, r.first_name, r.last_name
");
$all_characters = $stmt->fetchAll();

// Get current Starfleet Auditor characters
$auditor_characters = array_filter($all_characters, function($char) {
    return $char['department'] === 'Starfleet Auditor';
});

// Get recent auditor assignments
$stmt = $pdo->query("
    SELECT caa.*, r.first_name, r.last_name, r.department,
           u.first_name as assigned_by_first, u.last_name as assigned_by_last
    FROM character_auditor_assignments caa
    JOIN roster r ON caa.roster_id = r.id
    JOIN users u ON caa.assigned_by_user_id = u.id
    ORDER BY caa.assigned_at DESC
    LIMIT 20
");
$recent_assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Auditor Management - USS Serenity</title>
    <link rel="stylesheet" href="../assets/classic.css">
    <script src="../assets/lcars.js"></script>
</head>
<body>
    <audio id="audio1" src="../assets/beep1.mp3" preload="auto"></audio>
    <audio id="audio2" src="../assets/beep2.mp3" preload="auto"></audio>
    <audio id="audio3" src="../assets/beep3.mp3" preload="auto"></audio>
    <audio id="audio4" src="../assets/beep4.mp3" preload="auto"></audio>

    <div class="lcars-wrapper">
        <!-- Header Section -->
        <section class="lcars-section">
            <div class="lcars-content">
                <h1>🛡️ Character Auditor Management</h1>
                <p>Captain-Only Interface: Manage Starfleet Auditor Character Assignments</p>
                
                <?php if (isset($success_message)): ?>
                    <div style="background-color: var(--orange); color: black; padding: 1rem; margin: 1rem 0; border-radius: 5px;">
                        ✅ <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div style="background-color: #ff3366; color: white; padding: 1rem; margin: 1rem 0; border-radius: 5px;">
                        ❌ <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Current Auditor Characters -->
        <section class="lcars-section">
            <div class="lcars-content">
                <h2>🔒 Current Starfleet Auditor Characters</h2>
                <?php if (count($auditor_characters) > 0): ?>
                    <div class="records-container">
                        <?php foreach ($auditor_characters as $char): ?>
                            <div class="record-item">
                                <div class="record-header">
                                    <strong><?= htmlspecialchars($char['rank']) ?> <?= htmlspecialchars($char['first_name']) ?> <?= htmlspecialchars($char['last_name']) ?></strong>
                                    <span style="color: var(--orange);">🛡️ AUDITOR</span>
                                </div>
                                <div class="record-details">
                                    <p><strong>Player:</strong> <?= htmlspecialchars($char['user_first_name']) ?> <?= htmlspecialchars($char['user_last_name']) ?></p>
                                    <p><strong>Position:</strong> <?= htmlspecialchars($char['position'] ?? 'N/A') ?></p>
                                    <p><strong>Status:</strong> <span style="color: #ff3366;">INVISIBLE</span></p>
                                    
                                    <form method="POST" style="margin-top: 1rem;">
                                        <input type="hidden" name="action" value="update_character_department">
                                        <input type="hidden" name="character_id" value="<?= $char['id'] ?>">
                                        <select name="new_department" required>
                                            <option value="">Select New Department</option>
                                            <option value="Command">Command</option>
                                            <option value="MED/SCI">MED/SCI</option>
                                            <option value="ENG/OPS">ENG/OPS</option>
                                            <option value="SEC/TAC">SEC/TAC</option>
                                        </select>
                                        <input type="text" name="notes" placeholder="Reason for change" style="margin-left: 1rem; width: 300px;">
                                        <button type="submit" style="background-color: #ff3366; color: white; margin-left: 1rem;">
                                            🔄 Revoke Auditor Status
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--blue);">ℹ No characters currently have Starfleet Auditor status.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Assign New Auditor -->
        <section class="lcars-section">
            <div class="lcars-content">
                <h2>➕ Assign Starfleet Auditor Status</h2>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_character_department">
                        <input type="hidden" name="new_department" value="Starfleet Auditor">
                        
                        <div class="form-group">
                            <label for="character_id">Select Character:</label>
                            <select name="character_id" id="character_id" required>
                                <option value="">Choose a character to promote...</option>
                                <?php foreach ($all_characters as $char): ?>
                                    <?php if ($char['department'] !== 'Starfleet Auditor'): ?>
                                        <option value="<?= $char['id'] ?>">
                                            <?= htmlspecialchars($char['rank']) ?> <?= htmlspecialchars($char['first_name']) ?> <?= htmlspecialchars($char['last_name']) ?> 
                                            (<?= htmlspecialchars($char['department']) ?>) - 
                                            Player: <?= htmlspecialchars($char['user_first_name']) ?> <?= htmlspecialchars($char['user_last_name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Assignment Notes:</label>
                            <textarea name="notes" id="notes" placeholder="Reason for assignment, special instructions, etc." rows="3"></textarea>
                        </div>
                        
                        <button type="submit" style="background-color: var(--orange); color: black; padding: 1rem 2rem; border: none; border-radius: 5px; font-weight: bold;">
                            🛡️ Assign Starfleet Auditor Status
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Assignment History -->
        <section class="lcars-section">
            <div class="lcars-content">
                <h2>📋 Recent Assignment History</h2>
                <div class="records-container">
                    <?php foreach ($recent_assignments as $assignment): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <strong><?= htmlspecialchars($assignment['first_name']) ?> <?= htmlspecialchars($assignment['last_name']) ?></strong>
                                <span style="color: <?= $assignment['is_active'] ? 'var(--orange)' : '#ff3366' ?>;">
                                    <?= $assignment['is_active'] ? '🛡️ ASSIGNED' : '🔄 REVOKED' ?>
                                </span>
                            </div>
                            <div class="record-details">
                                <p><strong>Action:</strong> <?= $assignment['is_active'] ? 'Promoted to Starfleet Auditor' : 'Auditor status revoked' ?></p>
                                <p><strong>By:</strong> <?= htmlspecialchars($assignment['assigned_by_first']) ?> <?= htmlspecialchars($assignment['assigned_by_last']) ?></p>
                                <p><strong>Date:</strong> <?= date('Y-m-d H:i:s', strtotime($assignment['assigned_at'])) ?></p>
                                <?php if ($assignment['notes']): ?>
                                    <p><strong>Notes:</strong> <?= htmlspecialchars($assignment['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Navigation -->
        <section class="lcars-section">
            <div class="lcars-content">
                <div style="text-align: center; margin-top: 2rem;">
                    <button onclick="playSoundAndRedirect('audio1', 'command.php')" style="background-color: var(--blue); color: white; border: none; padding: 1rem 2rem; border-radius: 5px; margin: 0 1rem;">
                        ⬅️ Back to Command Center
                    </button>
                </div>
            </div>
        </section>
    </div>

    <style>
        .form-container {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 2rem;
            border-radius: 10px;
            border: 2px solid var(--orange);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: var(--orange);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border: 1px solid var(--orange);
            border-radius: 5px;
            font-family: inherit;
        }
        
        .records-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .record-item {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--blue);
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .record-details p {
            margin: 0.25rem 0;
            color: #cccccc;
        }
    </style>
</body>
</html>
