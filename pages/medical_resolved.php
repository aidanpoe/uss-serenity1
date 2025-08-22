<?php
require_once '../includes/config.php';

// Check if user has medical department access or is Captain
if (!hasPermission('MED/SCI') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get resolved medical records
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               CONCAT(r.rank, ' ', r.first_name, ' ', r.last_name) as patient_name,
               r.department as patient_department
        FROM medical_records mr 
        JOIN roster r ON mr.roster_id = r.id 
        WHERE mr.status = 'Resolved'
        ORDER BY mr.updated_at DESC
    ");
    $stmt->execute();
    $resolved_records = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Resolved Medical Cases</title>
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
				<div class="banner">USS-SERENITY &#149; RESOLVED MEDICAL CASES</div>
				<div class="data-cascade-button-group">
					<div class="data-cascade-wrapper" id="default">
						<div class="data-column">
							<div class="dc-row-1">MED</div>
							<div class="dc-row-1">RESOLVED</div>
							<div class="dc-row-2">CASES</div>
							<div class="dc-row-3">ARCHIVE</div>
							<div class="dc-row-3">SYSTEM</div>
							<div class="dc-row-4">STATUS</div>
							<div class="dc-row-5">ACTIVE</div>
							<div class="dc-row-6">ACCESS</div>
							<div class="dc-row-7">GRANTED</div>
						</div>
					</div>				
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', 'med_sci.php')" style="background-color: var(--blue);">ACTIVE CASES</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red);">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'reports.php')" style="background-color: var(--orange);">REPORTS</button>
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
					<div class="panel-3">MED<span class="hop">-ARCHIVE</span></div>
					<div class="panel-4">CASE<span class="hop">-RESOLVED</span></div>
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
					<h1>Resolved Medical Cases Archive</h1>
					<h2>Medical Department &#149; Case History</h2>
					
					<?php if (isset($error)): ?>
						<div style="background: rgba(204, 68, 68, 0.3); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid var(--red);">
							<h4 style="color: var(--red);">Error</h4>
							<p><?php echo htmlspecialchars($error); ?></p>
						</div>
					<?php endif; ?>

					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Resolved Medical Cases (<?php echo count($resolved_records); ?> total)</h3>
						
						<?php if (empty($resolved_records)): ?>
							<p style="color: var(--orange);">No resolved medical cases found.</p>
						<?php else: ?>
							<div style="overflow-x: auto;">
								<table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
									<thead>
										<tr style="background: rgba(85, 102, 255, 0.3);">
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--blue);">Patient</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--blue);">Department</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--blue);">Condition</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--blue);">Treatment</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--blue);">Reported</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--blue);">Resolved</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($resolved_records as $record): ?>
										<tr style="border-bottom: 1px solid var(--blue);">
											<td style="padding: 1rem; border: 1px solid var(--blue);">
												<strong style="color: var(--bluey);"><?php echo htmlspecialchars($record['patient_name']); ?></strong>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--blue);">
												<span style="color: var(--orange);"><?php echo htmlspecialchars($record['patient_department']); ?></span>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--blue);">
												<div style="color: var(--red); font-size: 0.9rem; margin-bottom: 0.5rem;">Condition:</div>
												<div><?php echo htmlspecialchars($record['condition_description']); ?></div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--blue);">
												<div style="color: var(--blue); font-size: 0.9rem; margin-bottom: 0.5rem;">Treatment:</div>
												<div><?php echo htmlspecialchars($record['treatment'] ?? 'No treatment recorded'); ?></div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--blue);">
												<div style="color: var(--orange); font-size: 0.8rem;">
													<?php echo formatICDateTime($record['created_at']); ?>
												</div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--blue);">
												<div style="color: var(--green); font-size: 0.8rem;">
													<?php echo formatICDateTime($record['updated_at']); ?>
												</div>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					Medical Archive System - Authorized Personnel Only			 		 
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
