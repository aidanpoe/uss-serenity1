<?php
// Awards Management System - Command Interface
session_start();
require_once '../includes/config.php';

// Check if user is logged in and has command permissions
if (!isset($_SESSION['steamid']) || !hasPermission('Command')) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle award assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_award'])) {
    $roster_id = (int)$_POST['roster_id'];
    $award_id = (int)$_POST['award_id'];
    $citation = trim($_POST['citation']);
    $date_awarded = $_POST['date_awarded'];
    
    try {
        // Check if award already exists for this person
        $check_stmt = $pdo->prepare("SELECT id FROM crew_awards WHERE roster_id = ? AND award_id = ?");
        $check_stmt->execute([$roster_id, $award_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = "This crew member already has this award.";
        } else {
            // Get the awarding officer's roster ID
            $awarding_officer_id = null;
            if (isset($_SESSION['roster_id'])) {
                $awarding_officer_id = $_SESSION['roster_id'];
            }
            
            // Insert the award
            $stmt = $pdo->prepare("INSERT INTO crew_awards (roster_id, award_id, awarded_by_roster_id, date_awarded, citation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$roster_id, $award_id, $awarding_officer_id, $date_awarded, $citation]);
            
            // Update award count in roster
            $update_stmt = $pdo->prepare("UPDATE roster SET award_count = (SELECT COUNT(*) FROM crew_awards WHERE roster_id = ?) WHERE id = ?");
            $update_stmt->execute([$roster_id, $roster_id]);
            
            $message = "Award successfully assigned!";
        }
    } catch (Exception $e) {
        $error = "Error assigning award: " . $e->getMessage();
    }
}

// Handle award removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_award'])) {
    $crew_award_id = (int)$_POST['crew_award_id'];
    
    try {
        // Get roster_id before deletion for award count update
        $get_roster_stmt = $pdo->prepare("SELECT roster_id FROM crew_awards WHERE id = ?");
        $get_roster_stmt->execute([$crew_award_id]);
        $roster_data = $get_roster_stmt->fetch();
        
        if ($roster_data) {
            // Delete the award
            $stmt = $pdo->prepare("DELETE FROM crew_awards WHERE id = ?");
            $stmt->execute([$crew_award_id]);
            
            // Update award count
            $update_stmt = $pdo->prepare("UPDATE roster SET award_count = (SELECT COUNT(*) FROM crew_awards WHERE roster_id = ?) WHERE id = ?");
            $update_stmt->execute([$roster_data['roster_id'], $roster_data['roster_id']]);
            
            $message = "Award successfully removed!";
        }
    } catch (Exception $e) {
        $error = "Error removing award: " . $e->getMessage();
    }
}

// Get all available awards
try {
    $awards_stmt = $pdo->query("SELECT * FROM awards ORDER BY order_precedence, name");
    $awards = $awards_stmt->fetchAll();
} catch (Exception $e) {
    $awards = [];
    $error = "Awards system not initialized. Please run setup_awards_system.php first.";
}

// Get all crew members
try {
    $crew_stmt = $pdo->query("SELECT id, rank, first_name, last_name, department, position FROM roster ORDER BY rank, last_name, first_name");
    $crew_members = $crew_stmt->fetchAll();
} catch (Exception $e) {
    $crew_members = [];
    if (empty($error)) {
        $error = "Error loading crew members: " . $e->getMessage();
    }
}

// Get current award assignments
try {
    $assignments_stmt = $pdo->query("
        SELECT ca.*, a.name as award_name, a.type as award_type, 
               r.rank, r.first_name, r.last_name,
               aw.rank as awarding_rank, aw.first_name as awarding_first_name, aw.last_name as awarding_last_name
        FROM crew_awards ca
        JOIN awards a ON ca.award_id = a.id
        JOIN roster r ON ca.roster_id = r.id
        LEFT JOIN roster aw ON ca.awarded_by_roster_id = aw.id
        ORDER BY ca.date_awarded DESC
    ");
    $current_assignments = $assignments_stmt->fetchAll();
} catch (Exception $e) {
    $current_assignments = [];
    if (empty($error)) {
        $error = "Error loading award assignments: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USS-Serenity - Awards Management</title>
    <link rel="stylesheet" href="../assets/lcars.css">
    <link rel="stylesheet" href="../assets/lower-decks.css">
    <style>
        .content-area {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .awards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .award-panel {
            background: linear-gradient(145deg, rgba(153, 153, 204, 0.1) 0%, rgba(0, 0, 0, 0.3) 100%);
            border: 2px solid var(--bluey);
            border-radius: 15px;
            padding: 1.5rem;
            position: relative;
        }
        
        .award-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange) 0%, var(--bluey) 50%, var(--orange) 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .panel-header {
            color: var(--orange);
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .lcars-form {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--bluey);
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .form-row {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            color: var(--orange);
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        
        .lcars-input,
        .lcars-select,
        .lcars-textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid var(--bluey);
            border-radius: 5px;
            color: var(--bluey);
            font-family: 'Antonio', sans-serif;
            font-size: 1rem;
        }
        
        .lcars-input:focus,
        .lcars-select:focus,
        .lcars-textarea:focus {
            border-color: var(--orange);
            outline: none;
            box-shadow: 0 0 10px rgba(255, 153, 0, 0.3);
        }
        
        .lcars-button {
            background: linear-gradient(145deg, var(--orange) 0%, #ff6600 100%);
            color: black;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Antonio', sans-serif;
        }
        
        .lcars-button:hover {
            background: linear-gradient(145deg, #ff6600 0%, var(--orange) 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 153, 0, 0.4);
        }
        
        .lcars-button.danger {
            background: linear-gradient(145deg, var(--red) 0%, #cc0000 100%);
            color: white;
        }
        
        .lcars-button.danger:hover {
            background: linear-gradient(145deg, #cc0000 0%, var(--red) 100%);
            box-shadow: 0 5px 15px rgba(204, 68, 68, 0.4);
        }
        
        .awards-display {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--bluey);
            border-radius: 10px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .award-category {
            background: linear-gradient(90deg, var(--orange) 0%, transparent 100%);
            color: black;
            padding: 0.8rem 1rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--bluey);
        }
        
        .award-entry {
            padding: 1rem;
            border-bottom: 1px solid rgba(153, 153, 204, 0.3);
            transition: background 0.3s ease;
        }
        
        .award-entry:hover {
            background: rgba(153, 153, 204, 0.1);
        }
        
        .award-entry:last-child {
            border-bottom: none;
        }
        
        .award-title {
            color: var(--orange);
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }
        
        .award-type {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .award-type.medal {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: black;
        }
        
        .award-type.ribbon {
            background: linear-gradient(45deg, #87CEEB, #4682B4);
            color: black;
        }
        
        .award-type.badge {
            background: linear-gradient(45deg, #32CD32, #228B22);
            color: black;
        }
        
        .award-details {
            color: var(--bluey);
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .award-meta {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }
        
        .award-dept {
            color: var(--green);
            font-weight: bold;
        }
        
        .award-rank {
            color: var(--gold);
        }
        
        .assignments-panel {
            grid-column: 1 / -1;
        }
        
        .assignment-item {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--bluey);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .assignment-info {
            flex: 1;
        }
        
        .recipient-name {
            color: var(--orange);
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .award-name {
            color: var(--bluey);
            margin: 0.3rem 0;
        }
        
        .assignment-meta {
            color: var(--green);
            font-size: 0.9rem;
        }
        
        .citation {
            color: var(--gold);
            font-style: italic;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 5px;
        }
        
        .message-panel {
            background: linear-gradient(145deg, rgba(0, 255, 0, 0.1) 0%, rgba(0, 0, 0, 0.3) 100%);
            border: 2px solid var(--green);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            color: var(--green);
        }
        
        .error-panel {
            background: linear-gradient(145deg, rgba(204, 68, 68, 0.2) 0%, rgba(0, 0, 0, 0.3) 100%);
            border: 2px solid var(--red);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            color: var(--red);
        }
        
        .scrollbar-style {
            scrollbar-width: thin;
            scrollbar-color: var(--orange) rgba(0, 0, 0, 0.3);
        }
        
        .scrollbar-style::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollbar-style::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }
        
        .scrollbar-style::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 4px;
        }
        
        .scrollbar-style::-webkit-scrollbar-thumb:hover {
            background: var(--gold);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- LCARS Header -->
        <div class="lcars-row">
            <div class="lcars-column spacing">
                <div class="lcars-bar horizontal orange"></div>
                <div class="lcars-bar horizontal orange"></div>
            </div>
            <div class="lcars-column">
                <div class="panel-1">STARFLEET</div>
            </div>
        </div>
        
        <div class="lcars-row">
            <div class="lcars-column spacing">
                <div class="lcars-bar horizontal bluey"></div>
            </div>
            <div class="lcars-column">
                <div class="panel-2">AWARDS<span class="hop">-MANAGEMENT</span></div>
            </div>
        </div>
        
        <div class="lcars-row">
            <div class="lcars-column spacing">
                <div class="lcars-bar horizontal orange"></div>
            </div>
            <div class="lcars-column">
                <div class="banner">STARFLEET AWARDS MANAGEMENT &#149; USS-SERENITY</div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="lcars-row">
            <div class="lcars-column u-1-8">
                <div class="panel-3">LCARS<span class="hop">-09</span></div>
                <div class="lcars-bracket">
                    <div class="lcars-bracket-content">
                        <button onclick="window.location.href='../pages/command.php'" style="background-color: var(--orange);">COMMAND</button>
                        <button onclick="window.location.href='../pages/roster.php'" style="background-color: var(--bluey);">ROSTER</button>
                        <button onclick="window.location.href='awards_management.php'" style="background-color: var(--red);">AWARDS</button>
                    </div>
                </div>
            </div>
            <div class="lcars-column u-7-8">
                <div class="panel-3">USS<span class="hop">-SERENITY</span></div>
                
                <div class="content-area">
                    <h1 style="color: var(--orange); text-align: center; margin-bottom: 2rem; font-size: 2rem; text-transform: uppercase; letter-spacing: 3px;">Starfleet Awards Management</h1>
                    
                    <?php if ($message): ?>
                        <div class="message-panel"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="error-panel">
                            <?php echo htmlspecialchars($error); ?>
                            <?php if (strpos($error, 'not initialized') !== false): ?>
                                <br><br>
                                <a href="../setup_awards_system.php" style="color: var(--orange); text-decoration: underline; font-weight: bold;">
                                    → INITIALIZE AWARDS SYSTEM
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($awards) && !empty($crew_members)): ?>
                    <div class="awards-grid">
                        <!-- Award Assignment Panel -->
                        <div class="award-panel">
                            <div class="panel-header">🏅 Assign Award</div>
                            <form method="POST" class="lcars-form">
                                <div class="form-row">
                                    <label for="roster_id" class="form-label">Crew Member:</label>
                                    <select name="roster_id" id="roster_id" class="lcars-select" required>
                                        <option value="">Select crew member...</option>
                                        <?php foreach ($crew_members as $member): ?>
                                            <option value="<?php echo $member['id']; ?>">
                                                <?php echo htmlspecialchars($member['rank'] . ' ' . $member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['department'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="award_id" class="form-label">Award:</label>
                                    <select name="award_id" id="award_id" class="lcars-select" required>
                                        <option value="">Select award...</option>
                                        <?php 
                                        $current_type = '';
                                        foreach ($awards as $award): 
                                            if ($award['type'] !== $current_type) {
                                                if ($current_type !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . htmlspecialchars($award['type'] . 's') . '">';
                                                $current_type = $award['type'];
                                            }
                                        ?>
                                            <option value="<?php echo $award['id']; ?>">
                                                <?php echo htmlspecialchars($award['name']); ?>
                                                <?php if ($award['specialization']): ?>
                                                    (<?php echo htmlspecialchars($award['specialization']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if ($current_type !== '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="date_awarded" class="form-label">Date Awarded:</label>
                                    <input type="date" name="date_awarded" id="date_awarded" class="lcars-input" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-row">
                                    <label for="citation" class="form-label">Citation (Optional):</label>
                                    <textarea name="citation" id="citation" class="lcars-textarea" rows="3" placeholder="Brief description of why this award was given..."></textarea>
                                </div>
                                
                                <div style="text-align: center; margin-top: 1.5rem;">
                                    <button type="submit" name="assign_award" class="lcars-button">
                                        ⚡ ASSIGN AWARD
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Available Awards Panel -->
                        <div class="award-panel">
                            <div class="panel-header">📋 Available Awards</div>
                            <div class="awards-display scrollbar-style">
                                <?php 
                                $current_type = '';
                                foreach ($awards as $award): 
                                    if ($award['type'] !== $current_type) {
                                        if ($current_type !== '') echo '</div>';
                                        echo '<div class="award-category">' . htmlspecialchars($award['type'] . 's') . '</div>';
                                        $current_type = $award['type'];
                                    }
                                ?>
                                    <div class="award-entry">
                                        <div class="award-title"><?php echo htmlspecialchars($award['name']); ?></div>
                                        <div class="award-type <?php echo strtolower($award['type']); ?>">
                                            <?php echo htmlspecialchars($award['type']); ?>
                                        </div>
                                        <div class="award-details">
                                            <?php echo htmlspecialchars($award['description']); ?>
                                        </div>
                                        <div class="award-meta">
                                            <?php if ($award['specialization']): ?>
                                                <span class="award-dept">📡 <?php echo htmlspecialchars($award['specialization']); ?></span>
                                            <?php endif; ?>
                                            <span class="award-rank">⭐ Min. Rank: <?php echo htmlspecialchars($award['minimum_rank']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Award Assignments -->
                    <div class="award-panel assignments-panel">
                        <div class="panel-header">🎖️ Current Award Assignments</div>
                        <div class="awards-display scrollbar-style">
                            <?php if (empty($current_assignments)): ?>
                                <div style="text-align: center; padding: 3rem; color: var(--bluey);">
                                    <h4>No Awards Assigned</h4>
                                    <p>No awards have been assigned to crew members yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($current_assignments as $assignment): ?>
                                    <div class="assignment-item">
                                        <div class="assignment-info">
                                            <div class="recipient-name">
                                                <?php echo htmlspecialchars($assignment['rank'] . ' ' . $assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                            </div>
                                            <div class="award-name">
                                                <span class="award-type <?php echo strtolower($assignment['award_type']); ?>">
                                                    <?php echo htmlspecialchars($assignment['award_type']); ?>
                                                </span>
                                                <?php echo htmlspecialchars($assignment['award_name']); ?>
                                            </div>
                                            <div class="assignment-meta">
                                                📅 Awarded: <?php echo htmlspecialchars($assignment['date_awarded']); ?> | 
                                                👤 By: <?php if ($assignment['awarding_first_name']): ?>
                                                    <?php echo htmlspecialchars($assignment['awarding_rank'] . ' ' . $assignment['awarding_first_name'] . ' ' . $assignment['awarding_last_name']); ?>
                                                <?php else: ?>
                                                    System
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($assignment['citation']): ?>
                                                <div class="citation">
                                                    "<?php echo htmlspecialchars($assignment['citation']); ?>"
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="crew_award_id" value="<?php echo $assignment['id']; ?>">
                                            <button type="submit" name="remove_award" class="lcars-button danger" onclick="return confirm('Are you sure you want to remove this award?')">
                                                🗑️ REMOVE
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: var(--orange);">
                            <h3>Awards System Not Available</h3>
                            <p>The awards system has not been initialized yet.</p>
                            <?php if (strpos($error, 'not initialized') !== false): ?>
                                <p><a href="../setup_awards_system.php" style="color: var(--bluey); text-decoration: underline;">Initialize Awards System</a></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Award selection helper
        <?php if (!empty($awards) && !empty($crew_members)): ?>
        document.getElementById('award_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                // Could add award details display here
                console.log('Selected award:', selectedOption.text);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
