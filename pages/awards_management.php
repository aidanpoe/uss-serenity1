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
    $assigned_by = (int)$_POST['assigned_by'];
    $citation = trim($_POST['citation']);
    $date_awarded = $_POST['date_awarded'];
    
    try {
        // Check if award already exists for this person
        $check_stmt = $pdo->prepare("SELECT id FROM crew_awards WHERE roster_id = ? AND award_id = ?");
        $check_stmt->execute([$roster_id, $award_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = "This crew member already has this award.";
        } else {
            // Insert the award
            $stmt = $pdo->prepare("INSERT INTO crew_awards (roster_id, award_id, awarded_by_roster_id, date_awarded, citation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$roster_id, $award_id, $assigned_by, $date_awarded, $citation]);
            
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
    $awards_stmt = $pdo->query("SELECT DISTINCT id, name, type, description, specialization, order_precedence FROM awards ORDER BY order_precedence, name");
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

// Get command personnel for "Assigned By" dropdown
try {
    $command_stmt = $pdo->query("SELECT id, rank, first_name, last_name, position FROM roster WHERE department = 'Command' OR position LIKE '%Captain%' OR position LIKE '%Commander%' OR position LIKE '%Admiral%' ORDER BY rank, last_name, first_name");
    $command_personnel = $command_stmt->fetchAll();
} catch (Exception $e) {
    $command_personnel = [];
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
<html>
<head>
    <title>USS-Serenity - Awards Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="date=no">
    <link rel="stylesheet" type="text/css" href="../assets/classic.css">
    <style>
        /* Awards Management Specific Styles */
        .awards-section {
            margin: 2rem 0;
        }
        
        .awards-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .award-panel {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid var(--bluey);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .panel-header {
            color: var(--orange);
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 2px solid var(--orange);
            padding-bottom: 0.5rem;
        }
        
        .award-form {
            background: rgba(0, 0, 0, 0.5);
            padding: 1rem;
            border-radius: 10px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            color: var(--orange);
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--bluey);
            border-radius: 5px;
            color: var(--bluey);
            font-family: inherit;
        }
        
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--orange);
            outline: none;
        }
        
        .btn-assign {
            background: var(--orange);
            color: black;
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-assign:hover {
            background: var(--gold);
            transform: translateY(-2px);
        }
        
        .btn-remove {
            background: var(--red);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-remove:hover {
            background: #cc0000;
            transform: translateY(-1px);
        }
        
        .awards-display {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 1.5rem;
            min-height: 500px;
        }
        
        .award-browser {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .award-type-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--bluey);
            padding-bottom: 1rem;
        }
        
        .award-tab {
            flex: 1;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid var(--bluey);
            border-radius: 10px 10px 0 0;
            color: var(--bluey);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .award-tab.active {
            background: var(--orange);
            color: black;
            border-color: var(--orange);
            transform: translateY(-2px);
        }
        
        .award-tab:hover:not(.active) {
            background: rgba(255, 136, 0, 0.2);
            border-color: var(--orange);
        }
        
        .award-content {
            flex: 1;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid var(--bluey);
            min-height: 400px;
        }
        
        .award-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .award-card {
            background: linear-gradient(145deg, rgba(0, 0, 0, 0.8) 0%, rgba(153, 153, 204, 0.1) 100%);
            border: 2px solid var(--bluey);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .award-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--orange) 0%, var(--gold) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .award-card:hover {
            border-color: var(--orange);
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(255, 136, 0, 0.3);
        }
        
        .award-card:hover::before {
            opacity: 1;
        }
        
        .award-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        
        .award-card-title {
            color: var(--orange);
            font-weight: bold;
            font-size: 1rem;
            line-height: 1.2;
        }
        
        .award-card-icon {
            font-size: 1.5rem;
            opacity: 0.8;
        }
        
        .award-card-type {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0.8rem;
        }
        
        .award-card-description {
            color: var(--bluey);
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 0.8rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .award-card-specialization {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--green);
            border-radius: 8px;
            padding: 0.3rem 0.6rem;
            color: var(--green);
            font-size: 0.75rem;
            text-align: center;
            font-weight: bold;
        }
        
        .award-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border-left: 4px solid var(--orange);
        }
        
        .award-count {
            color: var(--orange);
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .award-filter {
            color: var(--bluey);
            font-size: 0.9rem;
        }
        
        .no-awards {
            text-align: center;
            padding: 3rem;
            color: var(--bluey);
            font-style: italic;
        }
        
        .award-search {
            margin-bottom: 1rem;
        }
        
        .award-search input {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid var(--bluey);
            border-radius: 8px;
            color: var(--bluey);
            font-family: inherit;
        }
        
        .award-search input:focus {
            border-color: var(--orange);
            outline: none;
        }
        
        .award-search input::placeholder {
            color: rgba(153, 153, 204, 0.6);
        }
        
        /* Scrollbar styling for award grid */
        .award-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .award-grid::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }
        
        .award-grid::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 4px;
        }
        
        .award-grid::-webkit-scrollbar-thumb:hover {
            background: var(--gold);
        }
        
        .award-category {
            background: var(--orange);
            color: black;
            padding: 0.5rem 1rem;
            font-weight: bold;
            text-transform: uppercase;
            margin: 1rem 0 0.5rem 0;
            border-radius: 5px;
        }
        
        .award-entry {
            padding: 0.8rem;
            border-bottom: 1px solid rgba(153, 153, 204, 0.2);
            transition: background 0.3s ease;
            margin-bottom: 0.5rem;
            border-radius: 5px;
        }
        
        .award-entry:hover {
            background: rgba(153, 153, 204, 0.1);
        }
        
        .award-entry:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .award-title {
            color: var(--orange);
            font-weight: bold;
            margin-bottom: 0.5rem;
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
            background: #FFD700;
            color: black;
        }
        
        .award-type.ribbon {
            background: #87CEEB;
            color: black;
        }
        
        .award-type.badge {
            background: #32CD32;
            color: black;
        }
        
        .award-details {
            color: var(--bluey);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .award-meta {
            font-size: 0.8rem;
            color: var(--green);
            margin-top: 0.5rem;
            padding: 0.3rem 0.5rem;
            background: rgba(0, 255, 0, 0.05);
            border-radius: 3px;
            border-left: 3px solid var(--green);
        }
        
        .assignment-item {
            background: rgba(0, 0, 0, 0.5);
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
        
        .message {
            background: rgba(0, 255, 0, 0.3);
            border: 2px solid var(--green);
            color: var(--green);
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        
        .error {
            background: rgba(204, 68, 68, 0.3);
            border: 2px solid var(--red);
            color: var(--red);
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <audio id="audio1" src="../assets/beep1.mp3" preload="auto"></audio>
    <audio id="audio2" src="../assets/beep2.mp3" preload="auto"></audio>
    <audio id="audio3" src="../assets/beep3.mp3" preload="auto"></audio>
    <audio id="audio4" src="../assets/beep4.mp3" preload="auto"></audio>
    <section class="wrap-standard" id="column-3">
        <div class="wrap">
            <div class="left-frame-top">
                <button onclick="playSoundAndRedirect('audio2', '../index.php')" class="panel-1-button">LCARS</button>
                <div class="panel-2">AWARDS<span class="hop">-MANAGEMENT</span></div>
            </div>
            <div class="right-frame-top">
                <div class="banner">STARFLEET AWARDS MANAGEMENT &#149; USS-SERENITY</div>
                <div class="data-cascade-button-group">
                    <nav> 
                        <button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
                        <button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
                        <button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
                        <button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">AWARDS</button>
                    </nav>
                </div>
                <div class="bar-panel first-bar-panel">
                    <div class="bar-1"></div>
                    <div class="bar-2"></div>
                    <div class="bar-3"></div>
                    <div class="bar-4"></div>
                    <div class="bar-5"></div>
                </div>
            </div>
        </div>
        <div class="wrap" id="gap">
            <div class="left-frame">
                <button onclick="topFunction(); playSoundAndRedirect('audio4', '#')" id="topBtn"><span class="hop">screen</span> top</button>
                <div>
                    <div class="panel-3">AWARDS<span class="hop">-SYS</span></div>
                    <div class="panel-4">MEDALS<span class="hop">-12</span></div>
                    <div class="panel-5">RIBBONS<span class="hop">-15</span></div>
                    <div class="panel-6">BADGES<span class="hop">-8</span></div>
                    <div class="panel-7">ACTIVE<span class="hop">-ALL</span></div>
                </div>
                <div>
                    <div class="panel-10">CMD<span class="hop">-ACCESS</span></div>
                </div>
            </div>
            <div class="right-frame">
                <div class="bar-panel">
                    <div class="bar-6"></div>
                    <div class="bar-7"></div>
                    <div class="bar-8"></div>
                    <div class="bar-9"></div>
                    <div class="bar-10"></div>
                </div>
                <main>
                    <h1>Awards Management</h1>
                    <h2>Starfleet Recognition System</h2>
                    
                    <?php if ($message): ?>
                        <div class="message"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="error">
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
                    
                    <div class="awards-section">
                        <h3>Award Assignment</h3>
                        <div class="awards-grid">
                            <!-- Award Assignment Panel -->
                            <div class="award-panel">
                                <div class="panel-header">🏅 Assign Award</div>
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
                                    <label for="assigned_by">Assigned By:</label>
                                    <select name="assigned_by" id="assigned_by" required>
                                        <option value="">Select assigning officer...</option>
                                        <?php foreach ($command_personnel as $officer): ?>
                                            <option value="<?php echo $officer['id']; ?>" <?php echo (isset($_SESSION['roster_id']) && $_SESSION['roster_id'] == $officer['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($officer['rank'] . ' ' . $officer['first_name'] . ' ' . $officer['last_name'] . ' - ' . $officer['position']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="citation">Citation (Optional):</label>
                                    <textarea name="citation" id="citation" rows="3" placeholder="Brief description of why this award was given..."></textarea>
                                </div>
                                
                                <div style="text-align: center; margin-top: 1.5rem;">
                                    <button type="submit" name="assign_award" class="btn-assign">
                                        ⚡ ASSIGN AWARD
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Available Awards Panel -->
                        <div class="award-panel">
                            <div class="panel-header">📋 Available Awards</div>
                            <div class="awards-display">
                                <div class="award-browser">
                                    <?php 
                                    // Group awards by type
                                    $awards_by_type = [];
                                    foreach ($awards as $award) {
                                        $awards_by_type[$award['type']][] = $award;
                                    }
                                    ?>
                                    
                                    <!-- Award Type Tabs -->
                                    <div class="award-type-tabs">
                                        <?php foreach ($awards_by_type as $type => $type_awards): ?>
                                            <div class="award-tab" data-type="<?php echo strtolower($type); ?>" onclick="showAwardType('<?php echo strtolower($type); ?>')">
                                                <div style="font-size: 1.2rem; margin-bottom: 0.3rem;">
                                                    <?php 
                                                    switch($type) {
                                                        case 'Medal': echo '🏅'; break;
                                                        case 'Ribbon': echo '🎗️'; break;
                                                        case 'Badge': echo '🏆'; break;
                                                        default: echo '⭐'; break;
                                                    }
                                                    ?>
                                                </div>
                                                <?php echo htmlspecialchars($type . 's'); ?>
                                                <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 0.2rem;">
                                                    (<?php echo count($type_awards); ?>)
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Award Content Area -->
                                    <div class="award-content">
                                        <!-- Search Box -->
                                        <div class="award-search">
                                            <input type="text" id="award-search" placeholder="🔍 Search awards..." onkeyup="filterAwards()">
                                        </div>
                                        
                                        <!-- Award Stats -->
                                        <div class="award-stats">
                                            <div class="award-count" id="award-count">
                                                Total: <?php echo count($awards); ?> awards
                                            </div>
                                            <div class="award-filter" id="award-filter">
                                                Showing all types
                                            </div>
                                        </div>
                                        
                                        <!-- Award Grid for each type -->
                                        <?php foreach ($awards_by_type as $type => $type_awards): ?>
                                            <div class="award-grid award-type-content" id="<?php echo strtolower($type); ?>-content" style="display: none;">
                                                <?php foreach ($type_awards as $award): ?>
                                                    <div class="award-card" data-name="<?php echo htmlspecialchars(strtolower($award['name'])); ?>" data-specialization="<?php echo htmlspecialchars(strtolower($award['specialization'] ?? '')); ?>">
                                                        <div class="award-card-header">
                                                            <div class="award-card-title"><?php echo htmlspecialchars($award['name']); ?></div>
                                                            <div class="award-card-icon">
                                                                <?php 
                                                                switch($award['type']) {
                                                                    case 'Medal': echo '🏅'; break;
                                                                    case 'Ribbon': echo '🎗️'; break;
                                                                    case 'Badge': echo '🏆'; break;
                                                                    default: echo '⭐'; break;
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="award-card-type <?php echo strtolower($award['type']); ?>">
                                                            <?php echo htmlspecialchars($award['type']); ?>
                                                        </div>
                                                        
                                                        <div class="award-card-description">
                                                            <?php echo htmlspecialchars($award['description']); ?>
                                                        </div>
                                                        
                                                        <?php if ($award['specialization']): ?>
                                                            <div class="award-card-specialization">
                                                                📡 <?php echo htmlspecialchars($award['specialization']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- No results message -->
                                        <div class="no-awards" id="no-awards" style="display: none;">
                                            <h4>No awards found</h4>
                                            <p>Try adjusting your search terms or browse different categories.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    
                    <!-- Current Award Assignments -->
                    <div class="awards-section">
                        <h3>Current Award Assignments</h3>
                        <div class="award-panel full-width">
                            <div class="panel-header">🎖️ Assigned Awards</div>
                            <div class="awards-display">
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
                                                <button type="submit" name="remove_award" class="btn-remove" onclick="return confirm('Are you sure you want to remove this award?')">
                                                    🗑️ REMOVE
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                    <?php endif; ?>
                    
                </main>
            </div>
        </div>
    </section>
    <script src="../assets/lcars.js"></script>
    <script>
        // Award Browser JavaScript
        let currentType = '';
        
        // Initialize the award browser
        document.addEventListener('DOMContentLoaded', function() {
            // Show first tab by default
            const firstTab = document.querySelector('.award-tab');
            if (firstTab) {
                const firstType = firstTab.getAttribute('data-type');
                showAwardType(firstType);
            }
        });
        
        // Show awards of specific type
        function showAwardType(type) {
            currentType = type;
            
            // Update tabs
            document.querySelectorAll('.award-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-type="${type}"]`).classList.add('active');
            
            // Update content
            document.querySelectorAll('.award-type-content').forEach(content => {
                content.style.display = 'none';
            });
            document.getElementById(`${type}-content`).style.display = 'grid';
            
            // Update stats
            updateAwardStats();
            
            // Clear search
            document.getElementById('award-search').value = '';
        }
        
        // Filter awards based on search
        function filterAwards() {
            const searchTerm = document.getElementById('award-search').value.toLowerCase();
            const currentContent = document.getElementById(`${currentType}-content`);
            
            if (!currentContent) return;
            
            const cards = currentContent.querySelectorAll('.award-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const specialization = card.getAttribute('data-specialization');
                const description = card.querySelector('.award-card-description').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || 
                    specialization.includes(searchTerm) || 
                    description.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noAwards = document.getElementById('no-awards');
            if (visibleCount === 0 && searchTerm !== '') {
                noAwards.style.display = 'block';
                currentContent.style.display = 'none';
            } else {
                noAwards.style.display = 'none';
                currentContent.style.display = 'grid';
            }
            
            updateAwardStats(visibleCount, searchTerm);
        }
        
        // Update award statistics display
        function updateAwardStats(visibleCount = null, searchTerm = '') {
            const currentContent = document.getElementById(`${currentType}-content`);
            if (!currentContent) return;
            
            const totalCards = currentContent.querySelectorAll('.award-card').length;
            const displayCount = visibleCount !== null ? visibleCount : totalCards;
            
            document.getElementById('award-count').textContent = `${displayCount} of ${totalCards} ${currentType}s`;
            
            if (searchTerm) {
                document.getElementById('award-filter').textContent = `Filtered by: "${searchTerm}"`;
            } else {
                document.getElementById('award-filter').textContent = `Showing all ${currentType}s`;
            }
        }
        
        // Award selection helper for form
        <?php if (!empty($awards) && !empty($crew_members)): ?>
        if (document.getElementById('award_id')) {
            document.getElementById('award_id').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    console.log('Selected award:', selectedOption.text);
                }
            });
        }
        <?php endif; ?>
        
        // Sound effects function from other LCARS pages
        function playSoundAndRedirect(audioId, url) {
            document.getElementById(audioId).play();
            setTimeout(function() {
                window.location.href = url;
            }, 150);
        }
        
        // Top function for scroll to top
        function topFunction() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
    </script>
</body>
</html>
