<?php
// Traditional registration has been disabled - redirect to Steam authentication
header('Location: ../steamauth/steamauth.php?login');
exit;
?>
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
                    return null; // No file uploaded, which is OK
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("Server configuration error: no temp directory.");
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Server error: cannot write file.");
                default:
                    throw new Exception("Unknown upload error.");
            }
        }
        return null;
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
    $upload_dir = '../uploads/';
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
    $filename = uniqid('user_') . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return 'uploads/' . $filename;
    } else {
        throw new Exception("Failed to save uploaded image file.");
    }
}

// If already logged in, redirect to home
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

// Handle registration
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'register') {
    try {
        $pdo = getConnection();
        
        // Validate inputs
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Basic validation
        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required.");
        }
        
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("Username already exists. Please choose a different username.");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Handle image upload
        $image_path = null;
        $image_error = null;
        if (isset($_FILES['image'])) {
            try {
                $image_path = handleImageUpload($_FILES['image']);
            } catch (Exception $e) {
                $image_error = "Image upload failed: " . $e->getMessage();
                // Continue with registration even if image upload fails
            }
        }
        
        // Only proceed if no critical errors
        if (!$error) {
        
        // Insert into roster first
        $stmt = $pdo->prepare("
            INSERT INTO roster (rank, first_name, last_name, species, department, position, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['rank'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['species'],
            $_POST['department'],
            $_POST['position'] ?? null,
            $image_path
        ]);
        
        $roster_id = $pdo->lastInsertId();
        
        // Create user account and link to roster
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if the additional columns exist in users table
        try {
            $pdo->query("SELECT position, rank, roster_id FROM users LIMIT 1");
            $has_extended_columns = true;
        } catch (Exception $e) {
            $has_extended_columns = false;
        }
        
        if ($has_extended_columns) {
            // Use full INSERT with all columns
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, first_name, last_name, department, position, rank, roster_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $password_hash,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['department'],
                $_POST['position'] ?? null,
                $_POST['rank'],
                $roster_id
            ]);
        } else {
            // Use basic INSERT with only existing columns
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, first_name, last_name, department) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $password_hash,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['department']
            ]);
        }
        
        $pdo->commit();
        $success = "Account created successfully! You can now log in with your username and password.";
        
        // Add image upload status to success message
        if ($image_error) {
            $success .= " Note: " . $image_error;
        } else if ($image_path) {
            $success .= " Profile photo uploaded successfully.";
        }
        
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-VOYAGER - Create Account</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
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
				<div class="panel-2">REGISTER<span class="hop">-NEW</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">CREW REGISTRATION &#149; NEW ACCOUNT</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'login.php')">LOGIN</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--african-violet);">REGISTER</button>
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
					<div class="panel-3">NEW<span class="hop">-USER</span></div>
					<div class="panel-4">CREW<span class="hop">-REG</span></div>
					<div class="panel-5">DEPT<span class="hop">-SELECT</span></div>
					<div class="panel-6">SECURE<span class="hop">-LOGIN</span></div>
					<div class="panel-7">STARFLEET<span class="hop">-DB</span></div>
					<div class="panel-8">ACTV<span class="hop">-MON</span></div>
					<div class="panel-9">REAL<span class="hop">-TIME</span></div>
				</div>
				<div>
					<div class="panel-10">STATUS<span class="hop">-READY</span></div>
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
					<h1>Create New Account</h1>
					<h2>Crew Registration &#149; Starfleet Personnel</h2>
					
					<?php if ($success): ?>
					<div style="background: rgba(0, 255, 0, 0.2); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid var(--green);">
						<h4 style="color: var(--green);">Registration Successful</h4>
						<p><?php echo htmlspecialchars($success); ?></p>
						<button onclick="playSoundAndRedirect('audio2', 'login.php')" style="background-color: var(--green); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; margin-top: 0.5rem;">Go to Login</button>
					</div>
					<?php endif; ?>
					
					<?php if ($error): ?>
					<div style="background: rgba(204, 68, 68, 0.3); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 2px solid var(--red);">
						<h4 style="color: var(--red);">Registration Error</h4>
						<p><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (!$success): ?>
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
						<h3>Join the USS-VOYAGER Crew</h3>
						<p style="color: var(--blue);"><em>Create your personal account and join a department (Command positions require Captain approval)</em></p>
						
						<form method="POST" action="" enctype="multipart/form-data">
							<input type="hidden" name="action" value="register">
							
							<!-- Account Information -->
							<fieldset style="border: 1px solid var(--african-violet); border-radius: 10px; padding: 1.5rem; margin: 1.5rem 0; background: rgba(102, 51, 153, 0.1);">
								<legend style="color: var(--african-violet); font-weight: bold; padding: 0 1rem;">Account Information</legend>
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
									<div>
										<label style="color: var(--african-violet); font-weight: bold;">Username:</label>
										<input type="text" name="username" required pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
										<small style="color: var(--bluey);">Letters, numbers, and underscores only</small>
									</div>
									<div>
										<label style="color: var(--african-violet); font-weight: bold;">Password:</label>
										<input type="password" name="password" required minlength="6" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
										<small style="color: var(--bluey);">Minimum 6 characters</small>
									</div>
									<div>
										<label style="color: var(--african-violet); font-weight: bold;">Confirm Password:</label>
										<input type="password" name="confirm_password" required minlength="6" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
										<small style="color: var(--bluey);">Must match password above</small>
									</div>
								</div>
							</fieldset>
							
							<!-- Personal Information -->
							<fieldset style="border: 1px solid var(--blue); border-radius: 10px; padding: 1.5rem; margin: 1.5rem 0; background: rgba(85, 102, 255, 0.1);">
								<legend style="color: var(--blue); font-weight: bold; padding: 0 1rem;">Personal Information</legend>
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
									<div>
										<label style="color: var(--blue);">Rank:</label>
										<select name="rank" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
											<option value="Crewman 3rd Class">Crewman 3rd Class</option>
											<option value="Crewman 2nd Class">Crewman 2nd Class</option>
											<option value="Crewman 1st Class">Crewman 1st Class</option>
											<option value="Petty Officer 3rd class">Petty Officer 3rd class</option>
											<option value="Petty Officer 1st class">Petty Officer 1st class</option>
											<option value="Chief Petty Officer">Chief Petty Officer</option>
											<option value="Senior Chief Petty Officer">Senior Chief Petty Officer</option>
											<option value="Master Chief Petty Officer">Master Chief Petty Officer</option>
											<option value="Command Master Chief Petty Officer">Command Master Chief Petty Officer</option>
											<option value="Warrant Officer">Warrant Officer</option>
											<option value="Ensign">Ensign</option>
											<option value="Lieutenant Junior Grade">Lieutenant Junior Grade</option>
											<option value="Lieutenant">Lieutenant</option>
											<option value="Lieutenant Commander">Lieutenant Commander</option>
										</select>
									</div>
									<div>
										<label style="color: var(--blue);">First Name:</label>
										<input type="text" name="first_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
									</div>
									<div>
										<label style="color: var(--blue);">Last Name:</label>
										<input type="text" name="last_name" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
									</div>
									<div>
										<label style="color: var(--blue);">Species:</label>
										<input type="text" name="species" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);" value="<?php echo isset($_POST['species']) ? htmlspecialchars($_POST['species']) : ''; ?>">
									</div>
									<div>
										<label style="color: var(--blue);">Department:</label>
										<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);">
											<option value="">Select Department...</option>
											<option value="MED/SCI" <?php echo (isset($_POST['department']) && $_POST['department'] === 'MED/SCI') ? 'selected' : ''; ?>>MED/SCI</option>
											<option value="ENG/OPS" <?php echo (isset($_POST['department']) && $_POST['department'] === 'ENG/OPS') ? 'selected' : ''; ?>>ENG/OPS</option>
											<option value="SEC/TAC" <?php echo (isset($_POST['department']) && $_POST['department'] === 'SEC/TAC') ? 'selected' : ''; ?>>SEC/TAC</option>
										</select>
										<small style="color: var(--orange);">Command positions require Captain approval</small>
									</div>
									<div>
										<label style="color: var(--blue);">Position (Optional):</label>
										<input type="text" name="position" placeholder="e.g., Medical Officer, Engineer" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue);" value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
									</div>
								</div>
							</fieldset>
							
							<!-- Profile Photo -->
							<fieldset style="border: 1px solid var(--orange); border-radius: 10px; padding: 1.5rem; margin: 1.5rem 0; background: rgba(255, 136, 0, 0.1);">
								<legend style="color: var(--orange); font-weight: bold; padding: 0 1rem;">Profile Photo (Optional)</legend>
								<div>
									<label style="color: var(--orange);">Crew Photo:</label>
									<input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange);">
									<small style="color: var(--bluey);">Accepted formats: JPEG, PNG, GIF, WebP (Max 5MB)</small>
								</div>
							</fieldset>
							
							<div style="text-align: center; margin: 2rem 0;">
								<button type="submit" style="background-color: var(--green); color: black; border: none; padding: 1rem 3rem; border-radius: 10px; font-size: 1.2rem; font-weight: bold;">
									CREATE ACCOUNT & JOIN CREW
								</button>
							</div>
						</form>
						
						<div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--gray);">
							<p>Already have an account?</p>
							<button onclick="playSoundAndRedirect('audio2', 'login.php')" style="background-color: var(--african-violet); color: black; border: none; padding: 0.5rem 1.5rem; border-radius: 5px;">
								Login Here
							</button>
						</div>
					</div>
					<?php endif; ?>
				</main>
				<footer>
					USS-VOYAGER NCC-74656 &copy; 2401 Starfleet Command<br>
					Crew Registration System - Authorized Personnel Only
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
