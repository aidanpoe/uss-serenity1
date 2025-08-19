<?php
require_once '../includes/config.php';

// Handle fault report submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'fault_report') {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO fault_reports (location_type, deck_number, room, jefferies_tube_number, access_point, fault_description, reported_by_roster_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['location_type'],
            $_POST['deck_number'] ?? null,
            $_POST['room'] ?? null,
            $_POST['jefferies_tube_number'] ?? null,
            $_POST['access_point'] ?? null,
            $_POST['fault_description'],
            $_POST['reported_by_roster_id'] ?? null
        ]);
        $success = "Fault report submitted successfully.";
    } catch (Exception $e) {
        $error = "Error submitting report: " . $e->getMessage();
    }
}

// Handle fault resolution (backend only)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'resolve_fault') {
    if (hasPermission('ENG/OPS')) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE fault_reports SET status = ?, resolution_description = ? WHERE id = ?");
            $stmt->execute([
                $_POST['status'],
                $_POST['resolution_description'],
                $_POST['fault_id']
            ]);
            $success = "Fault report updated successfully.";
        } catch (Exception $e) {
            $error = "Error updating fault: " . $e->getMessage();
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get roster for dropdown
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, rank FROM roster ORDER BY last_name, first_name");
    $stmt->execute();
    $roster = $stmt->fetchAll();
    
    // Get department heads
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Head of ENG/OPS', 'Chief Engineer', 'Operations Officer', 'Helm Officer') ORDER BY position");
    $stmt->execute();
    $dept_heads = $stmt->fetchAll();
    
    // Get fault reports for backend
    if (hasPermission('ENG/OPS')) {
        $stmt = $pdo->prepare("
            SELECT fr.*, r.first_name, r.last_name, r.rank 
            FROM fault_reports fr 
            LEFT JOIN roster r ON fr.reported_by_roster_id = r.id 
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
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--orange); margin: 2rem 0;">
						<h4>System Fault Report</h4>
						<form method="POST" action="" id="faultForm">
							<input type="hidden" name="action" value="fault_report">
							
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--orange);">Location Type:</label>
									<select name="location_type" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);" onchange="toggleLocationFields()">
										<option value="">Select Location Type</option>
										<option value="Deck">Deck/Room</option>
										<option value="Hull">Hull</option>
										<option value="Jefferies Tube">Jefferies Tube</option>
									</select>
								</div>
								<div>
									<label style="color: var(--orange);">Reported By:</label>
									<select name="reported_by_roster_id" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
										<option value="">Select Person (Optional)</option>
										<?php foreach ($roster as $person): ?>
										<option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							
							<div id="deckFields" style="display: none; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--orange);">Deck Number:</label>
									<input type="number" name="deck_number" min="1" max="15" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
								</div>
								<div>
									<label style="color: var(--orange);">Room/Section:</label>
									<input type="text" name="room" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
								</div>
							</div>
							
							<div id="jefferiesFields" style="display: none; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--orange);">Jefferies Tube Number:</label>
									<input type="text" name="jefferies_tube_number" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
								</div>
								<div>
									<label style="color: var(--orange);">Closest Access Point:</label>
									<input type="text" name="access_point" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
								</div>
							</div>
							
							<div style="margin-bottom: 1rem;">
								<label style="color: var(--orange);">Fault Description:</label>
								<textarea name="fault_description" required rows="4" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);" placeholder="Describe the fault, symptoms, and any relevant details..."></textarea>
							</div>
							
							<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; width: 100%;">Submit Fault Report</button>
						</form>
					</div>
					
					<?php if (hasPermission('ENG/OPS')): ?>
					<!-- Engineering Staff Backend -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--orange);">
						<h3>Engineering Work Orders</h3>
						<p style="color: var(--orange);"><em>Engineering/Operations Staff Access Only</em></p>
						
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
										<small>Reported: <?php echo date('Y-m-d H:i', strtotime($fault['created_at'])); ?></small><br>
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
											<select name="status" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--orange); margin-bottom: 0.5rem;">
												<option value="Open" <?php echo $fault['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
												<option value="In Progress" <?php echo $fault['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
												<option value="Resolved" <?php echo $fault['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
											</select>
											<textarea name="resolution_description" placeholder="Resolution details..." rows="3" style="width: 100%; padding: 0.25rem; background: black; color: white; border: 1px solid var(--orange); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($fault['resolution_description'] ?? ''); ?></textarea>
											<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; width: 100%;">Update</button>
										</form>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
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
			const deckFields = document.getElementById('deckFields');
			const jefferiesFields = document.getElementById('jefferiesFields');
			
			// Hide all fields first
			deckFields.style.display = 'none';
			jefferiesFields.style.display = 'none';
			
			// Show relevant fields
			if (locationType === 'Deck') {
				deckFields.style.display = 'grid';
			} else if (locationType === 'Jefferies Tube') {
				jefferiesFields.style.display = 'grid';
			}
		}
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
