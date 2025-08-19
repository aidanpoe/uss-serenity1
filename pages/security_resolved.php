<?php
require_once '../includes/config.php';

// Check if user has security department access or is Captain
if (!hasPermission('SEC/TAC') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get resolved security reports
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               CASE 
                   WHEN r.first_name IS NOT NULL THEN CONCAT(r.rank, ' ', r.first_name, ' ', r.last_name)
                   ELSE 'N/A'
               END as involved_person,
               r.department as involved_department
        FROM security_reports sr 
        LEFT JOIN roster r ON sr.involved_roster_id = r.id 
        WHERE sr.status = 'Resolved'
        ORDER BY sr.updated_at DESC
    ");
    $stmt->execute();
    $resolved_reports = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Resolved Security Reports</title>
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
				<div class="banner">USS-SERENITY &#149; RESOLVED SECURITY REPORTS</div>
				<div class="data-cascade-button-group">
					<div class="data-cascade-wrapper" id="default">
						<div class="data-column">
							<div class="dc-row-1">SEC</div>
							<div class="dc-row-1">RESOLVED</div>
							<div class="dc-row-2">REPORTS</div>
							<div class="dc-row-3">ARCHIVE</div>
							<div class="dc-row-3">SYSTEM</div>
							<div class="dc-row-4">STATUS</div>
							<div class="dc-row-5">ACTIVE</div>
							<div class="dc-row-6">ACCESS</div>
							<div class="dc-row-7">GRANTED</div>
						</div>
					</div>				
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')" style="background-color: var(--gold);">ACTIVE REPORTS</button>
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
					<div class="panel-3">SEC<span class="hop">-ARCHIVE</span></div>
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
					<h1>Resolved Security Reports Archive</h1>
					<h2>Security Department &#149; Incident History</h2>
					
					<?php if (isset($error)): ?>
						<div style="background: rgba(204, 68, 68, 0.3); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid var(--red);">
							<h4 style="color: var(--red);">Error</h4>
							<p><?php echo htmlspecialchars($error); ?></p>
						</div>
					<?php endif; ?>

					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Resolved Security Reports (<?php echo count($resolved_reports); ?> total)</h3>
						
						<?php if (empty($resolved_reports)): ?>
							<p style="color: var(--orange);">No resolved security reports found.</p>
						<?php else: ?>
							<div style="overflow-x: auto;">
								<table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
									<thead>
										<tr style="background: rgba(255, 170, 0, 0.3);">
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--gold);">Incident Type</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--gold);">Description</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--gold);">Involved Person</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--gold);">Resolution</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--gold);">Reported</th>
											<th style="padding: 1rem; text-align: left; border: 1px solid var(--gold);">Resolved</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($resolved_reports as $report): ?>
										<tr style="border-bottom: 1px solid var(--gold);">
											<td style="padding: 1rem; border: 1px solid var(--gold);">
												<strong style="color: var(--gold);"><?php echo htmlspecialchars($report['incident_type']); ?></strong>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--gold);">
												<div style="color: var(--red); font-size: 0.9rem; margin-bottom: 0.5rem;">Incident:</div>
												<div><?php echo htmlspecialchars($report['description']); ?></div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--gold);">
												<span style="color: var(--bluey);"><?php echo htmlspecialchars($report['involved_person']); ?></span>
												<?php if ($report['involved_department']): ?>
													<br><span style="color: var(--orange); font-size: 0.8rem;"><?php echo htmlspecialchars($report['involved_department']); ?></span>
												<?php endif; ?>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--gold);">
												<div style="color: var(--green); font-size: 0.9rem; margin-bottom: 0.5rem;">Resolution:</div>
												<div><?php echo htmlspecialchars($report['resolution_notes'] ?? 'No resolution details recorded'); ?></div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--gold);">
												<div style="color: var(--orange); font-size: 0.8rem;">
													<?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?>
												</div>
											</td>
											<td style="padding: 1rem; border: 1px solid var(--gold);">
												<div style="color: var(--green); font-size: 0.8rem;">
													<?php echo date('Y-m-d H:i', strtotime($report['updated_at'])); ?>
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
					Security Archive System - Authorized Personnel Only			 		 
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
