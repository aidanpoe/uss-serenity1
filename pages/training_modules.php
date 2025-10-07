<?php
require_once '../includes/config.php';

// Check if user has Command access
if (!hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Handle adding new training module
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_module') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO training_modules 
                (module_name, module_code, department, description, prerequisites, certification_level, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['module_name'],
                $_POST['module_code'],
                $_POST['department'],
                $_POST['description'],
                $_POST['prerequisites'],
                $_POST['certification_level'],
                $_SESSION['user_id']
            ]);
            
            $success = "Training module '" . htmlspecialchars($_POST['module_name']) . "' added successfully!";
        }
    }
    
    // Handle updating module
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_module') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE training_modules 
                SET module_name = ?, description = ?, prerequisites = ?, 
                    certification_level = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['module_name'],
                $_POST['description'],
                $_POST['prerequisites'],
                $_POST['certification_level'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['module_id']
            ]);
            
            $success = "Training module updated successfully!";
        }
    }
    
    // Handle deleting module
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_module') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            // Check if module has active assignments
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM crew_competencies WHERE module_id = ?");
            $checkStmt->execute([$_POST['module_id']]);
            $assignmentCount = $checkStmt->fetch()['count'];
            
            if ($assignmentCount > 0) {
                $error = "Cannot delete module: {$assignmentCount} active assignments exist. Please complete or remove assignments first.";
            } else {
                // Get module name for success message
                $nameStmt = $pdo->prepare("SELECT module_name FROM training_modules WHERE id = ?");
                $nameStmt->execute([$_POST['module_id']]);
                $moduleName = $nameStmt->fetch()['module_name'];
                
                // Delete all historical assignments first
                $deleteAssignments = $pdo->prepare("DELETE FROM crew_competencies WHERE module_id = ?");
                $deleteAssignments->execute([$_POST['module_id']]);
                
                // Delete the module
                $deleteModule = $pdo->prepare("DELETE FROM training_modules WHERE id = ?");
                $deleteModule->execute([$_POST['module_id']]);
                
                $success = "Training module '{$moduleName}' and all associated records deleted successfully!";
            }
        }
    }
    
    // Get all training modules
    $stmt = $pdo->query("
        SELECT tm.*, u.username as created_by_name,
               COUNT(cc.id) as assigned_count
        FROM training_modules tm
        LEFT JOIN users u ON tm.created_by = u.id
        LEFT JOIN crew_competencies cc ON tm.id = cc.module_id
        GROUP BY tm.id
        ORDER BY tm.department, tm.certification_level, tm.module_name
    ");
    $modules = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>USS-VOYAGER - Training Module Management</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.training-container {
			background: rgba(85, 102, 255, 0.1);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--blue);
		}
		.module-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
			gap: 1.5rem;
			margin: 2rem 0;
		}
		.module-card {
			background: rgba(0,0,0,0.7);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 4px solid var(--blue);
		}
		.module-card.inactive {
			border-left-color: var(--red);
			background: rgba(204, 68, 68, 0.1);
		}
		.module-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 1rem;
		}
		.module-title {
			color: var(--gold);
			font-size: 1.2rem;
			font-weight: bold;
			margin: 0;
		}
		.module-code {
			background: var(--blue);
			color: black;
			padding: 0.25rem 0.5rem;
			border-radius: 5px;
			font-size: 0.8rem;
			font-weight: bold;
		}
		.module-details {
			margin: 1rem 0;
		}
		.detail-row {
			display: flex;
			justify-content: space-between;
			margin: 0.5rem 0;
			font-size: 0.9rem;
		}
		.detail-label {
			color: var(--blue);
			font-weight: bold;
		}
		.detail-value {
			color: var(--green);
		}
		.module-description {
			background: rgba(255, 255, 255, 0.05);
			padding: 1rem;
			border-radius: 5px;
			margin: 1rem 0;
			border-left: 4px solid var(--gold);
		}
		.form-section {
			background: rgba(0,0,0,0.5);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--blue);
		}
		.form-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 1rem;
			margin: 1rem 0;
		}
		.form-group {
			margin-bottom: 1rem;
		}
		.form-group label {
			display: block;
			color: var(--blue);
			font-weight: bold;
			margin-bottom: 0.5rem;
		}
		.form-group input,
		.form-group select,
		.form-group textarea {
			width: 100%;
			padding: 0.75rem;
			background: rgba(0, 0, 0, 0.8);
			border: 1px solid var(--blue);
			border-radius: 5px;
			color: var(--green);
			font-family: inherit;
		}
		.form-group textarea {
			height: 100px;
			resize: vertical;
		}
		.level-badge {
			padding: 0.25rem 0.75rem;
			border-radius: 15px;
			font-size: 0.8rem;
			font-weight: bold;
		}
		.level-basic { background: var(--green); color: black; }
		.level-intermediate { background: var(--gold); color: black; }
		.level-advanced { background: var(--orange); color: black; }
		.level-expert { background: var(--red); color: white; }
		.dept-badge {
			padding: 0.25rem 0.75rem;
			border-radius: 5px;
			font-size: 0.8rem;
			font-weight: bold;
			margin-right: 0.5rem;
		}
		.dept-medsci { background: var(--blue); color: black; }
		.dept-engops { background: var(--gold); color: black; }
		.dept-sectac { background: var(--red); color: white; }
		.dept-command { background: var(--purple); color: white; }
		.dept-all { background: var(--green); color: black; }
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}
		.stat-card {
			background: rgba(255, 153, 0, 0.1);
			border: 2px solid var(--gold);
			border-radius: 10px;
			padding: 1rem;
			text-align: center;
		}
		.stat-number {
			font-size: 2rem;
			font-weight: bold;
			color: var(--gold);
		}
		.edit-form {
			display: none;
			background: rgba(0, 0, 0, 0.9);
			border: 2px solid var(--gold);
			border-radius: 10px;
			padding: 1.5rem;
			margin-top: 1rem;
		}
		.success-message {
			background: rgba(0, 255, 0, 0.2);
			border: 2px solid var(--green);
			padding: 1rem;
			border-radius: 10px;
			margin: 1rem 0;
			color: var(--green);
		}
		.error-message {
			background: rgba(255, 0, 0, 0.2);
			border: 2px solid var(--red);
			padding: 1rem;
			border-radius: 10px;
			margin: 1rem 0;
			color: var(--red);
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
				<div class="panel-2">TRAINING<span class="hop">-MGMT</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">TRAINING MODULE MANAGEMENT &#149; USS-VOYAGER</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">TRAINING</button>
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
					<div class="panel-3">TRAIN<span class="hop">-CTRL</span></div>
					<div class="panel-4">MODULES<span class="hop">-<?php echo count($modules); ?></span></div>
					<div class="panel-5">ACTIVE<span class="hop">-<?php echo count(array_filter($modules, function($m) { return $m['is_active']; })); ?></span></div>
					<div class="panel-6">DEPTS<span class="hop">-5</span></div>
					<div class="panel-7">STATUS<span class="hop">-READY</span></div>
					<div class="panel-8">ACCESS<span class="hop">-GRANTED</span></div>
					<div class="panel-9">COMMAND<span class="hop">-ONLY</span></div>
				</div>
				<div>
					<div class="button-row">
						<button onclick="playSoundAndRedirect('audio3', 'training_assignment.php')" class="f1-button">ASSIGN</button>
						<button onclick="playSoundAndRedirect('audio3', '#')" class="f1-button">MODULES</button>
					</div>
				</div>
			</div>
			<div class="right-frame">
				<main>
					<h1>🎓 Training Module Management</h1>
					<p>Manage training modules and competencies for crew certification</p>
					
					<?php if ($success): ?>
					<div class="success-message">
						<p>✅ <?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if ($error): ?>
					<div class="error-message">
						<p>❌ <?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Statistics -->
					<div class="training-container">
						<h3>Training System Statistics</h3>
						<div class="stats-grid">
							<?php
							$stats = [
								'total' => count($modules),
								'active' => count(array_filter($modules, function($m) { return $m['is_active']; })),
								'medsci' => count(array_filter($modules, function($m) { return $m['department'] === 'MED/SCI'; })),
								'engops' => count(array_filter($modules, function($m) { return $m['department'] === 'ENG/OPS'; })),
								'sectac' => count(array_filter($modules, function($m) { return $m['department'] === 'SEC/TAC'; })),
								'command' => count(array_filter($modules, function($m) { return $m['department'] === 'Command'; })),
								'universal' => count(array_filter($modules, function($m) { return $m['department'] === 'All'; }))
							];
							?>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['total']; ?></div>
								<div>Total Modules</div>
							</div>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['active']; ?></div>
								<div>Active Modules</div>
							</div>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['medsci']; ?></div>
								<div>MED/SCI</div>
							</div>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['engops']; ?></div>
								<div>ENG/OPS</div>
							</div>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['sectac']; ?></div>
								<div>SEC/TAC</div>
							</div>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['command']; ?></div>
								<div>Command</div>
							</div>
							<div class="stat-card">
								<div class="stat-number"><?php echo $stats['universal']; ?></div>
								<div>Universal</div>
							</div>
						</div>
					</div>
					<!-- Add New Module Form -->
					<div class="form-section">
						<h2>➕ Add New Training Module</h2>
						<form method="POST">
							<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
							<input type="hidden" name="action" value="add_module">
							
							<div class="form-grid">
								<div class="form-group">
									<label for="module_name">Module Name:</label>
									<input type="text" name="module_name" id="module_name" required>
								</div>
								<div class="form-group">
									<label for="module_code">Module Code:</label>
									<input type="text" name="module_code" id="module_code" required placeholder="e.g., MED-FA-001">
								</div>
								<div class="form-group">
									<label for="department">Department:</label>
									<select name="department" id="department" required>
										<option value="">Select Department</option>
										<option value="MED/SCI">MED/SCI</option>
										<option value="ENG/OPS">ENG/OPS</option>
										<option value="SEC/TAC">SEC/TAC</option>
										<option value="Command">Command</option>
										<option value="All">All Departments</option>
									</select>
								</div>
								<div class="form-group">
									<label for="certification_level">Certification Level:</label>
									<select name="certification_level" id="certification_level" required>
										<option value="Basic">Basic</option>
										<option value="Intermediate">Intermediate</option>
										<option value="Advanced">Advanced</option>
										<option value="Expert">Expert</option>
									</select>
								</div>
							</div>
							
							<div class="form-group">
								<label for="description">Description:</label>
								<textarea name="description" id="description" required></textarea>
							</div>
							
							<div class="form-group">
								<label for="prerequisites">Prerequisites (optional):</label>
								<input type="text" name="prerequisites" id="prerequisites" placeholder="e.g., First Aid Certification, Basic Medical Training">
							</div>
							
							<button type="submit" class="button-medium">Add Training Module</button>
						</form>
					</div>
					
					<!-- Existing Modules -->
					<div class="training-container">
						<h2>📚 Existing Training Modules</h2>
						<div class="module-grid">
							<?php foreach ($modules as $module): ?>
							<div class="module-card <?php echo $module['is_active'] ? '' : 'inactive'; ?>">
								<div class="module-header">
									<h3 class="module-title"><?php echo htmlspecialchars($module['module_name']); ?></h3>
									<span class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></span>
								</div>
								
								<div class="module-details">
									<div class="detail-row">
										<span class="detail-label">Department:</span>
										<span class="dept-badge dept-<?php echo strtolower(str_replace('/', '', $module['department'])); ?>">
											<?php echo htmlspecialchars($module['department']); ?>
										</span>
									</div>
									<div class="detail-row">
										<span class="detail-label">Level:</span>
										<span class="level-badge level-<?php echo strtolower($module['certification_level']); ?>">
											<?php echo htmlspecialchars($module['certification_level']); ?>
										</span>
									</div>
									<div class="detail-row">
										<span class="detail-label">Assigned to:</span>
										<span class="detail-value"><?php echo $module['assigned_count']; ?> crew member(s)</span>
									</div>
									<div class="detail-row">
										<span class="detail-label">Status:</span>
										<span class="detail-value" style="color: <?php echo $module['is_active'] ? 'var(--green)' : 'var(--red)'; ?>">
											<?php echo $module['is_active'] ? 'Active' : 'Inactive'; ?>
										</span>
									</div>
								</div>
								
								<div class="module-description">
									<?php echo htmlspecialchars($module['description']); ?>
									<?php if ($module['prerequisites']): ?>
									<br><br><strong>Prerequisites:</strong> <?php echo htmlspecialchars($module['prerequisites']); ?>
									<?php endif; ?>
								</div>
								
								<div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
									<button onclick="toggleEditForm(<?php echo $module['id']; ?>)" class="button-small">Edit</button>
									<a href="training_assignment.php?module_id=<?php echo $module['id']; ?>" class="button-small">Assign to Crew</a>
									<button onclick="confirmDeleteModule(<?php echo $module['id']; ?>, '<?php echo htmlspecialchars($module['module_name'], ENT_QUOTES); ?>', <?php echo $module['assigned_count']; ?>)" 
									        class="button-small" style="background: var(--red); color: white;">Delete</button>
								</div>
								
								<!-- Edit Form (Hidden by default) -->
								<div id="edit-form-<?php echo $module['id']; ?>" class="edit-form">
									<form method="POST">
										<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
										<input type="hidden" name="action" value="update_module">
										<input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
										
										<div class="form-group">
											<label>Module Name:</label>
											<input type="text" name="module_name" value="<?php echo htmlspecialchars($module['module_name']); ?>" required>
										</div>
										
										<div class="form-group">
											<label>Description:</label>
											<textarea name="description" required><?php echo htmlspecialchars($module['description']); ?></textarea>
										</div>
										
										<div class="form-group">
											<label>Prerequisites:</label>
											<input type="text" name="prerequisites" value="<?php echo htmlspecialchars($module['prerequisites']); ?>">
										</div>
										
										<div class="form-group">
											<label>Certification Level:</label>
											<select name="certification_level" required>
												<option value="Basic" <?php echo $module['certification_level'] === 'Basic' ? 'selected' : ''; ?>>Basic</option>
												<option value="Intermediate" <?php echo $module['certification_level'] === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
												<option value="Advanced" <?php echo $module['certification_level'] === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
												<option value="Expert" <?php echo $module['certification_level'] === 'Expert' ? 'selected' : ''; ?>>Expert</option>
											</select>
										</div>
										
										<div class="form-group">
											<label>
												<input type="checkbox" name="is_active" <?php echo $module['is_active'] ? 'checked' : ''; ?>>
												Active Module
											</label>
										</div>
										
										<div style="display: flex; gap: 0.5rem;">
											<button type="submit" class="button-small">Save Changes</button>
											<button type="button" onclick="toggleEditForm(<?php echo $module['id']; ?>)" class="button-small" style="background-color: var(--red);">Cancel</button>
										</div>
									</form>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					
					<!-- Hidden Delete Form -->
					<form id="delete-form" method="POST" style="display: none;">
						<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
						<input type="hidden" name="action" value="delete_module">
						<input type="hidden" name="module_id" id="delete-module-id">
					</form>
					
				</main>
				<footer>
					USS-VOYAGER NCC-74656 &copy; 2401 Starfleet Command<br>
					Training Management System - Command Access Only
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<script>
		function toggleEditForm(moduleId) {
			const form = document.getElementById('edit-form-' + moduleId);
			if (form.style.display === 'none' || form.style.display === '') {
				form.style.display = 'block';
			} else {
				form.style.display = 'none';
			}
		}
		
		function confirmDeleteModule(moduleId, moduleName, assignedCount) {
			if (assignedCount > 0) {
				alert('Cannot delete "' + moduleName + '": It has ' + assignedCount + ' active assignments. Please complete or remove assignments first.');
				return;
			}
			
			if (confirm('Are you sure you want to delete the training module "' + moduleName + '"?\n\nThis action cannot be undone and will also delete all historical assignment records for this module.')) {
				document.getElementById('delete-module-id').value = moduleId;
				document.getElementById('delete-form').submit();
			}
		}
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
