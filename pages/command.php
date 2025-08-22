<?php
require_once '../includes/config.php';
require_once '../includes/department_training.php';

// Update last active timestamp for current character
updateLastActive();

// Handle department training if user has permission
if (hasPermission('Command')) {
    handleDepartmentTraining('Command');
}

// Initialize variables
$success = '';
$error = '';
$command_officers = [];
$suggestions = [];
$award_recommendations = [];
$summary = [
    'open_medical' => 0,
    'open_faults' => 0, 
    'open_security' => 0,
    'total_crew' => 0
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Handle suggestion submission
    if ($_POST['action'] === 'submit_suggestion') {
        if (!isLoggedIn()) {
            $error = "You must be logged in to submit suggestions.";
        } else {
            try {
                $submitted_by = trim(
                    ($_SESSION['rank'] ?? '') . ' ' . 
                    ($_SESSION['first_name'] ?? '') . ' ' . 
                    ($_SESSION['last_name'] ?? '')
                );
                
                $pdo = getConnection();
                
                // Create command_suggestions table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS command_suggestions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    suggestion_title VARCHAR(255) NOT NULL,
                    suggestion_description TEXT NOT NULL,
                    submitted_by VARCHAR(255) NOT NULL,
                    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'reviewed', 'implemented') DEFAULT 'pending'
                )");
                
                $stmt = $pdo->prepare("INSERT INTO command_suggestions (suggestion_title, suggestion_description, submitted_by) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['suggestion_title'] ?? '',
                    $_POST['suggestion_description'] ?? '',
                    $submitted_by
                ]);
                $success = "Suggestion submitted successfully.";
            } catch (Exception $e) {
                $error = "Error submitting suggestion: " . $e->getMessage();
            }
        }
    }
    
    // Handle award recommendation submission
    if ($_POST['action'] === 'submit_award_recommendation') {
        if (!isLoggedIn()) {
            $error = "You must be logged in to submit award recommendations.";
        } else {
            try {
                $submitted_by = trim(
                    ($_SESSION['rank'] ?? '') . ' ' . 
                    ($_SESSION['first_name'] ?? '') . ' ' . 
                    ($_SESSION['last_name'] ?? '')
                );
                
                $pdo = getConnection();
                
                // Create award_recommendations table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS award_recommendations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    recommended_person VARCHAR(255) NOT NULL,
                    recommended_award VARCHAR(255) NOT NULL,
                    justification TEXT NOT NULL,
                    submitted_by VARCHAR(255) NOT NULL,
                    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                    reviewed_by VARCHAR(255),
                    review_notes TEXT,
                    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    reviewed_at TIMESTAMP NULL
                )");
                
                $stmt = $pdo->prepare("INSERT INTO award_recommendations (recommended_person, recommended_award, justification, submitted_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['recommended_person'] ?? '',
                    $_POST['recommended_award'] ?? '',
                    $_POST['justification'] ?? '',
                    $submitted_by
                ]);
                $success = "Award recommendation submitted successfully and is pending command review.";
            } catch (Exception $e) {
                $error = "Error submitting award recommendation: " . $e->getMessage();
            }
        }
    }
    
    // Handle award recommendation update (command staff only)
    if ($_POST['action'] === 'update_award_recommendation') {
        if (!hasPermission('Command')) {
            $error = "Access denied. Command authorization required.";
        } else {
            try {
                $reviewed_by = trim(
                    ($_SESSION['rank'] ?? '') . ' ' . 
                    ($_SESSION['first_name'] ?? '') . ' ' . 
                    ($_SESSION['last_name'] ?? '')
                );
                
                $pdo = getConnection();
                $stmt = $pdo->prepare("UPDATE award_recommendations SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([
                    $_POST['status'] ?? '',
                    $_POST['review_notes'] ?? '',
                    $reviewed_by,
                    $_POST['recommendation_id'] ?? 0
                ]);
                $success = "Award recommendation updated successfully.";
            } catch (Exception $e) {
                $error = "Error updating award recommendation: " . $e->getMessage();
            }
        }
    }
    
    // Handle award recommendation deletion (Command/Starfleet Auditor only)
    if ($_POST['action'] === 'delete_award_recommendation') {
        $roster_dept = $_SESSION['roster_department'] ?? '';
        if (!hasPermission('Command') && $roster_dept !== 'Starfleet Auditor') {
            $error = "Access denied. Command or Starfleet Auditor authorization required.";
        } else {
            try {
                $pdo = getConnection();
                
                // Get recommendation details for logging
                $stmt = $pdo->prepare("SELECT * FROM award_recommendations WHERE id = ?");
                $stmt->execute([$_POST['recommendation_id'] ?? 0]);
                $recommendation = $stmt->fetch();
                
                if ($recommendation) {
                    // Delete the recommendation
                    $stmt = $pdo->prepare("DELETE FROM award_recommendations WHERE id = ?");
                    $stmt->execute([$_POST['recommendation_id'] ?? 0]);
                    
                    // Log the action for auditing (both Command and Starfleet Auditors)
                    if (isset($_SESSION['character_id']) && (hasPermission('Command') || $roster_dept === 'Starfleet Auditor')) {
                        logAuditorAction($_SESSION['character_id'], 'delete_award_recommendation', 'award_recommendations', $recommendation['id'], [
                            'recommended_person' => $recommendation['recommended_person'],
                            'recommended_award' => $recommendation['recommended_award'],
                            'status' => $recommendation['status'],
                            'user_type' => $roster_dept === 'Starfleet Auditor' ? 'Starfleet Auditor' : 'Command Staff'
                        ]);
                    }
                    
                    $success = "Award recommendation deleted successfully.";
                } else {
                    $error = "Award recommendation not found.";
                }
            } catch (Exception $e) {
                $error = "Error deleting award recommendation: " . $e->getMessage();
            }
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get command structure - try broader search for command positions
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position LIKE '%Command%' OR position LIKE '%Captain%' OR position LIKE '%Officer%' OR position LIKE '%Executive%' ORDER BY position");
    $stmt->execute();
    $all_command_related = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Filter for actual command positions (prioritize high-ranking positions)
    $command_officers = [];
    $priority_positions = ['Captain', 'Commanding Officer', 'Head of Command', 'First Officer', 'Executive Officer', 'Second Officer', 'Third Officer'];
    
    // First, add officers with priority positions
    foreach ($all_command_related as $officer) {
        foreach ($priority_positions as $priority_pos) {
            if (stripos($officer['position'], $priority_pos) !== false) {
                $command_officers[] = $officer;
                break;
            }
        }
    }
    
    // If no priority positions found, show any command-related positions
    if (empty($command_officers)) {
        $command_officers = array_slice($all_command_related, 0, 6); // Limit to 6 for display
    }
    
    // Get roster data for award recommendations dropdown (excluding invisible users)
    $roster_members = [];
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.rank, r.first_name, r.last_name, r.department, r.position 
            FROM roster r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE (r.is_invisible IS NULL OR r.is_invisible = 0) 
              AND (u.is_invisible IS NULL OR u.is_invisible = 0 OR u.department != 'Starfleet Auditor')
            ORDER BY r.rank DESC, r.last_name ASC, r.first_name ASC
        ");
        $stmt->execute();
        $roster_members = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        // Roster table may not exist yet, continue without dropdown data
    }
    
    // Get awards data for award recommendations dropdown
    $available_awards = [];
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, specialization, description FROM awards ORDER BY order_precedence ASC, name ASC");
        $stmt->execute();
        $available_awards = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        // Awards table may not exist yet, continue without dropdown data
    }
    
    // Get data for command users only
    if (hasPermission('Command')) {
        
        // Get suggestions
        try {
            $stmt = $pdo->prepare("SELECT * FROM command_suggestions ORDER BY status ASC, submission_date DESC");
            $stmt->execute();
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            $suggestions = [];
        }
        
        // Get award recommendations
        try {
            $stmt = $pdo->prepare("SELECT * FROM award_recommendations ORDER BY status ASC, submitted_at DESC");
            $stmt->execute();
            $award_recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            $award_recommendations = [];
        }
        
        // Get department summary data
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM roster");
            $summary['total_crew'] = $stmt->fetch()['count'] ?? 0;
        } catch (Exception $e) {
            $summary['total_crew'] = 0;
        }
    }
    
} catch (Exception $e) {
    $error = "Database connection error. Please try again later.";
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Command</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
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
				<div class="panel-2">CMD<span class="hop">-CTR</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">COMMAND &#149; CONTROL</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', 'training_modules.php')">TRAINING</button>
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
					<div class="panel-3">BRIDGE<span class="hop">-CTRL</span></div>
					<div class="panel-4">READY<span class="hop">-ROOM</span></div>
					<div class="panel-5">CAPT<span class="hop">-CABIN</span></div>
					<div class="panel-6">EXEC<span class="hop">-OFF</span></div>
					<div class="panel-7">STRAT<span class="hop">-OPS</span></div>
					<div class="panel-8">BRIEF<span class="hop">-ROOM</span></div>
					<div class="panel-9">CMD<span class="hop">-DECK</span></div>
				</div>
				<div>
					<div class="panel-10">EXEC<span class="hop">-CTRL</span></div>
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
					<h1>Command & Control</h1>
					<h2>USS-Serenity Strategic Operations</h2>
					
					<?php if (isset($success) && $success): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error) && $error): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<h3>Command Structure</h3>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php if (!empty($command_officers)): ?>
							<?php foreach ($command_officers as $officer): ?>
							<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--red);">
								<h4><?php echo htmlspecialchars($officer['rank'] . ' ' . $officer['first_name'] . ' ' . $officer['last_name']); ?></h4>
								<p><?php echo htmlspecialchars($officer['position']); ?></p>
							</div>
							<?php endforeach; ?>
						<?php else: ?>
							<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--red);">
								<p>No command officers found in database.</p>
							</div>
						<?php endif; ?>
					</div>
					
					<?php if (hasPermission('Command')): ?>
					<!-- Command Dashboard -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--red);">
						<h3>Command Dashboard</h3>
						<p style="color: var(--red);"><em>Command Staff Access Only</em></p>
						
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
							<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
								<h4 style="color: var(--red);">Total Crew</h4>
								<div style="font-size: 2rem; color: var(--red);"><?php echo $summary['total_crew']; ?></div>
								<small>Personnel</small>
							</div>
						</div>
						
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
							<div>
								<h4>Quick Actions</h4>
								<div style="display: flex; flex-direction: column; gap: 0.5rem;">
									<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Ship's Roster</button>
									<?php 
									$roster_dept = $_SESSION['roster_department'] ?? '';
									if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor' || canEditPersonnelFiles()): ?>
									<button onclick="playSoundAndRedirect('audio2', 'personnel_edit.php')" style="background-color: var(--blue); color: black; border: none; padding: 0.75rem; border-radius: 5px;">👥 Personnel Editor</button>
									<?php endif; ?>
									<button onclick="playSoundAndRedirect('audio2', 'awards_management.php')" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem; border-radius: 5px;">🏅 Awards Management</button>
									<button onclick="playSoundAndRedirect('audio2', 'admin_management.php')" style="background-color: var(--orange); color: black; border: none; padding: 0.75rem; border-radius: 5px;">⚠️ Admin Management</button>
									<button onclick="playSoundAndRedirect('audio2', 'training_modules.php')" style="background-color: var(--green); color: black; border: none; padding: 0.75rem; border-radius: 5px;">🎓 Training Modules</button>
									<?php if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor'): ?>
									<button onclick="playSoundAndRedirect('audio2', 'auditor_activity_log.php')" style="background-color: var(--purple); color: white; border: none; padding: 0.75rem; border-radius: 5px;">🔍 Auditor Activity Log</button>
									<?php endif; ?>
								</div>
							</div>
							<div>
								<h4>Mission Status</h4>
								<ul style="color: var(--red); list-style: none; padding: 0;">
									<li style="margin: 0.5rem 0;">→ Deep Space Exploration</li>
									<li style="margin: 0.5rem 0;">→ All Systems Nominal</li>
									<li style="margin: 0.5rem 0;">→ Crew Status: Green</li>
									<li style="margin: 0.5rem 0;">→ Current Stardate: 101825.4</li>
								</ul>
							</div>
						</div>
					</div>
					
					<!-- Captain-Only Character Auditor Management -->
					<?php if (hasPermission('Captain')): ?>
					<div style="background: rgba(255, 140, 0, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--orange);">
						<h3 style="color: var(--orange); text-align: center;">🛡️ Captain's Special Operations</h3>
						<p style="color: var(--orange); text-align: center; margin-bottom: 2rem;">
							<em>Captain Access Only - Character-Based OOC Administration</em>
						</p>
						
						<div style="display: flex; justify-content: center; gap: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'character_auditor_management.php')" style="background-color: var(--orange); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; font-weight: bold;">
								🛡️ Character Auditor Management
							</button>
						</div>
						
						<div style="margin-top: 1.5rem; text-align: center;">
							<small style="color: var(--orange);">
								Assign Starfleet Auditor status to specific characters.<br>
								Character-based system for OOC moderation without roleplay disruption.
							</small>
						</div>
					</div>
					<?php endif; ?>
					
					<!-- Department Training Section -->
					<?php renderDepartmentTrainingSection('Command', 'Command'); ?>
					<?php endif; ?>
					
					<!-- Public Suggestion Form -->
					<?php if (isLoggedIn()): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--red); margin: 2rem 0;">
						<h3 style="color: var(--red); text-align: center;">Submit Suggestion to Command</h3>
						<form method="POST" action="">
							<input type="hidden" name="action" value="submit_suggestion">
							<div style="margin-bottom: 1rem;">
								<label for="suggestion_title" style="color: var(--red); display: block; margin-bottom: 0.5rem;">Suggestion Title:</label>
								<input type="text" name="suggestion_title" id="suggestion_title" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--red); border-radius: 5px;">
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="suggestion_description" style="color: var(--red); display: block; margin-bottom: 0.5rem;">Description:</label>
								<textarea name="suggestion_description" id="suggestion_description" rows="4" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--red); border-radius: 5px;"></textarea>
							</div>
							<button type="submit" style="background-color: var(--red); color: black; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; width: 100%; font-weight: bold;">Submit Suggestion</button>
						</form>
					</div>
					
					<!-- Award Recommendation Form -->
					<div style="background: rgba(255, 215, 0, 0.1); padding: 2rem; border-radius: 15px; border: 2px solid var(--gold); margin: 2rem 0;">
						<h3 style="color: var(--gold); text-align: center;">🏅 Recommend Award</h3>
						<p style="color: var(--gold); text-align: center; margin-bottom: 1.5rem; font-style: italic;">
							Notice exceptional service? Recommend a crew member for an award!
						</p>
						<form method="POST" action="">
							<input type="hidden" name="action" value="submit_award_recommendation">
							<div style="margin-bottom: 1rem;">
								<label for="recommended_person" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Crew Member to Recommend:</label>
								<select name="recommended_person" id="recommended_person" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;">
									<option value="">-- Select a Crew Member --</option>
									<?php if (!empty($roster_members)): ?>
										<?php foreach ($roster_members as $member): ?>
											<option value="<?php echo htmlspecialchars(($member['rank'] ?? '') . ' ' . ($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')); ?>">
												<?php echo htmlspecialchars(($member['rank'] ?? '') . ' ' . ($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '') . ' - ' . ($member['department'] ?? '') . ($member['position'] ? ' (' . $member['position'] . ')' : '')); ?>
											</option>
										<?php endforeach; ?>
									<?php else: ?>
										<option value="" disabled>No crew members found in roster</option>
									<?php endif; ?>
								</select>
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="recommended_award" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Suggested Award:</label>
								<select name="recommended_award" id="recommended_award" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;">
									<option value="">-- Select an Award --</option>
									<?php if (!empty($available_awards)): ?>
										<?php 
										$current_category = '';
										foreach ($available_awards as $award): 
											$award_category = ($award['type'] ?? 'Medal') . ($award['specialization'] ? ' - ' . $award['specialization'] : '');
											if ($award_category !== $current_category) {
												if ($current_category !== '') echo '</optgroup>';
												echo '<optgroup label="' . htmlspecialchars($award_category) . '">';
												$current_category = $award_category;
											}
										?>
											<option value="<?php echo htmlspecialchars($award['name'] ?? ''); ?>" title="<?php echo htmlspecialchars($award['description'] ?? ''); ?>">
												<?php echo htmlspecialchars($award['name'] ?? ''); ?>
											</option>
										<?php endforeach; ?>
										<?php if ($current_category !== '') echo '</optgroup>'; ?>
									<?php else: ?>
										<option value="" disabled>No awards found in database</option>
									<?php endif; ?>
								</select>
								<small style="color: var(--orange); font-size: 0.8rem; display: block; margin-top: 0.5rem;">
									Awards are grouped by type and specialization. Hover over options to see descriptions.
								</small>
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="justification" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Justification:</label>
								<textarea name="justification" id="justification" rows="4" required placeholder="Explain why this crew member deserves this award..." style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;"></textarea>
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="recommended_by" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Recommended By:</label>
								<?php 
								$current_user = trim(
									($_SESSION['rank'] ?? '') . ' ' . 
									($_SESSION['first_name'] ?? '') . ' ' . 
									($_SESSION['last_name'] ?? '')
								);
								?>
								<input type="text" value="<?php echo htmlspecialchars($current_user); ?>" readonly style="width: 100%; padding: 0.5rem; background: #333; color: var(--gold); border: 2px solid var(--gold); border-radius: 5px; cursor: not-allowed;">
								<small style="color: var(--gold); font-size: 0.8rem;">Auto-filled from your current character profile</small>
							</div>
							<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; width: 100%; font-weight: bold;">🏅 Submit Recommendation</button>
						</form>
					</div>
					<?php else: ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--red); margin: 2rem 0;">
						<h4>Submit Suggestion to Command</h4>
						<p style="color: var(--red); text-align: center;">You must be logged in to submit suggestions to Command.</p>
						<div style="text-align: center; margin-top: 1rem;">
							<a href="../index.php" style="background-color: var(--red); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">Return to Login</a>
						</div>
					</div>
					<?php endif; ?>
					
					<!-- Award Recommendations Management (Command Staff Only) -->
					<?php if (hasPermission('Command')): ?>
					<div style="background: rgba(255, 215, 0, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
						<h3 style="color: var(--gold); text-align: center;">🏅 Award Recommendations Management</h3>
						<p style="color: var(--gold); text-align: center; margin-bottom: 1.5rem; font-style: italic;">
							Command Staff Access Only - Review and process award recommendations
						</p>
						
						<div style="max-height: 400px; overflow-y: auto;">
							<?php if (!empty($award_recommendations)): ?>
								<?php foreach ($award_recommendations as $recommendation): ?>
								<div style="background: rgba(255, 215, 0, 0.1); padding: 1.5rem; margin: 1rem 0; border-radius: 10px; border: 1px solid var(--gold);">
									<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
										<div>
											<h4 style="color: var(--gold); margin: 0 0 1rem 0;">
												Recommend: <?php echo htmlspecialchars($recommendation['recommended_person'] ?? 'Unknown'); ?>
											</h4>
											<p style="margin: 0.5rem 0; color: white;">
												<strong style="color: var(--gold);">Award:</strong> <?php echo htmlspecialchars($recommendation['recommended_award'] ?? 'Unknown Award'); ?>
											</p>
											<p style="margin: 0.5rem 0; color: white;">
												<strong style="color: var(--gold);">Justification:</strong><br>
												<?php echo htmlspecialchars($recommendation['justification'] ?? 'No justification provided'); ?>
											</p>
											<div style="margin-top: 1rem;">
												<small style="color: var(--orange);">
													Recommended by: <?php echo htmlspecialchars($recommendation['submitted_by'] ?? 'Unknown'); ?> 
													on <?php echo htmlspecialchars($recommendation['submitted_at'] ?? 'Unknown date'); ?>
												</small><br>
												<small style="color: var(--gold);">
													Status: <strong><?php echo htmlspecialchars($recommendation['status'] ?? 'Pending'); ?></strong>
													<?php if (!empty($recommendation['reviewed_by'])): ?>
														| Reviewed by: <?php echo htmlspecialchars($recommendation['reviewed_by']); ?>
													<?php endif; ?>
												</small>
												<?php if (!empty($recommendation['review_notes'])): ?>
													<br><small style="color: var(--blue);">
														Review Notes: <?php echo htmlspecialchars($recommendation['review_notes']); ?>
													</small>
												<?php endif; ?>
											</div>
										</div>
										<div>
											<form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
												<input type="hidden" name="action" value="update_award_recommendation">
												<input type="hidden" name="recommendation_id" value="<?php echo htmlspecialchars($recommendation['id'] ?? ''); ?>">
												
												<div>
													<label style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Status:</label>
													<select name="status" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;">
														<option value="Pending" <?php echo ($recommendation['status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
														<option value="Approved" <?php echo ($recommendation['status'] ?? '') === 'Approved' ? 'selected' : ''; ?>>Approved</option>
														<option value="Needs More Work" <?php echo ($recommendation['status'] ?? '') === 'Needs More Work' ? 'selected' : ''; ?>>Needs More Work</option>
														<option value="Rejected" <?php echo ($recommendation['status'] ?? '') === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
													</select>
												</div>
												
												<div>
													<label style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Review Notes:</label>
													<textarea name="review_notes" placeholder="Add review comments..." style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px; resize: vertical; min-height: 80px;"><?php echo htmlspecialchars($recommendation['review_notes'] ?? ''); ?></textarea>
												</div>
												
												<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem; border-radius: 5px; font-weight: bold;">
													Update Review
												</button>
											</form>
											
											<!-- Delete Button for Command/Starfleet Auditor -->
											<?php 
												$roster_dept = $_SESSION['roster_department'] ?? '';
												if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor'): 
											?>
											<form method="POST" action="" style="margin-top: 1rem;" onsubmit="return confirm('Are you sure you want to delete this award recommendation? This action cannot be undone.');">
												<input type="hidden" name="action" value="delete_award_recommendation">
												<input type="hidden" name="recommendation_id" value="<?php echo htmlspecialchars($recommendation['id'] ?? ''); ?>">
												<button type="submit" style="background-color: #ff3366; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; width: 100%;">
													🗑️ Delete Recommendation
												</button>
											</form>
											<?php endif; ?>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<div style="text-align: center; padding: 2rem;">
									<p style="color: var(--gold); font-size: 1.1rem;">No award recommendations have been submitted yet.</p>
									<small style="color: var(--orange);">Award recommendations will appear here for command staff review.</small>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<div style="background: rgba(204, 68, 68, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0;">
						<h4>Command Information</h4>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
							<div>
								<strong style="color: var(--red);">Bridge:</strong> Deck 1<br>
								<strong style="color: var(--red);">Ready Room:</strong> Deck 1<br>
								<strong style="color: var(--red);">Conference Room:</strong> Deck 1
							</div>
							<div>
								<strong style="color: var(--red);">Captain's Quarters:</strong> Deck 3<br>
								<strong style="color: var(--red);">Senior Staff:</strong> Deck 2-3
							</div>
							<div>
								<strong style="color: var(--red);">Current Mission:</strong> Exploration<br>
								<strong style="color: var(--red);">ETA:</strong> Waiting for a response sorry...<br>
								<strong style="color: var(--red);">Status:</strong> All Green
							</div>
						</div>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					LCARS Inspired Website Template by <a href="https://www.thelcars.com">www.TheLCARS.com</a>.
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
