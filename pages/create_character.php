<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../steamauth/steamauth.php?login");
    exit();
}

// Check if user can create more characters
if (!canCreateCharacter()) {
    header("Location: profile.php?error=max_characters");
    exit();
}

$success = '';
$error = '';

// Available departments and their permission mappings
$department_permissions = [
    'Medical' => 'MED/SCI',
    'Science' => 'MED/SCI', 
    'Engineering' => 'ENG/OPS',
    'Operations' => 'ENG/OPS',
    'Security' => 'SEC/TAC',
    'Tactical' => 'SEC/TAC'
];

// Available ranks (excluding command ranks)
$available_ranks = [
    'Crewman Recruit', 'Crewman Apprentice', 'Crewman', 'Petty Officer 3rd Class',
    'Petty Officer 2nd Class', 'Petty Officer 1st Class', 'Chief Petty Officer',
    'Senior Chief Petty Officer', 'Master Chief Petty Officer', 'Ensign',
    'Lieutenant Junior Grade', 'Lieutenant'
];

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_character') {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        
        // Validate required fields
        $required_fields = ['character_name', 'first_name', 'last_name', 'species', 'department', 'position', 'rank'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Validate rank selection
        if (!in_array($_POST['rank'], $available_ranks)) {
            throw new Exception("Invalid rank selected.");
        }
        
        // Validate department
        if (!array_key_exists($_POST['department'], $department_permissions)) {
            throw new Exception("Invalid department selected.");
        }
        
        // Check character name uniqueness for this user
        $stmt = $pdo->prepare("SELECT id FROM roster WHERE user_id = ? AND character_name = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id'], $_POST['character_name']]);
        if ($stmt->fetch()) {
            throw new Exception("You already have a character with this name. Please choose a different character name.");
        }
        
        // Handle image upload if provided
        $image_path = '';
        if (isset($_FILES['character_image']) && $_FILES['character_image']['error'] === UPLOAD_ERR_OK) {
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($_FILES['character_image']['size'] > $max_size) {
                throw new Exception("Image file size must be less than 5MB.");
            }
            
            $file_extension = strtolower(pathinfo($_FILES['character_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Only JPEG, PNG, GIF, and WebP images are allowed.");
            }
            
            $upload_dir = '../assets/crew_photos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'character_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['character_image']['tmp_name'], $upload_path)) {
                $image_path = 'assets/crew_photos/' . $filename;
            }
        }
        
        // Create new character
        $stmt = $pdo->prepare("
            INSERT INTO roster (user_id, character_name, first_name, last_name, species, department, position, rank, image_path, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['character_name'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['species'],
            $_POST['department'],
            $_POST['position'],
            $_POST['rank'],
            $image_path
        ]);
        
        $character_id = $pdo->lastInsertId();
        
        // Update user's department permission based on new character's department
        $permission_group = $department_permissions[$_POST['department']];
        $stmt = $pdo->prepare("UPDATE users SET department = ? WHERE id = ?");
        $stmt->execute([$permission_group, $_SESSION['user_id']]);
        
        // If this is the user's first character, set it as active
        $stmt = $pdo->prepare("SELECT COUNT(*) as char_count FROM roster WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $char_count = $stmt->fetch()['char_count'];
        
        if ($char_count == 1) {
            $stmt = $pdo->prepare("UPDATE users SET active_character_id = ? WHERE id = ?");
            $stmt->execute([$character_id, $_SESSION['user_id']]);
        }
        
        $pdo->commit();
        $success = "Character '" . htmlspecialchars($_POST['character_name']) . "' created successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Create New Character</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 1rem;
			margin: 1rem 0;
		}
		.form-group {
			margin-bottom: 1rem;
		}
		.form-group label {
			display: block;
			margin-bottom: 0.5rem;
			color: var(--gold);
			font-weight: bold;
		}
		.form-group input,
		.form-group select,
		.form-group textarea {
			width: 100%;
			padding: 0.5rem;
			background: black;
			color: white;
			border: 1px solid var(--gold);
			border-radius: 3px;
		}
		.form-group textarea {
			height: 80px;
			resize: vertical;
		}
		.help-text {
			color: var(--orange);
			font-size: 0.9rem;
			margin-top: 0.25rem;
		}
		.alert {
			padding: 1rem;
			border-radius: 10px;
			margin: 1rem 0;
			font-weight: bold;
		}
		.alert.success {
			background: rgba(85, 102, 255, 0.3);
			border: 2px solid var(--blue);
			color: white;
		}
		.alert.error {
			background: rgba(204, 68, 68, 0.3);
			border: 2px solid var(--red);
			color: white;
		}
		.lcars-button {
			background-color: var(--gold);
			color: black;
			border: none;
			padding: 0.75rem 1.5rem;
			border-radius: 5px;
			cursor: pointer;
			font-weight: bold;
			font-size: 0.9rem;
			margin-right: 1rem;
		}
		.lcars-button:hover {
			background-color: var(--orange);
		}
		.lcars-button.secondary {
			background-color: var(--gray);
			color: white;
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
				<div class="panel-2">CREATE<span class="hop">-CHAR</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">CREATE CHARACTER &#149; USS-SERENITY</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'profile.php')">PROFILE</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--gold);">CREATE</button>
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
					<div class="panel-3">SYS<span class="hop">-STATUS</span></div>
					<div class="panel-4">PWR<span class="hop">-ONLINE</span></div>
					<div class="panel-5">NAV<span class="hop">-READY</span></div>
					<div class="panel-6">COM<span class="hop">-ACTIVE</span></div>
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
					<?php if ($success): ?>
					<div class="alert success">
						✅ <?php echo htmlspecialchars($success); ?>
					</div>
					<p><a href="profile.php" style="color: var(--blue);">Return to Profile</a> to switch to your new character.</p>
					<?php endif; ?>

					<?php if ($error): ?>
					<div class="alert error">
						❌ <?php echo htmlspecialchars($error); ?>
					</div>
					<?php endif; ?>

					<h1>Create New Character</h1>
					<h2>Multi-Character System</h2>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Character Creation</h3>
						<p style="color: var(--bluey); margin-bottom: 1rem;">
							You can create up to 5 different crew roster profiles under your Steam account. 
							Each character can have different ranks, departments, and roles.
						</p>
						
						<form method="POST" enctype="multipart/form-data">
							<input type="hidden" name="action" value="create_character">
							
							<div class="form-grid">
								<div class="form-group">
									<label for="character_name">Character Name *</label>
									<input type="text" id="character_name" name="character_name" required maxlength="100">
									<small class="help-text">A unique name to identify this character (e.g., "John Smith - Medical Officer")</small>
								</div>
								
								<div class="form-group">
									<label for="first_name">First Name *</label>
									<input type="text" id="first_name" name="first_name" required maxlength="50">
								</div>
								
								<div class="form-group">
									<label for="last_name">Last Name *</label>
									<input type="text" id="last_name" name="last_name" required maxlength="50">
								</div>
								
								<div class="form-group">
									<label for="species">Species *</label>
									<input type="text" id="species" name="species" required maxlength="50" placeholder="e.g., Human, Vulcan, Andorian">
								</div>
								
								<div class="form-group">
									<label for="department">Department *</label>
									<select id="department" name="department" required>
										<option value="">Select Department</option>
										<?php foreach ($department_permissions as $dept => $perm): ?>
										<option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?> (<?php echo htmlspecialchars($perm); ?> Access)</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<div class="form-group">
									<label for="position">Position *</label>
									<input type="text" id="position" name="position" required maxlength="100" placeholder="e.g., Medical Officer, Security Guard, Engineer">
								</div>
								
								<div class="form-group">
									<label for="rank">Rank *</label>
									<select id="rank" name="rank" required>
										<option value="">Select Rank</option>
										<?php foreach ($available_ranks as $rank): ?>
										<option value="<?php echo htmlspecialchars($rank); ?>"><?php echo htmlspecialchars($rank); ?></option>
										<?php endforeach; ?>
									</select>
									<small class="help-text">Command ranks (Lieutenant Commander+) must be assigned by Captain</small>
								</div>
								
								<div class="form-group">
									<label for="character_image">Character Image</label>
									<input type="file" id="character_image" name="character_image" accept="image/*">
									<small class="help-text">Optional. Max 5MB. Formats: JPEG, PNG, GIF, WebP</small>
								</div>
							</div>
							
							<div style="margin-top: 2rem;">
								<button type="submit" class="lcars-button" onclick="playSoundAndRedirect('audio2', '#')">Create Character</button>
								<button type="button" class="lcars-button secondary" onclick="playSoundAndRedirect('audio2', 'profile.php')">Cancel</button>
							</div>
						</form>
					</div>
				</main>
			</div>
		</div>
	</section>
	<script src="../assets/lcars.js"></script>
</body>
</html>
