<?php
require_once '../includes/config.php';

// Update last active timestamp for current character
updateLastActive();

// Only allow Command staff access
if (!hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: ../index.php');
    exit();
}

// Handle training removal
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'remove_training') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        try {
            $pdo = getConnection();
            
            // Get training details for confirmation message
            $stmt = $pdo->prepare("
                SELECT 
                    cc.id,
                    tm.module_name,
                    r.rank, r.first_name, r.last_name,
                    cc.assigned_date,
                    cc.status
                FROM crew_competencies cc
                JOIN training_modules tm ON cc.module_id = tm.id
                JOIN roster r ON cc.roster_id = r.id
                WHERE cc.id = ?
            ");
            $stmt->execute([$_POST['competency_id']]);
            $training = $stmt->fetch();
            
            if ($training) {
                // Delete the training record
                $stmt = $pdo->prepare("DELETE FROM crew_competencies WHERE id = ?");
                $stmt->execute([$_POST['competency_id']]);
                
                $success = "Successfully removed '{$training['module_name']}' training from {$training['rank']} {$training['first_name']} {$training['last_name']}.";
            } else {
                $error = "Training record not found.";
            }
        } catch (Exception $e) {
            error_log("Error removing training: " . $e->getMessage());
            $error = "Error removing training. Please try again.";
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get all crew members with their training
    $stmt = $pdo->prepare("
        SELECT 
            cc.id as competency_id,
            r.id as roster_id,
            r.rank, r.first_name, r.last_name, r.department,
            tm.module_name, tm.module_code, tm.certification_level,
            cc.assigned_date, cc.status, cc.completion_date,
            u.username
        FROM crew_competencies cc
        JOIN training_modules tm ON cc.module_id = tm.id
        JOIN roster r ON cc.roster_id = r.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.is_active = 1
        ORDER BY r.department, r.rank, r.last_name, r.first_name, tm.module_name
    ");
    $stmt->execute();
    $training_records = $stmt->fetchAll();
    
    // Group by crew member
    $crew_training = [];
    foreach ($training_records as $record) {
        $crew_key = $record['roster_id'];
        if (!isset($crew_training[$crew_key])) {
            $crew_training[$crew_key] = [
                'roster_id' => $record['roster_id'],
                'rank' => $record['rank'],
                'first_name' => $record['first_name'],
                'last_name' => $record['last_name'],
                'department' => $record['department'],
                'username' => $record['username'],
                'training' => []
            ];
        }
        $crew_training[$crew_key]['training'][] = $record;
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Training Removal</title>
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
				<div class="panel-2">TRAIN<span class="hop">-RMV</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">TRAINING REMOVAL SYSTEM</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', 'training_modules.php')">MODULES</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--purple);">REMOVE</button>
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
					<div class="panel-3">CMD<span class="hop">-AUTH</span></div>
					<div class="panel-4">TRAIN<span class="hop">-DB</span></div>
					<div class="panel-5">CREW<span class="hop">-LST</span></div>
					<div class="panel-6">SECURE<span class="hop">-ACC</span></div>
					<div class="panel-7">LOG<span class="hop">-ENTRY</span></div>
					<div class="panel-8">DATA<span class="hop">-RMV</span></div>
					<div class="panel-9">CONF<span class="hop">-REQ</span></div>
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
					<h1>Training Record Removal</h1>
					<h2>Command Authority Required</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(0, 255, 0, 0.3); border: 2px solid var(--green); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--green);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<div style="background: rgba(204, 68, 68, 0.2); padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border: 2px solid var(--red);">
						<h3 style="color: var(--red);">⚠️ Warning</h3>
						<p style="color: var(--red);">This action will permanently remove training records from crew members. This action cannot be undone.</p>
						<p style="color: var(--red);">Only use this feature to correct errors or remove outdated certifications.</p>
					</div>
					
					<?php if (empty($crew_training)): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; border: 2px solid var(--orange); margin: 2rem 0; text-align: center;">
						<h3>No Training Records Found</h3>
						<p>There are currently no training records to manage.</p>
						<button onclick="playSoundAndRedirect('audio2', 'training_modules.php')" style="background-color: var(--green); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">
							Manage Training Modules
						</button>
					</div>
					<?php else: ?>
					
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--purple);">
						<h3>Crew Training Records</h3>
						<p style="color: var(--purple); margin-bottom: 2rem;">Select training records to remove from crew members</p>
						
						<?php foreach ($crew_training as $crew): ?>
						<div style="background: rgba(0,0,0,0.5); margin: 1rem 0; padding: 1.5rem; border-radius: 10px; border: 1px solid var(--purple);">
							<h4 style="color: var(--purple); margin-bottom: 1rem;">
								<?php echo htmlspecialchars($crew['rank'] . ' ' . $crew['first_name'] . ' ' . $crew['last_name']); ?>
								<span style="color: var(--orange); font-size: 0.9em;">(<?php echo htmlspecialchars($crew['department']); ?>)</span>
								<?php if ($crew['username']): ?>
								<span style="color: var(--gray); font-size: 0.8em;">- <?php echo htmlspecialchars($crew['username']); ?></span>
								<?php endif; ?>
							</h4>
							
							<div style="display: grid; gap: 0.5rem;">
								<?php foreach ($crew['training'] as $training): ?>
								<div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: rgba(0,0,0,0.3); border-radius: 5px; border-left: 3px solid var(--purple);">
									<div style="flex: 1;">
										<strong style="color: white;"><?php echo htmlspecialchars($training['module_name']); ?></strong>
										<span style="color: var(--orange);">(<?php echo htmlspecialchars($training['certification_level']); ?>)</span>
										<br>
										<small style="color: var(--gray);">
											Assigned: <?php echo date('Y-m-d', strtotime($training['assigned_date'])); ?>
											| Status: <span style="color: var(--green);"><?php echo htmlspecialchars($training['status']); ?></span>
											<?php if ($training['completion_date']): ?>
											| Completed: <?php echo date('Y-m-d', strtotime($training['completion_date'])); ?>
											<?php endif; ?>
										</small>
									</div>
									<div>
										<form method="POST" style="display: inline;" onsubmit="return confirmRemoval('<?php echo htmlspecialchars($training['module_name']); ?>', '<?php echo htmlspecialchars($crew['rank'] . ' ' . $crew['first_name'] . ' ' . $crew['last_name']); ?>')">
											<input type="hidden" name="action" value="remove_training">
											<input type="hidden" name="competency_id" value="<?php echo $training['competency_id']; ?>">
											<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
											<button type="submit" style="background-color: var(--red); color: white; border: none; padding: 0.5rem 1rem; border-radius: 3px; cursor: pointer; font-size: 0.9rem;">
												🗑️ Remove
											</button>
										</form>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					
					<?php endif; ?>
					
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
		function confirmRemoval(moduleName, crewName) {
			return confirm(
				'⚠️ CONFIRM TRAINING REMOVAL ⚠️\n\n' +
				'Module: ' + moduleName + '\n' +
				'Crew Member: ' + crewName + '\n\n' +
				'This action cannot be undone.\n\n' +
				'Are you sure you want to remove this training record?'
			);
		}
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
