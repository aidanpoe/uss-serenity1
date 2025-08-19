<?php
require_once '../includes/config.php';

// Handle image upload
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        if (isset($file['error'])) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("File size exceeds maximum allowed.");
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("File upload was incomplete.");
                case UPLOAD_ERR_NO_FILE:
                    return ''; // No file uploaded, which is OK
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("Server configuration error: no temp directory.");
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Server error: cannot write file.");
                default:
                    throw new Exception("Unknown upload error.");
            }
        }
        return '';
    }
    
    // Check file size (5MB limit)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception("Image file size must be less than 5MB. Your file is " . round($file['size'] / 1024 / 1024, 2) . "MB.");
    }
    
    // Get file extension and normalize it
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Only JPEG, PNG, GIF, and WebP images are allowed. You uploaded: " . strtoupper($file_extension));
    }
    
    // Additional MIME type validation (more comprehensive)
    $allowed_mime_types = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($detected_type, $allowed_mime_types) && !in_array($file['type'], $allowed_mime_types)) {
        throw new Exception("Invalid image file type detected. Expected image file, got: " . $detected_type);
    }
    
    // Verify it's actually an image by trying to get image info
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        throw new Exception("File is not a valid image or is corrupted.");
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../assets/crew_photos/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Could not create upload directory.");
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        throw new Exception("Upload directory is not writable.");
    }
    
    // Generate unique filename
    $filename = uniqid('crew_') . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return 'assets/crew_photos/' . $filename;
    } else {
        throw new Exception("Failed to save uploaded image file.");
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
            $success = "Personnel added successfully." . ($image_path ? " Image uploaded successfully." : "");
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
        // Check if person already exists (by name only, species can be shared)
        $pdo = getConnection();
        $check_stmt = $pdo->prepare("SELECT id FROM roster WHERE first_name = ? AND last_name = ?");
        $check_stmt->execute([$_POST['first_name'], $_POST['last_name']]);
        
        if ($check_stmt->fetch()) {
            $error = "A crew member with this name already exists in the roster.";
        } else {
            $image_path = '';
            if (isset($_FILES['crew_image']) && $_FILES['crew_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = handleImageUpload($_FILES['crew_image']);
            }
            
            // Self-registration limited to ranks below Commander
            $allowed_self_ranks = [
                'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
                'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petter Officer',
                'Senior Chief Petty Officer', 'Master Chief Petty Officer', 
                'Command Master Chief Petty Officer', 'Warrant officer', 'Ensign',
                'Lieutenant Junior Grade', 'Lieutenant', 'Lieutenant Commander'
            ];
            
            if (!in_array($_POST['rank'], $allowed_self_ranks)) {
                $error = "Self-registration is limited to ranks below Commander.";
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
                $success = "Self-registration completed successfully. You can now submit reports." . ($image_path ? " Image uploaded successfully." : "");
            }
        }
    } catch (Exception $e) {
        $error = "Error during self-registration: " . $e->getMessage();
    }
}

// Get roster data
try {
    $pdo = getConnection();
    
    // Handle admin update for Captain's name
    if (isset($_GET['update_captain_name']) && hasPermission('Captain')) {
        try {
            // Update the users table
            $stmt = $pdo->prepare("UPDATE users SET first_name = 'Aidan' WHERE username = 'Poe' AND department = 'Captain'");
            $stmt->execute();
            $affected1 = $stmt->rowCount();
            
            // Update the roster table
            $stmt = $pdo->prepare("UPDATE roster SET first_name = 'Aidan' WHERE last_name = 'Poe' AND rank = 'Captain'");
            $stmt->execute();
            $affected2 = $stmt->rowCount();
            
            // Update session if this is the current user
            if (isset($_SESSION['username']) && $_SESSION['username'] === 'Poe') {
                $_SESSION['first_name'] = 'Aidan';
            }
            
            $update_message = "Captain's name updated successfully. Users table: $affected1 rows, Roster table: $affected2 rows affected.";
            
        } catch (PDOException $e) {
            $update_message = "Error updating Captain's name: " . $e->getMessage();
        }
    }
    
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
    
    // Calculate department counts
    $department_counts = [
        'All' => count($all_crew),
        'Command' => 0,
        'ENG/OPS' => 0,
        'MED/SCI' => 0,
        'SEC/TAC' => 0
    ];
    
    foreach ($all_crew as $crew_member) {
        if (isset($department_counts[$crew_member['department']])) {
            $department_counts[$crew_member['department']]++;
        }
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

$ranks = [
    'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
    'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petter Officer', 
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
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 2.5rem;
			margin: 3rem 0;
		}
		.command-row {
			display: flex;
			justify-content: center;
			gap: 1.5rem;
			width: 100%;
		}
		.command-row.single {
			justify-content: center;
		}
		.command-row.triple {
			justify-content: space-evenly;
			max-width: 900px;
		}
		.command-row.lower {
			justify-content: space-between;
			max-width: 1100px;
		}
		.officer-box {
			padding: 2rem 1.5rem;
			border-radius: 12px;
			border: 3px solid;
			text-align: center;
			min-height: 140px;
			min-width: 280px;
			flex: 0 0 auto;
			position: relative;
			box-shadow: 0 4px 8px rgba(0,0,0,0.3);
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
					
					<?php if (isset($update_message)): ?>
					<div style="background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--blue);"><?php echo htmlspecialchars($update_message); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<h3>Command Structure</h3>
					<?php if (hasPermission('Captain')): ?>
					<div style="text-align: center; margin: 1rem 0;">
						<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" style="background-color: var(--bluey); color: black; border: none; padding: 0.75rem 1.5rem; border-radius: 5px;">✏️ Edit Command Structure</button>
					</div>
					<?php endif; ?>
					<div class="command-structure">
						<!-- Captain (Top Level) -->
						<div class="command-row single">
							<div class="officer-box command-box">
								<div style="color: var(--red); font-size: 0.9rem; margin-bottom: 0.5rem;">Commanding Officer</div>
								<div style="color: var(--bluey); font-weight: bold; margin-bottom: 0.5rem;">
									<?php if ($command_positions['Commanding Officer']): ?>
										<?php echo htmlspecialchars($command_positions['Commanding Officer']['rank'] . ' ' . $command_positions['Commanding Officer']['first_name'] . ' ' . $command_positions['Commanding Officer']['last_name']); ?>
									<?php else: ?>
										Position Vacant
									<?php endif; ?>
								</div>
								<?php if (hasPermission('Captain')): ?>
								<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" style="background-color: var(--bluey); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">Edit Assignment</button>
								<?php endif; ?>
							</div>
						</div>
						
						<!-- Dynamic Command Structure Levels -->
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
								$color = '';
								switch($box_class) {
									case 'command-box': $color = 'var(--red)'; break;
									case 'eng-ops-box': $color = 'var(--orange)'; break;
									case 'med-sci-box': $color = 'var(--blue)'; break;
									case 'sec-tac-box': $color = 'var(--gold)'; break;
								}
								?>
								<div class="officer-box <?php echo $box_class; ?>">
									<div style="color: <?php echo $color; ?>; font-size: 0.9rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($position); ?></div>
									<div style="color: var(--bluey); font-weight: bold; margin-bottom: 0.5rem;">
										<?php if ($person): ?>
											<?php echo htmlspecialchars($person['rank'] . ' ' . $person['first_name'] . ' ' . $person['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" style="background-color: var(--bluey); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">Edit Assignment</button>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<?php endforeach; ?>
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
									<input type="file" name="crew_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									<small style="color: var(--orange);">Optional. JPEG, PNG, GIF, or WebP. Max 5MB.</small>
								</div>
							</div>
							<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Add Personnel</button>
						</form>
					</div>
					<?php endif; ?>
					
					<?php if (!hasPermission('Captain')): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h4>Self-Registration (Limited Rank Personnel)</h4>
						<p style="color: var(--orange); font-size: 0.9rem;">Limited rank crew can add themselves for reporting purposes.</p>
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="self_register">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
								<div>
									<label style="color: var(--gold);">Rank:</label>
									<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
										<option value="Crewman 3rd Class">Crewman 3rd Class</option>
										<option value="Crewman 2nd Class">Crewman 2nd Class</option>
										<option value="Crewman 1st Class">Crewman 1st Class</option>
										<option value="Petty Officer 3rd class">Petty Officer 3rd class</option>
										<option value="Petty Officer 1st class">Petty Officer 1st class</option>
										<option value="Chief Petter Officer">Chief Petter Officer</option>
										<option value="Ensign">Ensign</option>
										<option value="Lieutenant Junior Grade">Lieutenant Junior Grade</option>
										<option value="Lieutenant">Lieutenant</option>
									</select>
								</div>
								<div>
									<label style="color: var(--gold);">First Name:</label>
									<input type="text" name="first_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">Last Name:</label>
									<input type="text" name="last_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">Species:</label>
									<input type="text" name="species" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">Department:</label>
									<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
										<option value="MED/SCI">MED/SCI</option>
										<option value="ENG/OPS">ENG/OPS</option>
										<option value="SEC/TAC">SEC/TAC</option>
									</select>
								</div>
								<div style="grid-column: span 2;">
									<label style="color: var(--gold);">Crew Photo:</label>
									<input type="file" name="crew_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
									<small style="color: var(--orange);">Optional. JPEG, PNG, GIF, or WebP. Max 5MB.</small>
								</div>
							</div>
							<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Self-Register</button>
						</form>
					</div>
					<?php endif; ?>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h4>Need Access to Ship Systems?</h4>
						<p style="color: var(--orange);">All new crew members should create a personal account to access department systems.</p>
						<div style="text-align: center; margin: 1rem 0;">
							<button onclick="playSoundAndRedirect('audio2', 'register.php')" style="background-color: var(--blue); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem;">
								Create Account & Join Crew
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'login.php')" style="background-color: var(--african-violet); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem;">
								Login to Existing Account
							</button>
						</div>
						<p style="color: var(--bluey); font-size: 0.9rem; text-align: center;">Account creation automatically adds you to the ship's roster.</p>
					</div>
					
					<!-- Department Filter Section -->
					<div style="background: rgba(0,0,0,0.5); padding: 1.5rem; border-radius: 15px; margin: 2rem 0;">
						<h4 style="margin-bottom: 1rem;">Department Filters</h4>
						<div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
							<button class="filter-btn active" onclick="filterByDepartment('All')" data-department="All" style="background-color: var(--blue); color: black; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer;">
								All Personnel (<?php echo $department_counts['All']; ?>)
							</button>
							<button class="filter-btn" onclick="filterByDepartment('Command')" data-department="Command" style="background-color: var(--red); color: black; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer;">
								Command (<?php echo $department_counts['Command']; ?>)
							</button>
							<button class="filter-btn" onclick="filterByDepartment('ENG/OPS')" data-department="ENG/OPS" style="background-color: var(--gold); color: black; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer;">
								ENG/OPS (<?php echo $department_counts['ENG/OPS']; ?>)
							</button>
							<button class="filter-btn" onclick="filterByDepartment('MED/SCI')" data-department="MED/SCI" style="background-color: var(--bluey); color: black; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer;">
								MED/SCI (<?php echo $department_counts['MED/SCI']; ?>)
							</button>
							<button class="filter-btn" onclick="filterByDepartment('SEC/TAC')" data-department="SEC/TAC" style="background-color: var(--orange); color: black; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer;">
								SEC/TAC (<?php echo $department_counts['SEC/TAC']; ?>)
							</button>
						</div>
						<div id="filter-status" style="text-align: center; margin-top: 1rem; color: var(--gold); font-size: 0.9rem;">
							Showing all <?php echo $department_counts['All']; ?> personnel
						</div>
					</div>
					
					<h3 id="personnel-header">All Personnel</h3>
					<div class="crew-grid">
						<?php foreach ($all_crew as $crew_member): ?>
						<div class="crew-card <?php 
							switch($crew_member['department']) {
								case 'Command': echo 'command-box'; break;
								case 'ENG/OPS': echo 'eng-ops-box'; break;
								case 'MED/SCI': echo 'med-sci-box'; break;
								case 'SEC/TAC': echo 'sec-tac-box'; break;
							}
						?>" data-department="<?php echo htmlspecialchars($crew_member['department']); ?>">
							<?php if ($crew_member['image_path']): ?>
								<?php 
								$image_file_path = '../' . $crew_member['image_path'];
								if (file_exists($image_file_path)): 
								?>
								<img src="../<?php echo htmlspecialchars($crew_member['image_path']); ?>" alt="<?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem; border: 2px solid var(--bluey);">
								<?php else: ?>
								<div style="width: 80px; height: 80px; border-radius: 50%; background: #333; border: 2px solid var(--bluey); margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; color: #666; font-size: 0.8rem;">No Photo</div>
								<?php endif; ?>
							<?php else: ?>
							<div style="width: 80px; height: 80px; border-radius: 50%; background: #333; border: 2px solid var(--bluey); margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; color: #666; font-size: 0.8rem;">No Photo</div>
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
							
							<?php if (hasPermission('MED/SCI')): ?>
							<div style="margin-top: 0.5rem;">
								<a href="medical_history.php?crew_id=<?php echo $crew_member['id']; ?>" style="background-color: var(--blue); color: black; border: none; padding: 0.3rem 0.8rem; border-radius: 3px; font-size: 0.8rem; text-decoration: none; display: inline-block;">
									Medical History
								</a>
							</div>
							<?php endif; ?>
							
							<?php if (hasPermission(['SEC/TAC', 'COMMAND', 'CAPTAIN'])): ?>
							<div style="margin-top: 0.5rem;">
								<a href="criminal_history.php?crew_id=<?php echo $crew_member['id']; ?>" style="background-color: var(--red); color: black; border: none; padding: 0.3rem 0.8rem; border-radius: 3px; font-size: 0.8rem; text-decoration: none; display: inline-block;">
									Criminal Records
								</a>
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
	
	<script>
		// Department filtering functionality
		function filterByDepartment(department) {
			const crewCards = document.querySelectorAll('.crew-card');
			const filterButtons = document.querySelectorAll('.filter-btn');
			const personnelHeader = document.getElementById('personnel-header');
			const filterStatus = document.getElementById('filter-status');
			
			// Update button states
			filterButtons.forEach(btn => {
				btn.classList.remove('active');
				if (btn.getAttribute('data-department') === department) {
					btn.classList.add('active');
				}
			});
			
			// Filter crew cards
			let visibleCount = 0;
			crewCards.forEach(card => {
				if (department === 'All' || card.getAttribute('data-department') === department) {
					card.style.display = 'block';
					visibleCount++;
				} else {
					card.style.display = 'none';
				}
			});
			
			// Update header and status
			if (department === 'All') {
				personnelHeader.textContent = 'All Personnel';
				filterStatus.textContent = `Showing all ${visibleCount} personnel`;
			} else {
				personnelHeader.textContent = `${department} Department`;
				filterStatus.textContent = `Showing ${visibleCount} personnel in ${department}`;
			}
		}
		
		// Add CSS for active filter button
		document.addEventListener('DOMContentLoaded', function() {
			const style = document.createElement('style');
			style.textContent = `
				.filter-btn.active {
					box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
					transform: scale(1.05);
				}
				.filter-btn:hover {
					transform: scale(1.02);
					transition: transform 0.2s;
				}
			`;
			document.head.appendChild(style);
		});
	</script>
	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
