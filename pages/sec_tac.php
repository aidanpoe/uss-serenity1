<?php
require_once '../includes/config.php';
require_once '../includes/department_training.php';
require_once '../includes/promotion_system.php';

// Update last active timestamp for current character
updateLastActive();

// Handle department training if user has permission
if (hasPermission('SEC/TAC') || hasPermission('Command')) {
    handleDepartmentTraining('Security');
}

// Handle security report submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'security_report') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        try {
            // Auto-populate reported_by with current user's character
            $reported_by = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO security_reports (incident_type, description, involved_roster_id, reported_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                sanitizeInput($_POST['incident_type']),
                sanitizeInput($_POST['description']),
                isset($_POST['involved_roster_id']) ? filter_var($_POST['involved_roster_id'], FILTER_VALIDATE_INT) : null,
                $reported_by
            ]);
            $success = "Security report submitted successfully.";
        } catch (Exception $e) {
            error_log("Error submitting security report: " . $e->getMessage());
            $error = "Error submitting report. Please try again.";
        }
    }
}

// Handle adding new persons (SEC/TAC only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_person') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('SEC/TAC')) {
        try {
            // Check if person already exists (by name only, species can be shared)
            $pdo = getConnection();
            $check_stmt = $pdo->prepare("SELECT id FROM roster WHERE first_name = ? AND last_name = ?");
            $check_stmt->execute([sanitizeInput($_POST['first_name']), sanitizeInput($_POST['last_name'])]);
            
            if ($check_stmt->fetch()) {
                $error = "A crew member with this name already exists in the roster.";
            } else {
                // SEC/TAC limited to ranks below Commander for person addition
                $allowed_person_ranks = [
                    'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
                    'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petter Officer',
                    'Senior Chief Petty Officer', 'Master Chief Petty Officer', 
                    'Command Master Chief Petty Officer', 'Warrant officer', 'Ensign',
                    'Lieutenant Junior Grade', 'Lieutenant', 'Lieutenant Commander'
                ];
                
                if (!in_array($_POST['rank'], $allowed_person_ranks)) {
                    $error = "Security personnel can only add persons with ranks below Commander.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, position, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        sanitizeInput($_POST['rank']),
                        sanitizeInput($_POST['first_name']),
                        sanitizeInput($_POST['last_name']),
                        sanitizeInput($_POST['species']),
                        sanitizeInput($_POST['department']),
                        '', // No special positions for person addition
                        '' // No image upload in this simplified form
                    ]);
                    $success = "New person added to roster successfully.";
                }
            }
        } catch (Exception $e) {
            error_log("Error adding person: " . $e->getMessage());
            $error = "Error adding person. Please try again.";
        }
    } else {
        $error = "Access denied. Security/Tactical authorization required.";
    }
}

// Handle security report update (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_security') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('SEC/TAC')) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE security_reports SET status = ?, resolution_notes = ? WHERE id = ?");
            $stmt->execute([
                sanitizeInput($_POST['status']),
                sanitizeInput($_POST['resolution_notes']),
                filter_var($_POST['report_id'], FILTER_VALIDATE_INT)
            ]);
            $success = "Security report updated successfully.";
        } catch (Exception $e) {
            error_log("Error updating security report: " . $e->getMessage());
            $error = "Error updating report. Please try again.";
        }
    }
}

// Handle security report deletion (Command or Starfleet Auditor only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_security_report') {
    $roster_dept = $_SESSION['roster_department'] ?? '';
    if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor') {
        try {
            $pdo = getConnection();
            
            // Get report details for logging
            $stmt = $pdo->prepare("SELECT sr.*, r.first_name, r.last_name FROM security_reports sr LEFT JOIN roster r ON sr.involved_roster_id = r.id WHERE sr.id = ?");
            $stmt->execute([$_POST['report_id']]);
            $report = $stmt->fetch();
            
            if ($report) {
                // Delete the report
                $stmt = $pdo->prepare("DELETE FROM security_reports WHERE id = ?");
                $stmt->execute([$_POST['report_id']]);
                
                // Log the action for auditing (both Command and Starfleet Auditors)
                if (isset($_SESSION['character_id']) && (hasPermission('Command') || $roster_dept === 'Starfleet Auditor')) {
                    logAuditorAction($_SESSION['character_id'], 'delete_security_report', 'security_reports', $report['id'], [
                        'incident_type' => $report['incident_type'],
                        'description' => $report['description'],
                        'involved_person' => ($report['first_name'] ?? '') . ' ' . ($report['last_name'] ?? ''),
                        'status' => $report['status'],
                        'user_type' => $roster_dept === 'Starfleet Auditor' ? 'Starfleet Auditor' : 'Command Staff'
                    ]);
                }
                
                $success = "Security report deleted successfully.";
            } else {
                $error = "Security report not found.";
            }
        } catch (Exception $e) {
            $error = "Error deleting security report: " . $e->getMessage();
        }
    } else {
        $error = "Only Command staff and Starfleet Auditors can delete security reports.";
    }
}

// Handle phaser training update (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_phaser_training') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('SEC/TAC')) {
        try {
            $pdo = getConnection();
            $training_levels = [];
            if (isset($_POST['type_1'])) $training_levels[] = 'Type I';
            if (isset($_POST['type_2'])) $training_levels[] = 'Type II';
            if (isset($_POST['type_3'])) $training_levels[] = 'Type III';
            
            $training_string = implode(', ', $training_levels);
            
            $stmt = $pdo->prepare("UPDATE roster SET phaser_training = ? WHERE id = ?");
            $stmt->execute([$training_string, filter_var($_POST['roster_id'], FILTER_VALIDATE_INT)]);
            $success = "Phaser training record updated successfully.";
        } catch (Exception $e) {
            error_log("Error updating phaser training: " . $e->getMessage());
            $error = "Error updating training. Please try again.";
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get roster for dropdown
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, rank FROM roster WHERE (is_invisible IS NULL OR is_invisible = 0) ORDER BY last_name, first_name");
    $stmt->execute();
    $roster = $stmt->fetchAll();
    
    // Get department heads
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Head of SEC/TAC', 'Security Chief', 'Tactical Officer', 'Intelligence Officer', 'S.R.T. Leader') AND (is_invisible IS NULL OR is_invisible = 0) ORDER BY position");
    $stmt->execute();
    $dept_heads = $stmt->fetchAll();
    
    // Get security reports for backend (excluding resolved cases)
    if (hasPermission('SEC/TAC')) {
        $stmt = $pdo->prepare("
            SELECT sr.*, r.first_name, r.last_name, r.rank 
            FROM security_reports sr 
            LEFT JOIN roster r ON sr.involved_roster_id = r.id 
            WHERE sr.status != 'Resolved'
            ORDER BY sr.status ASC, sr.created_at DESC
        ");
        $stmt->execute();
        $security_reports = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Security/Tactical</title>
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
				<div class="panel-2">SEC<span class="hop">-TAC</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">SECURITY &#149; TACTICAL</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--gold);">SEC/TAC</button>
						<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')">CRIMINAL</button>
						<button onclick="playSoundAndRedirect('audio2', 'security_resolved.php')">RESOLVED</button>
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
					<div class="panel-3">ALERT<span class="hop">-GREEN</span></div>
					<div class="panel-4">SHIELDS<span class="hop">-UP</span></div>
					<div class="panel-5">WEAP<span class="hop">-RDY</span></div>
					<div class="panel-6">SEC<span class="hop">-PATR</span></div>
					<div class="panel-7">INTRUD<span class="hop">-DET</span></div>
					<div class="panel-8">FORCE<span class="hop">-FLD</span></div>
					<div class="panel-9">TACT<span class="hop">-ANLY</span></div>
				</div>
				<div>
					<div class="panel-10">SEC<span class="hop">-STAT</span></div>
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
					<h1>Security & Tactical</h1>
					<h2>USS-Serenity Defense & Security Operations</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(255, 170, 0, 0.3); border: 2px solid var(--gold); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--gold);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Security Tools Quick Access -->
					<div style="background: rgba(255, 165, 0, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
						<h3 style="color: var(--gold); text-align: center; margin-bottom: 1.5rem;">🛡️ Security Department Tools</h3>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')" style="background-color: var(--red); color: white; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: bold;">
								🗂️ Criminal Records<br><small style="font-weight: normal;">Search personnel criminal history</small>
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'security_resolved.php')" style="background-color: var(--green); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: bold;">
								✅ Resolved Cases<br><small style="font-weight: normal;">View completed security cases</small>
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--orange); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: bold;">
								👥 Full Roster<br><small style="font-weight: normal;">Complete crew manifest</small>
							</button>
						</div>
					</div>
					
					<h3>Department Leadership</h3>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php foreach ($dept_heads as $head): ?>
						<div style="background: rgba(255, 170, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--gold);">
							<h4><?php echo htmlspecialchars($head['rank'] . ' ' . $head['first_name'] . ' ' . $head['last_name']); ?></h4>
							<p><?php echo htmlspecialchars($head['position']); ?></p>
						</div>
						<?php endforeach; ?>
					</div>
					
					<!-- Public Security Reporting Form -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--gold); margin: 2rem 0;">
						<h4>Security Incident Report</h4>
						<form method="POST" action="">
							<input type="hidden" name="action" value="security_report">
							<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
							
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--gold);">Incident Type:</label>
									<select name="incident_type" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
										<option value="">Select Incident Type</option>
										<option value="Crime">Crime</option>
										<option value="Security Concern">Security Concern</option>
										<option value="Arrest">Arrest Report</option>
									</select>
								</div>
								<div>
									<label style="color: var(--gold);">Involved Person (Optional):</label>
									<select name="involved_roster_id" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
										<option value="">Select Person (Optional)</option>
										<?php foreach ($roster as $person): ?>
										<option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							
							<div style="margin-bottom: 1rem;">
								<label style="color: var(--gold);">Incident Description:</label>
								<textarea name="description" required rows="4" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);" placeholder="Provide detailed description of the incident, including time, location, and circumstances..."></textarea>
							</div>
							
							<div style="margin-bottom: 1rem;">
								<label style="color: var(--gold);">Reported By:</label>
								<?php 
								$current_user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
								?>
								<input type="text" value="<?php echo htmlspecialchars($current_user); ?>" readonly style="width: 100%; padding: 0.5rem; background: #333; color: var(--gold); border: 1px solid var(--gold); cursor: not-allowed;">
								<small style="color: var(--gold); font-size: 0.8rem;">Auto-filled from your current character profile</small>
							</div>
							
							<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; width: 100%;">Submit Security Report</button>
						</form>
					</div>
					
					<!-- Add New Person Form (SEC/TAC Only) -->
					<?php if (hasPermission('SEC/TAC')): ?>
					<div style="background: rgba(255, 170, 0, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
						<h3 style="color: var(--gold); text-align: center; margin-bottom: 1.5rem;">👮 Add New Person to Roster</h3>
						<form method="POST" action="">
							<input type="hidden" name="action" value="add_person">
							<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
								<div>
									<label style="color: var(--gold);">Rank:</label>
									<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
										<option value="Crewman 3rd Class">Crewman 3rd Class</option>
										<option value="Crewman 2nd Class">Crewman 2nd Class</option>
										<option value="Crewman 1st Class">Crewman 1st Class</option>
										<option value="Petty Officer 3rd class">Petty Officer 3rd class</option>
										<option value="Petty Officer 1st class">Petty Officer 1st class</option>
										<option value="Chief Petter Officer">Chief Petter Officer</option>
										<option value="Senior Chief Petty Officer">Senior Chief Petty Officer</option>
										<option value="Master Chief Petty Officer">Master Chief Petty Officer</option>
										<option value="Command Master Chief Petty Officer">Command Master Chief Petty Officer</option>
										<option value="Warrant officer">Warrant officer</option>
										<option value="Ensign">Ensign</option>
										<option value="Lieutenant Junior Grade">Lieutenant Junior Grade</option>
										<option value="Lieutenant">Lieutenant</option>
										<option value="Lieutenant Commander">Lieutenant Commander</option>
									</select>
								</div>
								<div>
									<label style="color: var(--gold);">First Name:</label>
									<input type="text" name="first_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">Last Name:</label>
									<input type="text" name="last_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">Species:</label>
									<input type="text" name="species" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">Department:</label>
									<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
										<option value="Command">Command</option>
										<option value="MED/SCI">MED/SCI</option>
										<option value="ENG/OPS">ENG/OPS</option>
										<option value="SEC/TAC">SEC/TAC</option>
									</select>
								</div>
							</div>
							<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem; width: 100%;">Add New Person to Roster</button>
						</form>
						<p style="color: var(--gold); font-size: 0.9rem; text-align: center; margin-top: 1rem;">
							<em>Security personnel can add new persons to the ship's roster for incident and crime tracking purposes.</em>
						</p>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('SEC/TAC')): ?>
					<!-- Security Staff Backend -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--gold);">
						<h3>Security Operations Center</h3>
						<p style="color: var(--gold);"><em>Security/Tactical Staff Access Only</em></p>
						
						<!-- Phaser Training Management -->
						<div style="background: rgba(255, 170, 0, 0.1); padding: 1.5rem; border-radius: 10px; margin: 1.5rem 0; border: 1px solid var(--gold);">
							<h4>Phaser Training Management</h4>
							<form method="POST" action="">
								<input type="hidden" name="action" value="update_phaser_training">
								<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
								<div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end;">
									<div>
										<label style="color: var(--gold);">Select Personnel:</label>
										<select name="roster_id" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
											<option value="">Select Person</option>
											<?php foreach ($roster as $person): ?>
											<option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div>
										<label style="color: var(--gold);">Training Levels:</label><br>
										<label><input type="checkbox" name="type_1" value="1"> Type I</label>
										<label style="margin-left: 1rem;"><input type="checkbox" name="type_2" value="1"> Type II</label>
										<label style="margin-left: 1rem;"><input type="checkbox" name="type_3" value="1"> Type III</label>
									</div>
									<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.75rem 1rem; border-radius: 5px;">Update Training</button>
								</div>
							</form>
						</div>
						
						<!-- Security Incident Management -->
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
							<h4>Security Incident Reports</h4>
							<button onclick="playSoundAndRedirect('audio2', 'security_resolved.php')" style="background-color: var(--gold); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem;">
								View Resolved Cases
							</button>
						</div>
						<div style="max-height: 600px; overflow-y: auto; border: 1px solid var(--gold); border-radius: 5px; padding: 1rem; margin: 1rem 0; background: rgba(0,0,0,0.3);">
							<?php foreach ($security_reports as $report): ?>
							<div style="border-bottom: 1px solid var(--gray); padding: 1rem 0; <?php echo $report['status'] === 'Resolved' ? 'opacity: 0.7;' : ''; ?>">
								<div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
									<div>
										<strong>Report #<?php echo $report['id']; ?></strong><br>
										<span style="background: var(--<?php echo strtolower(str_replace(' ', '-', $report['incident_type'])); ?>); color: black; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">
											<?php echo htmlspecialchars($report['incident_type']); ?>
										</span><br><br>
										<small>Reported: <?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></small><br>
										<small>By: <?php echo htmlspecialchars($report['reported_by']); ?></small><br>
										<?php if ($report['first_name']): ?>
										<small>Involves: <?php echo htmlspecialchars($report['rank'] . ' ' . $report['first_name'] . ' ' . $report['last_name']); ?></small>
										<?php endif; ?>
									</div>
									<div>
										<strong>Description:</strong><br>
										<?php echo htmlspecialchars($report['description']); ?>
										<?php if ($report['resolution_notes']): ?>
										<br><br><strong>Resolution:</strong><br>
										<?php echo htmlspecialchars($report['resolution_notes']); ?>
										<?php endif; ?>
									</div>
									<div>
										<form method="POST" action="">
											<input type="hidden" name="action" value="update_security">
											<input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<select name="status" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--gold); margin-bottom: 0.5rem;">
												<option value="Open" <?php echo $report['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
												<option value="Under Investigation" <?php echo $report['status'] === 'Under Investigation' ? 'selected' : ''; ?>>Under Investigation</option>
												<option value="Resolved" <?php echo $report['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
											</select>
											<textarea name="resolution_notes" placeholder="Resolution notes..." rows="3" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--gold); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($report['resolution_notes'] ?? ''); ?></textarea>
											<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%;">Update</button>
										</form>
										
										<!-- Delete Button for Command/Starfleet Auditor -->
										<?php $roster_dept = $_SESSION['roster_department'] ?? ''; if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor'): ?>
										<form method="POST" style="margin-top: 0.5rem;" onsubmit="return confirm('Are you sure you want to delete this security report? This action cannot be undone.');">
											<input type="hidden" name="action" value="delete_security_report">
											<input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
											<button type="submit" style="background-color: #ff3366; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%; font-size: 0.8rem;">
												🗑️ Delete Report
											</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('SEC/TAC') || hasPermission('Command')): ?>
					<!-- Department Training Section -->
					<?php renderDepartmentTrainingSection('Security', 'Security & Tactical'); ?>
					<?php endif; ?>
					
					<!-- Promotion/Demotion Form -->
					<?php renderPromotionForm('SEC/TAC'); ?>
					
					<div style="background: rgba(255, 170, 0, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0;">
						<h4>Security Information</h4>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
							<div>
								<strong style="color: var(--gold);">Alert Status:</strong> Green<br>
								<strong style="color: var(--gold);">Shield Status:</strong> Up<br>
								<strong style="color: var(--gold);">Weapons:</strong> Ready
							</div>
							<div>
								<strong style="color: var(--gold);">Security Teams:</strong> 4 Active<br>
								<strong style="color: var(--gold);">Patrols:</strong> Deck 1-15<br>
								<strong style="color: var(--gold);">Armory:</strong> Secured
							</div>
							<div>
								<strong style="color: var(--gold);">Brig:</strong> Deck 7<br>
								<strong style="color: var(--gold);">Security Office:</strong> Deck 7<br>
								<strong style="color: var(--gold);">Tactical:</strong> Bridge
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
