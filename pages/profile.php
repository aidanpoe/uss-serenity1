<?php
require_once '../includes/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Get current user data - handle both new and old database schema
    $stmt = $pdo->prepare("SELECT u.*, r.id as roster_id, r.rank, r.first_name, r.last_name, r.species, r.department, r.position, r.image_path 
                          FROM users u 
                          LEFT JOIN roster r ON (u.roster_id = r.id OR (r.first_name = u.first_name AND r.last_name = u.last_name))
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    
    if (!$current_user) {
        throw new Exception("User not found.");
    }
    
    // Handle form submission
    if ($_POST) {
        $pdo->beginTransaction();
        
        // Handle password change
        if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            if (!password_verify($current_password, $current_user['password'])) {
                throw new Exception("Current password is incorrect.");
            }
            
            // Validate new password
            if (strlen($new_password) < 6) {
                throw new Exception("New password must be at least 6 characters long.");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }
            
            // Update password and clear force_password_change flag
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
            $stmt->execute([$password_hash, $_SESSION['user_id']]);
            
            $success = "Password changed successfully!";
        }
        
        // Handle username change
        elseif (isset($_POST['action']) && $_POST['action'] === 'change_username') {
            $new_username = trim($_POST['new_username']);
            
            if (empty($new_username)) {
                throw new Exception("Username cannot be empty.");
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
                throw new Exception("Username can only contain letters, numbers, and underscores.");
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
            
            // Update username
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['username'] = $new_username;
            
            $success = "Username changed successfully!";
        }
        
        // Handle profile information update
        elseif (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
            if ($current_user['roster_id']) {
                // Update roster information
                $stmt = $pdo->prepare("UPDATE roster SET first_name = ?, last_name = ?, species = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['species'],
                    $current_user['roster_id']
                ]);
                
                // Update session names if changed
                $_SESSION['first_name'] = $_POST['first_name'];
                $_SESSION['last_name'] = $_POST['last_name'];
            }
            
            $success = "Profile information updated successfully!";
        }
        
        // Handle image upload
        elseif (isset($_POST['action']) && $_POST['action'] === 'update_image') {
            if ($current_user['roster_id'] && isset($_FILES['profile_image'])) {
                try {
                    // Use the same image upload function from roster.php
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
                                        return '';
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
                        
                        $max_size = 5 * 1024 * 1024; // 5MB
                        if ($file['size'] > $max_size) {
                            throw new Exception("Image file size must be less than 5MB.");
                        }
                        
                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (!in_array($file_extension, $allowed_extensions)) {
                            throw new Exception("Only JPEG, PNG, GIF, and WebP images are allowed.");
                        }
                        
                        $allowed_mime_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $detected_type = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        
                        if (!in_array($detected_type, $allowed_mime_types) && !in_array($file['type'], $allowed_mime_types)) {
                            throw new Exception("Invalid image file type detected.");
                        }
                        
                        $image_info = getimagesize($file['tmp_name']);
                        if ($image_info === false) {
                            throw new Exception("File is not a valid image or is corrupted.");
                        }
                        
                        $upload_dir = '../assets/crew_photos/';
                        if (!is_dir($upload_dir)) {
                            if (!mkdir($upload_dir, 0755, true)) {
                                throw new Exception("Could not create upload directory.");
                            }
                        }
                        
                        if (!is_writable($upload_dir)) {
                            throw new Exception("Upload directory is not writable.");
                        }
                        
                        $filename = uniqid('crew_') . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            return 'assets/crew_photos/' . $filename;
                        } else {
                            throw new Exception("Failed to save uploaded image file.");
                        }
                    }
                    
                    $image_path = handleImageUpload($_FILES['profile_image']);
                    if ($image_path) {
                        // Delete old image if exists
                        if ($current_user['image_path'] && file_exists('../' . $current_user['image_path'])) {
                            unlink('../' . $current_user['image_path']);
                        }
                        
                        // Update database
                        $stmt = $pdo->prepare("UPDATE roster SET image_path = ? WHERE id = ?");
                        $stmt->execute([$image_path, $current_user['roster_id']]);
                        
                        $success = "Profile image updated successfully!";
                    }
                } catch (Exception $e) {
                    throw new Exception("Image upload failed: " . $e->getMessage());
                }
            }
        }
        
        $pdo->commit();
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT u.*, r.id as roster_id, r.rank, r.first_name, r.last_name, r.species, r.department, r.position, r.image_path 
                              FROM users u 
                              LEFT JOIN roster r ON (u.roster_id = r.id OR (r.first_name = u.first_name AND r.last_name = u.last_name))
                              WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user = $stmt->fetch();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Check if user needs to change password
$force_password_change = $current_user['force_password_change'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>USS-Serenity NCC-74714 | Profile Settings</title>
	<link rel="stylesheet" href="../assets/classic.css">
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
						<div class="gap-1b-1b"><?php echo strtoupper(htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name'])); ?></div>
						<div class="gap-1b-1c"><?php echo strtoupper(htmlspecialchars($_SESSION['department'])); ?></div>
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
					<?php if ($force_password_change): ?>
					<div style="background: #5a2d2d; padding: 1.5rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--red);">
						<h3 style="color: var(--red); margin-top: 0;">⚠️ Password Change Required</h3>
						<p style="color: var(--orange);">Your password has been reset by the Captain. You must change it before accessing other features.</p>
					</div>
					<?php endif; ?>
					
					<?php if ($success): ?>
					<div style="background: rgba(0, 255, 0, 0.1); color: #00ff00; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid #00ff00;">
						<?php echo htmlspecialchars($success); ?>
					</div>
					<?php endif; ?>

					<?php if ($error): ?>
					<div style="background: rgba(255, 0, 0, 0.1); color: #ff6666; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid #ff6666;">
						<?php echo htmlspecialchars($error); ?>
					</div>
					<?php endif; ?>

					<h1>Personnel Profile Settings</h1>
					<h2><?php echo htmlspecialchars($current_user['rank'] . ' ' . $current_user['first_name'] . ' ' . $current_user['last_name']); ?></h2>
					
					<!-- Current Profile Display -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Current Profile</h3>
						<div style="display: flex; gap: 2rem; align-items: flex-start;">
							<div>
								<?php if ($current_user['image_path']): ?>
									<?php 
									$image_file_path = '../' . $current_user['image_path'];
									if (file_exists($image_file_path)): 
									?>
									<img src="../<?php echo htmlspecialchars($current_user['image_path']); ?>" alt="Profile Photo" style="width: 150px; height: 150px; border-radius: 10px; object-fit: cover; border: 2px solid var(--bluey);">
									<?php else: ?>
									<div style="width: 150px; height: 150px; border-radius: 10px; background: #333; border: 2px solid var(--bluey); display: flex; align-items: center; justify-content: center; color: #666;">No Photo</div>
									<?php endif; ?>
								<?php else: ?>
								<div style="width: 150px; height: 150px; border-radius: 10px; background: #333; border: 2px solid var(--bluey); display: flex; align-items: center; justify-content: center; color: #666;">No Photo</div>
								<?php endif; ?>
							</div>
							<div>
								<p><strong>Username:</strong> <?php echo htmlspecialchars($current_user['username']); ?></p>
								<p><strong>Name:</strong> <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></p>
								<p><strong>Species:</strong> <?php echo htmlspecialchars($current_user['species']); ?></p>
								<p><strong>Rank:</strong> <?php echo htmlspecialchars($current_user['rank']); ?></p>
								<p><strong>Department:</strong> <?php echo htmlspecialchars($current_user['department']); ?></p>
								<?php if ($current_user['position']): ?>
								<p><strong>Position:</strong> <?php echo htmlspecialchars($current_user['position']); ?></p>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Password Change Form -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Change Password</h3>
						<form method="POST" action="">
							<input type="hidden" name="action" value="change_password">
							<div style="display: grid; grid-template-columns: 1fr; gap: 1rem; max-width: 400px;">
								<div>
									<label style="color: var(--gold);">Current Password:</label>
									<input type="password" name="current_password" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
								<div>
									<label style="color: var(--gold);">New Password:</label>
									<input type="password" name="new_password" required minlength="6" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
									<small style="color: var(--orange);">Minimum 6 characters</small>
								</div>
								<div>
									<label style="color: var(--gold);">Confirm New Password:</label>
									<input type="password" name="confirm_password" required minlength="6" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold);">
								</div>
							</div>
							<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Change Password</button>
						</form>
					</div>

					<?php if (!$force_password_change): ?>
					<!-- Username Change Form -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Change Username</h3>
						<p style="color: var(--orange); font-size: 0.9rem;">⚠️ Warning: Changing your username will affect your login credentials.</p>
						<form method="POST" action="">
							<input type="hidden" name="action" value="change_username">
							<div style="display: grid; grid-template-columns: 1fr; gap: 1rem; max-width: 400px;">
								<div>
									<label style="color: var(--bluey);">New Username:</label>
									<input type="text" name="new_username" required pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores" value="<?php echo htmlspecialchars($current_user['username']); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--bluey);">
									<small style="color: var(--orange);">Letters, numbers, and underscores only</small>
								</div>
							</div>
							<button type="submit" style="background-color: var(--bluey); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;" onclick="return confirm('Are you sure you want to change your username?')">Change Username</button>
						</form>
					</div>

					<!-- Profile Information Form -->
					<?php if ($current_user['roster_id']): ?>
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Update Profile Information</h3>
						<p style="color: var(--orange); font-size: 0.9rem;">Note: Rank, Department, and Position can only be changed by Command staff.</p>
						<form method="POST" action="">
							<input type="hidden" name="action" value="update_profile">
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
								<div>
									<label style="color: var(--african-violet);">First Name:</label>
									<input type="text" name="first_name" required value="<?php echo htmlspecialchars($current_user['first_name']); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
								</div>
								<div>
									<label style="color: var(--african-violet);">Last Name:</label>
									<input type="text" name="last_name" required value="<?php echo htmlspecialchars($current_user['last_name']); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
								</div>
								<div>
									<label style="color: var(--african-violet);">Species:</label>
									<input type="text" name="species" required value="<?php echo htmlspecialchars($current_user['species']); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
								</div>
							</div>
							<button type="submit" style="background-color: var(--african-violet); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Update Profile</button>
						</form>
					</div>

					<!-- Profile Image Upload -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Update Profile Photo</h3>
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="update_image">
							<div>
								<label style="color: var(--orange);">Profile Photo:</label>
								<input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
								<small style="color: var(--orange);">JPEG, PNG, GIF, or WebP. Max 5MB.</small>
							</div>
							<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem;">Upload Photo</button>
						</form>
					</div>
					<?php endif; ?>
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
