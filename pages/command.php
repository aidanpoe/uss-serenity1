<?php
require_once '../includes/config.php';
require_once '../includes/department_training.php';

// Update last active timestamp for current character
updateLastActive();

// Handle department training if user has permission
if (hasPermission('Command')) {
    handleDepartmentTraining('Command');
}

// Handle suggestion submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_suggestion') {
    if (!isLoggedIn()) {
        $error = "You must be logged in to submit suggestions.";
    } else {
        try {
            // Auto-populate submitted_by with current user's character
            $submitted_by = ($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            $submitted_by = trim($submitted_by); // Remove extra spaces
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO command_suggestions (suggestion_title, suggestion_description, submitted_by) VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['suggestion_title'],
                $_POST['suggestion_description'],
                $submitted_by
            ]);
            $success = "Suggestion submitted successfully.";
        } catch (Exception $e) {
            $error = "Error submitting suggestion: " . $e->getMessage();
        }
    }
}

// Handle award recommendation submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_award_recommendation') {
    if (!isLoggedIn()) {
        $error = "You must be logged in to submit award recommendations.";
    } else {
        try {
            $submitted_by = ($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            $submitted_by = trim($submitted_by);
            
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
                $_POST['recommended_person'],
                $_POST['recommended_award'],
                $_POST['justification'],
                $submitted_by
            ]);
            $success = "Award recommendation submitted successfully and is pending command review.";
        } catch (Exception $e) {
            $error = "Error submitting award recommendation: " . $e->getMessage();
        }
    }
}

// Handle award recommendation update (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_award_recommendation') {
    if (hasPermission('Command')) {
        try {
            $pdo = getConnection();
            
            $reviewed_by = ($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            $reviewed_by = trim($reviewed_by);
            
            $stmt = $pdo->prepare("UPDATE award_recommendations SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([
                $_POST['status'],
                $_POST['review_notes'],
                $reviewed_by,
                $_POST['recommendation_id']
            ]);
            $success = "Award recommendation updated successfully.";
        } catch (Exception $e) {
            $error = "Error updating award recommendation: " . $e->getMessage();
        }
    }
}

// Handle suggestion update (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_suggestion') {
    if (hasPermission('Command')) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE command_suggestions SET status = ?, response = ? WHERE id = ?");
            $stmt->execute([
                $_POST['status'],
                $_POST['response'],
                $_POST['suggestion_id']
            ]);
            $success = "Suggestion updated successfully.";
        } catch (Exception $e) {
            $error = "Error updating suggestion: " . $e->getMessage();
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get command structure
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer') ORDER BY FIELD(position, 'Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer')");
    $stmt->execute();
    $command_officers = $stmt->fetchAll();
    
    // Get suggestions for backend
    if (hasPermission('Command')) {
        $stmt = $pdo->prepare("SELECT * FROM command_suggestions ORDER BY status ASC, created_at DESC");
        $stmt->execute();
        $suggestions = $stmt->fetchAll();
        
        // Get award recommendations for backend
        try {
            $stmt = $pdo->prepare("SELECT * FROM award_recommendations ORDER BY status ASC, submitted_at DESC");
            $stmt->execute();
            $award_recommendations = $stmt->fetchAll();
        } catch (Exception $e) {
            // If table doesn't exist, create it and initialize empty array
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
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
                $award_recommendations = [];
            } else {
                $award_recommendations = [];
                error_log("Award recommendations query error: " . $e->getMessage());
            }
        }
        
        // Get department summary data
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM medical_records WHERE status != 'Resolved') as open_medical,
                (SELECT COUNT(*) FROM fault_reports WHERE status != 'Resolved') as open_faults,
                (SELECT COUNT(*) FROM security_reports WHERE status != 'Resolved') as open_security,
                (SELECT COUNT(*) FROM roster) as total_crew
        ");
        $stmt->execute();
        $summary = $stmt->fetch();
    } else {
        // Initialize empty arrays for non-command users
        $suggestions = [];
        $award_recommendations = [];
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $suggestions = [];
    $award_recommendations = [];
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
				<div class="panel-2">CMD<span class="hop">-CTRL</span></div>
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
					<h1>Command Center</h1>
					<h2>USS-Serenity Strategic Operations</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<h3>Command Structure</h3>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php foreach ($command_officers as $officer): ?>
						<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--red);">
							<h4><?php echo htmlspecialchars($officer['rank'] . ' ' . $officer['first_name'] . ' ' . $officer['last_name']); ?></h4>
							<p><?php echo htmlspecialchars($officer['position']); ?></p>
						</div>
						<?php endforeach; ?>
					</div>
					
					<?php if (hasPermission('Command')): ?>
					<!-- Command Dashboard -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--red);">
						<h3>Command Dashboard</h3>
						<p style="color: var(--red);"><em>Command Staff Access Only</em></p>
						
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
							<div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
								<h4 style="color: var(--blue);">Medical Issues</h4>
								<div style="font-size: 2rem; color: var(--blue);"><?php echo $summary['open_medical']; ?></div>
								<small>Open Cases</small>
							</div>
							<div style="background: rgba(255, 136, 0, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
								<h4 style="color: var(--orange);">Engineering Faults</h4>
								<div style="font-size: 2rem; color: var(--orange);"><?php echo $summary['open_faults']; ?></div>
								<small>Open Reports</small>
							</div>
							<div style="background: rgba(255, 170, 0, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
								<h4 style="color: var(--gold);">Security Incidents</h4>
								<div style="font-size: 2rem; color: var(--gold);"><?php echo $summary['open_security']; ?></div>
								<small>Open Reports</small>
							</div>
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
									<button onclick="playSoundAndRedirect('audio2', 'med_sci.php')" style="background-color: var(--blue); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Medical Department</button>
									<button onclick="playSoundAndRedirect('audio2', 'eng_ops.php')" style="background-color: var(--orange); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Engineering Department</button>
									<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Security Department</button>
									<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Ship's Roster</button>
									<?php if (canEditPersonnelFiles()): ?>
									<button onclick="playSoundAndRedirect('audio2', 'personnel_edit.php')" style="background-color: var(--bluey); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Personnel File Editor</button>
									<?php endif; ?>
									<?php if (hasPermission('Captain') || hasPermission('Command')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'training_modules.php')" style="background-color: var(--green); color: black; border: none; padding: 0.75rem; border-radius: 5px;">🎓 Training Modules</button>
									<button onclick="playSoundAndRedirect('audio2', 'training_removal.php')" style="background-color: var(--purple); color: white; border: none; padding: 0.75rem; border-radius: 5px;">🗑️ Remove Training</button>
									<button onclick="playSoundAndRedirect('audio2', 'awards_management.php')" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem; border-radius: 5px;">🏅 Awards Management</button>
									<button onclick="playSoundAndRedirect('audio2', 'admin_management.php')" style="background-color: var(--red); color: white; border: none; padding: 0.75rem; border-radius: 5px;">⚠️ Admin Management</button>
									<?php endif; ?>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" style="background-color: var(--bluey); color: black; border: none; padding: 0.75rem; border-radius: 5px;">Command Structure Editor</button>
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
						<?php if (isLoggedIn()): ?>
						<form method="POST" action="">
							<input type="hidden" name="action" value="submit_award_recommendation">
							<div style="margin-bottom: 1rem;">
								<label for="recommended_person" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Crew Member to Recommend:</label>
								<input type="text" name="recommended_person" id="recommended_person" required placeholder="e.g., Lieutenant Commander Jane Doe" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;">
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="recommended_award" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Suggested Award:</label>
								<input type="text" name="recommended_award" id="recommended_award" required placeholder="e.g., Starfleet Commendation Medal" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;">
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="justification" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Justification:</label>
								<textarea name="justification" id="justification" rows="4" required placeholder="Explain why this crew member deserves this award..." style="width: 100%; padding: 0.5rem; background: black; color: white; border: 2px solid var(--gold); border-radius: 5px;"></textarea>
							</div>
							<div style="margin-bottom: 1rem;">
								<label for="recommended_by" style="color: var(--gold); display: block; margin-bottom: 0.5rem;">Recommended By:</label>
								<?php 
								$current_user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
								?>
								<input type="text" value="<?php echo htmlspecialchars($current_user); ?>" readonly style="width: 100%; padding: 0.5rem; background: #333; color: var(--gold); border: 2px solid var(--gold); border-radius: 5px; cursor: not-allowed;">
								<small style="color: var(--gold); font-size: 0.8rem;">Auto-filled from your current character profile</small>
							</div>
							<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; width: 100%; font-weight: bold;">🏅 Submit Recommendation</button>
						</form>
						<?php else: ?>
						<div style="text-align: center; padding: 2rem;">
							<p style="color: var(--gold); font-size: 1.1rem; margin-bottom: 1rem;">
								You must be logged in to submit award recommendations.
							</p>
							<a href="../index.php" style="background-color: var(--gold); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
								Return to Login
							</a>
						</div>
						<?php endif; ?>
					</div>
					
					<?php if (!isset($_SESSION['user_id'])): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--red); margin: 2rem 0;">
						<h4>Submit Suggestion to Command</h4>
						<p style="color: var(--red); text-align: center;">You must be logged in to submit suggestions to Command.</p>
						<div style="text-align: center; margin-top: 1rem;">
							<a href="../index.php" style="background-color: var(--red); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">Return to Login</a>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('Command')): ?>
					<!-- Suggestion Management -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--red);">
						<h4>Crew Suggestions Management</h4>
						<div style="max-height: 600px; overflow-y: auto; border: 1px solid var(--red); border-radius: 5px; padding: 1rem; margin: 1rem 0; background: rgba(0,0,0,0.3);">
							<?php foreach ($suggestions as $suggestion): ?>
							<div style="border-bottom: 1px solid var(--gray); padding: 1rem 0; <?php echo $suggestion['status'] === 'Implemented' || $suggestion['status'] === 'Rejected' ? 'opacity: 0.7;' : ''; ?>">
								<div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
									<div>
										<strong>Suggestion #<?php echo $suggestion['id']; ?></strong><br>
										<h5><?php echo htmlspecialchars($suggestion['suggestion_title']); ?></h5>
										<small>Submitted: <?php echo date('Y-m-d H:i', strtotime($suggestion['created_at'])); ?></small><br>
										<small>By: <?php echo htmlspecialchars($suggestion['submitted_by']); ?></small>
									</div>
									<div>
										<strong>Description:</strong><br>
										<?php echo htmlspecialchars($suggestion['suggestion_description']); ?>
										<?php if ($suggestion['response']): ?>
										<br><br><strong>Command Response:</strong><br>
										<?php echo htmlspecialchars($suggestion['response']); ?>
										<?php endif; ?>
									</div>
									<div>
										<form method="POST" action="">
											<input type="hidden" name="action" value="update_suggestion">
											<input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
											<select name="status" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--red); margin-bottom: 0.5rem;">
												<option value="Open" <?php echo $suggestion['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
												<option value="Under Review" <?php echo $suggestion['status'] === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
												<option value="Implemented" <?php echo $suggestion['status'] === 'Implemented' ? 'selected' : ''; ?>>Implemented</option>
												<option value="Rejected" <?php echo $suggestion['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
											</select>
											<textarea name="response" placeholder="Command response..." rows="3" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--red); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($suggestion['response'] ?? ''); ?></textarea>
											<button type="submit" style="background-color: var(--red); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%;">Update</button>
										</form>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('Command')): ?>
					<!-- Award Recommendations Management -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
						<h4>🏅 Award Recommendations Management</h4>
						<div style="max-height: 600px; overflow-y: auto; border: 1px solid var(--gold); border-radius: 5px; padding: 1rem; margin: 1rem 0; background: rgba(0,0,0,0.3);">
							<?php if (empty($award_recommendations)): ?>
								<p style="color: var(--gold); text-align: center; margin: 2rem 0;">
									No award recommendations submitted yet.
								</p>
							<?php else: ?>
								<?php foreach ($award_recommendations as $recommendation): ?>
								<div style="border-bottom: 1px solid var(--gray); padding: 1rem 0; <?php echo $recommendation['status'] === 'Approved' || $recommendation['status'] === 'Rejected' ? 'opacity: 0.7;' : ''; ?>">
									<div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
										<div>
											<strong style="color: var(--gold);">Recommendation #<?php echo $recommendation['id']; ?></strong><br>
											<h5 style="color: var(--gold);"><?php echo htmlspecialchars($recommendation['recommended_person']); ?></h5>
											<small>For: <strong><?php echo htmlspecialchars($recommendation['recommended_award']); ?></strong></small><br>
											<small>Submitted: <?php echo date('Y-m-d H:i', strtotime($recommendation['submitted_at'])); ?></small><br>
											<small>By: <?php echo htmlspecialchars($recommendation['submitted_by']); ?></small>
										</div>
										<div>
											<strong style="color: var(--gold);">Justification:</strong><br>
											<div style="color: var(--bluey); line-height: 1.4; margin-bottom: 1rem;">
												<?php echo nl2br(htmlspecialchars($recommendation['justification'])); ?>
											</div>
											<?php if ($recommendation['review_notes']): ?>
											<strong style="color: var(--orange);">Command Review:</strong><br>
											<div style="color: var(--orange); font-style: italic;">
												<?php echo nl2br(htmlspecialchars($recommendation['review_notes'])); ?>
											</div>
											<?php endif; ?>
										</div>
										<div>
											<strong>Status:</strong> 
											<span style="color: <?php 
												echo $recommendation['status'] === 'Approved' ? 'var(--green)' : 
													($recommendation['status'] === 'Rejected' ? 'var(--red)' : 'var(--gold)'); 
											?>">
												<?php echo $recommendation['status']; ?>
											</span><br>
											<?php if ($recommendation['status'] === 'Pending'): ?>
											<form method="POST" action="" style="margin-top: 1rem;">
												<input type="hidden" name="action" value="update_award_recommendation">
												<input type="hidden" name="recommendation_id" value="<?php echo $recommendation['id']; ?>">
												<select name="status" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--gold); margin-bottom: 0.5rem;">
													<option value="Pending">Pending</option>
													<option value="Approved">Approve</option>
													<option value="Rejected">Reject</option>
												</select>
												<textarea name="review_notes" placeholder="Command review notes..." rows="3" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--gold); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($recommendation['review_notes'] ?? ''); ?></textarea>
												<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%;">Update</button>
											</form>
											<?php endif; ?>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('Command')): ?>
					<!-- Department Training Section -->
					<?php renderDepartmentTrainingSection('Command', 'Command'); ?>
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
								<strong style="color: var(--red);">Captain's Cabin:</strong> Deck 2<br>
								<strong style="color: var(--red);">Senior Staff:</strong> Deck 2-3<br>
								<strong style="color: var(--red);">Strategic Ops:</strong> Deck 8
							</div>
							<div>
								<strong style="color: var(--red);">Current Mission:</strong> Exploration<br>
								<strong style="color: var(--red);">ETA:</strong> Starbase 47: 3 Days<br>
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
