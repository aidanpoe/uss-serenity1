<?php
require_once '../includes/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: ../steamauth/steamauth.php?login');
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Get current user data with roster information
    $stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.species, r.department as roster_department, r.position, r.image_path 
                          FROM users u 
                          LEFT JOIN roster r ON u.id = r.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    
    if (!$current_user) {
        throw new Exception("User not found.");
    }
    
    // Handle form submission for image upload
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_image') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $pdo->beginTransaction();
                
                // Handle image upload
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES['profile_image']['size'] > $max_size) {
                    throw new Exception("Image file size must be less than 5MB.");
                }
                
                $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Only JPEG, PNG, GIF, and WebP images are allowed.");
                }
                
                $upload_dir = '../assets/crew_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $filename = uniqid('crew_') . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $image_path = 'assets/crew_photos/' . $filename;
                    
                    // Delete old image if exists
                    if ($current_user['image_path'] && file_exists('../' . $current_user['image_path'])) {
                        unlink('../' . $current_user['image_path']);
                    }
                    
                    // Update roster image
                    $stmt = $pdo->prepare("UPDATE roster SET image_path = ? WHERE user_id = ?");
                    $stmt->execute([$image_path, $_SESSION['user_id']]);
                    
                    $pdo->commit();
                    $success = "Profile image updated successfully!";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.species, r.department as roster_department, r.position, r.image_path 
                                          FROM users u 
                                          LEFT JOIN roster r ON u.id = r.user_id 
                                          WHERE u.id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $current_user = $stmt->fetch();
                } else {
                    throw new Exception("Failed to save uploaded image file.");
                }
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Image upload failed: " . $e->getMessage();
            }
        } else {
            $error = "Please select an image file to upload.";
        }
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>USS-Serenity NCC-74714 | Profile Settings</title>
	<link rel="stylesheet" href="../assets/classic.css">
	<style>
		/* LCARS Panel Styling */
		.lcars-panel {
			background: rgba(0,0,0,0.8);
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--bluey);
			overflow: hidden;
		}
		
		.lcars-panel.warning {
			border-color: var(--orange);
		}
		
		.lcars-panel-header {
			background: var(--bluey);
			color: black;
			padding: 1rem 2rem;
			font-weight: bold;
		}
		
		.lcars-panel.warning .lcars-panel-header {
			background: var(--orange);
		}
		
		.lcars-panel-content {
			padding: 2rem;
		}
		
		/* Alert Messages */
		.alert {
			padding: 1rem 2rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 5px solid;
		}
		
		.alert.success {
			background: rgba(0, 255, 0, 0.1);
			color: var(--green);
			border-left-color: var(--green);
		}
		
		.alert.error {
			background: rgba(255, 0, 0, 0.1);
			color: var(--red);
			border-left-color: var(--red);
		}
		
		/* Profile Display */
		.profile-display {
			display: flex;
			gap: 2rem;
			align-items: flex-start;
			flex-wrap: wrap;
		}
		
		.profile-image {
			flex-shrink: 0;
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
		
		/* Status Indicators */
		.status-indicator {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			margin-bottom: 1rem;
			padding: 0.75rem;
			border-radius: 5px;
		}
		
		.status-indicator.success {
			background: rgba(0, 255, 0, 0.1);
			border: 1px solid var(--green);
		}
		
		.status-indicator.warning {
			background: rgba(255, 136, 0, 0.1);
			border: 1px solid var(--orange);
		}
		
		.status-text {
			font-weight: bold;
			color: white;
		}
		
		/* Form Styling */
		.lcars-form {
			margin-top: 1rem;
		}
		
		.form-group {
			margin-bottom: 1.5rem;
		}
		
		.form-group label {
			display: block;
			margin-bottom: 0.5rem;
			color: var(--bluey);
			font-weight: bold;
		}
		
		.file-input {
			margin-bottom: 0.5rem;
			color: white;
		}
		
		.profile-form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 1rem;
			margin: 1rem 0;
		}
		
		.readonly-input {
			width: 100%;
			padding: 0.75rem;
			background: rgba(0,0,0,0.5);
			color: #ccc;
			border: 1px solid #555;
			border-radius: 5px;
		}
		
		/* LCARS Button */
		.lcars-button {
			background: var(--gold);
			color: black;
			border: none;
			padding: 0.75rem 2rem;
			border-radius: 5px;
			font-weight: bold;
			cursor: pointer;
			transition: background-color 0.3s ease;
		}
		
		.lcars-button:hover {
			background: var(--yellow);
		}
		
		.lcars-button.primary {
			background: var(--bluey);
		}
		
		.lcars-button.primary:hover {
			background: var(--african-violet);
		}
		
		/* Text Styling */
		.info-text {
			color: var(--bluey);
			font-size: 0.9rem;
			margin-top: 0.5rem;
		}
		
		.help-text {
			color: var(--gray);
			font-size: 0.9rem;
			margin-top: 0.5rem;
		}
		
		.info-message {
			background: rgba(255, 136, 0, 0.1);
			border: 1px solid var(--orange);
			padding: 1rem;
			border-radius: 5px;
			margin-bottom: 1rem;
		}
		
		.info-message p {
			margin: 0;
			color: var(--orange);
			font-size: 0.9rem;
		}
		
		/* Responsive Design */
		@media (max-width: 768px) {
			.profile-display {
				flex-direction: column;
			}
			
			.profile-form-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>
<body>
	<audio id="audio1" preload="auto"><source src="../assets/beep1.mp3" type="audio/mpeg"></audio>
	<audio id="audio2" preload="auto"><source src="../assets/beep2.mp3" type="audio/mpeg"></audio>
	<audio id="audio3" preload="auto"><source src="../assets/beep3.mp3" type="audio/mpeg"></audio>

	<section class="lcars-layout">
		<div class="left-frame">
			<div>
				<a href="../index.php" onclick="playSound('audio2')"><div class="banner">USS-SERENITY<br><span class="banner-2">NCC-74714</span></div></a>
			</div>
			<div class="panel-1">
				<div class="panel-1a"><a href="../index.php" onclick="playSound('audio2')">HOME</a></div>
				<div class="panel-1b"><a href="roster.php" onclick="playSound('audio2')">ROSTER</a></div>
				<div class="panel-1c"><a href="reports.php" onclick="playSound('audio2')">REPORTS</a></div>
				<div class="panel-1d"></div>
			</div>
			<div class="gap-1">
				<div class="gap-1a">
					<a href="logout.php" onclick="playSound('audio2')"><div class="gap-1a-1">LOGOUT</div></a>
				</div>
				<div class="gap-1b">
					<div class="gap-1b-1">
						<div class="gap-1b-1a">CURRENT USER</div>
						<div class="gap-1b-1b"><?php echo strtoupper(htmlspecialchars(($_SESSION['first_name'] ?? $_SESSION['username']) . ' ' . ($_SESSION['last_name'] ?? ''))); ?></div>
						<div class="gap-1b-1c"><?php echo strtoupper(htmlspecialchars($_SESSION['department'] ?? 'CREW')); ?></div>
					</div>
				</div>
			</div>
			<div class="gap-2">
				<div class="gap-2a"> </div>
				<div class="gap-2b">
					<div class="gap-2b-1">STARDATE</div>
					<div class="gap-2b-2" id="stardate"><?php echo date('ymd') . '.' . (date('z') + 1); ?></div>
				</div>
			</div>
			<div class="panel-2">
				<div class="panel-2a"><a href="command.php" onclick="playSound('audio2')">COMMAND</a></div>
				<div class="panel-2b"><a href="eng_ops.php" onclick="playSound('audio2')">ENG/OPS</a></div>
				<div class="panel-2c"><a href="med_sci.php" onclick="playSound('audio2')">MED/SCI</a></div>
				<div class="panel-2d"><a href="sec_tac.php" onclick="playSound('audio2')">SEC/TAC</a></div>
			</div>
		</div>
		<div class="right-frame">
			<div class="right-frame-2">
				<main>
					<?php if ($success): ?>
					<div class="alert success">
						<?php echo htmlspecialchars($success); ?>
					</div>
					<?php endif; ?>

					<?php if ($error): ?>
					<div class="alert error">
						<?php echo htmlspecialchars($error); ?>
					</div>
					<?php endif; ?>

					<h1>Personnel Profile Settings</h1>
					<h2><?php echo htmlspecialchars(($current_user['rank'] ? $current_user['rank'] . ' ' : '') . ($current_user['first_name'] ? $current_user['first_name'] . ' ' . $current_user['last_name'] : $current_user['username'])); ?></h2>
					
					<!-- Current Profile Display -->
					<div class="lcars-panel">
						<div class="lcars-panel-header">
							<h3>Current Profile</h3>
						</div>
						<div class="lcars-panel-content">
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
									<p><span class="label">Roster Department:</span> <?php echo htmlspecialchars($current_user['roster_department']); ?></p>
									<?php endif; ?>
									<?php if ($current_user['position']): ?>
									<p><span class="label">Position:</span> <?php echo htmlspecialchars($current_user['position']); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Steam Account Information -->
					<div class="lcars-panel">
						<div class="lcars-panel-header">
							<h3>Steam Account</h3>
						</div>
						<div class="lcars-panel-content">
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
					</div>

					<!-- Image Upload Form -->
					<?php if ($current_user['first_name']): ?>
					<div class="lcars-panel">
						<div class="lcars-panel-header">
							<h3>Update Profile Image</h3>
						</div>
						<div class="lcars-panel-content">
							<form method="POST" enctype="multipart/form-data" class="lcars-form">
								<input type="hidden" name="action" value="update_image">
								<div class="form-group">
									<label for="profile_image">Profile Image:</label>
									<input type="file" id="profile_image" name="profile_image" accept="image/*" class="file-input">
									<small class="help-text">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF, WebP</small>
								</div>
								<button type="submit" class="lcars-button primary" onclick="playSound('audio2')">Update Image</button>
							</form>
						</div>
					</div>
					<?php endif; ?>

					<!-- Profile Information Display -->
					<?php if ($current_user['first_name']): ?>
					<div class="lcars-panel">
						<div class="lcars-panel-header">
							<h3>Profile Information</h3>
						</div>
						<div class="lcars-panel-content">
							<div class="info-message">
								<p>Note: Most profile information can only be changed by Command staff via the roster system.</p>
							</div>
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
									<label>Roster Department (Read Only):</label>
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
					</div>
					<?php else: ?>
					<div class="lcars-panel warning">
						<div class="lcars-panel-header">
							<h3>⚠️ Incomplete Profile</h3>
						</div>
						<div class="lcars-panel-content">
							<p>Your account exists but you don't have a crew roster profile yet. This may happen if:</p>
							<ul>
								<li>You haven't completed the Steam registration process</li>
								<li>Your roster entry was removed by Command staff</li>
								<li>There was an issue during account creation</li>
							</ul>
							<p>Contact your Captain or try logging out and back in through Steam to complete your profile setup.</p>
						</div>
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
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
