<?php
require_once '../includes/config.php';

// Check if user has medical department access or is Captain
if (!hasPermission('MED/SCI') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

$crew_id = $_GET['id'] ?? null;
if (!$crew_id) {
    header('Location: roster.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get crew member details
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE id = ?");
    $stmt->execute([$crew_id]);
    $crew_member = $stmt->fetch();
    
    if (!$crew_member) {
        header('Location: roster.php');
        exit();
    }
    
    // Get all medical records for this crew member
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               u.first_name as reported_by_first, 
               u.last_name as reported_by_last
        FROM medical_records mr 
        LEFT JOIN users u ON mr.reported_by = u.id
        WHERE mr.roster_id = ? 
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$crew_id]);
    $medical_history = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Medical History</title>
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
				<div class="panel-2">74<span class="hop">-714000</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">USS-SERENITY &#149; MEDICAL HISTORY</div>
				<div class="data-cascade-button-group">
					<div class="data-cascade-wrapper" id="default">
						<div class="data-column">
							<div class="dc-row-1">MED</div>
							<div class="dc-row-1">HISTORY</div>
							<div class="dc-row-2">PATIENT</div>
							<div class="dc-row-3">RECORDS</div>
							<div class="dc-row-3">ACCESS</div>
							<div class="dc-row-4">STATUS</div>
							<div class="dc-row-5">ACTIVE</div>
							<div class="dc-row-6">SECURE</div>
							<div class="dc-row-7">MODE</div>
						</div>
					</div>				
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red);">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'med_sci.php')" style="background-color: var(--blue);">MEDICAL</button>
						<button onclick="playSoundAndRedirect('audio2', 'medical_resolved.php')" style="background-color: var(--green);">RESOLVED</button>
						<button onclick="playSoundAndRedirect('audio2', '../index.php')" style="background-color: var(--african-violet);">MAIN</button>
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
					<div class="panel-3">MED<span class="hop">-HISTORY</span></div>
					<div class="panel-4">PATIENT<span class="hop">-FILE</span></div>
					<div class="panel-5">ACCESS<span class="hop">-GRANTED</span></div>
					<div class="panel-6">STATUS<span class="hop">-ACTIVE</span></div>
					<div class="panel-7">SEC<span class="hop">-GREEN</span></div>
					<div class="panel-8">MED<span class="hop">-READY</span></div>
					<div class="panel-9">ENG<span class="hop">-NOMINAL</span></div>
				</div>
				<div>
					<div class="panel-10">LCARS<span class="hop">-24.1</span></div>
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
					<h1>Medical History - <?php echo htmlspecialchars($crew_member['rank'] . ' ' . $crew_member['first_name'] . ' ' . $crew_member['last_name']); ?></h1>
					<h2>Medical Department &#149; Patient Records</h2>
					
					<?php if (isset($error)): ?>
						<div style="background: rgba(204, 68, 68, 0.3); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid var(--red);">
							<h4 style="color: var(--red);">Error</h4>
							<p><?php echo htmlspecialchars($error); ?></p>
						</div>
					<?php endif; ?>

					<!-- Patient Information -->
					<div style="background: rgba(85, 102, 255, 0.2); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
						<h3>Patient Information</h3>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
							<div>
								<strong style="color: var(--blue);">Name:</strong><br>
								<?php echo htmlspecialchars($crew_member['rank'] . ' ' . $crew_member['first_name'] . ' ' . $crew_member['last_name']); ?>
							</div>
							<div>
								<strong style="color: var(--blue);">Species:</strong><br>
								<?php echo htmlspecialchars($crew_member['species']); ?>
							</div>
							<div>
								<strong style="color: var(--blue);">Department:</strong><br>
								<span style="color: var(--orange);"><?php echo htmlspecialchars($crew_member['department']); ?></span>
							</div>
							<div>
								<strong style="color: var(--blue);">Position:</strong><br>
								<?php echo htmlspecialchars($crew_member['position'] ?? 'Not Assigned'); ?>
							</div>
						</div>
						<?php if ($crew_member['image_path']): ?>
						<div style="margin-top: 1rem;">
							<img src="../<?php echo htmlspecialchars($crew_member['image_path']); ?>" 
								 alt="<?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name']); ?>" 
								 style="max-width: 150px; height: auto; border-radius: 10px; border: 2px solid var(--blue);">
						</div>
						<?php endif; ?>
					</div>

					<!-- Medical History -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Complete Medical History (<?php echo count($medical_history); ?> records)</h3>
						
						<?php if (empty($medical_history)): ?>
							<p style="color: var(--green);">No medical records found for this crew member. Clean medical history.</p>
						<?php else: ?>
							<?php foreach ($medical_history as $record): ?>
							<div style="background: rgba(85, 102, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin: 1rem 0; border-left: 4px solid <?php echo $record['status'] === 'Resolved' ? 'var(--green)' : 'var(--red)'; ?>;">
								<div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
									<div>
										<div style="color: var(--blue); font-weight: bold; margin-bottom: 0.5rem;">
											Medical Record - <?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?>
										</div>
										
										<div style="margin-bottom: 1rem;">
											<strong style="color: var(--red);">Condition/Symptoms:</strong><br>
											<?php echo htmlspecialchars($record['condition_description']); ?>
										</div>
										
										<?php if ($record['treatment']): ?>
										<div style="margin-bottom: 1rem;">
											<strong style="color: var(--green);">Treatment:</strong><br>
											<?php echo htmlspecialchars($record['treatment']); ?>
										</div>
										<?php endif; ?>
										
										<?php if ($record['reported_by_first']): ?>
										<div style="margin-bottom: 0.5rem;">
											<strong style="color: var(--orange);">Reported by:</strong>
											<?php echo htmlspecialchars($record['reported_by_first'] . ' ' . $record['reported_by_last']); ?>
										</div>
										<?php endif; ?>
										
										<?php if ($record['updated_at'] !== $record['created_at']): ?>
										<div style="color: var(--orange); font-size: 0.9rem;">
											Last updated: <?php echo date('Y-m-d H:i', strtotime($record['updated_at'])); ?>
										</div>
										<?php endif; ?>
									</div>
									
									<div style="text-align: right;">
										<div style="background: <?php echo $record['status'] === 'Resolved' ? 'var(--green)' : 'var(--red)'; ?>; color: black; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold;">
											<?php echo htmlspecialchars($record['status']); ?>
										</div>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<div style="text-align: center; margin: 2rem 0;">
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; font-size: 1.1rem;">
							Return to Roster
						</button>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					Medical Records System - Authorized Personnel Only			 		 
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
