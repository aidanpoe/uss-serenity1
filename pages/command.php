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
									<button onclick="playSoundAndRedirect('audio2', 'awards_management.php')" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem; border-radius: 5px;">🏅 Awards Management</button>
									<button onclick="playSoundAndRedirect('audio2', 'admin_management.php')" style="background-color: var(--orange); color: black; border: none; padding: 0.75rem; border-radius: 5px;">⚠️ Admin Management</button>
									<button onclick="playSoundAndRedirect('audio2', 'training_modules.php')" style="background-color: var(--green); color: black; border: none; padding: 0.75rem; border-radius: 5px;">🎓 Training Modules</button>
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
