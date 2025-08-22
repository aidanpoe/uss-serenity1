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
    <style>
        .awards-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .award-section {
            background: rgba(153, 153, 204, 0.1);
            border: 2px solid var(--bluey);
            border-radius: 8px;
            padding: 20px;
        }
        
        .award-form {
            background: rgba(255, 153, 0, 0.1);
            border: 2px solid var(--orange);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .award-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--bluey);
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .award-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid rgba(153, 153, 204, 0.3);
        }
        
        .award-item:last-child {
            border-bottom: none;
        }
        
        .award-info {
            flex: 1;
        }
        
        .award-name {
            color: var(--orange);
            font-weight: bold;
        }
        
        .award-details {
            color: var(--bluey);
            font-size: 0.9em;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: var(--orange);
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--bluey);
            color: var(--bluey);
            border-radius: 4px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .btn-assign {
            background: var(--orange);
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-remove {
            background: var(--red);
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
        }
        
        .message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .award-type-medal { color: #FFD700; }
        .award-type-ribbon { color: #87CEEB; }
        .award-type-badge { color: #32CD32; }
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
                    <h1 style="color: var(--orange);">Starfleet Awards Management</h1>
                    
                    <?php if ($message): ?>
                        <div class="message"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="error">
                            <?php echo htmlspecialchars($error); ?>
                            <?php if (strpos($error, 'not initialized') !== false): ?>
                                <br><br>
                                <a href="../setup_awards_system.php" style="color: var(--orange); text-decoration: underline;">
                                    Click here to initialize the awards system
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($awards) && !empty($crew_members)): ?>
                    <div class="awards-container">
                        <!-- Award Assignment Form -->
                        <div class="award-section">
                            <h2 style="color: var(--orange);">Assign Award</h2>
                            <form method="POST" class="award-form">
                                <div class="form-group">
                                    <label for="roster_id">Crew Member:</label>
                                    <select name="roster_id" id="roster_id" required>
                                        <option value="">Select crew member...</option>
                                        <?php foreach ($crew_members as $member): ?>
                                            <option value="<?php echo $member['id']; ?>">
                                                <?php echo htmlspecialchars($member['rank'] . ' ' . $member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['department'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="award_id">Award:</label>
                                    <select name="award_id" id="award_id" required>
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
                                
                                <div class="form-group">
                                    <label for="date_awarded">Date Awarded:</label>
                                    <input type="date" name="date_awarded" id="date_awarded" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="citation">Citation (Optional):</label>
                                    <textarea name="citation" id="citation" placeholder="Brief description of why this award was given..."></textarea>
                                </div>
                                
                                <button type="submit" name="assign_award" class="btn-assign">ASSIGN AWARD</button>
                            </form>
                        </div>
                        
                        <!-- Available Awards List -->
                        <div class="award-section">
                            <h2 style="color: var(--orange);">Available Awards</h2>
                            <div class="award-list">
                                <?php 
                                $current_type = '';
                                foreach ($awards as $award): 
                                    if ($award['type'] !== $current_type) {
                                        if ($current_type !== '') echo '</div>';
                                        echo '<h3 style="color: var(--orange); margin: 15px 0 5px 0;">' . htmlspecialchars($award['type'] . 's') . '</h3>';
                                        echo '<div style="margin-left: 10px;">';
                                        $current_type = $award['type'];
                                    }
                                ?>
                                    <div class="award-item">
                                        <div class="award-info">
                                            <div class="award-name award-type-<?php echo strtolower($award['type']); ?>">
                                                <?php echo htmlspecialchars($award['name']); ?>
                                            </div>
                                            <div class="award-details">
                                                <?php if ($award['specialization']): ?>
                                                    Department: <?php echo htmlspecialchars($award['specialization']); ?> | 
                                                <?php endif; ?>
                                                Min. Rank: <?php echo htmlspecialchars($award['minimum_rank']); ?><br>
                                                <?php echo htmlspecialchars($award['description']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($current_type !== '') echo '</div>'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Award Assignments -->
                    <div class="award-section" style="grid-column: 1 / -1;">
                        <h2 style="color: var(--orange);">Current Award Assignments</h2>
                        <div class="award-list">
                            <?php if (empty($current_assignments)): ?>
                                <p style="color: var(--bluey); text-align: center; padding: 20px;">No awards have been assigned yet.</p>
                            <?php else: ?>
                                <?php foreach ($current_assignments as $assignment): ?>
                                    <div class="award-item">
                                        <div class="award-info">
                                            <div class="award-name award-type-<?php echo strtolower($assignment['award_type']); ?>">
                                                <?php echo htmlspecialchars($assignment['award_name']); ?>
                                            </div>
                                            <div class="award-details">
                                                Awarded to: <?php echo htmlspecialchars($assignment['rank'] . ' ' . $assignment['first_name'] . ' ' . $assignment['last_name']); ?><br>
                                                Date: <?php echo htmlspecialchars($assignment['date_awarded']); ?> | 
                                                <?php if ($assignment['awarding_first_name']): ?>
                                                    By: <?php echo htmlspecialchars($assignment['awarding_rank'] . ' ' . $assignment['awarding_first_name'] . ' ' . $assignment['awarding_last_name']); ?>
                                                <?php else: ?>
                                                    By: System
                                                <?php endif; ?>
                                                <?php if ($assignment['citation']): ?>
                                                    <br>Citation: <?php echo htmlspecialchars($assignment['citation']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <form method="POST" style="margin-left: 10px;">
                                            <input type="hidden" name="crew_award_id" value="<?php echo $assignment['id']; ?>">
                                            <button type="submit" name="remove_award" class="btn-remove" onclick="return confirm('Are you sure you want to remove this award?')">REMOVE</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
