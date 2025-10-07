<?php
require_once '../includes/config.php';

// Check permission
if (!canEditPersonnelFiles()) {
    header('Location: login.php');
    exit();
}

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

// Handle personnel file update
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_personnel') {
    try {
        $pdo = getConnection();
        
        // Handle image upload if provided
        $image_path = $_POST['current_image_path'] ?? '';
        if (isset($_FILES['crew_image']) && $_FILES['crew_image']['error'] === UPLOAD_ERR_OK) {
            // Delete old image if it exists
            if ($image_path && file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
            $image_path = handleImageUpload($_FILES['crew_image']);
        }
        
        $stmt = $pdo->prepare("UPDATE roster SET rank = ?, first_name = ?, last_name = ?, species = ?, department = ?, position = ?, image_path = ? WHERE id = ?");
        $stmt->execute([
            $_POST['rank'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['species'],
            $_POST['department'],
            $_POST['position'],
            $image_path,
            $_POST['personnel_id']
        ]);
        $success = "Personnel file updated successfully.";
    } catch (Exception $e) {
        $error = "Error updating personnel file: " . $e->getMessage();
    }
}

// Handle personnel deletion
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_personnel') {
    if (hasPermission('Captain') || hasPermission('Starfleet Auditor')) { // Captain or Starfleet Auditor can delete
        try {
            $pdo = getConnection();
            
            // Get image path before deletion
            $stmt = $pdo->prepare("SELECT image_path FROM roster WHERE id = ?");
            $stmt->execute([$_POST['personnel_id']]);
            $person = $stmt->fetch();
            
            // Delete image if it exists
            if ($person && $person['image_path'] && file_exists('../' . $person['image_path'])) {
                unlink('../' . $person['image_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM roster WHERE id = ?");
            $stmt->execute([$_POST['personnel_id']]);
            $success = "Personnel file deleted successfully.";
        } catch (Exception $e) {
            $error = "Error deleting personnel file: " . $e->getMessage();
        }
    } else {
        $error = "Only the Captain and Starfleet Auditors can delete personnel files.";
    }
}

try {
    $pdo = getConnection();
    
    // Get all personnel for editing
    $stmt = $pdo->prepare("SELECT * FROM roster ORDER BY department, rank, last_name, first_name");
    $stmt->execute();
    $all_personnel = $stmt->fetchAll();
    
    // Get specific person for editing if ID provided
    $editing_person = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM roster WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editing_person = $stmt->fetch();
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
	<title>USS-VOYAGER - Personnel File Editor</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.personnel-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}
		.personnel-card {
			padding: 1rem;
			border-radius: 10px;
			border: 2px solid;
			position: relative;
		}
		.command-box { border-color: var(--red); background: rgba(204, 68, 68, 0.2); }
		.eng-ops-box { border-color: var(--orange); background: rgba(255, 136, 0, 0.2); }
		.med-sci-box { border-color: var(--blue); background: rgba(85, 102, 255, 0.2); }
		.sec-tac-box { border-color: var(--gold); background: rgba(255, 170, 0, 0.2); }
		.edit-form {
			background: rgba(0,0,0,0.8);
			padding: 2rem;
			border-radius: 15px;
			border: 2px solid var(--bluey);
			margin: 2rem 0;
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
			margin-bottom: 1rem;
		}
		.current-image {
			max-width: 100px;
			height: 100px;
			object-fit: cover;
			border-radius: 50%;
			border: 2px solid var(--bluey);
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
				<div class="panel-2">PERS<span class="hop">-EDIT</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">PERSONNEL FILE EDITOR</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--bluey);">PERSONNEL</button>
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
					<div class="panel-3">EDIT<span class="hop">-MODE</span></div>
					<div class="panel-4">FILES<span class="hop">-<?php echo count($all_personnel); ?></span></div>
					<div class="panel-5">ACCESS<span class="hop">-AUTH</span></div>
					<div class="panel-6">UPDATE<span class="hop">-SYS</span></div>
					<div class="panel-7">IMAGE<span class="hop">-UPL</span></div>
					<div class="panel-8">SECURE<span class="hop">-CONN</span></div>
					<div class="panel-9">DATA<span class="hop">-SYNC</span></div>
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
					<h1>Personnel File Editor</h1>
					<h2>USS-VOYAGER Crew Management System</h2>
					
					<?php showShowcaseNotice(); ?>
					
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
					
					<div style="background: rgba(255, 170, 0, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border: 1px solid var(--gold);">
						<h4>Access Level: <?php echo canEditPersonnelFiles() ? 'AUTHORIZED' : 'UNAUTHORIZED'; ?></h4>
						<p style="color: var(--gold);"><strong>Authorized Personnel:</strong> Heads of Departments, Command Staff, Captain</p>
						<p style="color: var(--orange);"><em>Note: Only the Captain can delete personnel files.</em></p>
					</div>
					
					<?php if ($editing_person): ?>
					<!-- Edit Form -->
					<div class="edit-form">
						<h3>Editing Personnel File</h3>
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="update_personnel">
							<input type="hidden" name="personnel_id" value="<?php echo $editing_person['id']; ?>">
							<input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($editing_person['image_path']); ?>">
							
							<div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem; margin-bottom: 2rem;">
								<div>
									<label style="color: var(--bluey);">Current Photo:</label><br>
									<?php if ($editing_person['image_path'] && file_exists('../' . $editing_person['image_path'])): ?>
										<img src="../<?php echo htmlspecialchars($editing_person['image_path']); ?>" alt="Current Photo" class="current-image">
									<?php else: ?>
										<div style="width: 100px; height: 100px; border: 2px solid var(--bluey); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--bluey);">No Photo</div>
									<?php endif; ?>
								</div>
								<div class="form-grid">
									<div>
										<label style="color: var(--bluey);">Rank:</label>
										<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
											<?php foreach ($ranks as $rank): ?>
											<option value="<?php echo htmlspecialchars($rank); ?>" <?php echo $rank === $editing_person['rank'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($rank); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div>
										<label style="color: var(--bluey);">First Name:</label>
										<input type="text" name="first_name" value="<?php echo htmlspecialchars($editing_person['first_name']); ?>" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									</div>
									<div>
										<label style="color: var(--bluey);">Last Name:</label>
										<input type="text" name="last_name" value="<?php echo htmlspecialchars($editing_person['last_name']); ?>" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									</div>
									<div>
										<label style="color: var(--bluey);">Species:</label>
										<input type="text" name="species" value="<?php echo htmlspecialchars($editing_person['species']); ?>" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									</div>
									<div>
										<label style="color: var(--bluey);">Department:</label>
										<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
											<option value="Command" <?php echo $editing_person['department'] === 'Command' ? 'selected' : ''; ?>>Command</option>
											<option value="MED/SCI" <?php echo $editing_person['department'] === 'MED/SCI' ? 'selected' : ''; ?>>MED/SCI</option>
											<option value="ENG/OPS" <?php echo $editing_person['department'] === 'ENG/OPS' ? 'selected' : ''; ?>>ENG/OPS</option>
											<option value="SEC/TAC" <?php echo $editing_person['department'] === 'SEC/TAC' ? 'selected' : ''; ?>>SEC/TAC</option>
										</select>
									</div>
									<div>
										<label style="color: var(--bluey);">Position:</label>
										<input type="text" name="position" value="<?php echo htmlspecialchars($editing_person['position']); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									</div>
									<div style="grid-column: span 2;">
										<label style="color: var(--bluey);">New Photo (Optional):</label>
										<input type="file" name="crew_image" accept="image/*" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
										<small style="color: var(--orange);">JPEG, PNG, or GIF. Max 5MB. Will replace current photo.</small>
									</div>
								</div>
							</div>
							
							<div style="display: flex; gap: 1rem;">
								<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 1rem 2rem; border-radius: 5px;">Update Personnel File</button>
								<button type="button" onclick="window.location.href='personnel_edit.php'" style="background-color: var(--gray); color: black; border: none; padding: 1rem 2rem; border-radius: 5px;">Cancel</button>
								<?php if (hasPermission('Captain')): ?>
								<button type="button" onclick="if(confirm('Are you sure you want to delete this personnel file? This action cannot be undone.')) { document.getElementById('deleteForm').submit(); }" style="background-color: var(--red); color: black; border: none; padding: 1rem 2rem; border-radius: 5px;">Delete File</button>
								<?php endif; ?>
							</div>
						</form>
						
						<?php if (hasPermission('Captain')): ?>
						<form id="deleteForm" method="POST" action="" style="display: none;">
							<input type="hidden" name="action" value="delete_personnel">
							<input type="hidden" name="personnel_id" value="<?php echo $editing_person['id']; ?>">
						</form>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					
					<h3>Personnel Files</h3>
					<div class="personnel-grid">
						<?php foreach ($all_personnel as $person): ?>
						<div class="personnel-card <?php 
							switch($person['department']) {
								case 'Command': echo 'command-box'; break;
								case 'ENG/OPS': echo 'eng-ops-box'; break;
								case 'MED/SCI': echo 'med-sci-box'; break;
								case 'SEC/TAC': echo 'sec-tac-box'; break;
							}
						?>">
							<?php if ($person['image_path'] && file_exists('../' . $person['image_path'])): ?>
							<img src="../<?php echo htmlspecialchars($person['image_path']); ?>" alt="<?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; float: right; border: 2px solid var(--bluey);">
							<?php endif; ?>
							<strong><?php echo htmlspecialchars($person['rank']); ?></strong><br>
							<h4><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h4>
							<p>Species: <?php echo htmlspecialchars($person['species']); ?></p>
							<p>Department: <?php echo htmlspecialchars($person['department']); ?></p>
							<?php if ($person['position']): ?>
							<p><em><?php echo htmlspecialchars($person['position']); ?></em></p>
							<?php endif; ?>
							<button onclick="playSoundAndRedirect('audio2', 'personnel_edit.php?edit=<?php echo $person['id']; ?>')" style="background-color: var(--bluey); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; margin-top: 1rem; width: 100%;">Edit File</button>
						</div>
						<?php endforeach; ?>
					</div>
				</main>
				<footer>
					USS-VOYAGER NCC-74656 &copy; 2401 Starfleet Command<br>
					LCARS Inspired Website Template by <a href="https://www.thelcars.com">www.TheLCARS.com</a>.
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<?php displayShowcaseMessage(); ?>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
