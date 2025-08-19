<?php
require_once '../includes/config.php';

// Check if user has engineering department access or is Captain
if (!hasPermission('ENG/OPS') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get resolved engineering faults
    $stmt = $pdo->prepare("
        SELECT fr.*, 
               CONCAT(r.rank, ' ', r.first_name, ' ', r.last_name) as reported_by_name,
               r.department as reporter_department
        FROM fault_reports fr 
        LEFT JOIN roster r ON fr.reported_by_roster_id = r.id 
        WHERE fr.status = 'Resolved'
        ORDER BY fr.updated_at DESC
    ");
    $stmt->execute();
    $resolved_faults = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Resolved Engineering Faults</title>
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
				<div class="banner">USS-SERENITY &#149; RESOLVED ENGINEERING FAULTS</div>
				<div class="data-cascade-button-group">
					<div class="data-cascade-wrapper" id="default">
						<div class="data-column">
							<div class="dc-row-1">ENG</div>
							<div class="dc-row-1">RESOLVED</div>
							<div class="dc-row-2">FAULTS</div>
							<div class="dc-row-3">ARCHIVE</div>
							<div class="dc-row-3">SYSTEM</div>
							<div class="dc-row-4">STATUS</div>
							<div class="dc-row-5">ACTIVE</div>
							<div class="dc-row-6">ACCESS</div>
							<div class="dc-row-7">GRANTED</div>
						</div>
					</div>				
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', 'eng_ops.php')" style="background-color: var(--orange);">ACTIVE FAULTS</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red);">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'reports.php')" style="background-color: var(--blue);">REPORTS</button>
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
					<div class="panel-3">ENG<span class="hop">-ARCHIVE</span></div>
					<div class="panel-4">FAULT<span class="hop">-RESOLVED</span></div>
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
					<h1>Resolved Engineering Faults Archive</h1>
					<h2>Engineering Department &#149; Fault History</h2>
					
					<?php if (isset($error)): ?>
						<div style="background: rgba(204, 68, 68, 0.3); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid var(--red);">
							<h4 style="color: var(--red);">Error</h4>
							<p><?php echo htmlspecialchars($error); ?></p>
						</div>
					<?php endif; ?>

					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Resolved Engineering Faults (<?php echo count($resolved_faults); ?> total)</h3>
						
						<?php if (empty($resolved_faults)): ?>
							<p style="color: var(--orange);">No resolved engineering faults found.</p>
						<?php else: ?>
							<div style="overflow-x: auto;">
								<table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
									<thead>
										<tr style="background: rgba(255, 136, 0, 0.3);">
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--orange);">Location</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--orange);">Fault Description</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--orange);">Resolution</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--orange);">Reported By</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--orange);">Reported</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--orange);">Resolved</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($resolved_faults as $fault): ?>
										<tr style="border-bottom: 1px solid var(--orange);">
											<td style="padding: 1rem; border: 1px solid var(--orange);">
												<strong style="color: var(--bluey);">
													<?php 
													if ($fault['location_type'] === 'Deck') {
														echo "Deck " . htmlspecialchars($fault['deck_number']) . " - " . htmlspecialchars($fault['room']);
													} elseif ($fault['location_type'] === 'Hull') {
														echo "Hull Section";
													} elseif ($fault['location_type'] === 'Jefferies Tube') {
														echo "Jefferies Tube " . htmlspecialchars($fault['jefferies_tube_number']);
													}
													?>
												</strong>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--orange);">
												<div style="color: var(--red); font-size: 0.9rem; margin-bottom: 0.5rem;">Fault:</div>
												<div><?php echo htmlspecialchars($fault['fault_description']); ?></div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--orange);">
												<div style="color: var(--green); font-size: 0.9rem; margin-bottom: 0.5rem;">Resolution:</div>
												<div><?php echo htmlspecialchars($fault['resolution_description'] ?? 'No resolution details recorded'); ?></div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--orange);">
												<span style="color: var(--bluey);"><?php echo htmlspecialchars($fault['reported_by_name'] ?? 'Unknown'); ?></span>
												<?php if ($fault['reporter_department']): ?>
													<br><span style="color: var(--orange); font-size: 0.8rem;"><?php echo htmlspecialchars($fault['reporter_department']); ?></span>
												<?php endif; ?>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--orange);">
												<div style="color: var(--orange); font-size: 0.8rem;">
													<?php echo date('Y-m-d H:i', strtotime($fault['created_at'])); ?>
												</div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--orange);">
												<div style="color: var(--green); font-size: 0.8rem;">
													<?php echo date('Y-m-d H:i', strtotime($fault['updated_at'])); ?>
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
					Engineering Archive System - Authorized Personnel Only			 		 
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
