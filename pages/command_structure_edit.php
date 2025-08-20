<?php
require_once '../includes/config.php';

// Check if user is Captain
if (!hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

// Handle command position assignment
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'assign_position') {
    try {
        $pdo = getConnection();
        
        // Start a transaction to ensure both operations succeed or fail together
        $pdo->beginTransaction();
        
        // First, clear the old position assignment for this position
        $stmt = $pdo->prepare("UPDATE roster SET position = NULL WHERE position = ?");
        $stmt->execute([$_POST['command_position']]);
        
        // If assigning a person (not "none"), also clear their current position first
        if ($_POST['personnel_id'] && $_POST['personnel_id'] !== 'none') {
            // Clear any existing position for this person
            $stmt = $pdo->prepare("UPDATE roster SET position = NULL WHERE id = ?");
            $stmt->execute([$_POST['personnel_id']]);
            
            // Then assign the new person to this position
            $stmt = $pdo->prepare("UPDATE roster SET position = ? WHERE id = ?");
            $stmt->execute([$_POST['command_position'], $_POST['personnel_id']]);
        }
        
        // Commit the transaction
        $pdo->commit();
        
        $success = "Command position updated successfully.";
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Error updating command position: " . $e->getMessage();
    }
}

try {
    $pdo = getConnection();
    
    // Get all personnel available for assignment
    $stmt = $pdo->prepare("SELECT id, rank, first_name, last_name, department, position FROM roster ORDER BY department, rank, last_name, first_name");
    $stmt->execute();
    $all_personnel = $stmt->fetchAll();
    
    // Get current command structure
    $command_positions = [
        'Commanding Officer' => null,
        'First Officer' => null,
        'Second Officer' => null,
        'Third Officer' => null,
        'Head of ENG/OPS' => null,
        'Head of MED/SCI' => null,
        'Head of SEC/TAC' => null,
        'Chief Engineer' => null,
        'Chief Medical Officer' => null,
        'Security Chief' => null,
        'Operations Officer' => null,
        'Chief Science Officer' => null,
        'Tactical Officer' => null,
        'Helm Officer' => null,
        'Intelligence Officer' => null,
        'S.R.T. Leader' => null
    ];
    
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('" . implode("','", array_keys($command_positions)) . "') AND position IS NOT NULL AND position != ''");
    $stmt->execute();
    $command_crew = $stmt->fetchAll();
    
    foreach ($command_crew as $officer) {
        if (isset($command_positions[$officer['position']]) && $officer['position']) {
            $command_positions[$officer['position']] = $officer;
        }
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Define position requirements for filtering
$position_requirements = [
    'Commanding Officer' => ['department' => ['Command'], 'min_rank' => 'Captain'],
    'First Officer' => ['department' => ['Command'], 'min_rank' => 'Commander'],
    'Second Officer' => ['department' => ['Command'], 'min_rank' => 'Lieutenant Commander'],
    'Third Officer' => ['department' => ['Command'], 'min_rank' => 'Lieutenant'],
    'Head of ENG/OPS' => ['department' => ['ENG/OPS', 'Command'], 'min_rank' => 'Lieutenant Commander'],
    'Head of MED/SCI' => ['department' => ['MED/SCI', 'Command'], 'min_rank' => 'Lieutenant Commander'],
    'Head of SEC/TAC' => ['department' => ['SEC/TAC', 'Command'], 'min_rank' => 'Lieutenant Commander'],
    'Chief Engineer' => ['department' => ['ENG/OPS'], 'min_rank' => 'Lieutenant'],
    'Chief Medical Officer' => ['department' => ['MED/SCI'], 'min_rank' => 'Lieutenant'],
    'Security Chief' => ['department' => ['SEC/TAC'], 'min_rank' => 'Lieutenant'],
    'Operations Officer' => ['department' => ['ENG/OPS'], 'min_rank' => 'Lieutenant Junior Grade'],
    'Chief Science Officer' => ['department' => ['MED/SCI'], 'min_rank' => 'Lieutenant Junior Grade'],
    'Tactical Officer' => ['department' => ['SEC/TAC'], 'min_rank' => 'Lieutenant Junior Grade'],
    'Helm Officer' => ['department' => ['ENG/OPS'], 'min_rank' => 'Ensign'],
    'Intelligence Officer' => ['department' => ['SEC/TAC'], 'min_rank' => 'Lieutenant Junior Grade'],
    'S.R.T. Leader' => ['department' => ['SEC/TAC'], 'min_rank' => 'Lieutenant Junior Grade']
];

$rank_hierarchy = [
    'Crewman 3rd Class' => 1, 'Crewman 2nd Class' => 2, 'Crewman 1st Class' => 3,
    'Petty Officer 3rd class' => 4, 'Petty Officer 1st class' => 5, 'Chief Petter Officer' => 6,
    'Senior Chief Petty Officer' => 7, 'Master Chief Petty Officer' => 8,
    'Command Master Chief Petty Officer' => 9, 'Warrant officer' => 10, 'Ensign' => 11,
    'Lieutenant Junior Grade' => 12, 'Lieutenant' => 13, 'Lieutenant Commander' => 14,
    'Commander' => 15, 'Captain' => 16
];

function isEligibleForPosition($person, $position, $requirements, $rank_hierarchy) {
    // Check department
    if (!in_array($person['department'], $requirements['department'])) {
        return false;
    }
    
    // Check rank
    $person_rank_level = $rank_hierarchy[$person['rank']] ?? 0;
    $min_rank_level = $rank_hierarchy[$requirements['min_rank']] ?? 0;
    
    return $person_rank_level >= $min_rank_level;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Command Structure Editor</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.command-structure {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 1.5rem;
			margin: 2rem 0;
		}
		.command-row {
			display: flex;
			justify-content: center;
			gap: 1rem;
			width: 100%;
		}
		.command-row.single {
			justify-content: center;
		}
		.command-row.triple {
			justify-content: space-evenly;
			max-width: 800px;
		}
		.command-row.lower {
			justify-content: space-between;
			max-width: 1000px;
		}
		.position-box {
			padding: 1.5rem;
			border-radius: 10px;
			border: 2px solid;
			text-align: center;
			min-height: 120px;
			min-width: 250px;
			flex: 0 0 auto;
			position: relative;
		}
		.command-box { border-color: var(--red); background: rgba(204, 68, 68, 0.2); }
		.eng-ops-box { border-color: var(--orange); background: rgba(255, 136, 0, 0.2); }
		.med-sci-box { border-color: var(--blue); background: rgba(85, 102, 255, 0.2); }
		.sec-tac-box { border-color: var(--gold); background: rgba(255, 170, 0, 0.2); }
		
		.assignment-form {
			margin-top: 1rem;
		}
		.current-assignment {
			color: var(--bluey);
			font-weight: bold;
			margin-bottom: 0.5rem;
		}
		.position-title {
			color: var(--red);
			font-size: 0.9rem;
			margin-bottom: 0.5rem;
		}
		.edit-button {
			background-color: var(--bluey);
			color: black;
			border: none;
			padding: 0.5rem 1rem;
			border-radius: 5px;
			cursor: pointer;
			margin-top: 0.5rem;
		}
		.save-button {
			background-color: var(--blue);
			color: black;
			border: none;
			padding: 0.5rem 1rem;
			border-radius: 5px;
			cursor: pointer;
		}
		.cancel-button {
			background-color: var(--gray);
			color: black;
			border: none;
			padding: 0.5rem 1rem;
			border-radius: 5px;
			cursor: pointer;
		}
		.editing {
			background: rgba(0,0,0,0.3);
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
				<div class="panel-2">CMD<span class="hop">-EDIT</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">COMMAND STRUCTURE EDITOR</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">CMD-EDIT</button>
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
					<div class="panel-3">CAPT<span class="hop">-ONLY</span></div>
					<div class="panel-4">ASSIGN<span class="hop">-CMD</span></div>
					<div class="panel-5">STRUCT<span class="hop">-EDIT</span></div>
					<div class="panel-6">RANKS<span class="hop">-CHK</span></div>
					<div class="panel-7">DEPT<span class="hop">-FILT</span></div>
					<div class="panel-8">SECURE<span class="hop">-CONN</span></div>
					<div class="panel-9">UPDATE<span class="hop">-SYS</span></div>
				</div>
				<div>
					<div class="panel-10">ADMIN<span class="hop">-CTRL</span></div>
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
					<h1>Command Structure Editor</h1>
					<h2>USS-Serenity Chain of Command Management</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--blue);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<div style="background: rgba(204, 68, 68, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border: 1px solid var(--red);">
						<h4>Captain Authorization Required</h4>
						<p style="color: var(--red);"><strong>Access Level:</strong> Captain Only</p>
						<p style="color: var(--orange);"><em>Click "Edit" on any position to assign personnel. All crew members are available for selection. If someone holds a different position, they will be automatically moved to the new assignment.</em></p>
						<p style="color: var(--bluey); font-size: 0.9rem;"><strong>Legend:</strong> ✓ = Meets requirements | ⚠ = Does not meet rank/department requirements</p>
						
						<!-- Debug: Show filled positions -->
						<div style="margin-top: 1rem; padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 5px;">
							<h5 style="color: var(--orange);">Current Assignments (Debug):</h5>
							<?php foreach ($command_positions as $pos => $person): ?>
								<div style="color: var(--bluey); font-size: 0.8rem;">
									<?php echo $pos; ?>: <?php echo $person ? htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']) : 'VACANT'; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					
					<h3>Current Command Structure</h3>
					<div class="command-structure">
						<!-- Captain Level -->
						<div class="command-row single">
							<?php 
							$position = 'Commanding Officer';
							$person = $command_positions[$position];
							?>
							<div class="position-box command-box" id="pos_<?php echo str_replace(' ', '_', $position); ?>">
								<div class="position-title"><?php echo $position; ?></div>
								<div class="current-assignment">
									<?php if ($person): ?>
										<?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?>
									<?php else: ?>
										Position Vacant
									<?php endif; ?>
								</div>
								<button class="edit-button" onclick="editPosition('<?php echo $position; ?>')">Edit Assignment</button>
								<div class="assignment-form" id="form_<?php echo str_replace(' ', '_', $position); ?>" style="display: none;">
									<form method="POST" action="">
										<input type="hidden" name="action" value="assign_position">
										<input type="hidden" name="command_position" value="<?php echo $position; ?>">
										<select name="personnel_id" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--red); margin: 0.5rem 0;">
											<option value="none">Vacant Position</option>
											<?php foreach ($all_personnel as $personnel): ?>
												<?php 
												$is_eligible = isEligibleForPosition($personnel, $position, $position_requirements[$position], $rank_hierarchy);
												$current_position = $personnel['position'] ? ' [Currently: ' . $personnel['position'] . ']' : '';
												$eligibility_indicator = $is_eligible ? '✓' : '⚠';
												?>
												<option value="<?php echo $personnel['id']; ?>" <?php echo ($person && $person['id'] == $personnel['id']) ? 'selected' : ''; ?>>
													<?php echo htmlspecialchars($eligibility_indicator . ' ' . $personnel['rank'] . ' ' . $personnel['first_name'] . ' ' . $personnel['last_name'] . ' (' . $personnel['department'] . ')' . $current_position); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<div>
											<button type="submit" class="save-button">Save</button>
											<button type="button" class="cancel-button" onclick="cancelEdit('<?php echo $position; ?>')">Cancel</button>
										</div>
									</form>
								</div>
							</div>
						</div>
						
						<!-- Repeat for all other positions... -->
						<?php 
						$structure_levels = [
							['First Officer'],
							['Second Officer'],
							['Third Officer'],
							['Head of ENG/OPS', 'Head of MED/SCI', 'Head of SEC/TAC'],
							['Chief Engineer', 'Chief Medical Officer', 'Security Chief'],
							['Operations Officer', 'Chief Science Officer', 'Tactical Officer'],
							['Helm Officer', 'Intelligence Officer', 'S.R.T. Leader']
						];
						
						$box_classes = [
							'First Officer' => 'command-box',
							'Second Officer' => 'command-box',
							'Third Officer' => 'command-box',
							'Head of ENG/OPS' => 'eng-ops-box',
							'Head of MED/SCI' => 'med-sci-box',
							'Head of SEC/TAC' => 'sec-tac-box',
							'Chief Engineer' => 'eng-ops-box',
							'Chief Medical Officer' => 'med-sci-box',
							'Security Chief' => 'sec-tac-box',
							'Operations Officer' => 'eng-ops-box',
							'Chief Science Officer' => 'med-sci-box',
							'Tactical Officer' => 'sec-tac-box',
							'Helm Officer' => 'eng-ops-box',
							'Intelligence Officer' => 'sec-tac-box',
							'S.R.T. Leader' => 'sec-tac-box'
						];
						
						foreach ($structure_levels as $level):
							$row_class = count($level) == 1 ? 'single' : (count($level) == 3 ? 'triple' : 'lower');
						?>
						<div class="command-row <?php echo $row_class; ?>">
							<?php foreach ($level as $position): ?>
								<?php 
								$person = $command_positions[$position];
								$box_class = $box_classes[$position];
								?>
								<div class="position-box <?php echo $box_class; ?>" id="pos_<?php echo str_replace(' ', '_', $position); ?>">
									<div class="position-title"><?php echo $position; ?></div>
									<div class="current-assignment">
										<?php if ($person): ?>
											<?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<button class="edit-button" onclick="editPosition('<?php echo $position; ?>')">Edit Assignment</button>
									<div class="assignment-form" id="form_<?php echo str_replace(' ', '_', $position); ?>" style="display: none;">
										<form method="POST" action="">
											<input type="hidden" name="action" value="assign_position">
											<input type="hidden" name="command_position" value="<?php echo $position; ?>">
											<select name="personnel_id" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey); margin: 0.5rem 0;">
												<option value="none">Vacant Position</option>
												<?php foreach ($all_personnel as $personnel): ?>
													<?php 
													$is_eligible = isEligibleForPosition($personnel, $position, $position_requirements[$position], $rank_hierarchy);
													$current_position = $personnel['position'] ? ' [Currently: ' . $personnel['position'] . ']' : '';
													$eligibility_indicator = $is_eligible ? '✓' : '⚠';
													?>
													<option value="<?php echo $personnel['id']; ?>" <?php echo ($person && $person['id'] == $personnel['id']) ? 'selected' : ''; ?>>
														<?php echo htmlspecialchars($eligibility_indicator . ' ' . $personnel['rank'] . ' ' . $personnel['first_name'] . ' ' . $personnel['last_name'] . ' (' . $personnel['department'] . ')' . $current_position); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<div>
												<button type="submit" class="save-button">Save</button>
												<button type="button" class="cancel-button" onclick="cancelEdit('<?php echo $position; ?>')">Cancel</button>
											</div>
										</form>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php endforeach; ?>
					</div>
					
					<div style="background: rgba(0,0,0,0.5); padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border: 1px solid var(--bluey);">
						<h4>Assignment Requirements</h4>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
							<div>
								<strong style="color: var(--red);">Command Positions:</strong>
								<ul style="color: var(--red); font-size: 0.9rem;">
									<li>Captain: Command Department, Captain rank</li>
									<li>First Officer: Command Department, Commander+</li>
									<li>Second Officer: Command Department, Lt. Commander+</li>
									<li>Third Officer: Command Department, Lieutenant+</li>
								</ul>
							</div>
							<div>
								<strong style="color: var(--orange);">Engineering/Operations:</strong>
								<ul style="color: var(--orange); font-size: 0.9rem;">
									<li>Head of ENG/OPS: ENG/OPS or Command, Lt. Commander+</li>
									<li>Chief Engineer: ENG/OPS Department, Lieutenant+</li>
									<li>Operations Officer: ENG/OPS Department, Lt. JG+</li>
									<li>Helm Officer: ENG/OPS Department, Ensign+</li>
								</ul>
							</div>
							<div>
								<strong style="color: var(--blue);">Medical/Science:</strong>
								<ul style="color: var(--blue); font-size: 0.9rem;">
									<li>Head of MED/SCI: MED/SCI or Command, Lt. Commander+</li>
									<li>Chief Medical Officer: MED/SCI Department, Lieutenant+</li>
									<li>Chief Science Officer: MED/SCI Department, Lt. JG+</li>
								</ul>
							</div>
							<div>
								<strong style="color: var(--gold);">Security/Tactical:</strong>
								<ul style="color: var(--gold); font-size: 0.9rem;">
									<li>Head of SEC/TAC: SEC/TAC or Command, Lt. Commander+</li>
									<li>Security Chief: SEC/TAC Department, Lieutenant+</li>
									<li>Tactical Officer: SEC/TAC Department, Lt. JG+</li>
									<li>Intelligence & S.R.T.: SEC/TAC Department, Lt. JG+</li>
								</ul>
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
		function editPosition(position) {
			// Hide all other forms first
			const allForms = document.querySelectorAll('.assignment-form');
			allForms.forEach(form => {
				form.style.display = 'none';
				form.parentElement.classList.remove('editing');
			});
			
			// Show the selected form
			const formId = 'form_' + position.replace(/ /g, '_');
			const posId = 'pos_' + position.replace(/ /g, '_');
			const form = document.getElementById(formId);
			const posBox = document.getElementById(posId);
			
			if (form && posBox) {
				form.style.display = 'block';
				posBox.classList.add('editing');
			}
		}
		
		function cancelEdit(position) {
			const formId = 'form_' + position.replace(/ /g, '_');
			const posId = 'pos_' + position.replace(/ /g, '_');
			const form = document.getElementById(formId);
			const posBox = document.getElementById(posId);
			
			if (form && posBox) {
				form.style.display = 'none';
				posBox.classList.remove('editing');
			}
		}
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
