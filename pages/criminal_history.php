<?php
require_once '../includes/config.php';

// Check if user has security department access, command, or is Captain
if (!hasPermission('SEC/TAC') && !hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

$crew_id = $_GET['crew_id'] ?? null;
if (!$crew_id) {
    header('Location: criminal_records.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get crew member information
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE id = ?");
    $stmt->execute([$crew_id]);
    $crew_member = $stmt->fetch();
    
    if (!$crew_member) {
        header('Location: criminal_records.php');
        exit();
    }
    
    // Get all criminal records for this crew member
    $stmt = $pdo->prepare("
        SELECT * FROM criminal_records 
        WHERE roster_id = ? 
        ORDER BY incident_date DESC, created_at DESC
    ");
    $stmt->execute([$crew_id]);
    $criminal_records = $stmt->fetchAll();
    
    // Get summary statistics
    $total_records = count($criminal_records);
    $open_cases = count(array_filter($criminal_records, function($r) { return $r['status'] === 'Under Investigation' || $r['status'] === 'Pending Review'; }));
    $guilty_verdicts = count(array_filter($criminal_records, function($r) { return $r['status'] === 'Closed - Guilty'; }));
    $not_guilty = count(array_filter($criminal_records, function($r) { return $r['status'] === 'Closed - Not Guilty'; }));
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Criminal Record: <?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name']); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.record-container {
			background: rgba(0,0,0,0.7);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--gold);
		}
		.incident-card {
			background: rgba(255, 165, 0, 0.1);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 4px solid var(--gold);
		}
		.incident-card.guilty {
			border-left-color: var(--red);
			background: rgba(204, 68, 68, 0.1);
		}
		.incident-card.not-guilty {
			border-left-color: var(--green);
			background: rgba(0, 255, 0, 0.1);
		}
		.incident-card.investigation {
			border-left-color: var(--blue);
			background: rgba(85, 102, 255, 0.1);
		}
		.incident-card.classified {
			border-left-color: var(--purple);
			background: rgba(128, 0, 128, 0.1);
		}
		.personnel-info {
			background: rgba(255, 165, 0, 0.1);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--gold);
		}
		.status-badge {
			display: inline-block;
			padding: 0.25rem 0.5rem;
			border-radius: 3px;
			font-size: 0.8rem;
			font-weight: bold;
			margin: 0.25rem;
		}
		.status-under-investigation { background: var(--blue); color: black; }
		.status-closed-guilty { background: var(--red); color: white; }
		.status-closed-not-guilty { background: var(--green); color: black; }
		.status-closed-insufficient { background: var(--orange); color: black; }
		.status-pending { background: var(--purple); color: white; }
		.classification-badge {
			font-size: 0.7rem;
			padding: 0.2rem 0.4rem;
			border-radius: 2px;
			margin-left: 0.5rem;
		}
		.class-public { background: var(--green); color: black; }
		.class-restricted { background: var(--orange); color: black; }
		.class-classified { background: var(--red); color: white; }
		.incident-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 1rem;
			margin: 1rem 0;
		}
		.summary-stats {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}
		.stat-card {
			background: rgba(0,0,0,0.5);
			padding: 1rem;
			border-radius: 10px;
			text-align: center;
			border: 2px solid var(--gold);
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
				<div class="panel-2">CRIM<span class="hop">-FILE</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">CRIMINAL RECORD &#149; CLASSIFIED ACCESS</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')">CRIMINAL DB</button>
						<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')">SECURITY</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">RECORD</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
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
					<div class="panel-3">CRIM<span class="hop">-REC</span></div>
					<div class="panel-4">SECURE<span class="hop">-FILE</span></div>
					<div class="panel-5">RECORDS<span class="hop">-<?php echo $total_records; ?></span></div>
					<div class="panel-6">GUILTY<span class="hop">-<?php echo $guilty_verdicts; ?></span></div>
					<div class="panel-7">OPEN<span class="hop">-<?php echo $open_cases; ?></span></div>
					<div class="panel-8">ACCESS<span class="hop">-AUTH</span></div>
					<div class="panel-9">CLASSIFIED<span class="hop">-DATA</span></div>
				</div>
				<div>
					<div class="panel-10">STATUS<span class="hop">-SECURE</span></div>
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
					<h1>Criminal Record File</h1>
					<h2>⚠️ RESTRICTED ACCESS - Security Personnel Only</h2>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Personnel Information -->
					<div class="personnel-info">
						<div style="display: flex; align-items: center; margin-bottom: 2rem;">
							<?php if ($crew_member['image_path'] && file_exists('../' . $crew_member['image_path'])): ?>
							<img src="../<?php echo htmlspecialchars($crew_member['image_path']); ?>" alt="Personnel Photo" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-right: 2rem; border: 4px solid var(--gold);">
							<?php endif; ?>
							
							<div style="flex: 1;">
								<h3 style="color: var(--gold); margin: 0; font-size: 1.8rem;">
									<?php echo htmlspecialchars($crew_member['rank'] . ' ' . $crew_member['first_name'] . ' ' . $crew_member['last_name']); ?>
								</h3>
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
									<div>
										<strong style="color: var(--gold);">Personnel ID:</strong><br>
										<?php echo str_pad($crew_member['id'], 6, '0', STR_PAD_LEFT); ?>
									</div>
									<div>
										<strong style="color: var(--gold);">Species:</strong><br>
										<?php echo htmlspecialchars($crew_member['species']); ?>
									</div>
									<div>
										<strong style="color: var(--gold);">Department:</strong><br>
										<?php echo htmlspecialchars($crew_member['department']); ?>
									</div>
									<div>
										<strong style="color: var(--gold);">Position:</strong><br>
										<?php echo htmlspecialchars($crew_member['position'] ?? 'Not Specified'); ?>
									</div>
									<div>
										<strong style="color: var(--gold);">Status:</strong><br>
										<span style="color: <?php echo ($crew_member['status'] === 'Active') ? 'var(--green)' : 'var(--red)'; ?>;">
											<?php echo htmlspecialchars($crew_member['status'] ?? 'Active'); ?>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Summary Statistics -->
					<div class="summary-stats">
						<div class="stat-card">
							<h4 style="color: var(--gold); margin: 0;">Total Records</h4>
							<div style="font-size: 2rem; color: <?php echo ($total_records > 0) ? 'var(--red)' : 'var(--green)'; ?>"><?php echo $total_records; ?></div>
							<small>Criminal Incidents</small>
						</div>
						<div class="stat-card">
							<h4 style="color: var(--red); margin: 0;">Guilty Verdicts</h4>
							<div style="font-size: 2rem; color: var(--red);"><?php echo $guilty_verdicts; ?></div>
							<small>Confirmed Violations</small>
						</div>
						<div class="stat-card">
							<h4 style="color: var(--green); margin: 0;">Not Guilty</h4>
							<div style="font-size: 2rem; color: var(--green);"><?php echo $not_guilty; ?></div>
							<small>Cleared Charges</small>
						</div>
						<div class="stat-card">
							<h4 style="color: var(--blue); margin: 0;">Open Cases</h4>
							<div style="font-size: 2rem; color: <?php echo ($open_cases > 0) ? 'var(--orange)' : 'var(--green)'; ?>"><?php echo $open_cases; ?></div>
							<small>Active Investigations</small>
						</div>
					</div>
					
					<!-- Criminal Records Timeline -->
					<div class="record-container">
						<h3>Criminal Records Timeline</h3>
						<?php if (empty($criminal_records)): ?>
						<div style="text-align: center; padding: 2rem; color: var(--green);">
							<h4>✅ Clean Criminal Record</h4>
							<p>This crew member has no criminal incidents on file.</p>
						</div>
						<?php else: ?>
						<?php foreach ($criminal_records as $record): ?>
						<?php 
							$card_class = 'incident-card';
							if ($record['status'] === 'Closed - Guilty') $card_class .= ' guilty';
							elseif ($record['status'] === 'Closed - Not Guilty') $card_class .= ' not-guilty';
							elseif (in_array($record['status'], ['Under Investigation', 'Pending Review'])) $card_class .= ' investigation';
							if ($record['classification'] === 'Classified') $card_class .= ' classified';
						?>
						<div class="<?php echo $card_class; ?>">
							<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
								<div>
									<h4 style="color: var(--gold); margin: 0;">
										<?php echo htmlspecialchars($record['incident_type']); ?>
										<span class="classification-badge class-<?php echo strtolower($record['classification']); ?>">
											<?php echo strtoupper($record['classification']); ?>
										</span>
									</h4>
									<p style="margin: 0.25rem 0; color: var(--orange);">
										Incident Date: <?php echo date('F j, Y', strtotime($record['incident_date'])); ?>
									</p>
								</div>
								<div style="text-align: right;">
									<span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], '-', $record['status'])); ?>">
										<?php echo $record['status']; ?>
									</span>
								</div>
							</div>
							
							<div class="incident-grid">
								<div>
									<h5 style="color: var(--gold); margin: 0.5rem 0;">Incident Description:</h5>
									<p style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 5px; margin: 0;">
										<?php echo nl2br(htmlspecialchars($record['incident_description'])); ?>
									</p>
								</div>
								
								<?php if ($record['investigation_details']): ?>
								<div>
									<h5 style="color: var(--blue); margin: 0.5rem 0;">Investigation Details:</h5>
									<p style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 5px; margin: 0;">
										<?php echo nl2br(htmlspecialchars($record['investigation_details'])); ?>
									</p>
								</div>
								<?php endif; ?>
								
								<?php if ($record['evidence_notes']): ?>
								<div>
									<h5 style="color: var(--orange); margin: 0.5rem 0;">Evidence Notes:</h5>
									<p style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 5px; margin: 0;">
										<?php echo nl2br(htmlspecialchars($record['evidence_notes'])); ?>
									</p>
								</div>
								<?php endif; ?>
								
								<?php if ($record['punishment_details'] && $record['status'] === 'Closed - Guilty'): ?>
								<div>
									<h5 style="color: var(--red); margin: 0.5rem 0;">Punishment Details:</h5>
									<p style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 5px; margin: 0; border: 1px solid var(--red);">
										<strong>Type:</strong> <?php echo htmlspecialchars($record['punishment_type']); ?><br>
										<?php if ($record['punishment_duration']): ?>
										<strong>Duration:</strong> <?php echo htmlspecialchars($record['punishment_duration']); ?><br>
										<?php endif; ?>
										<strong>Details:</strong> <?php echo nl2br(htmlspecialchars($record['punishment_details'])); ?>
									</p>
								</div>
								<?php endif; ?>
							</div>
							
							<div style="border-top: 1px solid var(--gray); padding-top: 1rem; margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
								<div style="font-size: 0.9rem; color: var(--bluey);">
									<strong>Investigating Officer:</strong> <?php echo htmlspecialchars($record['investigating_officer'] ?? 'Not Assigned'); ?><br>
									<strong>Reported By:</strong> <?php echo htmlspecialchars($record['reported_by'] ?? 'Unknown'); ?><br>
									<strong>Record Created:</strong> <?php echo date('M j, Y g:i A', strtotime($record['created_at'])); ?>
								</div>
								
								<?php if ($record['status'] === 'Under Investigation' || $record['status'] === 'Pending Review'): ?>
								<div>
									<span style="background: var(--orange); color: black; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem;">
										⚠️ ACTIVE CASE
									</span>
								</div>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<!-- Quick Actions -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0; text-align: center;">
						<h3>Quick Actions</h3>
						<div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')" style="background-color: var(--gold); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								🔍 Back to Search
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')" style="background-color: var(--orange); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								🛡️ Security Reports
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'medical_history.php?crew_id=<?php echo $crew_member['id']; ?>')" style="background-color: var(--blue); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								🏥 Medical History
							</button>
						</div>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					Criminal Records System - Classified Access - Security Clearance Required
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
