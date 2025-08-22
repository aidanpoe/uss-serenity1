<?php
require_once '../includes/config.php';

// Update last active timestamp for current character
updateLastActive();

// Handle image upload with enhanced security
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
    $max_size = MAX_UPLOAD_SIZE;
    if ($file['size'] > $max_size) {
        throw new Exception("Image file size must be less than " . ($max_size / 1024 / 1024) . "MB. Your file is " . round($file['size'] / 1024 / 1024, 2) . "MB.");
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
    
    // Additional security: Check for embedded PHP code in image
    $file_content = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php|<script|javascript:/i', $file_content)) {
        throw new Exception("Security violation: Invalid file content detected.");
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
    
    // Generate secure filename using random bytes
    $filename = bin2hex(random_bytes(16)) . '_crew.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Set secure file permissions
        chmod($upload_path, 0644);
        return 'assets/crew_photos/' . $filename;
    } else {
        throw new Exception("Failed to save uploaded image file.");
    }
}

// Handle adding new personnel (Captain only - full command positions)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_personnel') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } elseif (hasPermission('Captain')) {
        try {
            $image_path = '';
            if (isset($_FILES['crew_image']) && $_FILES['crew_image']['error'] === UPLOAD_ERR_OK) {
                $image_path = handleImageUpload($_FILES['crew_image']);
            }
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, position, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                sanitizeInput($_POST['rank']),
                sanitizeInput($_POST['first_name']),
                sanitizeInput($_POST['last_name']),
                sanitizeInput($_POST['species']),
                sanitizeInput($_POST['department']),
                sanitizeInput($_POST['position'] ?? ''),
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
    
    $stmt = $pdo->prepare("SELECT *, last_active FROM roster WHERE position IN ('" . implode("','", array_keys($command_positions)) . "') AND (is_invisible IS NULL OR is_invisible = 0) ORDER BY FIELD(position, '" . implode("','", array_keys($command_positions)) . "')");
    $stmt->execute();
    $command_crew = $stmt->fetchAll();
    
    foreach ($command_crew as $officer) {
        $command_positions[$officer['position']] = $officer;
    }
    
    // Get all crew members with current session info (excluding invisible users)
    $stmt = $pdo->prepare("
        SELECT r.*, r.last_active,
               u.last_login,
               CASE 
                   WHEN u.active_character_id = r.id AND u.last_login > DATE_SUB(NOW(), INTERVAL 30 MINUTE) 
                   THEN 1 
                   ELSE 0 
               END as is_currently_online
        FROM roster r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE (r.is_invisible IS NULL OR r.is_invisible = 0)
          AND (u.is_invisible IS NULL OR u.is_invisible = 0 OR u.department != 'Starfleet Auditor')
        ORDER BY r.department, r.rank, r.last_name, r.first_name
    ");
    $stmt->execute();
    $all_crew = $stmt->fetchAll();
    
    // Calculate department counts
    $department_counts = [
        'All' => count($all_crew),
        'Command' => 0,
        'ENG/OPS' => 0,
        'MED/SCI' => 0,
        'SEC/TAC' => 0,
        'Unassigned' => 0
    ];
    
    foreach ($all_crew as $crew_member) {
        $dept = $crew_member['department'] ?? 'Unassigned';
        if (isset($department_counts[$dept])) {
            $department_counts[$dept]++;
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
		/* Command Structure Styles - Hierarchical Design */
		.command-structure {
			margin: 2rem 0;
		}
		
		.command-tier {
			margin: 2rem 0;
		}
		
		.tier-title {
			font-size: 1.2rem;
			font-weight: bold;
			color: var(--orange);
			text-align: center;
			margin-bottom: 1.5rem;
			padding: 0.75rem;
			background: rgba(255, 136, 0, 0.15);
			border-radius: 8px;
			border: 1px solid var(--orange);
		}
		
		.command-row {
			display: flex;
			justify-content: center;
			gap: 1.5rem;
			margin-bottom: 1rem;
			flex-wrap: wrap;
		}
		
		.officer-box {
			padding: 1.5rem;
			border-radius: 12px;
			text-align: center;
			min-height: 140px;
			min-width: 200px;
			max-width: 300px;
			position: relative;
			box-shadow: 0 4px 8px rgba(0,0,0,0.4);
			word-wrap: break-word;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			transition: transform 0.2s ease;
		}
		
		.officer-box:hover {
			transform: translateY(-2px);
		}
		
		.department-label {
			font-size: 0.65rem;
			font-weight: normal;
			color: #999;
			margin-bottom: 0.25rem;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			opacity: 0.8;
		}
		
		.position-title {
			font-size: 0.85rem;
			font-weight: bold;
			color: var(--orange);
			margin-bottom: 0.75rem;
			text-transform: uppercase;
			letter-spacing: 1px;
		}
		
		.officer-name {
			font-size: 1rem;
			color: var(--bluey);
			font-weight: bold;
			margin-bottom: 1rem;
			flex-grow: 1;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.edit-btn {
			background-color: var(--bluey);
			color: black;
			border: none;
			padding: 0.5rem 1rem;
			border-radius: 6px;
			cursor: pointer;
			font-size: 0.8rem;
			font-weight: bold;
			transition: background-color 0.2s ease;
		}
		
		.edit-btn:hover {
			background-color: var(--blue);
		}
		
		/* Command Staff - Red Theme */
		.command-senior .officer-box {
			background: linear-gradient(135deg, rgba(204, 68, 68, 0.25), rgba(204, 68, 68, 0.15));
			border: 2px solid var(--red);
		}
		
		.command-co {
			border: 3px solid var(--red) !important;
			box-shadow: 0 0 15px rgba(204, 68, 68, 0.5) !important;
		}
		
		.command-co .position-title {
			color: var(--red);
			font-size: 1rem;
		}
		
		/* Department Heads - Multi-colored */
		.department-heads .medical {
			background: linear-gradient(135deg, rgba(85, 102, 255, 0.25), rgba(85, 102, 255, 0.15));
			border: 2px solid var(--blue);
		}
		
		.department-heads .science {
			background: linear-gradient(135deg, rgba(85, 102, 255, 0.25), rgba(85, 102, 255, 0.15));
			border: 2px solid var(--blue);
		}
		
		.department-heads .engineering {
			background: linear-gradient(135deg, rgba(255, 136, 0, 0.25), rgba(255, 136, 0, 0.15));
			border: 2px solid var(--orange);
		}
		
		.department-heads .operations {
			background: linear-gradient(135deg, rgba(255, 136, 0, 0.25), rgba(255, 136, 0, 0.15));
			border: 2px solid var(--orange);
		}
		
		.department-heads .security {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 215, 0, 0.15));
			border: 2px solid var(--gold);
		}
		
		.department-heads .tactical {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 215, 0, 0.15));
			border: 2px solid var(--gold);
		}
		
		.department-heads .command {
			background: linear-gradient(135deg, rgba(204, 68, 68, 0.25), rgba(204, 68, 68, 0.15));
			border: 2px solid var(--red);
		}
		
		/* Senior Staff - Department-based colors */
		.senior-staff .medical {
			background: linear-gradient(135deg, rgba(85, 102, 255, 0.25), rgba(85, 102, 255, 0.15));
			border: 2px solid var(--blue);
		}
		
		.senior-staff .science {
			background: linear-gradient(135deg, rgba(85, 102, 255, 0.25), rgba(85, 102, 255, 0.15));
			border: 2px solid var(--blue);
		}
		
		.senior-staff .engineering {
			background: linear-gradient(135deg, rgba(255, 136, 0, 0.25), rgba(255, 136, 0, 0.15));
			border: 2px solid var(--orange);
		}
		
		.senior-staff .operations {
			background: linear-gradient(135deg, rgba(255, 136, 0, 0.25), rgba(255, 136, 0, 0.15));
			border: 2px solid var(--orange);
		}
		
		.senior-staff .helm {
			background: linear-gradient(135deg, rgba(255, 136, 0, 0.25), rgba(255, 136, 0, 0.15));
			border: 2px solid var(--orange);
		}
		
		.senior-staff .security {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 215, 0, 0.15));
			border: 2px solid var(--gold);
		}
		
		.senior-staff .tactical {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 215, 0, 0.15));
			border: 2px solid var(--gold);
		}
		
		.senior-staff .intelligence {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 215, 0, 0.15));
			border: 2px solid var(--gold);
		}
		
		.senior-staff .srt {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.25), rgba(255, 215, 0, 0.15));
			border: 2px solid var(--gold);
		}
		
		.senior-staff .position-title {
			color: var(--orange);
		}
		
		/* Offline Status */
		.offline {
			opacity: 0.6;
			background: linear-gradient(135deg, rgba(136, 68, 68, 0.25), rgba(136, 68, 68, 0.15)) !important;
			border: 2px solid #664444 !important;
		}
		
		.offline-status {
			color: var(--red) !important;
		}
		
		/* Responsive Design */
		@media (max-width: 1200px) {
			.command-row {
				gap: 1rem;
			}
			.officer-box {
				min-width: 180px;
				max-width: 280px;
			}
		}
		
		@media (max-width: 768px) {
			.command-row {
				flex-direction: column;
				align-items: center;
			}
			.officer-box {
				max-width: 400px;
				width: 100%;
			}
		}
		
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
		
		/* Department-based color coding for crew cards */
		.command-box {
			background: linear-gradient(135deg, rgba(255, 0, 0, 0.15), rgba(255, 0, 0, 0.08));
			border-color: var(--red);
			color: var(--bluey);
		}
		
		.eng-ops-box {
			background: linear-gradient(135deg, rgba(255, 136, 0, 0.15), rgba(255, 136, 0, 0.08));
			border-color: var(--orange);
			color: var(--bluey);
		}
		
		.med-sci-box {
			background: linear-gradient(135deg, rgba(0, 136, 255, 0.15), rgba(0, 136, 255, 0.08));
			border-color: var(--blue);
			color: var(--bluey);
		}
		
		.sec-tac-box {
			background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 215, 0, 0.08));
			border-color: var(--gold);
			color: var(--bluey);
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
						<!-- Senior Command Staff -->
						<div class="command-tier command-senior">
							<div class="tier-title">SENIOR COMMAND STAFF</div>
							<div class="command-row">
								<!-- Commanding Officer - Center -->
								<div class="officer-box command-co">
									<div class="department-label">COMMAND</div>
									<div class="position-title">COMMANDING OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Commanding Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Commanding Officer']['rank'] . ' ' . $command_positions['Commanding Officer']['first_name'] . ' ' . $command_positions['Commanding Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
							</div>
							<div class="command-row">
								<!-- First Officer - Left, Second Officer - Center, Third Officer - Right -->
								<div class="officer-box command-xo">
									<div class="department-label">COMMAND</div>
									<div class="position-title">FIRST OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['First Officer']): ?>
											<?php echo htmlspecialchars($command_positions['First Officer']['rank'] . ' ' . $command_positions['First Officer']['first_name'] . ' ' . $command_positions['First Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								<div class="officer-box command-2o">
									<div class="department-label">COMMAND</div>
									<div class="position-title">SECOND OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Second Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Second Officer']['rank'] . ' ' . $command_positions['Second Officer']['first_name'] . ' ' . $command_positions['Second Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								<div class="officer-box command-3o">
									<div class="department-label">COMMAND</div>
									<div class="position-title">THIRD OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Third Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Third Officer']['rank'] . ' ' . $command_positions['Third Officer']['first_name'] . ' ' . $command_positions['Third Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Department Heads -->
						<div class="command-tier department-heads">
							<div class="tier-title">DEPARTMENT HEADS</div>
							<div class="command-row">
								<!-- Head of MED/SCI -->
								<div class="officer-box dept-head medical">
									<div class="department-label">MEDICAL/SCIENCE</div>
									<div class="position-title">HEAD OF MED/SCI</div>
									<div class="officer-name">
										<?php if ($command_positions['Head of MED/SCI']): ?>
											<?php echo htmlspecialchars($command_positions['Head of MED/SCI']['rank'] . ' ' . $command_positions['Head of MED/SCI']['first_name'] . ' ' . $command_positions['Head of MED/SCI']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Head of ENG/OPS -->
								<div class="officer-box dept-head engineering">
									<div class="department-label">ENGINEERING/OPERATIONS</div>
									<div class="position-title">HEAD OF ENG/OPS</div>
									<div class="officer-name">
										<?php if ($command_positions['Head of ENG/OPS']): ?>
											<?php echo htmlspecialchars($command_positions['Head of ENG/OPS']['rank'] . ' ' . $command_positions['Head of ENG/OPS']['first_name'] . ' ' . $command_positions['Head of ENG/OPS']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Head of SEC/TAC -->
								<div class="officer-box dept-head security">
									<div class="department-label">SECURITY/TACTICAL</div>
									<div class="position-title">HEAD OF SEC/TAC</div>
									<div class="officer-name">
										<?php if ($command_positions['Head of SEC/TAC']): ?>
											<?php echo htmlspecialchars($command_positions['Head of SEC/TAC']['rank'] . ' ' . $command_positions['Head of SEC/TAC']['first_name'] . ' ' . $command_positions['Head of SEC/TAC']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Senior Staff -->
						<div class="command-tier senior-staff">
							<div class="tier-title">SENIOR STAFF</div>
							<div class="command-row">
								<!-- Chief Medical Officer -->
								<div class="officer-box senior-staff medical">
									<div class="department-label">MED/SCI</div>
									<div class="position-title">CHIEF MEDICAL OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Chief Medical Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Chief Medical Officer']['rank'] . ' ' . $command_positions['Chief Medical Officer']['first_name'] . ' ' . $command_positions['Chief Medical Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Chief Science Officer -->
								<div class="officer-box senior-staff science">
									<div class="department-label">MED/SCI</div>
									<div class="position-title">CHIEF SCIENCE OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Chief Science Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Chief Science Officer']['rank'] . ' ' . $command_positions['Chief Science Officer']['first_name'] . ' ' . $command_positions['Chief Science Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Chief Engineer -->
								<div class="officer-box senior-staff engineering">
									<div class="department-label">ENG/OPS</div>
									<div class="position-title">CHIEF ENGINEER</div>
									<div class="officer-name">
										<?php if ($command_positions['Chief Engineer']): ?>
											<?php echo htmlspecialchars($command_positions['Chief Engineer']['rank'] . ' ' . $command_positions['Chief Engineer']['first_name'] . ' ' . $command_positions['Chief Engineer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Chief Operations Officer -->
								<div class="officer-box senior-staff operations">
									<div class="department-label">ENG/OPS</div>
									<div class="position-title">CHIEF OPERATIONS OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Operations Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Operations Officer']['rank'] . ' ' . $command_positions['Operations Officer']['first_name'] . ' ' . $command_positions['Operations Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Head of Helm -->
								<div class="officer-box senior-staff helm">
									<div class="department-label">ENG/OPS</div>
									<div class="position-title">HEAD OF HELM</div>
									<div class="officer-name">
										<?php if ($command_positions['Head of Helm']): ?>
											<?php echo htmlspecialchars($command_positions['Head of Helm']['rank'] . ' ' . $command_positions['Head of Helm']['first_name'] . ' ' . $command_positions['Head of Helm']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
							</div>
							<div class="command-row">
								<!-- Security Chief -->
								<div class="officer-box senior-staff security">
									<div class="department-label">SEC/TAC</div>
									<div class="position-title">SECURITY CHIEF</div>
									<div class="officer-name">
										<?php if ($command_positions['Security Chief']): ?>
											<?php echo htmlspecialchars($command_positions['Security Chief']['rank'] . ' ' . $command_positions['Security Chief']['first_name'] . ' ' . $command_positions['Security Chief']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Tactical Officer -->
								<div class="officer-box senior-staff tactical">
									<div class="department-label">SEC/TAC</div>
									<div class="position-title">TACTICAL OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Tactical Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Tactical Officer']['rank'] . ' ' . $command_positions['Tactical Officer']['first_name'] . ' ' . $command_positions['Tactical Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- Intelligence Officer -->
								<div class="officer-box senior-staff intelligence">
									<div class="department-label">SEC/TAC</div>
									<div class="position-title">INTELLIGENCE OFFICER</div>
									<div class="officer-name">
										<?php if ($command_positions['Intelligence Officer']): ?>
											<?php echo htmlspecialchars($command_positions['Intelligence Officer']['rank'] . ' ' . $command_positions['Intelligence Officer']['first_name'] . ' ' . $command_positions['Intelligence Officer']['last_name']); ?>
										<?php else: ?>
											Position Vacant
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
								
								<!-- S.R.T. Leader (Offline) -->
								<div class="officer-box senior-staff srt offline">
									<div class="department-label">SEC/TAC</div>
									<div class="position-title">S.R.T. LEADER</div>
									<div class="officer-name offline-status">
										<?php if ($command_positions['S.R.T. Leader']): ?>
											<?php echo htmlspecialchars($command_positions['S.R.T. Leader']['rank'] . ' ' . $command_positions['S.R.T. Leader']['first_name'] . ' ' . $command_positions['S.R.T. Leader']['last_name']); ?> - OFFLINE
										<?php else: ?>
											Position Vacant - OFFLINE
										<?php endif; ?>
									</div>
									<?php if (hasPermission('Captain')): ?>
									<button onclick="playSoundAndRedirect('audio2', 'command_structure_edit.php')" class="edit-btn">Edit Assignment</button>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Rank Structure Information -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3 style="text-align: center; color: var(--orange); margin-bottom: 2rem;">USS SERENITY RANK STRUCTURE</h3>
						
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
							<!-- NCO Ranks -->
							<div style="background: rgba(138, 43, 226, 0.1); border: 2px solid #8A2BE2; border-radius: 10px; padding: 1.5rem;">
								<h4 style="color: #8A2BE2; text-align: center; margin-bottom: 1rem; border-bottom: 1px solid #8A2BE2; padding-bottom: 0.5rem;">NON-COMMISSIONED OFFICERS</h4>
								<div style="font-size: 0.9rem; line-height: 1.6;">
									<div style="margin-bottom: 0.5rem;"><strong>Crewman 3rd Class (CRW3)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Crewman 2nd Class (CRW2)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Crewman 1st Class (CRW1)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Petty Officer 3rd Class (PO3)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Petty Officer 2nd Class (PO2)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Petty Officer 1st Class (PO1)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Chief Petty Officer (CPO)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Senior Chief Petty Officer (SCPO)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Master Chief Petty Officer (MCPO)</strong></div>
									<div><strong>Command Master Chief Petty Officer (CMCPO)</strong></div>
								</div>
							</div>
							
							<!-- Commissioned Officers -->
							<div style="background: rgba(0, 255, 127, 0.1); border: 2px solid #00FF7F; border-radius: 10px; padding: 1.5rem;">
								<h4 style="color: #00FF7F; text-align: center; margin-bottom: 1rem; border-bottom: 1px solid #00FF7F; padding-bottom: 0.5rem;">COMMISSIONED OFFICERS</h4>
								<div style="font-size: 0.9rem; line-height: 1.6;">
									<div style="margin-bottom: 0.5rem;"><strong>Warrant Officer (WO)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Ensign (ENS)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Lieutenant Junior Grade (LT. JG)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Lieutenant (LT)</strong></div>
									<div style="margin-bottom: 0.5rem;"><strong>Lieutenant Commander (LT. CMD)</strong> - Head of Departments</div>
									<div style="margin-bottom: 0.5rem;"><strong>Commander (CMD)</strong></div>
									<div><strong>Captain (CPT)</strong></div>
								</div>
							</div>
							
							<!-- Command Authority -->
							<div style="background: rgba(255, 0, 0, 0.1); border: 2px solid var(--red); border-radius: 10px; padding: 1.5rem;">
								<h4 style="color: var(--red); text-align: center; margin-bottom: 1rem; border-bottom: 1px solid var(--red); padding-bottom: 0.5rem;">COMMAND AUTHORITY</h4>
								<div style="font-size: 0.9rem; line-height: 1.6;">
									<div style="margin-bottom: 0.75rem;"><strong style="color: var(--red);">Captain (CPT)</strong><br>
									<span style="font-size: 0.85rem; color: var(--orange);">Ultimate authority over ship operations, crew, and mission execution.</span></div>
									
									<div style="margin-bottom: 0.75rem;"><strong style="color: var(--red);">First Officer (XO)</strong><br>
									<span style="font-size: 0.85rem; color: var(--orange);">Second in command, manages daily operations and crew assignments.</span></div>
									
									<div style="margin-bottom: 0.75rem;"><strong style="color: var(--red);">Second Officer (SO)</strong><br>
									<span style="font-size: 0.85rem; color: var(--orange);">Third in command, often department head with command training.</span></div>
									
									<div style="margin-bottom: 0.75rem;"><strong style="color: var(--red);">Third Officer (TO)</strong><br>
									<span style="font-size: 0.85rem; color: var(--orange);">Fourth in command, senior officer with bridge watch capabilities.</span></div>
									
									<div><strong style="color: var(--orange);">Heads of Departments</strong><br>
									<span style="font-size: 0.85rem; color: var(--bluey);">Department leaders with authority over personnel advancement, departmental operations, and specialized mission assignments within their respective areas.</span></div>
								</div>
							</div>
						</div>
						
						<div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 136, 0, 0.1); border-left: 4px solid var(--orange); border-radius: 5px;">
							<p style="font-size: 0.9rem; color: var(--bluey); margin: 0;">
								<strong style="color: var(--orange);">Note:</strong> Personnel advancement can be approved by Department Heads or Captain. Command assignments require Captain's approval. 
								Department assignments determine system access permissions and operational responsibilities.
							</p>
						</div>
					</div>

					<?php if (hasPermission('Captain')): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h4>Add New Personnel (Captain Only)</h4>
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="add_personnel">
							<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
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
					
					<?php if (!isLoggedIn()): ?>
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
					<?php endif; ?>
					
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
							<?php if ($department_counts['Unassigned'] > 0): ?>
							<button class="filter-btn" onclick="filterByDepartment('Unassigned')" data-department="Unassigned" style="background-color: var(--gray); color: black; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer;">
								Unassigned (<?php echo $department_counts['Unassigned']; ?>)
							</button>
							<?php endif; ?>
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
								default: echo 'crew-card'; break; // Default styling for unassigned
							}
						?>" data-department="<?php echo htmlspecialchars($crew_member['department'] ?? 'Unassigned'); ?>">
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
							<p>Department: <?php echo htmlspecialchars($crew_member['department'] ?? 'Unassigned'); ?></p>
							<?php if ($crew_member['position']): ?>
							<p><em><?php echo htmlspecialchars($crew_member['position']); ?></em></p>
							<?php endif; ?>
							
							<?php if ($crew_member['last_active']): ?>
							<div style="font-size: 0.8rem; color: var(--bluey); margin-top: 0.5rem; padding: 0.2rem; background: rgba(0,0,0,0.3); border-radius: 3px;">
								<strong>Last Active:</strong><br>
								<?php 
								// Check if user is currently online (has active session)
								if ($crew_member['is_currently_online']) {
									echo '<span style="color: var(--green);">Online</span>';
								} else {
									$last_active = new DateTime($crew_member['last_active']);
									$now = new DateTime();
									$interval = $now->diff($last_active);
									
									if ($interval->days == 0) {
										echo '<span style="color: var(--gold);">' . $interval->h . 'h ' . $interval->i . 'm ago</span>';
									} elseif ($interval->days == 1) {
										echo '<span style="color: var(--orange);">1 day ago</span>';
									} elseif ($interval->days < 7) {
										echo '<span style="color: var(--orange);">' . $interval->days . ' days ago</span>';
									} else {
										echo '<span style="color: var(--red);">' . $last_active->format('M j, Y') . '</span>';
									}
								}
								?>
							</div>
							<?php else: ?>
							<div style="font-size: 0.8rem; color: var(--gray); margin-top: 0.5rem; padding: 0.2rem; background: rgba(0,0,0,0.3); border-radius: 3px;">
								<strong>Last Active:</strong><br>
								<span style="color: var(--gray);">Never logged in</span>
							</div>
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
							
							<!-- Competencies and Awards Buttons - Available to Everyone -->
							<div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
								<button onclick="showCompetencies(<?php echo $crew_member['id']; ?>, '<?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name'], ENT_QUOTES); ?>')" 
								        style="background-color: var(--gold); color: black; border: none; padding: 0.3rem 0.8rem; border-radius: 3px; font-size: 0.8rem; cursor: pointer;">
									Competencies
								</button>
								<button onclick="showAwards(<?php echo $crew_member['id']; ?>, '<?php echo htmlspecialchars($crew_member['first_name'] . ' ' . $crew_member['last_name'], ENT_QUOTES); ?>')" 
								        style="background-color: var(--orange); color: black; border: none; padding: 0.3rem 0.8rem; border-radius: 3px; font-size: 0.8rem; cursor: pointer;">
									Awards (<?php echo $crew_member['award_count'] ?? 0; ?>)
								</button>
							</div>
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
	
	<!-- Competencies Modal -->
	<div id="competencies-modal" class="competencies-modal" style="display: none;">
		<div class="competencies-modal-content">
			<div class="competencies-header">
				<div class="competencies-title">TRAINING COMPETENCIES</div>
				<div class="competencies-subtitle" id="crew-name-display"></div>
			</div>
			<div class="competencies-body" id="competencies-content">
				<div class="loading-message">Loading training records...</div>
			</div>
			<div class="competencies-footer">
				<button onclick="closeCompetencies()" class="lcars-close-button">CLOSE DATABASE ACCESS</button>
			</div>
		</div>
	</div>

	<!-- Awards Modal -->
	<div id="awards-modal" class="competencies-modal" style="display: none;">
		<div class="competencies-modal-content">
			<div class="competencies-header">
				<div class="competencies-title">STARFLEET AWARDS & COMMENDATIONS</div>
				<div class="competencies-subtitle" id="awards-crew-name-display"></div>
			</div>
			<div class="competencies-body" id="awards-content">
				<div class="loading-message">Loading awards records...</div>
			</div>
			<div class="competencies-footer">
				<button onclick="closeAwards()" class="lcars-close-button">CLOSE DATABASE ACCESS</button>
			</div>
		</div>
	</div>
	
	<script>
		function showCompetencies(rosterId, crewName) {
			document.getElementById('crew-name-display').textContent = crewName;
			document.getElementById('competencies-modal').style.display = 'flex';
			document.getElementById('competencies-content').innerHTML = '<div class="loading-message">Loading training records...</div>';
			
			// Play LCARS sound
			if (typeof playAudio !== 'undefined') {
				playAudio('audio3');
			}
			
			// Fetch competencies data
			fetch('../api/get_competencies.php?roster_id=' + rosterId)
				.then(response => response.json())
				.then(data => {
					displayCompetencies(data);
				})
				.catch(error => {
					document.getElementById('competencies-content').innerHTML = 
						'<div class="error-message">❌ Error loading training records: ' + error.message + '</div>';
				});
		}
		
		function displayCompetencies(competencies) {
			const content = document.getElementById('competencies-content');
			
			if (!competencies || competencies.length === 0) {
				content.innerHTML = '<div class="no-training-message">📚 No training records found</div>';
				return;
			}
			
			let html = '<div class="competencies-grid">';
			
			// Group by department
			const departments = {};
			competencies.forEach(comp => {
				if (!departments[comp.module_department]) {
					departments[comp.module_department] = [];
				}
				departments[comp.module_department].push(comp);
			});
			
			// Display by department
			for (const [dept, modules] of Object.entries(departments)) {
				html += `<div class="department-section">
					<h3 class="department-header">${dept}</h3>
					<div class="modules-list">`;
				
				modules.forEach(comp => {
					const statusClass = comp.status.replace('_', '-');
					const statusText = comp.status.replace('_', ' ').toUpperCase();
					let statusIcon = '';
					
					switch(comp.status) {
						case 'assigned': statusIcon = '📋'; break;
						case 'in_progress': statusIcon = '⏳'; break;
						case 'completed': statusIcon = '✅'; break;
						case 'expired': statusIcon = '❌'; break;
						default: statusIcon = '📚'; break;
					}
					
					html += `<div class="competency-item status-${statusClass}">
						<div class="competency-header">
							<span class="competency-name">${comp.module_name}</span>
							<span class="competency-status">${statusIcon} ${statusText}</span>
						</div>
						<div class="competency-details">
							<div class="competency-code">${comp.module_code}</div>
							<div class="competency-level">Level: ${comp.certification_level}</div>
							<div class="competency-date">Assigned: ${comp.assigned_date}</div>
							${comp.completion_date ? `<div class="completion-date">Completed: ${comp.completion_date}</div>` : ''}
							${comp.notes ? `<div class="competency-notes">${comp.notes}</div>` : ''}
						</div>
					</div>`;
				});
				
				html += '</div></div>';
			}
			
			html += '</div>';
			content.innerHTML = html;
		}
		
		function closeCompetencies() {
			document.getElementById('competencies-modal').style.display = 'none';
			
			// Play LCARS sound
			if (typeof playAudio !== 'undefined') {
				playAudio('audio2');
			}
		}

		// Awards Modal Functions
		function showAwards(rosterId, crewName) {
			document.getElementById('awards-crew-name-display').textContent = crewName;
			document.getElementById('awards-modal').style.display = 'flex';
			document.getElementById('awards-content').innerHTML = '<div class="loading-message">Loading awards records...</div>';
			
			// Play LCARS sound
			if (typeof playAudio !== 'undefined') {
				playAudio('audio3');
			}
			
			// Fetch awards data
			fetch('../api/get_awards.php?roster_id=' + rosterId)
				.then(response => response.json())
				.then(data => {
					displayAwards(data);
				})
				.catch(error => {
					document.getElementById('awards-content').innerHTML = '<div class="error-message">Error loading awards data: ' + error.message + '</div>';
				});
		}

		function displayAwards(response) {
			const content = document.getElementById('awards-content');
			
			if (!response.success) {
				content.innerHTML = '<div class="error-message">Error: ' + response.error + '</div>';
				return;
			}
			
			const data = response.data;
			let html = '';
			
			if (data.total_count === 0) {
				html = `
					<div class="no-awards-message">
						<div style="text-align: center; color: var(--orange); font-size: 1.2rem; margin: 2rem 0;">
							NO AWARDS OR COMMENDATIONS ON RECORD
						</div>
						<div style="text-align: center; color: var(--bluey); font-size: 0.9rem;">
							This crew member has not yet received any Starfleet awards or commendations.
						</div>
					</div>
				`;
			} else {
				html = `
					<div class="awards-summary">
						<div style="text-align: center; color: var(--orange); font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--orange); padding-bottom: 0.5rem;">
							TOTAL AWARDS: ${data.total_count}
						</div>
					</div>
				`;
				
				// Display Medals
				if (data.medals.length > 0) {
					html += `
						<div class="award-section">
							<h3 style="color: #FFD700; margin-bottom: 1rem;">🏅 MEDALS (${data.medals.length})</h3>
							<div class="awards-grid">
					`;
					data.medals.forEach(award => {
						html += `
							<div class="award-item medal">
								<div class="award-name">${award.name}</div>
								${award.specialization ? `<div class="award-dept">${award.specialization}</div>` : ''}
								<div class="award-date">Awarded: ${award.date_awarded}</div>
								${award.awarded_by ? `<div class="award-by">By: ${award.awarded_by.rank} ${award.awarded_by.name}</div>` : ''}
								${award.citation ? `<div class="award-citation">"${award.citation}"</div>` : ''}
								<div class="award-description">${award.description}</div>
							</div>
						`;
					});
					html += '</div></div>';
				}
				
				// Display Ribbons
				if (data.ribbons.length > 0) {
					html += `
						<div class="award-section">
							<h3 style="color: #87CEEB; margin-bottom: 1rem;">🎗️ RIBBONS (${data.ribbons.length})</h3>
							<div class="awards-grid">
					`;
					data.ribbons.forEach(award => {
						html += `
							<div class="award-item ribbon">
								<div class="award-name">${award.name}</div>
								${award.specialization ? `<div class="award-dept">${award.specialization}</div>` : ''}
								<div class="award-date">Awarded: ${award.date_awarded}</div>
								${award.awarded_by ? `<div class="award-by">By: ${award.awarded_by.rank} ${award.awarded_by.name}</div>` : ''}
								${award.citation ? `<div class="award-citation">"${award.citation}"</div>` : ''}
								<div class="award-description">${award.description}</div>
							</div>
						`;
					});
					html += '</div></div>';
				}
				
				// Display Badges
				if (data.badges.length > 0) {
					html += `
						<div class="award-section">
							<h3 style="color: #32CD32; margin-bottom: 1rem;">🛡️ BADGES (${data.badges.length})</h3>
							<div class="awards-grid">
					`;
					data.badges.forEach(award => {
						html += `
							<div class="award-item badge">
								<div class="award-name">${award.name}</div>
								${award.specialization ? `<div class="award-dept">${award.specialization}</div>` : ''}
								<div class="award-date">Awarded: ${award.date_awarded}</div>
								${award.awarded_by ? `<div class="award-by">By: ${award.awarded_by.rank} ${award.awarded_by.name}</div>` : ''}
								${award.citation ? `<div class="award-citation">"${award.citation}"</div>` : ''}
								<div class="award-description">${award.description}</div>
							</div>
						`;
					});
					html += '</div></div>';
				}
			}
			
			content.innerHTML = html;
		}

		function closeAwards() {
			document.getElementById('awards-modal').style.display = 'none';
			
			// Play LCARS sound
			if (typeof playAudio !== 'undefined') {
				playAudio('audio2');
			}
		}
		
		// Close modal when clicking outside
		document.getElementById('competencies-modal').addEventListener('click', function(e) {
			if (e.target === this) {
				closeCompetencies();
			}
		});

		document.getElementById('awards-modal').addEventListener('click', function(e) {
			if (e.target === this) {
				closeAwards();
			}
		});
		
		// Close modal with Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				closeCompetencies();
				closeAwards();
			}
		});
	</script>
	
	<style>
		.competencies-modal {
			display: none;
			position: fixed;
			z-index: 10000;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.8);
			justify-content: center;
			align-items: center;
		}
		
		.competencies-modal-content {
			background: linear-gradient(45deg, #000 0%, #1a1a2e 50%, #000 100%);
			border: 3px solid var(--blue);
			border-radius: 15px;
			width: 90%;
			max-width: 800px;
			max-height: 80vh;
			overflow: hidden;
			position: relative;
		}
		
		.competencies-header {
			background: var(--blue);
			color: black;
			padding: 1rem;
			text-align: center;
		}
		
		.competencies-title {
			font-size: 1.4rem;
			font-weight: bold;
			font-family: 'Antonio', sans-serif;
		}
		
		.competencies-subtitle {
			font-size: 1rem;
			margin-top: 0.5rem;
			opacity: 0.8;
		}
		
		.competencies-body {
			padding: 1.5rem;
			max-height: 60vh;
			overflow-y: auto;
			color: var(--green);
		}
		
		.competencies-footer {
			background: rgba(85, 102, 255, 0.2);
			padding: 1rem;
			text-align: center;
			border-top: 2px solid var(--blue);
		}
		
		.lcars-close-button {
			background: var(--red);
			color: black;
			border: none;
			padding: 0.8rem 2rem;
			border-radius: 25px;
			font-size: 1rem;
			font-weight: bold;
			font-family: 'Antonio', sans-serif;
			cursor: pointer;
			transition: all 0.3s ease;
		}
		
		.lcars-close-button:hover {
			background: #ff6b6b;
			transform: scale(1.05);
		}
		
		.loading-message, .no-training-message, .error-message {
			text-align: center;
			padding: 2rem;
			font-size: 1.1rem;
			color: var(--gold);
		}
		
		.error-message {
			color: var(--red);
		}
		
		.department-section {
			margin-bottom: 2rem;
		}
		
		.department-header {
			color: var(--blue);
			font-size: 1.2rem;
			font-weight: bold;
			margin-bottom: 1rem;
			padding-bottom: 0.5rem;
			border-bottom: 2px solid var(--blue);
		}
		
		.modules-list {
			display: grid;
			gap: 1rem;
		}
		
		.competency-item {
			background: rgba(0, 0, 0, 0.6);
			border: 1px solid var(--blue);
			border-radius: 8px;
			padding: 1rem;
			transition: all 0.3s ease;
		}
		
		.competency-item:hover {
			border-color: var(--gold);
			background: rgba(0, 0, 0, 0.8);
		}
		
		.competency-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 0.5rem;
		}
		
		.competency-name {
			font-weight: bold;
			color: var(--green);
			font-size: 1.1rem;
		}
		
		.competency-status {
			font-size: 0.9rem;
			padding: 0.2rem 0.5rem;
			border-radius: 5px;
		}
		
		.status-assigned .competency-status {
			background: var(--blue);
			color: black;
		}
		
		.status-in-progress .competency-status {
			background: var(--gold);
			color: black;
		}
		
		.status-completed .competency-status {
			background: var(--green);
			color: black;
		}
		
		.status-expired .competency-status {
			background: var(--red);
			color: white;
		}
		
		.competency-details {
			font-size: 0.9rem;
			color: var(--blue);
		}
		
		.competency-code {
			font-family: monospace;
			color: var(--gold);
			margin-bottom: 0.3rem;
		}
		
		.competency-level {
			margin-bottom: 0.3rem;
		}
		
		.competency-date, .completion-date {
			margin-bottom: 0.3rem;
			font-size: 0.8rem;
			opacity: 0.8;
		}
		
		.competency-notes {
			font-style: italic;
			color: var(--green);
			margin-top: 0.5rem;
			padding: 0.5rem;
			background: rgba(0, 255, 0, 0.1);
			border-radius: 3px;
		}
		
		/* Awards Modal Styles */
		.awards-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
			gap: 1rem;
			margin-bottom: 2rem;
		}
		
		.award-section {
			margin-bottom: 2rem;
		}
		
		.award-item {
			background: rgba(0, 0, 0, 0.3);
			border-radius: 8px;
			padding: 1rem;
			border-left: 4px solid;
		}
		
		.award-item.medal {
			border-left-color: #FFD700;
			background: rgba(255, 215, 0, 0.1);
		}
		
		.award-item.ribbon {
			border-left-color: #87CEEB;
			background: rgba(135, 206, 235, 0.1);
		}
		
		.award-item.badge {
			border-left-color: #32CD32;
			background: rgba(50, 205, 50, 0.1);
		}
		
		.award-name {
			font-size: 1.1rem;
			font-weight: bold;
			color: var(--orange);
			margin-bottom: 0.5rem;
		}
		
		.award-dept {
			font-size: 0.9rem;
			color: var(--bluey);
			font-weight: bold;
			margin-bottom: 0.3rem;
		}
		
		.award-date {
			font-size: 0.8rem;
			color: var(--green);
			margin-bottom: 0.3rem;
		}
		
		.award-by {
			font-size: 0.8rem;
			color: var(--bluey);
			margin-bottom: 0.5rem;
		}
		
		.award-citation {
			font-style: italic;
			color: var(--gold);
			margin-bottom: 0.5rem;
			padding: 0.5rem;
			background: rgba(255, 215, 0, 0.1);
			border-radius: 3px;
		}
		
		.award-description {
			font-size: 0.9rem;
			color: var(--bluey);
			line-height: 1.4;
		}
		
		.no-awards-message {
			text-align: center;
			padding: 2rem;
		}
	</style>
	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
