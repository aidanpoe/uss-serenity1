<?php
require_once '../includes/config.php';
require_once '../includes/department_training.php';
require_once '../includes/promotion_system.php';

// Update last active timestamp for current character
updateLastActive();

// Handle department training if user has permission
if (hasPermission('MED/SCI') || hasPermission('Command')) {
    handleDepartmentTraining('Medical');
}

// Handle adding new patients (MED/SCI only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_patient') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('MED/SCI')) {
        try {
            // Check if person already exists (by name only, species can be shared)
            $pdo = getConnection();
            $check_stmt = $pdo->prepare("SELECT id FROM roster WHERE first_name = ? AND last_name = ?");
            $check_stmt->execute([sanitizeInput($_POST['first_name']), sanitizeInput($_POST['last_name'])]);
            
            if ($check_stmt->fetch()) {
                $error = "A crew member with this name already exists in the roster.";
            } else {
                // MED/SCI limited to ranks below Commander for patient addition
                $allowed_patient_ranks = [
                    'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
                    'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petter Officer',
                    'Senior Chief Petty Officer', 'Master Chief Petty Officer', 
                    'Command Master Chief Petty Officer', 'Warrant officer', 'Ensign',
                    'Lieutenant Junior Grade', 'Lieutenant', 'Lieutenant Commander'
                ];
                
                if (!in_array($_POST['rank'], $allowed_patient_ranks)) {
                    $error = "Medical personnel can only add patients with ranks below Commander.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, position, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        sanitizeInput($_POST['rank']),
                        sanitizeInput($_POST['first_name']),
                        sanitizeInput($_POST['last_name']),
                        sanitizeInput($_POST['species']),
                        sanitizeInput($_POST['department']),
                        '', // No special positions for patient addition
                        '' // No image upload in this simplified form
                    ]);
                    $success = "New patient added to roster successfully.";
                }
            }
        } catch (Exception $e) {
            error_log("Error adding patient: " . $e->getMessage());
            $error = "Error adding patient. Please try again.";
        }
    } else {
        $error = "Access denied. Medical/Science authorization required.";
    }
}

// Handle medical report submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'medical_report') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (!isLoggedIn()) {
        $error = "You must be logged in to submit medical reports.";
    } else {
        try {
            // Auto-populate reported_by with current user's character
            $reported_by = sanitizeInput(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO medical_records (roster_id, condition_description, reported_by, updated_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                filter_var($_POST['roster_id'], FILTER_VALIDATE_INT),
                sanitizeInput($_POST['condition_description']),
                $reported_by,
                getCurrentUserFullName()
            ]);
            $success = "Medical report submitted successfully.";
        } catch (Exception $e) {
            error_log("Error submitting medical report: " . $e->getMessage());
            $error = "Error submitting report. Please try again.";
        }
    }
}

// Handle science report submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'science_report') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (!isLoggedIn()) {
        $error = "You must be logged in to submit science reports.";
    } else {
        try {
            // Auto-populate reported_by with current user's character
            $reported_by = sanitizeInput(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO science_reports (title, description, reported_by) VALUES (?, ?, ?)");
            $stmt->execute([
                sanitizeInput($_POST['title']),
                sanitizeInput($_POST['description']),
                $reported_by
            ]);
            $success = "Science report submitted successfully.";
        } catch (Exception $e) {
            error_log("Error submitting science report: " . $e->getMessage());
            $error = "Error submitting report. Please try again.";
        }
    }
}

// Handle medical record update (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_medical') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('MED/SCI')) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE medical_records SET status = ?, treatment = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([
                sanitizeInput($_POST['status']),
                sanitizeInput($_POST['treatment']),
                getCurrentUserFullName(),
                filter_var($_POST['record_id'], FILTER_VALIDATE_INT)
            ]);
            $success = "Medical record updated successfully.";
        } catch (Exception $e) {
            error_log("Error updating medical record: " . $e->getMessage());
            $error = "Error updating record. Please try again.";
        }
    }
}

// Handle medical record deletion (Command or Starfleet Auditor only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_medical_record') {
    $roster_dept = $_SESSION['roster_department'] ?? '';
    if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor') {
        try {
            $pdo = getConnection();
            
            // Get record details for logging
            $stmt = $pdo->prepare("SELECT mr.*, r.first_name, r.last_name FROM medical_records mr JOIN roster r ON mr.roster_id = r.id WHERE mr.id = ?");
            $stmt->execute([$_POST['record_id']]);
            $record = $stmt->fetch();
            
            if ($record) {
                // Delete the record
                $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ?");
                $stmt->execute([$_POST['record_id']]);
                
                // Log the action for auditing (both Command and Starfleet Auditors)
                if (isset($_SESSION['character_id']) && (hasPermission('Command') || $roster_dept === 'Starfleet Auditor')) {
                    logAuditorAction($_SESSION['character_id'], 'delete_medical_record', 'medical_records', $record['id'], [
                        'patient_name' => $record['first_name'] . ' ' . $record['last_name'],
                        'condition' => $record['condition_description'],
                        'status' => $record['status'],
                        'user_type' => $roster_dept === 'Starfleet Auditor' ? 'Starfleet Auditor' : 'Command Staff'
                    ]);
                }
                
                $success = "Medical record deleted successfully.";
            } else {
                $error = "Medical record not found.";
            }
        } catch (Exception $e) {
            $error = "Error deleting medical record: " . $e->getMessage();
        }
    } else {
        $error = "Only Command staff and Starfleet Auditors can delete medical records.";
    }
}

// Handle science report deletion (Command or Starfleet Auditor only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_science_report') {
    $roster_dept = $_SESSION['roster_department'] ?? '';
    if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor') {
        try {
            $pdo = getConnection();
            
            // Get report details for logging
            $stmt = $pdo->prepare("SELECT * FROM science_reports WHERE id = ?");
            $stmt->execute([$_POST['report_id']]);
            $report = $stmt->fetch();
            
            if ($report) {
                // Delete the report
                $stmt = $pdo->prepare("DELETE FROM science_reports WHERE id = ?");
                $stmt->execute([$_POST['report_id']]);
                
                // Log the action for auditing (both Command and Starfleet Auditors)
                if (isset($_SESSION['character_id']) && (hasPermission('Command') || $roster_dept === 'Starfleet Auditor')) {
                    logAuditorAction($_SESSION['character_id'], 'delete_science_report', 'science_reports', $report['id'], [
                        'title' => $report['title'],
                        'reported_by' => $report['reported_by'],
                        'status' => $report['status'],
                        'user_type' => $roster_dept === 'Starfleet Auditor' ? 'Starfleet Auditor' : 'Command Staff'
                    ]);
                }
                
                $success = "Science report deleted successfully.";
            } else {
                $error = "Science report not found.";
            }
        } catch (Exception $e) {
            $error = "Error deleting science report: " . $e->getMessage();
        }
    } else {
        $error = "Only Command staff and Starfleet Auditors can delete science reports.";
    }
}

try {
    $pdo = getConnection();
    
    // Get roster for dropdown
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, rank FROM roster WHERE (is_invisible IS NULL OR is_invisible = 0) ORDER BY last_name, first_name");
    $stmt->execute();
    $roster = $stmt->fetchAll();
    
    // Get department heads
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Head of MED/SCI', 'Chief Medical Officer', 'Chief Science Officer') AND (is_invisible IS NULL OR is_invisible = 0) ORDER BY position");
    $stmt->execute();
    $dept_heads = $stmt->fetchAll();
    
    // Get medical records for backend (excluding resolved cases)
    if (hasPermission('MED/SCI')) {
        $stmt = $pdo->prepare("
            SELECT mr.*, r.first_name, r.last_name, r.rank 
            FROM medical_records mr 
            JOIN roster r ON mr.roster_id = r.id 
            WHERE mr.status != 'Resolved'
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute();
        $medical_records = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM science_reports ORDER BY created_at DESC");
        $stmt->execute();
        $science_reports = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Medical/Science</title>
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
				<div class="panel-2">MED<span class="hop">-SCI</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">MEDICAL &#149; SCIENCE</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--blue);">MED/SCI</button>
						<button onclick="playSoundAndRedirect('audio2', 'medical_patients.php')">PATIENTS</button>
						<button onclick="playSoundAndRedirect('audio2', 'medical_resolved.php')">RESOLVED</button>
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
					<div class="panel-3">MED<span class="hop">-BAY</span></div>
					<div class="panel-4">SCI<span class="hop">-LAB</span></div>
					<div class="panel-5">SICK<span class="hop">-BAY</span></div>
					<div class="panel-6">RESEARCH<span class="hop">-CTR</span></div>
					<div class="panel-7">BIOBED<span class="hop">-5</span></div>
					<div class="panel-8">TRICORD<span class="hop">-ON</span></div>
					<div class="panel-9">SCAN<span class="hop">-ACTV</span></div>
				</div>
				<div>
					<div class="panel-10">HLTH<span class="hop">-MON</span></div>
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
					<h1>Medical & Science Department</h1>
					<h2>USS-Serenity Health Services & Research</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--blue);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Medical Tools Quick Access -->
					<div style="background: rgba(85, 102, 255, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
						<h3 style="color: var(--blue); text-align: center; margin-bottom: 1.5rem;">🏥 Medical Department Tools</h3>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'medical_patients.php')" style="background-color: var(--blue); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: bold;">
								🔍 Patient Search<br><small style="font-weight: normal;">Search & filter crew medical records</small>
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'medical_resolved.php')" style="background-color: var(--green); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: bold;">
								✅ Resolved Cases<br><small style="font-weight: normal;">View completed medical cases</small>
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--orange); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1rem; font-weight: bold;">
								👥 Full Roster<br><small style="font-weight: normal;">Complete crew manifest</small>
							</button>
						</div>
					</div>
					
					<!-- Add New Patient Form (MED/SCI Only) -->
					<?php if (hasPermission('MED/SCI')): ?>
					<div style="background: rgba(85, 102, 255, 0.1); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
						<h3 style="color: var(--blue); text-align: center; margin-bottom: 1.5rem;">🏥 Add New Patient</h3>
						<form method="POST" action="">
							<input type="hidden" name="action" value="add_patient">
							<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
								<div>
									<label style="color: var(--blue);">Rank:</label>
									<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
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
									<label style="color: var(--blue);">First Name:</label>
									<input type="text" name="first_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
								</div>
								<div>
									<label style="color: var(--blue);">Last Name:</label>
									<input type="text" name="last_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
								</div>
								<div>
									<label style="color: var(--blue);">Species:</label>
									<input type="text" name="species" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
								</div>
								<div>
									<label style="color: var(--blue);">Department:</label>
									<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
										<option value="Command">Command</option>
										<option value="MED/SCI">MED/SCI</option>
										<option value="ENG/OPS">ENG/OPS</option>
										<option value="SEC/TAC">SEC/TAC</option>
									</select>
								</div>
							</div>
							<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem; width: 100%;">Add New Patient to Roster</button>
						</form>
						<p style="color: var(--blue); font-size: 0.9rem; text-align: center; margin-top: 1rem;">
							<em>Medical personnel can add new patients to the ship's roster for medical tracking purposes.</em>
						</p>
					</div>
					<?php endif; ?>
					
					<h3>Department Leadership</h3>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php foreach ($dept_heads as $head): ?>
						<div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--blue);">
							<h4><?php echo htmlspecialchars($head['rank'] . ' ' . $head['first_name'] . ' ' . $head['last_name']); ?></h4>
							<p><?php echo htmlspecialchars($head['position']); ?></p>
						</div>
						<?php endforeach; ?>
					</div>
					
					<!-- Public Forms -->
					<?php if (isLoggedIn()): ?>
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
						<!-- Medical Report Form -->
						<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--blue);">
							<h4>Medical Report</h4>
							<form method="POST" action="">
								<input type="hidden" name="action" value="medical_report">
								<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
								<div style="margin-bottom: 1rem;">
									<label style="color: var(--blue);">Patient:</label>
									<select name="roster_id" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
										<option value="">Select Patient</option>
										<?php foreach ($roster as $person): ?>
										<option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?></option>
										<?php endforeach; ?>
									</select>
									<small style="color: var(--orange);"><a href="roster.php" style="color: var(--orange);">Not in the roster? Click here to add yourself first.</a></small>
								</div>
								<div style="margin-bottom: 1rem;">
									<label style="color: var(--blue);">Medical Condition/Issue:</label>
									<textarea name="condition_description" required rows="4" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);"></textarea>
								</div>
								<div style="margin-bottom: 1rem;">
									<label style="color: var(--blue);">Reported By:</label>
									<?php 
									$current_user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
									?>
									<input type="text" value="<?php echo htmlspecialchars($current_user); ?>" readonly style="width: 100%; padding: 0.5rem; background: #333; color: var(--blue); border: 1px solid var(--blue); cursor: not-allowed;">
									<small style="color: var(--blue); font-size: 0.8rem;">Auto-filled from your current character profile</small>
								</div>
								<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 1rem; border-radius: 5px; width: 100%;">Submit Medical Report</button>
							</form>
						</div>
						
						<!-- Science Report Form -->
						<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--ice);">
							<h4>Science Inquiry</h4>
							<form method="POST" action="">
								<input type="hidden" name="action" value="science_report">
								<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
								<div style="margin-bottom: 1rem;">
									<label style="color: var(--ice);">Research Title:</label>
									<input type="text" name="title" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--ice);">
								</div>
								<div style="margin-bottom: 1rem;">
									<label style="color: var(--ice);">Description/Inquiry:</label>
									<textarea name="description" required rows="4" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--ice);"></textarea>
								</div>
								<div style="margin-bottom: 1rem;">
									<label style="color: var(--ice);">Reported By:</label>
									<?php 
									$current_user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
									?>
									<input type="text" value="<?php echo htmlspecialchars($current_user); ?>" readonly style="width: 100%; padding: 0.5rem; background: #333; color: var(--ice); border: 1px solid var(--ice); cursor: not-allowed;">
									<small style="color: var(--ice); font-size: 0.8rem;">Auto-filled from your current character profile</small>
								</div>
								<button type="submit" style="background-color: var(--ice); color: black; border: none; padding: 1rem; border-radius: 5px; width: 100%;">Submit Science Inquiry</button>
							</form>
						</div>
					</div>
					<?php else: ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--blue); margin: 2rem 0;">
						<h4>Medical & Science Reporting</h4>
						<p style="color: var(--blue); text-align: center;">You must be logged in to submit medical reports or science inquiries.</p>
						<div style="text-align: center; margin-top: 1rem;">
							<a href="../index.php" style="background-color: var(--blue); color: black; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">Return to Login</a>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('MED/SCI')): ?>
					<!-- Medical Staff Backend -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
						<h3>Medical Records Management</h3>
						<p style="color: var(--blue);"><em>Medical/Science Staff Access Only</em></p>
						
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
							<h4>Open Medical Cases</h4>
							<button onclick="playSoundAndRedirect('audio2', 'medical_resolved.php')" style="background-color: var(--blue); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem;">
								View Resolved Cases
							</button>
						</div>
						<div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--blue); border-radius: 5px; padding: 1rem; margin: 1rem 0; background: rgba(0,0,0,0.3);">
							<?php foreach ($medical_records as $record): ?>
							<div style="border-bottom: 1px solid var(--gray); padding: 1rem 0;">
								<div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
									<div>
										<strong><?php echo htmlspecialchars($record['rank'] . ' ' . $record['first_name'] . ' ' . $record['last_name']); ?></strong><br>
										<small>Reported: <?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></small><br>
										<small>By: <?php echo htmlspecialchars($record['reported_by']); ?></small>
									</div>
									<div>
										<strong>Condition:</strong><br>
										<?php echo htmlspecialchars($record['condition_description']); ?>
										<?php if ($record['treatment']): ?>
										<br><br><strong>Treatment:</strong><br>
										<?php echo htmlspecialchars($record['treatment']); ?>
										<?php endif; ?>
									</div>
									<div>
										<form method="POST" action="" style="display: inline;">
											<input type="hidden" name="action" value="update_medical">
											<input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<select name="status" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--blue); margin-bottom: 0.5rem;">
												<option value="Open" <?php echo $record['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
												<option value="In Progress" <?php echo $record['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
												<option value="Resolved" <?php echo $record['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
											</select>
											<textarea name="treatment" placeholder="Treatment notes..." rows="2" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--blue); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($record['treatment'] ?? ''); ?></textarea>
											<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%; margin-bottom: 0.5rem;">Update</button>
										</form>
										
										<?php 
										$roster_dept = $_SESSION['roster_department'] ?? '';
										if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor'): ?>
										<form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this medical record? This action cannot be undone.');">
											<input type="hidden" name="action" value="delete_medical_record">
											<input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<button type="submit" style="background-color: var(--red); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%; font-size: 0.8rem;">🗑️ Delete Record</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
						
						<h4>Science Research Database</h4>
						<div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--ice); border-radius: 5px; padding: 1rem; margin: 1rem 0; background: rgba(0,0,0,0.3);">
							<?php foreach ($science_reports as $report): ?>
							<div style="border-bottom: 1px solid var(--gray); padding: 1rem 0;">
								<div style="display: flex; justify-content: space-between; align-items: flex-start;">
									<div style="flex-grow: 1;">
										<h5 style="color: var(--ice);"><?php echo htmlspecialchars($report['title']); ?></h5>
										<p><?php echo htmlspecialchars($report['description']); ?></p>
										<small>Submitted by: <?php echo htmlspecialchars($report['reported_by']); ?> on <?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></small>
										<div style="margin-top: 0.5rem;">
											<span style="background: var(--<?php echo strtolower(str_replace(' ', '-', $report['status'])); ?>); color: black; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">
												<?php echo htmlspecialchars($report['status']); ?>
											</span>
										</div>
									</div>
									
									<?php 
									$roster_dept = $_SESSION['roster_department'] ?? '';
									if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor'): ?>
									<div style="margin-left: 1rem;">
										<form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this science report? This action cannot be undone.');">
											<input type="hidden" name="action" value="delete_science_report">
											<input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<button type="submit" style="background-color: var(--red); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">🗑️ Delete</button>
										</form>
									</div>
									<?php endif; ?>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if (hasPermission('MED/SCI') || hasPermission('Command')): ?>
					<!-- Department Training Section -->
					<?php renderDepartmentTrainingSection('Medical', 'Medical & Science'); ?>
					<?php endif; ?>
					
					<!-- Promotion/Demotion Form -->
					<?php renderPromotionForm('MED/SCI'); ?>
					
					<div style="background: rgba(85, 102, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0;">
						<h4>Department Information</h4>
						<ul style="color: var(--blue);">
							<li>Sickbay: Deck 6</li>
							<li>Science Labs: Decks 2 and Deck 5</li>
							<li>Medical Emergency: Contact Bridge & Medical staff on duty</li>
							<li>Research Requests: Submit via Science Inquiry form</li>
						</ul>
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
