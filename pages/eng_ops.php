<?php
require_once '../includes/config.php';
require_once '../includes/department_training.php';
require_once '../includes/promotion_system.php';

// Update last active timestamp for current character
updateLastActive();

// Handle department training if user has permission
if (hasPermission('ENG/OPS') || hasPermission('Command')) {
    handleDepartmentTraining('Engineering');
}

// Handle fault report submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'fault_report') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (!isLoggedIn()) {
        $error = "You must be logged in to submit fault reports.";
    } else {
        try {
            // Auto-populate reported_by with current user's character
            // Find the current user's roster ID based on their character
            $pdo = getConnection();
            $current_character = getCurrentCharacter();
            $reported_by_roster_id = null;
            
            if ($current_character && $current_character['character_id']) {
                $reported_by_roster_id = $current_character['character_id'];
            }
            
            // Prepare location data based on type
            $location_type = sanitizeInput($_POST['location_type']);
            $deck_number = null;
            $room = null;
            $jefferies_tube_number = null;
            $access_point = null;
            
            if ($location_type === 'Internal') {
                $deck_number = filter_var($_POST['deck_number'], FILTER_VALIDATE_INT);
                $room = sanitizeInput($_POST['room'] ?? '');
            } elseif ($location_type === 'External') {
                $hull_direction = sanitizeInput($_POST['hull_direction'] ?? '');
                $hull_position = sanitizeInput($_POST['hull_position'] ?? '');
                $room = $hull_direction . ($hull_position ? ' - ' . $hull_position : '');
            } elseif ($location_type === 'Jefferies Tube') {
                $access_point = sanitizeInput($_POST['access_point'] ?? '');
                $jefferies_tube_number = 'Near: ' . $access_point;
            }
            
            $stmt = $pdo->prepare("INSERT INTO fault_reports (location_type, deck_number, room, jefferies_tube_number, access_point, fault_description, reported_by_roster_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $location_type,
                $deck_number,
                $room,
                $jefferies_tube_number,
                $access_point,
                sanitizeInput($_POST['fault_description']),
                $reported_by_roster_id
            ]);
            $success = "Fault report submitted successfully.";
        } catch (Exception $e) {
            error_log("Error submitting fault report: " . $e->getMessage());
            $error = "Error submitting report. Please try again.";
        }
    }
}

// Handle fault resolution (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'resolve_fault') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('ENG/OPS')) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE fault_reports SET status = ?, resolution_description = ? WHERE id = ?");
            $stmt->execute([
                sanitizeInput($_POST['status']),
                sanitizeInput($_POST['resolution_description']),
                filter_var($_POST['fault_id'], FILTER_VALIDATE_INT)
            ]);
            $success = "Fault report updated successfully.";
        } catch (Exception $e) {
            error_log("Error updating fault report: " . $e->getMessage());
            $error = "Error updating fault. Please try again.";
        }
    }
}

// Handle fault report deletion (Command or Starfleet Auditor only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_fault_report') {
    $roster_dept = $_SESSION['roster_department'] ?? '';
    if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor') {
        try {
            $pdo = getConnection();
            
            // Get report details for logging
            $stmt = $pdo->prepare("SELECT fr.*, r.first_name, r.last_name FROM fault_reports fr LEFT JOIN roster r ON fr.reported_by_roster_id = r.id WHERE fr.id = ?");
            $stmt->execute([$_POST['report_id']]);
            $report = $stmt->fetch();
            
            if ($report) {
                // Delete the report
                $stmt = $pdo->prepare("DELETE FROM fault_reports WHERE id = ?");
                $stmt->execute([$_POST['report_id']]);
                
                // Log the action for auditing (both Command and Starfleet Auditors)
                if (isset($_SESSION['character_id']) && (hasPermission('Command') || $roster_dept === 'Starfleet Auditor')) {
                    logAuditorAction($_SESSION['character_id'], 'delete_fault_report', 'fault_reports', $report['id'], [
                        'system_name' => $report['system_name'],
                        'fault_description' => $report['fault_description'],
                        'status' => $report['status'],
                        'reported_by' => ($report['first_name'] ?? '') . ' ' . ($report['last_name'] ?? ''),
                        'user_type' => $roster_dept === 'Starfleet Auditor' ? 'Starfleet Auditor' : 'Command Staff'
                    ]);
                }
                
                $success = "Fault report deleted successfully.";
            } else {
                $error = "Fault report not found.";
            }
        } catch (Exception $e) {
            $error = "Error deleting fault report: " . $e->getMessage();
        }
    } else {
        $error = "Only Command staff and Starfleet Auditors can delete fault reports.";
    }
}

try {
    $pdo = getConnection();
    
    // Get roster for dropdown
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, rank FROM roster WHERE (is_invisible IS NULL OR is_invisible = 0) ORDER BY last_name, first_name");
    $stmt->execute();
    $roster = $stmt->fetchAll();
    
    // Get department heads
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Head of ENG/OPS', 'Chief Engineer', 'Operations Officer', 'Helm Officer') AND (is_invisible IS NULL OR is_invisible = 0) ORDER BY position");
    $stmt->execute();
    $dept_heads = $stmt->fetchAll();
    
    // Get fault reports for backend (excluding resolved cases)
    if (hasPermission('ENG/OPS')) {
        $stmt = $pdo->prepare("
            SELECT fr.*, r.first_name, r.last_name, r.rank 
            FROM fault_reports fr 
            LEFT JOIN roster r ON fr.reported_by_roster_id = r.id 
            WHERE fr.status != 'Resolved'
            ORDER BY fr.status ASC, fr.created_at DESC
        ");
        $stmt->execute();
        $fault_reports = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Engineering/Operations</title>
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
				<div class="panel-2">ENG<span class="hop">-OPS</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">ENGINEERING &#149; OPERATIONS</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--orange);">ENG/OPS</button>
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
					<div class="panel-3">WARP<span class="hop">-CORE</span></div>
					<div class="panel-4">IMPULSE<span class="hop">-ON</span></div>
					<div class="panel-5">SHIELDS<span class="hop">-100</span></div>
					<div class="panel-6">POWER<span class="hop">-NOM</span></div>
					<div class="panel-7">LIFE<span class="hop">-SUPP</span></div>
					<div class="panel-8">HULL<span class="hop">-INTG</span></div>
					<div class="panel-9">MAINT<span class="hop">-SCHED</span></div>
				</div>
				<div>
					<div class="panel-10">SYS<span class="hop">-STAT</span></div>
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
					<h1>Engineering & Operations</h1>
					<h2>USS-Serenity Ship Systems & Maintenance</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(255, 136, 0, 0.3); border: 2px solid var(--orange); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--orange);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<h3>Department Leadership</h3>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php foreach ($dept_heads as $head): ?>
						<div style="background: rgba(255, 136, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--orange);">
							<h4><?php echo htmlspecialchars($head['rank'] . ' ' . $head['first_name'] . ' ' . $head['last_name']); ?></h4>
							<p><?php echo htmlspecialchars($head['position']); ?></p>
						</div>
						<?php endforeach; ?>
					</div>
					
					<!-- Public Fault Reporting Form -->
					<?php if (isLoggedIn()): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--orange); margin: 2rem 0;">
						<h4>System Fault Report</h4>
						<form method="POST" action="" id="faultForm">
							<input type="hidden" name="action" value="fault_report">
							<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
							
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--orange);">Location Type:</label>
									<select name="location_type" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);" onchange="toggleLocationFields()">
										<option value="">Select Location Type</option>
										<option value="Internal">Internal</option>
										<option value="External">External</option>
										<option value="Jefferies Tube">Jefferies Tube</option>
									</select>
								</div>
								<div>
									<label style="color: var(--orange);">Reported By:</label>
									<?php 
									$current_user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
									?>
									<input type="text" value="<?php echo htmlspecialchars($current_user); ?>" readonly style="width: 100%; padding: 0.5rem; background: #333; color: var(--orange); border: 1px solid var(--orange); cursor: not-allowed;">
									<small style="color: var(--orange); font-size: 0.8rem;">Auto-filled from your current character profile</small>
								</div>
							</div>
							
							<!-- Internal Location Fields -->
							<div id="internalFields" style="display: none; margin-bottom: 1rem;">
								<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
									<div>
										<label style="color: var(--orange);">Deck:</label>
										<select name="deck_number" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);" onchange="updateSections()">
											<option value="">Select Deck</option>
											<option value="1">Deck 1</option>
											<option value="2">Deck 2</option>
											<option value="3">Deck 3</option>
											<option value="4">Deck 4</option>
											<option value="5">Deck 5</option>
											<option value="6">Deck 6</option>
											<option value="11">Deck 11</option>
										</select>
									</div>
									<div>
										<label style="color: var(--orange);">Section:</label>
										<select name="room" id="sectionSelect" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
											<option value="">Select Section</option>
										</select>
									</div>
								</div>
							</div>
							
							<!-- External Location Fields -->
							<div id="externalFields" style="display: none; margin-bottom: 1rem;">
								<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
									<div>
										<label style="color: var(--orange);">Hull Direction:</label>
										<select name="hull_direction" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);" onchange="updateHullPosition()">
											<option value="">Select Direction</option>
											<option value="Forward">Forward</option>
											<option value="Starboard">Starboard</option>
											<option value="Stern">Stern</option>
											<option value="Port">Port</option>
										</select>
									</div>
									<div>
										<label style="color: var(--orange);">Hull Position:</label>
										<select name="hull_position" id="hullPositionSelect" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
											<option value="">Select Position</option>
										</select>
									</div>
								</div>
							</div>
							
							<!-- Jefferies Tube Fields -->
							<div id="jefferiesFields" style="display: none; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--orange);">Nearest Access Point:</label>
									<select name="access_point" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
										<option value="">Select Access Point</option>
										<optgroup label="Deck 1">
											<option value="Deck 1 - Section 4 Hallway">Section 4 Hallway</option>
										</optgroup>
										<optgroup label="Deck 2">
											<option value="Deck 2 - Section 2 Turbo lift">Section 2 Turbo lift</option>
											<option value="Deck 2 - Section 3 VIP Quarters 1">Section 3 VIP Quarters 1</option>
											<option value="Deck 2 - Section 4 The Raven">Section 4 The Raven</option>
											<option value="Deck 2 - Section 5 Science Lab">Section 5 Science Lab</option>
										</optgroup>
										<optgroup label="Deck 3">
											<option value="Deck 3 - Turbo lift: left hatch">Turbo lift: left hatch</option>
											<option value="Deck 3 - Turbo lift: right hatch">Turbo lift: right hatch</option>
											<option value="Deck 3 - XO Quarters">XO Quarters</option>
											<option value="Deck 3 - SO Quarters">SO Quarters</option>
										</optgroup>
										<optgroup label="Deck 4">
											<option value="Deck 4 - Turbo lift: left hatch">Turbo lift: left hatch</option>
											<option value="Deck 4 - Turbo lift: right hatch">Turbo lift: right hatch</option>
											<option value="Deck 4 - Section 4 Security">Section 4 Security</option>
											<option value="Deck 4 - Section 3 Transporter room">Section 3 Transporter room</option>
										</optgroup>
										<optgroup label="Deck 5">
											<option value="Deck 5 - Turbo lift hatch">Turbo lift hatch</option>
											<option value="Deck 5 - Turbo lift door">Turbo lift door</option>
											<option value="Deck 5 - Section 3 Science Lab">Section 3 Science Lab</option>
										</optgroup>
										<optgroup label="Deck 6">
											<option value="Deck 6 - Section 3 Left Holodeck">Section 3 Left Holodeck</option>
											<option value="Deck 6 - Section 4 Right Holodeck">Section 4 Right Holodeck</option>
											<option value="Deck 6 - Section 15B Sickbay Lab">Section 15B Sickbay Lab</option>
										</optgroup>
										<optgroup label="Deck 11">
											<option value="Deck 11 - Section 4 Engineering lower level">Section 4 Engineering lower level</option>
											<option value="Deck 11 - Section 4 Engineering higher level">Section 4 Engineering higher level</option>
										</optgroup>
									</select>
								</div>
							</div>
							
							<div style="margin-bottom: 1rem;">
								<label style="color: var(--orange);">Fault Description:</label>
								<textarea name="fault_description" required rows="4" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);" placeholder="Describe the fault, symptoms, and any relevant details..."></textarea>
							</div>
							
							<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; width: 100%;">Submit Fault Report</button>
						</form>
					</div>
					<?php else: ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--orange); margin: 2rem 0;">
						<h4>System Fault Report</h4>
						<p style="color: var(--orange); text-align: center;">You must be logged in to submit fault reports.</p>
						<div style="text-align: center; margin-top: 1rem;">
							<a href="../index.php" style="background-color: var(--orange); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">Return to Login</a>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('ENG/OPS')): ?>
					<!-- Engineering Staff Backend -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--orange);">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
							<div>
								<h3>Engineering Work Orders</h3>
								<p style="color: var(--orange);"><em>Engineering/Operations Staff Access Only</em></p>
							</div>
							<button onclick="playSoundAndRedirect('audio2', 'engineering_resolved.php')" style="background-color: var(--orange); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem;">
								View Resolved Faults
							</button>
						</div>
						
						<div style="max-height: 600px; overflow-y: auto; border: 1px solid var(--orange); border-radius: 5px; padding: 1rem; margin: 1rem 0; background: rgba(0,0,0,0.3);">
							<?php foreach ($fault_reports as $fault): ?>
							<div style="border-bottom: 1px solid var(--gray); padding: 1rem 0; <?php echo $fault['status'] === 'Resolved' ? 'opacity: 0.7;' : ''; ?>">
								<div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
									<div>
										<strong>Report #<?php echo $fault['id']; ?></strong><br>
										<strong>Location:</strong> <?php echo htmlspecialchars($fault['location_type']); ?>
										<?php if ($fault['deck_number']): ?>
											- Deck <?php echo $fault['deck_number']; ?>
										<?php endif; ?>
										<?php if ($fault['room']): ?>
											, <?php echo htmlspecialchars($fault['room']); ?>
										<?php endif; ?>
										<?php if ($fault['jefferies_tube_number']): ?>
											- Tube <?php echo htmlspecialchars($fault['jefferies_tube_number']); ?>
										<?php endif; ?>
										<br>
										<small>Reported: <?php echo formatICDateTime($fault['created_at']); ?></small><br>
										<?php if ($fault['first_name']): ?>
										<small>By: <?php echo htmlspecialchars($fault['rank'] . ' ' . $fault['first_name'] . ' ' . $fault['last_name']); ?></small>
										<?php endif; ?>
									</div>
									<div>
										<strong>Fault Description:</strong><br>
										<?php echo htmlspecialchars($fault['fault_description']); ?>
										<?php if ($fault['resolution_description']): ?>
										<br><br><strong>Resolution:</strong><br>
										<?php echo htmlspecialchars($fault['resolution_description']); ?>
										<?php endif; ?>
									</div>
									<div>
										<form method="POST" action="">
											<input type="hidden" name="action" value="resolve_fault">
											<input type="hidden" name="fault_id" value="<?php echo $fault['id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<select name="status" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--orange); margin-bottom: 0.5rem;">
												<option value="Open" <?php echo $fault['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
												<option value="In Progress" <?php echo $fault['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
												<option value="Resolved" <?php echo $fault['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
											</select>
											<textarea name="resolution_description" placeholder="Resolution details..." rows="3" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--orange); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($fault['resolution_description'] ?? ''); ?></textarea>
											<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%; margin-bottom: 0.5rem;">Update</button>
										</form>
										
										<?php if (hasPermission('Command') || hasPermission('Starfleet Auditor')): ?>
										<form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this fault report? This action cannot be undone.');">
											<input type="hidden" name="action" value="delete_fault_report">
											<input type="hidden" name="report_id" value="<?php echo $fault['id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<button type="submit" style="background-color: var(--red); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%; font-size: 0.8rem;">🗑️ Delete Report</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('ENG/OPS') || hasPermission('Command')): ?>
					<!-- Department Training Section -->
					<?php renderDepartmentTrainingSection('Engineering', 'Engineering'); ?>
					<?php endif; ?>
					
					<!-- Promotion/Demotion Form -->
					<?php renderPromotionForm('ENG/OPS'); ?>
					
					<div style="background: rgba(255, 136, 0, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0;">
						<h4>Ship Systems Status</h4>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
							<div>
								<strong style="color: var(--orange);">Warp Drive:</strong> Online<br>
								<strong style="color: var(--orange);">Impulse:</strong> Ready<br>
								<strong style="color: var(--orange);">Power Grid:</strong> Nominal
							</div>
							<div>
								<strong style="color: var(--orange);">Life Support:</strong> Optimal<br>
								<strong style="color: var(--orange);">Shields:</strong> 100%<br>
								<strong style="color: var(--orange);">Hull Integrity:</strong> 100%
							</div>
							<div>
								<strong style="color: var(--orange);">Main Engineering:</strong> Deck 11<br>
								<strong style="color: var(--orange);">Emergency Response:</strong> 24/7<br>
								<strong style="color: var(--orange);">Maintenance Schedule:</strong> Current
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
	<script>
		function toggleLocationFields() {
			const locationType = document.querySelector('select[name="location_type"]').value;
			const internalFields = document.getElementById('internalFields');
			const externalFields = document.getElementById('externalFields');
			const jefferiesFields = document.getElementById('jefferiesFields');
			
			// Hide all fields first
			internalFields.style.display = 'none';
			externalFields.style.display = 'none';
			jefferiesFields.style.display = 'none';
			
			// Show relevant fields
			if (locationType === 'Internal') {
				internalFields.style.display = 'block';
			} else if (locationType === 'External') {
				externalFields.style.display = 'block';
			} else if (locationType === 'Jefferies Tube') {
				jefferiesFields.style.display = 'block';
			}
		}
		
		function updateSections() {
			const deckNumber = document.querySelector('select[name="deck_number"]').value;
			const sectionSelect = document.getElementById('sectionSelect');
			
			// Clear existing options
			sectionSelect.innerHTML = '<option value="">Select Section</option>';
			
			const sections = {
				'1': [
					'Section 1 Bridge',
					'Section 2 Ready Room',
					'Section 3 Conference Room',
					'Section 4 Hallway',
					'Section 5 Turbo lift 1',
					'Section 5B Turbo lift 2',
					'Section 6 Airlock'
				],
				'2': [
					'Section 1 Hallway Inboard',
					'Section 1B hallway Starboard',
					'Section 1C Hallway Port',
					'Section 2 Turbo lift',
					'Section 3 VIP Quarters 1',
					'Section 4 The Raven',
					'Section 5 Science Lab',
					'Section 13 Mess Hall'
				],
				'3': [
					'Section 1 Hallway Forward',
					'Section 1B Hallway Aft',
					'Section 1C Hallway Starboard',
					'Section 1D Hallway Port',
					'Section 2 Turbo lift 1',
					'Section 2B Turbolift 2',
					'Section 3 Captains Quarters',
					'Section 4 Small Conference Room',
					'Section 5 Officers Quarters 1',
					'Section 6 Officers Quarters 2',
					'Section 7 Officers Quarters 3',
					'Section 8 Officers Quarters 4',
					'Section 9 Officers Quarters 5',
					'Section 10 Officers Quarters 6'
				],
				'4': [
					'Section 1 Hallway',
					'Section 1B Hallway Security',
					'Section 2 Turbo lift',
					'Section 3 Transporter room',
					'Section 4 Security',
					'Section 5 Brig 1',
					'Section 5B Brig Cell 1',
					'Section 6 Brig 2',
					'Section 6B Brig Cell 2',
					'Section 7 Brig 3',
					'Section 7B Brig Cell 3',
					'Section 8 Brig 4',
					'Section 8B Interview brig cell 4'
				],
				'5': [
					'Section 1 Hallway inboard',
					'Section 1B Hallway Aft',
					'Section 1C Hallway Forward',
					'Section 2 Turbo lift',
					'Section 3 Science Lab',
					'Section 4 Crew Quarters 1',
					'Section 5 Crew Quarters 2',
					'Section 6 Crew Quarters 3',
					'Section 7 Crew Quarters 4',
					'Section 8 Crew Quarters 5',
					'Section 9 Crew Quarters 6',
					'Section 10 Crew Quarters 7',
					'Section 11 Crew Quarters 8',
					'Section 12 Crew Quarters 9',
					'Section 13 Crew Quarters 10',
					'Section 14 Crew Quarters 11',
					'Section 15 Crew Quarters 12',
					'Section 16 Crew Quarters 13',
					'Section 17 Crew Quarters 14',
					'Section 18 Crew Quarters 15',
					'Section 19 Crew Quarters 16'
				],
				'6': [
					'Section 1 Hallway',
					'Section 2 Turbo Lift',
					'Section 3 Left Holodeck',
					'Section 4 Right Holodeck',
					'Section 15 Sickbay',
					'Section 15B Sickbay Lab'
				],
				'11': [
					'Section 1B1 Hallway Starboard',
					'Section 2B1 Turbo lift 2',
					'Section 4 Engineering',
					'Section 5 Turbo lift 1',
					'Section 7 Cargo bay',
					'Section 8 Hallway Inboard'
				]
			};
			
			if (sections[deckNumber]) {
				sections[deckNumber].forEach(section => {
					const option = document.createElement('option');
					option.value = section;
					option.textContent = section;
					sectionSelect.appendChild(option);
				});
			}
		}
		
		function updateHullPosition() {
			const hullDirection = document.querySelector('select[name="hull_direction"]').value;
			const hullPositionSelect = document.getElementById('hullPositionSelect');
			
			// Clear existing options
			hullPositionSelect.innerHTML = '<option value="">Select Position</option>';
			
			if (hullDirection) {
				const positions = ['Dorsal (Top of ship)', 'Ventral (Bottom of ship)'];
				positions.forEach(position => {
					const option = document.createElement('option');
					option.value = position;
					option.textContent = position;
					hullPositionSelect.appendChild(option);
				});
			}
		}
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
