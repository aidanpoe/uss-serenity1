<?php
require_once '../includes/config.php';

// Check if user has security department access, command, or is Captain
if (!hasPermission('SEC/TAC') && !hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Get pre-selected crew member if coming from their profile
    $preselected_crew_id = $_GET['crew_id'] ?? '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $roster_id = $_POST['crew_id']; // Form uses crew_id but table uses roster_id
        $incident_type = $_POST['incident_type'];
        $incident_date = $_POST['incident_date'];
        $location = $_POST['location'];
        $description = $_POST['description'];
        $investigation_status = $_POST['investigation_status'];
        $investigating_officer = $_POST['investigating_officer'];
        $evidence_notes = $_POST['evidence_notes'];
        $witnesses = $_POST['witnesses'];
        $punishment_type = $_POST['punishment_type'];
        $punishment_details = $_POST['punishment_details'];
        $classification = $_POST['classification'];
        $created_by = $_SESSION['user_id'];
        
        // Insert the criminal record
        $stmt = $pdo->prepare("
            INSERT INTO criminal_records (
                roster_id, incident_type, incident_date, incident_description,
                investigation_details, evidence_notes, punishment_type, punishment_details,
                investigating_officer, status, classification, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        // Combine description and investigation details
        $investigation_details = "Location: " . $location . "\n";
        if (!empty($witnesses)) {
            $investigation_details .= "Witnesses: " . $witnesses . "\n";
        }
        $investigation_details .= "Status: " . $investigation_status;
        
        if ($stmt->execute([
            $roster_id, $incident_type, $incident_date, $description,
            $investigation_details, $evidence_notes, $punishment_type, $punishment_details,
            $investigating_officer, $investigation_status, $classification
        ])) {
            $message = "Criminal record added successfully!";
            // Clear form data
            $_POST = [];
        } else {
            $error = "Failed to add criminal record.";
        }
    }
    
    // Get crew members for dropdown
    $crew_stmt = $pdo->query("
        SELECT id, first_name, last_name, rank, department 
        FROM roster 
        WHERE status = 'Active' 
        ORDER BY rank, last_name, first_name
    ");
    $crew_members = $crew_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current user info for investigating officer default
    $default_officer = '';
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        $rank = $_SESSION['rank'] ?? '';
        $default_officer = trim($rank . ' ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Add Criminal Record</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.form-container {
			background: black;
			border: 4px solid var(--red);
			border-radius: 15px;
			padding: 2rem;
			margin: 1rem 0;
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 1rem;
			margin-bottom: 1rem;
		}
		.form-group {
			margin-bottom: 1rem;
		}
		.form-group label {
			display: block;
			color: var(--gold);
			font-weight: bold;
			margin-bottom: 0.5rem;
		}
		.form-group input,
		.form-group select,
		.form-group textarea {
			width: 100%;
			padding: 0.5rem;
			background: black;
			color: white;
			border: 1px solid var(--gold);
			border-radius: 5px;
			font-family: inherit;
		}
		.form-group textarea {
			min-height: 100px;
			resize: vertical;
		}
		.submit-btn {
			background: var(--red);
			color: black;
			padding: 0.8rem 2rem;
			border: none;
			border-radius: 5px;
			font-weight: bold;
			cursor: pointer;
			font-size: 1.1rem;
		}
		.submit-btn:hover {
			background: var(--orange);
		}
		.message {
			background: rgba(0, 255, 0, 0.2);
			border: 1px solid var(--green);
			color: var(--green);
			padding: 1rem;
			border-radius: 5px;
			margin-bottom: 1rem;
		}
		.error {
			background: rgba(255, 0, 0, 0.2);
			border: 1px solid var(--red);
			color: var(--red);
			padding: 1rem;
			border-radius: 5px;
			margin-bottom: 1rem;
		}
		.classification-info {
			font-size: 0.9rem;
			color: var(--orange);
			font-style: italic;
			margin-top: 0.25rem;
		}
		.required {
			color: var(--red);
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
				<div class="panel-2">SEC<span class="hop">-TAC</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">CRIMINAL RECORDS &#149; ENTRY</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')">SEC/TAC</button>
						<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')">RECORDS</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">ADD RECORD</button>
						<button onclick="playSoundAndRedirect('audio2', 'training.php')">TRAINING</button>
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
					<div class="panel-3">SEC<span class="hop">-DESK</span></div>
					<div class="panel-4">PHSR<span class="hop">-RNG</span></div>
					<div class="panel-5">ARM<span class="hop">-CHK</span></div>
					<div class="panel-6">CRIM<span class="hop">-DB</span></div>
					<div class="panel-7">INVST<span class="hop">-LOG</span></div>
					<div class="panel-8">ALERT<span class="hop">-SYS</span></div>
					<div class="panel-9">BRIG<span class="hop">-MON</span></div>
				</div>
				<div>
					<div class="panel-10">SEC<span class="hop">-SCAN</span></div>
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
				<div>
					<?php if ($message): ?>
						<div class="message"><?php echo htmlspecialchars($message); ?></div>
					<?php endif; ?>
					
					<?php if ($error): ?>
						<div class="error"><?php echo htmlspecialchars($error); ?></div>
					<?php endif; ?>
					
					<div class="form-container">
						<h3>📝 New Criminal Record Entry</h3>
						<p style="color: var(--gold);"><em>⚠️ All fields marked with <span class="required">*</span> are required</em></p>
						
						<form method="POST" action="">
							<div class="form-grid">
								<!-- Crew Member Selection -->
								<div class="form-group">
									<label for="crew_id">Subject Personnel <span class="required">*</span></label>
									<select name="crew_id" id="crew_id" required>
										<option value="">Select Crew Member</option>
										<?php foreach ($crew_members as $member): ?>
										<option value="<?php echo $member['id']; ?>" <?php echo (($preselected_crew_id && $preselected_crew_id == $member['id']) || (isset($_POST['crew_id']) && $_POST['crew_id'] == $member['id'])) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($member['rank'] . ' ' . $member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['department'] . ')'); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<!-- Incident Type -->
								<div class="form-group">
									<label for="incident_type">Incident Type <span class="required">*</span></label>
									<select name="incident_type" id="incident_type" required>
										<option value="">Select Type</option>
										<option value="Minor Infraction" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Minor Infraction') ? 'selected' : ''; ?>>Minor Infraction</option>
										<option value="Major Violation" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Major Violation') ? 'selected' : ''; ?>>Major Violation</option>
										<option value="Court Martial" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Court Martial') ? 'selected' : ''; ?>>Court Martial</option>
										<option value="Criminal Activity" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Criminal Activity') ? 'selected' : ''; ?>>Criminal Activity</option>
										<option value="Disciplinary Action" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Disciplinary Action') ? 'selected' : ''; ?>>Disciplinary Action</option>
									</select>
								</div>
								
								<!-- Incident Date -->
								<div class="form-group">
									<label for="incident_date">Incident Date <span class="required">*</span></label>
									<input type="date" name="incident_date" id="incident_date" value="<?php echo isset($_POST['incident_date']) ? htmlspecialchars($_POST['incident_date']) : formatICDateOnly(date('Y-m-d H:i:s')); ?>" required>
								</div>
								
								<!-- Location -->
								<div class="form-group">
									<label for="location">Location <span class="required">*</span></label>
									<input type="text" name="location" id="location" placeholder="e.g., Deck 5 - Security Office" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
								</div>
								
								<!-- Investigation Status -->
								<div class="form-group">
									<label for="investigation_status">Investigation Status <span class="required">*</span></label>
									<select name="investigation_status" id="investigation_status" required>
										<option value="">Select Status</option>
										<option value="Under Investigation" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Under Investigation') ? 'selected' : ''; ?>>Under Investigation</option>
										<option value="Closed - Guilty" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Closed - Guilty') ? 'selected' : ''; ?>>Closed - Guilty</option>
										<option value="Closed - Not Guilty" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Closed - Not Guilty') ? 'selected' : ''; ?>>Closed - Not Guilty</option>
										<option value="Closed - Insufficient Evidence" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Closed - Insufficient Evidence') ? 'selected' : ''; ?>>Closed - Insufficient Evidence</option>
										<option value="Pending Review" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Pending Review') ? 'selected' : ''; ?>>Pending Review</option>
									</select>
								</div>
								
								<!-- Investigating Officer -->
								<div class="form-group">
									<label for="investigating_officer">Investigating Officer <span class="required">*</span></label>
									<input type="text" name="investigating_officer" id="investigating_officer" placeholder="Rank First Last" value="<?php echo isset($_POST['investigating_officer']) ? htmlspecialchars($_POST['investigating_officer']) : htmlspecialchars($default_officer); ?>" required>
								</div>
								
								<!-- Classification -->
								<div class="form-group">
									<label for="classification">Security Classification <span class="required">*</span></label>
									<select name="classification" id="classification" required>
										<option value="">Select Classification</option>
										<option value="Public" <?php echo (isset($_POST['classification']) && $_POST['classification'] == 'Public') ? 'selected' : ''; ?>>Public</option>
										<option value="Restricted" <?php echo (isset($_POST['classification']) && $_POST['classification'] == 'Restricted') ? 'selected' : ''; ?>>Restricted</option>
										<option value="Classified" <?php echo (isset($_POST['classification']) && $_POST['classification'] == 'Classified') ? 'selected' : ''; ?>>Classified</option>
									</select>
									<div class="classification-info">
										Public: All security staff • Restricted: Command+ only • Classified: Captain only
									</div>
								</div>
								
								<!-- Punishment Type -->
								<div class="form-group">
									<label for="punishment_type">Punishment Type</label>
									<select name="punishment_type" id="punishment_type">
										<option value="">None/Pending</option>
										<option value="Verbal Warning" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Verbal Warning') ? 'selected' : ''; ?>>Verbal Warning</option>
										<option value="Written Reprimand" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Written Reprimand') ? 'selected' : ''; ?>>Written Reprimand</option>
										<option value="Loss of Privileges" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Loss of Privileges') ? 'selected' : ''; ?>>Loss of Privileges</option>
										<option value="Demotion" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Demotion') ? 'selected' : ''; ?>>Demotion</option>
										<option value="Confinement" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Confinement') ? 'selected' : ''; ?>>Confinement</option>
										<option value="Court Martial" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Court Martial') ? 'selected' : ''; ?>>Court Martial</option>
										<option value="Dismissal" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Dismissal') ? 'selected' : ''; ?>>Dismissal</option>
										<option value="Other" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
									</select>
								</div>
							</div>
							
							<!-- Full Width Fields -->
							<div class="form-group">
								<label for="description">Incident Description <span class="required">*</span></label>
								<textarea name="description" id="description" placeholder="Detailed description of the incident..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
							</div>
							
							<div class="form-group">
								<label for="evidence_notes">Evidence & Investigation Notes</label>
								<textarea name="evidence_notes" id="evidence_notes" placeholder="Physical evidence, witness statements, investigation findings..."><?php echo isset($_POST['evidence_notes']) ? htmlspecialchars($_POST['evidence_notes']) : ''; ?></textarea>
							</div>
							
							<div class="form-group">
								<label for="witnesses">Witnesses</label>
								<textarea name="witnesses" id="witnesses" placeholder="List of witnesses with ranks and names..."><?php echo isset($_POST['witnesses']) ? htmlspecialchars($_POST['witnesses']) : ''; ?></textarea>
							</div>
							
							<div class="form-group">
								<label for="punishment_details">Punishment Details</label>
								<textarea name="punishment_details" id="punishment_details" placeholder="Specific details of punishment, duration, conditions..."><?php echo isset($_POST['punishment_details']) ? htmlspecialchars($_POST['punishment_details']) : ''; ?></textarea>
							</div>
							
							<div style="text-align: center; margin-top: 2rem;">
								<button type="submit" class="submit-btn">🚨 Add Criminal Record</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="wrap">
			<div class="left-frame-bottom">
				<div>
					<div class="panel-3">STAR<span class="hop">FLEET</span></div>
					<div class="panel-4">SEC<span class="hop">9001</span></div>
					<div class="panel-5">47<span class="hop">###</span></div>
					<div class="panel-6">1701<span class="hop">D</span></div>
					<div class="panel-7">74<span class="hop">656</span></div>
					<div class="panel-8">NCC<span class="hop">REG</span></div>
					<div class="panel-9">DOCK<span class="hop">94</span></div>
				</div>
				<div>
					<div class="panel-10">USS<span class="hop">SERENITY</span></div>
				</div>
			</div>
			<div class="right-frame-bottom">
				<div class="bar-panel">
					<div class="bar-1"></div>
					<div class="bar-2"></div>
					<div class="bar-3"></div>
					<div class="bar-4"></div>
					<div class="bar-5"></div>
				</div>
			</div>
		</div>
	</section>
	<script src="../assets/lcars.js"></script>
</body>
</html>
