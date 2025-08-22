<?php
// Command.php - Comprehensive error handling version
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize all variables first to prevent undefined variable errors
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

try {
    // Start session
    session_start();
    
    // Include required files
    require_once '../includes/config.php';
    require_once '../includes/department_training.php';
    
    // Update last active timestamp for current character
    if (function_exists('updateLastActive')) {
        updateLastActive();
    }
    
    // Handle department training if user has permission
    if (function_exists('hasPermission') && hasPermission('Command')) {
        if (function_exists('handleDepartmentTraining')) {
            handleDepartmentTraining('Command');
        }
    }
    
    // Handle form submissions with comprehensive error checking
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        // Handle suggestion submission
        if ($_POST['action'] === 'submit_suggestion') {
            if (!function_exists('isLoggedIn') || !isLoggedIn()) {
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
            if (!function_exists('isLoggedIn') || !isLoggedIn()) {
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
        
        // Handle award recommendation update (backend only)
        if ($_POST['action'] === 'update_award_recommendation') {
            if (function_exists('hasPermission') && hasPermission('Command')) {
                try {
                    $pdo = getConnection();
                    
                    $reviewed_by = trim(
                        ($_SESSION['rank'] ?? '') . ' ' . 
                        ($_SESSION['first_name'] ?? '') . ' ' . 
                        ($_SESSION['last_name'] ?? '')
                    );
                    
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
        
        // Handle suggestion update (backend only)
        if ($_POST['action'] === 'update_suggestion') {
            if (function_exists('hasPermission') && hasPermission('Command')) {
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->prepare("UPDATE command_suggestions SET status = ?, response = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['status'] ?? '',
                        $_POST['response'] ?? '',
                        $_POST['suggestion_id'] ?? 0
                    ]);
                    $success = "Suggestion updated successfully.";
                } catch (Exception $e) {
                    $error = "Error updating suggestion: " . $e->getMessage();
                }
            }
        }
    }
    
    // Database queries with comprehensive error handling
    try {
        $pdo = getConnection();
        
        // Get command structure with error handling
        try {
            $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer') ORDER BY FIELD(position, 'Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer')");
            $stmt->execute();
            $command_officers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            $command_officers = [];
            error_log("Command officers query error: " . $e->getMessage());
        }
        
        // Get data for command users only
        if (function_exists('hasPermission') && hasPermission('Command')) {
            
            // Get suggestions
            try {
                $stmt = $pdo->prepare("SELECT * FROM command_suggestions ORDER BY status ASC, submission_date DESC");
                $stmt->execute();
                $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $suggestions = [];
                error_log("Suggestions query error: " . $e->getMessage());
            }
            
            // Get award recommendations
            try {
                $stmt = $pdo->prepare("SELECT * FROM award_recommendations ORDER BY status ASC, submitted_at DESC");
                $stmt->execute();
                $award_recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $e) {
                $award_recommendations = [];
                error_log("Award recommendations query error: " . $e->getMessage());
            }
            
            // Get department summary data
            try {
                // First check if tables exist
                $tables = ['medical_records', 'fault_reports', 'security_reports'];
                $summary_data = ['open_medical' => 0, 'open_faults' => 0, 'open_security' => 0, 'total_crew' => 0];
                
                // Check each table individually
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_records WHERE status != 'Resolved'");
                    $summary_data['open_medical'] = $stmt->fetch()['count'] ?? 0;
                } catch (Exception $e) {
                    $summary_data['open_medical'] = 0;
                }
                
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fault_reports WHERE status != 'Resolved'");
                    $summary_data['open_faults'] = $stmt->fetch()['count'] ?? 0;
                } catch (Exception $e) {
                    $summary_data['open_faults'] = 0;
                }
                
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM security_reports WHERE status != 'Resolved'");
                    $summary_data['open_security'] = $stmt->fetch()['count'] ?? 0;
                } catch (Exception $e) {
                    $summary_data['open_security'] = 0;
                }
                
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM roster");
                    $summary_data['total_crew'] = $stmt->fetch()['count'] ?? 0;
                } catch (Exception $e) {
                    $summary_data['total_crew'] = 0;
                }
                
                $summary = $summary_data;
                
            } catch (Exception $e) {
                error_log("Summary query error: " . $e->getMessage());
                $summary = ['open_medical' => 0, 'open_faults' => 0, 'open_security' => 0, 'total_crew' => 0];
            }
        }
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        $error = "Database connection error. Please try again later.";
    }
    
} catch (Exception $e) {
    error_log("Fatal error in command.php: " . $e->getMessage());
    $error = "System error occurred. Please contact an administrator.";
}

// Define helper functions if they don't exist
if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        return false;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('canEditPersonnelFiles')) {
    function canEditPersonnelFiles() {
        return hasPermission('admin') || hasPermission('Captain');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Command Center - USS Serenity</title>
	<link rel="stylesheet" href="../assets/lower-decks.css">
	<script src="../assets/lcars.js"></script>
</head>
<body class="bg">
<section>
	<div class="wrap">
		<div class="left-frame-top">
			<div class="panel-1">01<span class="hop">-111A</span></div>
			<div class="panel-2">02<span class="hop">-222A</span></div>
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
				
				<?php if (!empty($success)): ?>
				<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
					<p style="color: var(--red);"><?php echo htmlspecialchars($success); ?></p>
				</div>
				<?php endif; ?>
				
				<?php if (!empty($error)): ?>
				<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
					<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
				</div>
				<?php endif; ?>
				
				<h3>Command Structure</h3>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
					<?php if (!empty($command_officers)): ?>
						<?php foreach ($command_officers as $officer): ?>
						<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--red);">
							<h4><?php echo htmlspecialchars(($officer['rank'] ?? '') . ' ' . ($officer['first_name'] ?? '') . ' ' . ($officer['last_name'] ?? '')); ?></h4>
							<p><?php echo htmlspecialchars($officer['position'] ?? 'Unknown Position'); ?></p>
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
						<div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
							<h4 style="color: var(--blue);">Medical Issues</h4>
							<div style="font-size: 2rem; color: var(--blue);"><?php echo $summary['open_medical'] ?? 0; ?></div>
							<small>Open Cases</small>
						</div>
						<div style="background: rgba(255, 136, 0, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
							<h4 style="color: var(--orange);">Engineering Faults</h4>
							<div style="font-size: 2rem; color: var(--orange);"><?php echo $summary['open_faults'] ?? 0; ?></div>
							<small>Open Reports</small>
						</div>
						<div style="background: rgba(255, 170, 0, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
							<h4 style="color: var(--gold);">Security Incidents</h4>
							<div style="font-size: 2rem; color: var(--gold);"><?php echo $summary['open_security'] ?? 0; ?></div>
							<small>Open Reports</small>
						</div>
						<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; text-align: center;">
							<h4 style="color: var(--red);">Total Crew</h4>
							<div style="font-size: 2rem; color: var(--red);"><?php echo $summary['total_crew'] ?? 0; ?></div>
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
				
				<!-- Department Training Section -->
				<?php 
				if (function_exists('renderDepartmentTrainingSection')) {
					renderDepartmentTrainingSection('Command', 'Command');
				}
				?>
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
				
				<div style="background: rgba(255, 215, 0, 0.1); padding: 2rem; border-radius: 15px; border: 2px solid var(--gold); margin: 2rem 0;">
					<h3 style="color: var(--gold); text-align: center;">🏅 Recommend Award</h3>
					<div style="text-align: center; padding: 2rem;">
						<p style="color: var(--gold); font-size: 1.1rem; margin-bottom: 1rem;">
							You must be logged in to submit award recommendations.
						</p>
						<a href="../index.php" style="background-color: var(--gold); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
							Return to Login
						</a>
					</div>
				</div>
				<?php endif; ?>
				
				<?php if (function_exists('hasPermission') && hasPermission('Command')): ?>
				<!-- Crew Suggestions Management -->
				<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--red);">
					<h4>Crew Suggestions Management</h4>
					<div style="max-height: 300px; overflow-y: auto;">
						<?php if (!empty($suggestions)): ?>
							<?php foreach ($suggestions as $suggestion): ?>
							<div style="background: rgba(204, 68, 68, 0.1); padding: 1rem; margin: 1rem 0; border-radius: 10px; border: 1px solid var(--red);">
								<div style="display: flex; justify-content: space-between; align-items: flex-start;">
									<div style="flex: 1;">
										<h5 style="color: var(--red); margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($suggestion['suggestion_title'] ?? 'Untitled'); ?></h5>
										<p style="margin: 0.5rem 0; font-size: 0.9rem;"><?php echo htmlspecialchars($suggestion['suggestion_description'] ?? 'No description'); ?></p>
										<small style="color: var(--orange);">
											Submitted by: <?php echo htmlspecialchars($suggestion['submitted_by'] ?? 'Unknown'); ?> 
											on <?php echo htmlspecialchars($suggestion['submission_date'] ?? 'Unknown date'); ?>
										</small><br>
										<small style="color: var(--gold);">Status: <?php echo htmlspecialchars($suggestion['status'] ?? 'pending'); ?></small>
									</div>
									<div style="margin-left: 1rem;">
										<form method="POST" style="display: inline-block;">
											<input type="hidden" name="action" value="update_suggestion">
											<input type="hidden" name="suggestion_id" value="<?php echo htmlspecialchars($suggestion['id'] ?? ''); ?>">
											<select name="status" style="background: black; color: white; border: 1px solid var(--red); padding: 0.25rem;">
												<option value="pending" <?php echo ($suggestion['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
												<option value="reviewed" <?php echo ($suggestion['status'] ?? '') === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
												<option value="implemented" <?php echo ($suggestion['status'] ?? '') === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
											</select>
											<button type="submit" style="background-color: var(--red); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; margin-left: 0.5rem;">Update</button>
										</form>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p style="color: var(--red); text-align: center; padding: 2rem;">No suggestions submitted yet.</p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Award Recommendations Management -->
				<div style="background: rgba(255, 215, 0, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
					<h4>🏅 Award Recommendations Management</h4>
					<div style="max-height: 400px; overflow-y: auto;">
						<?php if (!empty($award_recommendations)): ?>
							<?php foreach ($award_recommendations as $recommendation): ?>
							<div style="background: rgba(255, 215, 0, 0.1); padding: 1rem; margin: 1rem 0; border-radius: 10px; border: 1px solid var(--gold);">
								<div style="display: flex; justify-content: space-between; align-items: flex-start;">
									<div style="flex: 1;">
										<h5 style="color: var(--gold); margin: 0 0 0.5rem 0;">
											Recommend <?php echo htmlspecialchars($recommendation['recommended_person'] ?? 'Unknown'); ?> 
											for <?php echo htmlspecialchars($recommendation['recommended_award'] ?? 'Unknown Award'); ?>
										</h5>
										<p style="margin: 0.5rem 0; font-size: 0.9rem; color: white;">
											<strong>Justification:</strong> <?php echo htmlspecialchars($recommendation['justification'] ?? 'No justification provided'); ?>
										</p>
										<small style="color: var(--orange);">
											Recommended by: <?php echo htmlspecialchars($recommendation['submitted_by'] ?? 'Unknown'); ?> 
											on <?php echo htmlspecialchars($recommendation['submitted_at'] ?? 'Unknown date'); ?>
										</small><br>
										<small style="color: var(--gold);">
											Status: <?php echo htmlspecialchars($recommendation['status'] ?? 'Pending'); ?>
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
									<div style="margin-left: 1rem;">
										<form method="POST" style="display: flex; flex-direction: column; gap: 0.5rem;">
											<input type="hidden" name="action" value="update_award_recommendation">
											<input type="hidden" name="recommendation_id" value="<?php echo htmlspecialchars($recommendation['id'] ?? ''); ?>">
											<select name="status" style="background: black; color: white; border: 1px solid var(--gold); padding: 0.25rem;">
												<option value="Pending" <?php echo ($recommendation['status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
												<option value="Approved" <?php echo ($recommendation['status'] ?? '') === 'Approved' ? 'selected' : ''; ?>>Approved</option>
												<option value="Rejected" <?php echo ($recommendation['status'] ?? '') === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
											</select>
											<textarea name="review_notes" placeholder="Review notes..." style="background: black; color: white; border: 1px solid var(--gold); padding: 0.25rem; resize: vertical; min-height: 60px; width: 200px;"><?php echo htmlspecialchars($recommendation['review_notes'] ?? ''); ?></textarea>
											<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.5rem; border-radius: 3px; font-weight: bold;">Update Review</button>
										</form>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p style="color: var(--gold); text-align: center; padding: 2rem;">No award recommendations submitted yet.</p>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Ship Information -->
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
