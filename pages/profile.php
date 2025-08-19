<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../steamauth/steamauth.php?login");
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Debug: Check if user_id exists in session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User ID not found in session. Session data: " . print_r($_SESSION, true));
    }
    
    // Get current user data with roster information
    $stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.species, r.department as roster_department, r.position, r.image_path 
                          FROM users u 
                          LEFT JOIN roster r ON u.id = r.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    
    if (!$current_user) {
        throw new Exception("User not found with ID: " . $_SESSION['user_id']);
    }
    
    // Handle image upload
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_image') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            try {
                // Validate file
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES['profile_image']['size'] > $max_size) {
                    throw new Exception("File size must be less than 5MB");
                }
                
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['profile_image']['type'];
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception("Only JPEG, PNG, GIF, and WebP images are allowed");
                }
                
                // Create upload directory if it doesn't exist
                $upload_dir = '../assets/crew_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $current_user['id'] . '_' . time() . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Update database
                    $stmt = $pdo->prepare("UPDATE roster SET image_path = ? WHERE user_id = ?");
                    $stmt->execute(['assets/crew_photos/' . $filename, $current_user['id']]);
                    
                    // Delete old image if it exists
                    if (!empty($current_user['image_path']) && file_exists('../' . $current_user['image_path'])) {
                        unlink('../' . $current_user['image_path']);
                    }
                    
                    $success = "Profile image updated successfully!";
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.species, r.department as roster_department, r.position, r.image_path 
                                          FROM users u 
                                          LEFT JOIN roster r ON u.id = r.user_id 
                                          WHERE u.id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_user = $stmt->fetch();
                } else {
                    throw new Exception("Failed to save uploaded file");
                }
            } catch (Exception $e) {
                $error = "Image upload failed: " . $e->getMessage();
            }
        } else {
            $error = "No file uploaded or upload error occurred";
        }
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Personnel Profile</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.profile-display {
			display: flex;
			gap: 2rem;
			align-items: flex-start;
			flex-wrap: wrap;
			margin: 1rem 0;
		}
		.profile-image {
			flex: 0 0 auto;
		}
		.crew-photo {
			width: 150px;
			height: 150px;
			border-radius: 10px;
			object-fit: cover;
			border: 2px solid var(--bluey);
		}
		.no-photo {
			width: 150px;
			height: 150px;
			border-radius: 10px;
			background: #333;
			border: 2px solid var(--bluey);
			display: flex;
			align-items: center;
			justify-content: center;
			color: #666;
			font-size: 0.9rem;
		}
		.profile-info {
			flex: 1;
			min-width: 300px;
		}
		.profile-info p {
			margin: 0.5rem 0;
			color: white;
		}
		.label {
			color: var(--bluey);
			font-weight: bold;
			display: inline-block;
			min-width: 120px;
		}
		.profile-form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
		.form-group input[type="text"], 
		.form-group input[type="file"] {
			width: 100%;
			padding: 0.5rem;
			background: black;
			color: white;
			border: 1px solid var(--gold);
			border-radius: 3px;
		}
		.readonly-input {
			background: black !important;
			color: #ccc !important;
			border: 1px solid #555 !important;
		}
		.help-text {
			color: var(--orange);
			font-size: 0.9rem;
			display: block;
			margin-top: 0.25rem;
		}
		.info-text {
			color: var(--bluey);
			font-size: 0.9rem;
		}
		.status-indicator {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			margin: 1rem 0;
		}
		.status-indicator.success {
			color: var(--green);
		}
		.status-indicator.warning {
			color: var(--orange);
		}
		.status-icon {
			font-size: 1.2rem;
		}
		.status-text {
			font-weight: bold;
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
		.alert.warning {
			background: rgba(255, 136, 0, 0.3);
			border: 2px solid var(--orange);
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
		}
		.lcars-button:hover {
			background-color: var(--orange);
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
				<div class="panel-2">PROFILE<span class="hop">-MGMT</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">PERSONNEL PROFILE &#149; USS-SERENITY</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--gold);">PROFILE</button>
						<?php if (isset($_SESSION['position']) && $_SESSION['position'] === 'Captain'): ?>
						<button onclick="playSoundAndRedirect('audio2', 'user_management.php')" style="background-color: var(--red);">ADMIN</button>
						<?php endif; ?>
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
					<?php endif; ?>

					<?php if ($error): ?>
					<div class="alert error">
						❌ <?php echo htmlspecialchars($error); ?>
					</div>
					<?php endif; ?>

					<h1>Personnel Profile Settings</h1>
					<h2><?php echo htmlspecialchars(($current_user['rank'] ? $current_user['rank'] . ' ' : '') . ($current_user['first_name'] ? $current_user['first_name'] . ' ' . $current_user['last_name'] : $current_user['username'])); ?></h2>
					
					<!-- Current Profile Display -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Current Profile</h3>
						<div class="profile-display">
							<div class="profile-image">
								<?php if ($current_user['image_path']): ?>
									<?php 
									$image_file_path = '../' . $current_user['image_path'];
									if (file_exists($image_file_path)): 
									?>
									<img src="../<?php echo htmlspecialchars($current_user['image_path']); ?>" alt="Profile Photo" class="crew-photo">
									<?php else: ?>
									<div class="no-photo">No Photo</div>
									<?php endif; ?>
								<?php else: ?>
								<div class="no-photo">No Photo</div>
								<?php endif; ?>
							</div>
							<div class="profile-info">
								<p><span class="label">Username:</span> <?php echo htmlspecialchars($current_user['username']); ?></p>
								<?php if ($current_user['first_name']): ?>
								<p><span class="label">Name:</span> <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></p>
								<?php endif; ?>
								<?php if ($current_user['species']): ?>
								<p><span class="label">Species:</span> <?php echo htmlspecialchars($current_user['species']); ?></p>
								<?php endif; ?>
								<?php if ($current_user['rank']): ?>
								<p><span class="label">Rank:</span> <?php echo htmlspecialchars($current_user['rank']); ?></p>
								<?php endif; ?>
								<p><span class="label">Access Group:</span> <?php echo htmlspecialchars($current_user['department'] ?? 'Not Assigned'); ?></p>
								<?php if ($current_user['roster_department']): ?>
								<p><span class="label">Department:</span> <?php echo htmlspecialchars($current_user['roster_department']); ?></p>
								<?php endif; ?>
								<?php if ($current_user['position']): ?>
								<p><span class="label">Position:</span> <?php echo htmlspecialchars($current_user['position']); ?></p>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Steam Account Information -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Steam Account</h3>
						<?php if (!empty($_SESSION['steamid']) || !empty($current_user['steam_id'])): ?>
						<div class="status-indicator success">
							<span class="status-icon">✅</span>
							<span class="status-text">Steam Account Linked</span>
						</div>
						<p><span class="label">Steam ID:</span> <?php echo htmlspecialchars($_SESSION['steamid'] ?? $current_user['steam_id']); ?></p>
						<p class="info-text">You are logged in via Steam. All authentication is handled through Steam.</p>
						<?php else: ?>
						<div class="status-indicator warning">
							<span class="status-icon">⚠️</span>
							<span class="status-text">No Steam Account Linked</span>
						</div>
						<p>This account is not linked to Steam. Contact your Captain to link a Steam account.</p>
						<?php endif; ?>
					</div>

					<!-- Image Upload Form -->
					<?php if ($current_user['first_name']): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Update Profile Image</h3>
						<form method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
							<input type="hidden" name="action" value="update_image">
							<div class="form-group">
								<label for="profile_image">Profile Image:</label>
								<input type="file" id="profile_image" name="profile_image" accept="image/*">
								<small class="help-text">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF, WebP</small>
							</div>
							<button type="submit" class="lcars-button" onclick="playSoundAndRedirect('audio2', '#')">Update Image</button>
						</form>
					</div>
					<?php endif; ?>

					<!-- Profile Information Display -->
					<?php if ($current_user['first_name']): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Profile Information</h3>
						<p class="info-text" style="margin-bottom: 1rem;">Note: Most profile information can only be changed by Command staff via the roster system.</p>
						<div class="profile-form-grid">
							<div class="form-group">
								<label>First Name (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['first_name']); ?>" disabled class="readonly-input">
							</div>
							<div class="form-group">
								<label>Last Name (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['last_name']); ?>" disabled class="readonly-input">
							</div>
							<?php if ($current_user['rank']): ?>
							<div class="form-group">
								<label>Rank (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['rank']); ?>" disabled class="readonly-input">
							</div>
							<?php endif; ?>
							<div class="form-group">
								<label>Access Group (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['department'] ?? 'Not Assigned'); ?>" disabled class="readonly-input">
							</div>
							<?php if ($current_user['roster_department']): ?>
							<div class="form-group">
								<label>Department (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['roster_department']); ?>" disabled class="readonly-input">
							</div>
							<?php endif; ?>
							<?php if ($current_user['position']): ?>
							<div class="form-group">
								<label>Position (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['position']); ?>" disabled class="readonly-input">
							</div>
							<?php endif; ?>
							<?php if ($current_user['species']): ?>
							<div class="form-group">
								<label>Species (Read Only):</label>
								<input type="text" value="<?php echo htmlspecialchars($current_user['species']); ?>" disabled class="readonly-input">
							</div>
							<?php endif; ?>
						</div>
						<p class="help-text">To change profile information, contact your department head or Command staff.</p>
					</div>
					<?php else: ?>
					<div class="alert warning">
						<h3 style="color: var(--orange);">⚠️ Incomplete Profile</h3>
						<p>Your account exists but you don't have a crew roster profile yet. This may happen if:</p>
						<ul>
							<li>You haven't completed the Steam registration process</li>
							<li>Your roster entry was removed by Command staff</li>
							<li>There was an issue during account creation</li>
						</ul>
						<p>Contact your Captain or try logging out and back in through Steam to complete your profile setup.</p>
					</div>
					<?php endif; ?>
				</main>
			</div>
		</div>
	</section>
	<script src="../assets/lcars.js"></script>
</body>
</html>
