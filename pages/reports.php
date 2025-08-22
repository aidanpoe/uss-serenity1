<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user's department and check permissions
$user_department = getUserDepartment();
$is_captain = hasPermission('Captain');
$is_command = hasPermission('Command');

try {
    $pdo = getConnection();
    
    // Initialize arrays
    $medical_reports = [];
    $engineering_reports = [];
    $security_reports = [];
    $command_suggestions = [];
    
    // Captain and Command can see everything, others only their department
    if ($is_captain || $is_command || $user_department === 'MED/SCI') {
        // Get recent medical reports
        $stmt = $pdo->prepare("
            SELECT 'Medical' as type, mr.id, mr.condition_description as description, mr.created_at, mr.status, 
                   CONCAT(r.rank, ' ', r.first_name, ' ', r.last_name) as person
            FROM medical_records mr 
            JOIN roster r ON mr.roster_id = r.id 
            ORDER BY mr.created_at DESC LIMIT 5
        ");
        $stmt->execute();
        $medical_reports = $stmt->fetchAll();
    }
    
    if ($is_captain || $is_command || $user_department === 'ENG/OPS') {
        // Get recent engineering reports
        $stmt = $pdo->prepare("
            SELECT 'Engineering' as type, fr.id, fr.fault_description as description, fr.created_at, fr.status,
                   CASE 
                       WHEN fr.location_type = 'Deck' THEN CONCAT('Deck ', fr.deck_number, ' - ', fr.room)
                       WHEN fr.location_type = 'Hull' THEN 'Hull'
                       WHEN fr.location_type = 'Jefferies Tube' THEN CONCAT('Tube ', fr.jefferies_tube_number)
                   END as location
            FROM fault_reports fr 
            ORDER BY fr.created_at DESC LIMIT 5
        ");
        $stmt->execute();
        $engineering_reports = $stmt->fetchAll();
    }
    
    if ($is_captain || $is_command || $user_department === 'SEC/TAC') {
        // Get recent security reports
        $stmt = $pdo->prepare("
            SELECT 'Security' as type, sr.id, sr.description, sr.created_at, sr.status, sr.incident_type,
                   CASE 
                       WHEN r.first_name IS NOT NULL THEN CONCAT(r.rank, ' ', r.first_name, ' ', r.last_name)
                       ELSE 'N/A'
                   END as person
            FROM security_reports sr 
            LEFT JOIN roster r ON sr.involved_roster_id = r.id 
            ORDER BY sr.created_at DESC LIMIT 5
        ");
        $stmt->execute();
        $security_reports = $stmt->fetchAll();
    }
    
    if ($is_captain || $is_command) {
        // Get command suggestions (only for Captain/Command)
        $stmt = $pdo->prepare("
            SELECT 'Command' as type, cs.id, cs.suggestion_description as description, cs.created_at, cs.status, cs.suggestion_title
            FROM command_suggestions cs 
            ORDER BY cs.created_at DESC LIMIT 5
        ");
        $stmt->execute();
        $command_suggestions = $stmt->fetchAll();
    }
    
    // Get statistics based on permissions
    $stats = [];
    if ($is_captain || $is_command || $user_department === 'MED/SCI') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_records WHERE status != 'Resolved'");
        $stmt->execute();
        $stats['open_medical'] = $stmt->fetch()['count'];
    }
    
    if ($is_captain || $is_command || $user_department === 'ENG/OPS') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM fault_reports WHERE status != 'Resolved'");
        $stmt->execute();
        $stats['open_engineering'] = $stmt->fetch()['count'];
    }
    
    if ($is_captain || $is_command || $user_department === 'SEC/TAC') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM security_reports WHERE status != 'Resolved'");
        $stmt->execute();
        $stats['open_security'] = $stmt->fetch()['count'];
    }
    
    if ($is_captain || $is_command) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM command_suggestions WHERE status = 'Pending'");
        $stmt->execute();
        $stats['pending_suggestions'] = $stmt->fetch()['count'];
    }
    
    // Get summary statistics (removed duplicate)
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Reports Overview</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.report-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 2rem;
			margin: 2rem 0;
		}
		.report-section {
			padding: 1.5rem;
			border-radius: 15px;
			border: 2px solid;
		}
		.medical { border-color: var(--blue); background: rgba(85, 102, 255, 0.1); }
		.engineering { border-color: var(--orange); background: rgba(255, 136, 0, 0.1); }
		.security { border-color: var(--gold); background: rgba(255, 170, 0, 0.1); }
		.command { border-color: var(--red); background: rgba(204, 68, 68, 0.1); }
		
		.report-item {
			background: rgba(0,0,0,0.3);
			padding: 1rem;
			border-radius: 8px;
			margin: 0.5rem 0;
			border-left: 4px solid;
		}
		.medical .report-item { border-left-color: var(--blue); }
		.engineering .report-item { border-left-color: var(--orange); }
		.security .report-item { border-left-color: var(--gold); }
		.command .report-item { border-left-color: var(--red); }
		
		.status-badge {
			padding: 0.25rem 0.5rem;
			border-radius: 3px;
			font-size: 0.8rem;
			color: black;
			font-weight: bold;
		}
		.status-open { background: var(--red); }
		.status-progress { background: var(--orange); }
		.status-resolved { background: var(--green); }
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
				<div class="panel-2">REPORTS<span class="hop">-STAT</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">REPORTS &#149; OVERVIEW</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--african-violet);">REPORTS</button>
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
					<?php if ($is_captain || $is_command || $user_department === 'MED/SCI'): ?>
					<div class="panel-3">MED<span class="hop">-<?php echo isset($stats['open_medical']) ? $stats['open_medical'] : 0; ?></span></div>
					<?php endif; ?>
					<?php if ($is_captain || $is_command || $user_department === 'ENG/OPS'): ?>
					<div class="panel-4">ENG<span class="hop">-<?php echo isset($stats['open_engineering']) ? $stats['open_engineering'] : 0; ?></span></div>
					<?php endif; ?>
					<?php if ($is_captain || $is_command || $user_department === 'SEC/TAC'): ?>
					<div class="panel-5">SEC<span class="hop">-<?php echo isset($stats['open_security']) ? $stats['open_security'] : 0; ?></span></div>
					<?php endif; ?>
					<?php if ($is_captain || $is_command): ?>
					<div class="panel-6">CMD<span class="hop">-<?php echo isset($stats['pending_suggestions']) ? $stats['pending_suggestions'] : 0; ?></span></div>
					<?php endif; ?>
					<div class="panel-7">DEPT<span class="hop">-<?php echo $user_department; ?></span></div>
					<div class="panel-8">ACTV<span class="hop">-MON</span></div>
					<div class="panel-9">REAL<span class="hop">-TIME</span></div>
				</div>
				<div>
					<div class="panel-10">STATUS<span class="hop">-LIVE</span></div>
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
					<h1>Department Reports Overview</h1>
					<h2>USS-Serenity Status Summary</h2>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Summary Statistics -->
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php if ($is_captain || $is_command || $user_department === 'MED/SCI'): ?>
						<div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 10px; text-align: center; border: 2px solid var(--blue);">
							<h4 style="color: var(--blue);">Medical Issues</h4>
							<div style="font-size: 2rem; color: var(--blue);"><?php echo isset($stats['open_medical']) ? $stats['open_medical'] : 0; ?></div>
							<small>Open Cases</small>
						</div>
						<?php endif; ?>
						<?php if ($is_captain || $is_command || $user_department === 'ENG/OPS'): ?>
						<div style="background: rgba(255, 136, 0, 0.2); padding: 1rem; border-radius: 10px; text-align: center; border: 2px solid var(--orange);">
							<h4 style="color: var(--orange);">Engineering Faults</h4>
							<div style="font-size: 2rem; color: var(--orange);"><?php echo isset($stats['open_engineering']) ? $stats['open_engineering'] : 0; ?></div>
							<small>Open Reports</small>
						</div>
						<?php endif; ?>
						<?php if ($is_captain || $is_command || $user_department === 'SEC/TAC'): ?>
						<div style="background: rgba(255, 170, 0, 0.2); padding: 1rem; border-radius: 10px; text-align: center; border: 2px solid var(--gold);">
							<h4 style="color: var(--gold);">Security Incidents</h4>
							<div style="font-size: 2rem; color: var(--gold);"><?php echo isset($stats['open_security']) ? $stats['open_security'] : 0; ?></div>
							<small>Open Reports</small>
						</div>
						<?php endif; ?>
						<?php if ($is_captain || $is_command): ?>
						<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; text-align: center; border: 2px solid var(--red);">
							<h4 style="color: var(--red);">Suggestions</h4>
							<div style="font-size: 2rem; color: var(--red);"><?php echo isset($stats['pending_suggestions']) ? $stats['pending_suggestions'] : 0; ?></div>
							<small>Pending Review</small>
						</div>
						<?php endif; ?>
					</div>
					
					<!-- Quick Access Buttons -->
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0;">
						<?php if ($is_captain || $is_command || $user_department === 'MED/SCI'): ?>
						<button onclick="playSoundAndRedirect('audio2', 'med_sci.php')" style="background-color: var(--blue); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1.1rem;">
							<strong>Medical/Science</strong><br>
							<small>Report & Manage Health Issues</small>
						</button>
						<?php endif; ?>
						<?php if ($is_captain || $is_command || $user_department === 'ENG/OPS'): ?>
						<button onclick="playSoundAndRedirect('audio2', 'eng_ops.php')" style="background-color: var(--orange); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1.1rem;">
							<strong>Engineering/Ops</strong><br>
							<small>System Faults & Maintenance</small>
						</button>
						<?php endif; ?>
						<?php if ($is_captain || $is_command || $user_department === 'SEC/TAC'): ?>
						<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')" style="background-color: var(--gold); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1.1rem;">
							<strong>Security/Tactical</strong><br>
							<small>Incidents & Security Concerns</small>
						</button>
						<?php endif; ?>
						<?php if ($is_captain || $is_command): ?>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')" style="background-color: var(--red); color: black; border: none; padding: 1rem; border-radius: 10px; font-size: 1.1rem;">
							<strong>Command</strong><br>
							<small>Suggestions & Strategic Issues</small>
						</button>
						<?php endif; ?>
					</div>
					
					<h3>Recent Reports</h3>
					<div class="report-grid">
						<!-- Medical Reports -->
						<?php if ($is_captain || $is_command || $user_department === 'MED/SCI'): ?>
						<div class="report-section medical">
							<h4 style="color: var(--blue);">Recent Medical Reports</h4>
							<?php if (empty($medical_reports)): ?>
							<p style="color: var(--blue);"><em>No recent medical reports.</em></p>
							<?php else: ?>
							<?php foreach ($medical_reports as $report): ?>
							<div class="report-item">
								<div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
									<strong>#<?php echo $report['id']; ?> - <?php echo htmlspecialchars($report['person']); ?></strong>
									<span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $report['status'])); ?>"><?php echo $report['status']; ?></span>
								</div>
								<p style="font-size: 0.9rem;"><?php echo htmlspecialchars(substr($report['description'], 0, 100)) . (strlen($report['description']) > 100 ? '...' : ''); ?></p>
								<small><?php echo formatICDateTime($report['created_at']); ?></small>
							</div>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endif; ?>
						
						<!-- Engineering Reports -->
						<?php if ($is_captain || $is_command || $user_department === 'ENG/OPS'): ?>
						<div class="report-section engineering">
							<h4 style="color: var(--orange);">Recent Engineering Reports</h4>
							<?php if (empty($engineering_reports)): ?>
							<p style="color: var(--orange);"><em>No recent engineering reports.</em></p>
							<?php else: ?>
							<?php foreach ($engineering_reports as $report): ?>
							<div class="report-item">
								<div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
									<strong>#<?php echo $report['id']; ?> - <?php echo htmlspecialchars($report['location']); ?></strong>
									<span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $report['status'])); ?>"><?php echo $report['status']; ?></span>
								</div>
								<p style="font-size: 0.9rem;"><?php echo htmlspecialchars(substr($report['description'], 0, 100)) . (strlen($report['description']) > 100 ? '...' : ''); ?></p>
								<small><?php echo formatICDateTime($report['created_at']); ?></small>
							</div>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endif; ?>
						
						<!-- Security Reports -->
						<?php if ($is_captain || $is_command || $user_department === 'SEC/TAC'): ?>
						<div class="report-section security">
							<h4 style="color: var(--gold);">Recent Security Reports</h4>
							<?php if (empty($security_reports)): ?>
							<p style="color: var(--gold);"><em>No recent security reports.</em></p>
							<?php else: ?>
							<?php foreach ($security_reports as $report): ?>
							<div class="report-item">
								<div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
									<strong>#<?php echo $report['id']; ?> - <?php echo htmlspecialchars($report['incident_type']); ?></strong>
									<span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $report['status'])); ?>"><?php echo $report['status']; ?></span>
								</div>
								<p style="font-size: 0.9rem;"><?php echo htmlspecialchars(substr($report['description'], 0, 100)) . (strlen($report['description']) > 100 ? '...' : ''); ?></p>
								<small><?php echo formatICDateTime($report['created_at']); ?></small>
							</div>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endif; ?>
						
						<!-- Command Suggestions -->
						<?php if ($is_captain || $is_command): ?>
						<div class="report-section command">
							<h4 style="color: var(--red);">Recent Suggestions</h4>
							<?php if (empty($command_suggestions)): ?>
							<p style="color: var(--red);"><em>No recent suggestions.</em></p>
							<?php else: ?>
							<?php foreach ($command_suggestions as $suggestion): ?>
							<div class="report-item">
								<div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
									<strong>#<?php echo $suggestion['id']; ?> - <?php echo htmlspecialchars($suggestion['suggestion_title']); ?></strong>
									<span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $suggestion['status'])); ?>"><?php echo $suggestion['status']; ?></span>
								</div>
								<p style="font-size: 0.9rem;"><?php echo htmlspecialchars(substr($suggestion['description'], 0, 100)) . (strlen($suggestion['description']) > 100 ? '...' : ''); ?></p>
								<small><?php echo formatICDateTime($suggestion['created_at']); ?></small>
							</div>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endif; ?>
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
