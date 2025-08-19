<?php
require_once '../includes/config.php';

// Handle image upload
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Only JPEG, PNG, and GIF images are allowed.");
    }
    
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception("Image file size must be less than 5MB.");
    }
    
    $upload_dir = '../assets/crew_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('crew_') . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return 'assets/crew_photos/' . $filename;
    } else {
        throw new Exception("Failed to upload image.");
    }
}

// Handle adding new personnel (Captain only - full command positions)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_personnel') {
    if (hasPermission('Captain')) {
        try {
            $image_path = '';
            if (isset($_FILES['crew_image']) && $_FILES['crew_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = handleImageUpload($_FILES['crew_image']);
            }
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, position, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['rank'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['species'],
                $_POST['department'],
                $_POST['position'] ?? '',
                $image_path
            ]);
            $success = "Personnel added successfully.";
        } catch (Exception $e) {
            $error = "Error adding personnel: " . $e->getMessage();
        }
    } else {
        $error = "Access denied. Captain authorization required.";
    }
}

// Handle self-registration for reporting purposes (limited to basic crew positions)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'self_register') {
    try {
        // Check if person already exists
        $pdo = getConnection();
        $check_stmt = $pdo->prepare("SELECT id FROM roster WHERE first_name = ? AND last_name = ? AND species = ?");
        $check_stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['species']]);
        
        if ($check_stmt->fetch()) {
            $error = "A crew member with this name and species already exists in the roster.";
        } else {
            $image_path = '';
            if (isset($_FILES['crew_image']) && $_FILES['crew_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = handleImageUpload($_FILES['crew_image']);
            }
            
            // Self-registration limited to lower ranks only
            $allowed_self_ranks = [
                'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
                'Petty Officer 3rd class', 'Petty Officer 1st class'
            ];
            
            if (!in_array($_POST['rank'], $allowed_self_ranks)) {
                $error = "Self-registration is limited to enlisted ranks only.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, position, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['rank'],
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['species'],
                    $_POST['department'],
                    '', // No special positions for self-registration
                    $image_path
                ]);
                $success = "Self-registration completed successfully. You can now submit reports.";
            }
        }
    } catch (Exception $e) {
        $error = "Error during self-registration: " . $e->getMessage();
    }
}

// Get roster data
try {
    $pdo = getConnection();
    
    // Get command structure
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
    
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('" . implode("','", array_keys($command_positions)) . "') ORDER BY FIELD(position, '" . implode("','", array_keys($command_positions)) . "')");
    $stmt->execute();
    $command_crew = $stmt->fetchAll();
    
    foreach ($command_crew as $officer) {
        $command_positions[$officer['position']] = $officer;
    }
    
    // Get all crew members
    $stmt = $pdo->prepare("SELECT * FROM roster ORDER BY department, rank, last_name, first_name");
    $stmt->execute();
    $all_crew = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

$ranks = [
    'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
    'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petty Officer', 
    'Senior Chief Petty Officer', 'Master Chief Petty Officer', 
    'Command Master Chief Petty Officer', 'Warrant officer', 'Ensign', 
    'Lieutenant Junior Grade', 'Lieutenant', 'Lieutenant Commander', 
    'Commander', 'Captain'
];
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Ship's Roster</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.command-structure {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 1rem;
			margin: 2rem 0;
		}
		.officer-box {
			padding: 1rem;
			border-radius: 10px;
			border: 2px solid;
			text-align: center;
			min-height: 100px;
		}
		.command-box { border-color: var(--red); background: rgba(204, 68, 68, 0.2); }
		.eng-ops-box { border-color: var(--orange); background: rgba(255, 136, 0, 0.2); }
		.med-sci-box { border-color: var(--blue); background: rgba(85, 102, 255, 0.2); }
		.sec-tac-box { border-color: var(--gold); background: rgba(255, 170, 0, 0.2); }
		
		.crew-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}
		.crew-card {
			padding: 1rem;
			border-radius: 10px;
			border: 2px solid;
		}
		.phaser-training {
			font-size: 0.8rem;
			margin-top: 0.5rem;
			padding: 0.25rem;
			background: rgba(0,0,0,0.3);
			border-radius: 5px;
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
				<div class="panel-2">CREW<span class="hop">-ROSTER</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">SHIP'S ROSTER &#149; USS-SERENITY</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'reports.php')">REPORTS</button>
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
					<div class="panel-3">CREW<span class="hop">-450</span></div>
					<div class="panel-4">CMD<span class="hop">-15</span></div>
					<div class="panel-5">ENG<span class="hop">-120</span></div>
					<div class="panel-6">MED<span class="hop">-85</span></div>
					<div class="panel-7">SEC<span class="hop">-95</span></div>
					<div class="panel-8">SCI<span class="hop">-75</span></div>
					<div class="panel-9">OPS<span class="hop">-60</span></div>
				</div>
				<div>
					<div class="panel-10">ACTIVE<span class="hop">-ALL</span></div>
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
					<h1>Ship's Roster</h1>
					<h2>USS-Serenity NCC-74714</h2>
					
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
					
					<h3>Command Structure</h3>
					<div class="command-structure">
						<!-- Top Level Command -->
						<div class="officer-box command-box" style="grid-column: 2;">
							<?php if ($command_positions['Commanding Officer']): ?>
								<strong><?php echo htmlspecialchars($command_positions['Commanding Officer']['rank'] . ' ' . $command_positions['Commanding Officer']['first_name'] . ' ' . $command_positions['Commanding Officer']['last_name']); ?></strong><br>
								<small>Commanding Officer</small>
							<?php else: ?>
								<em>Position Vacant</em><br>
								<small>Commanding Officer</small>
							<?php endif; ?>
						</div>
						
						<!-- First, Second, Third Officers -->
						<?php foreach (['First Officer', 'Second Officer', 'Third Officer'] as $position): ?>
						<div class="officer-box command-box">
							<?php if ($command_positions[$position]): ?>
								<strong><?php echo htmlspecialchars($command_positions[$position]['rank'] . ' ' . $command_positions[$position]['first_name'] . ' ' . $command_positions[$position]['last_name']); ?></strong><br>
								<small><?php echo $position; ?></small>
							<?php else: ?>
								<em>Position Vacant</em><br>
								<small><?php echo $position; ?></small>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
						
						<!-- Department Heads -->
						<div class="officer-box eng-ops-box">
							<?php if ($command_positions['Head of ENG/OPS']): ?>
								<strong><?php echo htmlspecialchars($command_positions['Head of ENG/OPS']['rank'] . ' ' . $command_positions['Head of ENG/OPS']['first_name'] . ' ' . $command_positions['Head of ENG/OPS']['last_name']); ?></strong><br>
								<small>Head of ENG/OPS</small>
							<?php else: ?>
								<em>Position Vacant</em><br>
								<small>Head of ENG/OPS</small>
							<?php endif; ?>
						</div>
						
						<div class="officer-box med-sci-box">
							<?php if ($command_positions['Head of MED/SCI']): ?>
								<strong><?php echo htmlspecialchars($command_positions['Head of MED/SCI']['rank'] . ' ' . $command_positions['Head of MED/SCI']['first_name'] . ' ' . $command_positions['Head of MED/SCI']['last_name']); ?></strong><br>
								<small>Head of MED/SCI</small>
							<?php else: ?>
								<em>Position Vacant</em><br>
								<small>Head of MED/SCI</small>
							<?php endif; ?>
						</div>
						
						<div class="officer-box sec-tac-box">
							<?php if ($command_positions['Head of SEC/TAC']): ?>
								<strong><?php echo htmlspecialchars($command_positions['Head of SEC/TAC']['rank'] . ' ' . $command_positions['Head of SEC/TAC']['first_name'] . ' ' . $command_positions['Head of SEC/TAC']['last_name']); ?></strong><br>
								<small>Head of SEC/TAC</small>
							<?php else: ?>
								<em>Position Vacant</em><br>
								<small>Head of SEC/TAC</small>
							<?php endif; ?>
						</div>
					</div>
					
					<?php if (hasPermission('Captain')): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h4>Add New Personnel (Captain Only)</h4>
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="add_personnel">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
								<div>
									<label style="color: var(--bluey);">Rank:</label>
									<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
										<?php foreach ($ranks as $rank): ?>
										<option value="<?php echo htmlspecialchars($rank); ?>"><?php echo htmlspecialchars($rank); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div>
									<label style="color: var(--bluey);">First Name:</label>
									<input type="text" name="first_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div>
									<label style="color: var(--bluey);">Last Name:</label>
									<input type="text" name="last_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div>
									<label style="color: var(--bluey);">Species:</label>
									<input type="text" name="species" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div>
									<label style="color: var(--bluey);">Department:</label>
									<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
										<option value="Command">Command</option>
										<option value="MED/SCI">MED/SCI</option>
										<option value="ENG/OPS">ENG/OPS</option>
										<option value="SEC/TAC">SEC/TAC</option>
									</select>
								</div>
								<div>
									<label style="color: var(--bluey);">Position:</label>
									<input type="text" name="position" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div style="grid-column: span 2;">
									<label style="color: var(--bluey);">Crew Photo:</label>
									<input type="file" name="crew_image" accept="image/*" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									<small style="color: var(--orange);">Optional. JPEG, PNG, or GIF. Max 5MB.</small>
								</div>
							</div>
							<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Add Personnel</button>
						</form>
					</div>
					<?php endif; ?>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h4>Not in the Roster? Register for Reporting</h4>
						<p style="color: var(--orange);">If you need to submit reports but aren't in the roster, you can add yourself here. Limited to enlisted ranks only.</p>
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="self_register">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
								<div>
									<label style="color: var(--bluey);">Rank:</label>
									<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
										<option value="Crewman 3rd Class">Crewman 3rd Class</option>
										<option value="Crewman 2nd Class">Crewman 2nd Class</option>
										<option value="Crewman 1st Class">Crewman 1st Class</option>
										<option value="Petty Officer 3rd class">Petty Officer 3rd class</option>
										<option value="Petty Officer 1st class">Petty Officer 1st class</option>
									</select>
								</div>
								<div>
									<label style="color: var(--bluey);">First Name:</label>
									<input type="text" name="first_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div>
									<label style="color: var(--bluey);">Last Name:</label>
									<input type="text" name="last_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div>
									<label style="color: var(--bluey);">Species:</label>
									<input type="text" name="species" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
								</div>
								<div>
									<label style="color: var(--bluey);">Department:</label>
									<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
										<option value="MED/SCI">MED/SCI</option>
										<option value="ENG/OPS">ENG/OPS</option>
										<option value="SEC/TAC">SEC/TAC</option>
									</select>
								</div>
								<div>
									<label style="color: var(--bluey);">Crew Photo:</label>
									<input type="file" name="crew_image" accept="image/*" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									<small style="color: var(--orange);">Optional. JPEG, PNG, or GIF. Max 5MB.</small>
								</div>
							</div>
							<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Register for Reporting</button>
						</form>
					</div>
					
					<h3>All Personnel</h3>
					<div class="crew-grid">
						<?php foreach ($all_crew as $crew_member): ?>
						<div class="crew-card <?php 
							switch($crew_member['department']) {
								case 'Command': echo 'command-box'; break;
								case 'ENG/OPS': echo 'eng-ops-box'; break;
								case 'MED/SCI': echo 'med-sci-box'; break;
								case 'SEC/TAC': echo 'sec-tac-box'; break;
							}
						?>">
							<?php if ($crew_member['image_path'] && file_exists('../' . $crew_member['image_path'])): ?>
							<img src="../<?php echo htmlspecialchars($crew_member['image_path']); ?>" alt="<?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem; border: 2px solid var(--bluey);">
							<?php endif; ?>
							<strong><?php echo htmlspecialchars($crew_member['rank']); ?></strong><br>
							<h4><?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name']); ?></h4>
							<p>Species: <?php echo htmlspecialchars($crew_member['species']); ?></p>
							<p>Department: <?php echo htmlspecialchars($crew_member['department']); ?></p>
							<?php if ($crew_member['position']): ?>
							<p><em><?php echo htmlspecialchars($crew_member['position']); ?></em></p>
							<?php endif; ?>
							
							<?php if ($crew_member['phaser_training']): ?>
							<div class="phaser-training">
								<strong>Phaser Training:</strong><br>
								<?php echo htmlspecialchars($crew_member['phaser_training']); ?>
							</div>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
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
