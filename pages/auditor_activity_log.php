<?php
require_once '../includes/config.php';

// Check if user has command access or is Starfleet Auditor
$roster_dept = $_SESSION['roster_department'] ?? '';
if (!hasPermission('Command') && $roster_dept !== 'Starfleet Auditor') {
    header('Location: login.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get filter parameters
    $filter_auditor = $_GET['filter_auditor'] ?? '';
    $filter_action = $_GET['filter_action'] ?? '';
    $filter_days = $_GET['filter_days'] ?? '30';
    
    // Build query with filters
    $where_conditions = ["cat.action_timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)"];
    $params = [$filter_days];
    
    if (!empty($filter_auditor)) {
        $where_conditions[] = "r.id = ?";
        $params[] = $filter_auditor;
    }
    
    if (!empty($filter_action)) {
        $where_conditions[] = "cat.action_type = ?";
        $params[] = $filter_action;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get auditor activity logs
    $stmt = $pdo->prepare("
        SELECT 
            cat.*,
            r.first_name,
            r.last_name,
            r.rank,
            r.department
        FROM character_audit_trail cat
        LEFT JOIN roster r ON cat.character_id = r.id
        WHERE $where_clause
        ORDER BY cat.action_timestamp DESC
        LIMIT 500
    ");
    $stmt->execute($params);
    $audit_logs = $stmt->fetchAll();
    
    // Get available auditors for filter
    $stmt = $pdo->prepare("
        SELECT DISTINCT r.id, r.first_name, r.last_name, r.rank
        FROM character_audit_trail cat
        LEFT JOIN roster r ON cat.character_id = r.id
        WHERE r.id IS NOT NULL
        ORDER BY r.first_name, r.last_name
    ");
    $stmt->execute();
    $auditors = $stmt->fetchAll();
    
    // Get action types for filter
    $stmt = $pdo->prepare("
        SELECT DISTINCT action_type
        FROM character_audit_trail
        ORDER BY action_type
    ");
    $stmt->execute();
    $action_types = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Auditor Activity Log Error: " . $e->getMessage());
    // For debugging, also display the error
    $debug_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Auditor Activity Log</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.audit-container {
			background: rgba(0,0,0,0.7);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--gold);
		}
		.audit-entry {
			background: rgba(255, 165, 0, 0.1);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 4px solid var(--gold);
		}
		.audit-entry.delete-action {
			border-left-color: var(--red);
			background: rgba(204, 68, 68, 0.1);
		}
		.filter-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}
		.action-badge {
			display: inline-block;
			padding: 0.25rem 0.5rem;
			border-radius: 3px;
			font-size: 0.8rem;
			font-weight: bold;
			margin: 0.25rem;
		}
		.action-delete { background: var(--red); color: white; }
		.action-create { background: var(--green); color: black; }
		.action-update { background: var(--blue); color: white; }
		.action-view { background: var(--orange); color: black; }
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
				<div class="panel-2">AUDIT<span class="hop">-LOG</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">AUDITOR ACTIVITY LOG &#149; CLASSIFIED ACCESS</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')">CRIMINAL DB</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">AUDIT LOG</button>
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
					<div class="panel-3">AUDIT<span class="hop">-TRAIL</span></div>
					<div class="panel-4">SECURE<span class="hop">-LOG</span></div>
					<div class="panel-5">ACTIONS<span class="hop">-<?php echo count($audit_logs); ?></span></div>
					<div class="panel-6">PERIOD<span class="hop">-<?php echo $filter_days; ?>D</span></div>
					<div class="panel-7">ACCESS<span class="hop">-AUTH</span></div>
					<div class="panel-8">CLASSIFIED<span class="hop">-DATA</span></div>
					<div class="panel-9">MONITOR<span class="hop">-ACTIVE</span></div>
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
					<h1>Starfleet Auditor Activity Log</h1>
					<h2>🔍 Accountability & Oversight System</h2>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($debug_error)): ?>
					<div style="background: rgba(255, 165, 0, 0.3); border: 2px solid var(--orange); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--orange);"><strong>Debug Error:</strong> <?php echo htmlspecialchars($debug_error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Debug Info -->
					<div style="background: rgba(0, 255, 255, 0.1); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<h4 style="color: var(--blue);">Debug Information:</h4>
						<p>Total audit logs found: <?php echo isset($audit_logs) ? count($audit_logs) : 'Not set'; ?></p>
						<p>Filter days: <?php echo htmlspecialchars($filter_days); ?></p>
						<p>Filter auditor: <?php echo htmlspecialchars($filter_auditor); ?></p>
						<p>Filter action: <?php echo htmlspecialchars($filter_action); ?></p>
						<p>Current user department: <?php echo htmlspecialchars($roster_dept); ?></p>
						<p>Available auditors: <?php echo isset($auditors) ? count($auditors) : 'Not set'; ?></p>
						<p>Available action types: <?php echo isset($action_types) ? count($action_types) : 'Not set'; ?></p>
						<p>Current character ID: <?php echo $_SESSION['character_id'] ?? 'Not set'; ?></p>
						<p>SQL WHERE clause: <?php echo htmlspecialchars($where_clause ?? 'Not set'); ?></p>
						<p>SQL parameters: <?php echo htmlspecialchars(json_encode($params ?? [])); ?></p>
						
						<?php if (isset($audit_logs) && count($audit_logs) > 0): ?>
						<h5>Sample audit log data:</h5>
						<pre><?php echo htmlspecialchars(json_encode(array_slice($audit_logs, 0, 2), JSON_PRETTY_PRINT)); ?></pre>
						<?php endif; ?>
					</div>
					
					<!-- Filter Section -->
					<div class="audit-container">
						<h3>Filter Audit Logs</h3>
						<form method="GET" action="">
							<div class="filter-grid">
								<!-- Auditor Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Auditor:</label>
									<select name="filter_auditor" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="">All Auditors</option>
										<?php foreach ($auditors as $auditor): ?>
										<option value="<?php echo $auditor['id']; ?>" <?php echo ($filter_auditor == $auditor['id']) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($auditor['rank'] . ' ' . $auditor['first_name'] . ' ' . $auditor['last_name']); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<!-- Action Type Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Action Type:</label>
									<select name="filter_action" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="">All Actions</option>
										<?php foreach ($action_types as $action): ?>
										<option value="<?php echo htmlspecialchars($action['action_type']); ?>" <?php echo ($filter_action === $action['action_type']) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $action['action_type']))); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<!-- Time Period Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Time Period:</label>
									<select name="filter_days" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="7" <?php echo ($filter_days === '7') ? 'selected' : ''; ?>>Last 7 Days</option>
										<option value="30" <?php echo ($filter_days === '30') ? 'selected' : ''; ?>>Last 30 Days</option>
										<option value="90" <?php echo ($filter_days === '90') ? 'selected' : ''; ?>>Last 90 Days</option>
										<option value="365" <?php echo ($filter_days === '365') ? 'selected' : ''; ?>>Last Year</option>
									</select>
								</div>
							</div>
							
							<div style="text-align: center; margin: 1.5rem 0;">
								<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem;">
									🔍 FILTER LOGS
								</button>
								<a href="?" style="background-color: var(--orange); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem; text-decoration: none; display: inline-block;">
									🔄 CLEAR FILTERS
								</a>
							</div>
						</form>
					</div>
					
					<!-- Audit Log Results -->
					<div class="audit-container">
						<h3>Audit Trail Results: <?php echo count($audit_logs ?? []); ?> actions found</h3>
						
						<!-- Quick test to verify data -->
						<div style="background: rgba(255, 255, 0, 0.1); border: 1px solid yellow; padding: 1rem; margin: 1rem 0;">
							<strong>Quick Data Check:</strong>
							<?php if (isset($audit_logs)): ?>
								Found <?php echo count($audit_logs); ?> audit log entries.
								<?php if (count($audit_logs) > 0): ?>
									<br>First entry: <?php echo htmlspecialchars($audit_logs[0]['action_type'] ?? 'Unknown'); ?> by character ID <?php echo htmlspecialchars($audit_logs[0]['character_id'] ?? 'Unknown'); ?>
								<?php endif; ?>
							<?php else: ?>
								audit_logs variable is not set.
							<?php endif; ?>
						</div>
						
						<?php if (!empty($filter_auditor) || !empty($filter_action) || $filter_days !== '30'): ?>
						<p style="color: var(--orange);">
							Active filters: 
							<?php if (!empty($filter_auditor)): ?>
								<?php 
								$selected_auditor = array_filter($auditors, function($a) use ($filter_auditor) { return $a['id'] == $filter_auditor; });
								$selected_auditor = reset($selected_auditor);
								if ($selected_auditor): ?>
								Auditor: <?php echo htmlspecialchars($selected_auditor['rank'] . ' ' . $selected_auditor['first_name'] . ' ' . $selected_auditor['last_name']); ?> 
								<?php endif; ?>
							<?php endif; ?>
							<?php if (!empty($filter_action)): ?>Action: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $filter_action))); ?> <?php endif; ?>
							Period: Last <?php echo $filter_days; ?> days
						</p>
						<?php endif; ?>
						
						<?php if (empty($audit_logs)): ?>
						<div style="background: rgba(0, 255, 0, 0.2); padding: 2rem; border-radius: 10px; text-align: center; margin: 2rem 0;">
							<h4 style="color: var(--green);">No Audit Log Entries Found</h4>
							<p>No auditor actions match your current filter criteria.</p>
						</div>
						<?php else: ?>
						
						<?php foreach ($audit_logs as $log): ?>
						<?php 
							$entry_class = 'audit-entry';
							if (strpos($log['action_type'], 'delete') !== false) $entry_class .= ' delete-action';
						?>
						<!-- Debug: Processing log entry <?php echo $log['id']; ?> -->
						<div class="<?php echo $entry_class; ?>">
							<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
								<div>
									<h4 style="color: var(--gold); margin: 0;">
										<?php echo htmlspecialchars(($log['rank'] ?? 'Unknown Rank') . ' ' . ($log['first_name'] ?? 'Unknown') . ' ' . ($log['last_name'] ?? 'User')); ?>
										<span class="action-badge action-<?php echo strpos($log['action_type'], 'delete') !== false ? 'delete' : 'view'; ?>">
											<?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $log['action_type']))); ?>
										</span>
									</h4>
									<p style="margin: 0.25rem 0; color: var(--orange);">
										<?php echo date('F j, Y \a\t g:i A', strtotime($log['action_timestamp'])); ?>
									</p>
								</div>
								<div style="text-align: right;">
									<strong style="color: var(--blue);">Table: <?php echo htmlspecialchars($log['table_name']); ?></strong><br>
									<small style="color: var(--gray);">Record ID: <?php echo htmlspecialchars($log['record_id']); ?></small>
								</div>
							</div>
							
							<?php if ($log['additional_data']): ?>
							<?php 
								$additional_data = json_decode($log['additional_data'], true);
								if ($additional_data): ?>
							<div style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 5px; margin: 1rem 0;">
								<h5 style="color: var(--blue); margin: 0 0 0.5rem 0;">Action Details:</h5>
								<?php foreach ($additional_data as $key => $value): ?>
								<p style="margin: 0.25rem 0; color: var(--gray);">
									<strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?>:</strong>
									<?php echo htmlspecialchars(is_array($value) ? implode(', ', $value) : $value); ?>
								</p>
								<?php endforeach; ?>
							</div>
							<?php endif; ?>
							<?php endif; ?>
							
							<div style="border-top: 1px solid var(--gray); padding-top: 0.5rem; margin-top: 1rem; font-size: 0.9rem; color: var(--bluey);">
								<strong>Department:</strong> <?php echo htmlspecialchars($log['department'] ?? 'Unknown'); ?> | 
								<strong>Timestamp:</strong> <?php echo $log['action_timestamp']; ?>
							</div>
						</div>
						<?php endforeach; ?>
						
						<?php endif; ?>
					</div>
					
					<!-- Quick Actions -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0; text-align: center;">
						<h3>Quick Actions</h3>
						<div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'command.php')" style="background-color: var(--gold); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								⭐ Command Center
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'criminal_records.php')" style="background-color: var(--red); color: white; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								🔍 Criminal Records
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'admin_management.php')" style="background-color: var(--blue); color: white; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								⚙️ Admin Management
							</button>
						</div>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					Auditor Activity Log - Classified Access - Command/Auditor Only
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
