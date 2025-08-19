<?php
session_start();
require '../includes/config.php';

// Check if there's a pending Steam ID
if (!isset($_SESSION['pending_steam_id'])) {
    header('Location: ../index.php');
    exit;
}

// Include Steam user info
require '../steamauth/userInfo.php';

// Handle form submission
if ($_POST) {
    $username = trim($_POST['username']);
    $rank = trim($_POST['rank']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $species = trim($_POST['species']);
    $department = $_POST['department'];
    $position = trim($_POST['position']);
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    if (empty($species)) {
        $errors[] = "Species is required";
    }
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    if (empty($position)) {
        $errors[] = "Position is required";
    }
    
    // Check if username already exists
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Username already exists";
        }
    }
    
    // Check if character name already exists
    if (!empty($first_name) && !empty($last_name)) {
        $stmt = $pdo->prepare("SELECT id FROM roster WHERE first_name = ? AND last_name = ?");
        $stmt->execute([$first_name, $last_name]);
        if ($stmt->fetch()) {
            $errors[] = "A crew member with this name already exists in the roster";
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create user account (Steam authentication only, no password needed)
            $stmt = $pdo->prepare("INSERT INTO users (username, steam_id, active, created_at) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$username, $_SESSION['pending_steam_id']]);
            $user_id = $pdo->lastInsertId();
            
            // Create roster entry and link to user
            $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, position, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$rank, $first_name, $last_name, $species, $department, $position, $user_id]);
            
            $pdo->commit();
            
            // Log the user in
            $stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.department, r.position, r.image_path 
                FROM users u 
                LEFT JOIN roster r ON u.id = r.user_id 
                WHERE u.id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rank'] = $user['rank'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['position'] = $user['position'];
            $_SESSION['image_path'] = $user['image_path'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user_id]);
            
            unset($_SESSION['pending_steam_id']);
            header('Location: ../index.php');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steam Registration - USS Serenity</title>
    <link rel="stylesheet" href="../assets/classic.css">
    <style>
        .registration-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(0,0,0,0.8);
            border-radius: 10px;
            border: 2px solid var(--blue);
        }
        
        .steam-profile {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(102, 153, 204, 0.2);
            border-radius: 10px;
            border: 1px solid var(--blue);
        }
        
        .steam-avatar {
            margin-right: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--blue);
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--blue);
            border-radius: 5px;
            background: rgba(0,0,0,0.8);
            color: white;
            font-size: 1rem;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--yellow);
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.3);
        }
        
        .btn {
            background-color: var(--blue);
            color: black;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-right: 1rem;
        }
        
        .btn:hover {
            background-color: var(--yellow);
        }
        
        .btn-secondary {
            background-color: var(--red);
        }
        
        .error {
            background: rgba(204, 68, 68, 0.2);
            border: 1px solid var(--red);
            color: var(--red);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .info {
            background: rgba(102, 153, 204, 0.2);
            border: 1px solid var(--blue);
            color: var(--blue);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h1 style="text-align: center; color: var(--blue); margin-bottom: 2rem;">Join the USS Serenity Crew</h1>
        
        <div class="steam-profile">
            <div class="steam-avatar">
                <img src="<?php echo htmlspecialchars($steamprofile['avatarmedium']); ?>" alt="Steam Avatar" style="border-radius: 5px;">
            </div>
            <div>
                <h3 style="color: var(--blue); margin: 0;"><?php echo htmlspecialchars($steamprofile['personaname']); ?></h3>
                <p style="margin: 0.5rem 0 0 0; color: var(--blue);">Steam ID: <?php echo htmlspecialchars($steamprofile['steamid']); ?></p>
            </div>
        </div>
        
        <div class="info">
            <strong>Welcome to USS Serenity!</strong> Your Steam account has been verified. Please create your account and crew roster profile.
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                <small style="color: var(--blue);">This will be your display name on USS Serenity systems.</small>
            </div>
            
            <h3 style="color: var(--blue); margin: 2rem 0 1rem 0; border-bottom: 2px solid var(--blue); padding-bottom: 0.5rem;">Crew Roster Profile</h3>
            
            <div class="form-group">
                <label for="rank">Rank</label>
                <input type="text" id="rank" name="rank" value="<?php echo htmlspecialchars($_POST['rank'] ?? ''); ?>" placeholder="e.g., Lieutenant, Commander">
                <small style="color: var(--blue);">Your Starfleet rank (optional).</small>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="species">Species *</label>
                <input type="text" id="species" name="species" value="<?php echo htmlspecialchars($_POST['species'] ?? ''); ?>" required placeholder="e.g., Human, Vulcan, Andorian">
                <small style="color: var(--blue);">Your character's species.</small>
            </div>
            
            <div class="form-group">
                <label for="department">Department *</label>
                <select id="department" name="department" required>
                    <option value="">-- Select Department --</option>
                    <option value="Command" <?php echo ($_POST['department'] ?? '') == 'Command' ? 'selected' : ''; ?>>Command</option>
                    <option value="Engineering" <?php echo ($_POST['department'] ?? '') == 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                    <option value="Operations" <?php echo ($_POST['department'] ?? '') == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                    <option value="Medical" <?php echo ($_POST['department'] ?? '') == 'Medical' ? 'selected' : ''; ?>>Medical</option>
                    <option value="Science" <?php echo ($_POST['department'] ?? '') == 'Science' ? 'selected' : ''; ?>>Science</option>
                    <option value="Security" <?php echo ($_POST['department'] ?? '') == 'Security' ? 'selected' : ''; ?>>Security</option>
                    <option value="Tactical" <?php echo ($_POST['department'] ?? '') == 'Tactical' ? 'selected' : ''; ?>>Tactical</option>
                    <option value="Marine" <?php echo ($_POST['department'] ?? '') == 'Marine' ? 'selected' : ''; ?>>Marine</option>
                    <option value="Civilian" <?php echo ($_POST['department'] ?? '') == 'Civilian' ? 'selected' : ''; ?>>Civilian</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="position">Position *</label>
                <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>" required placeholder="e.g., Chief Engineer, Tactical Officer">
                <small style="color: var(--blue);">Your character's role/position aboard the USS Serenity.</small>
            </div>
            
            <div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 5px; margin: 1rem 0; border: 1px solid var(--blue);">
                <p style="margin: 0; color: var(--blue); font-size: 0.9rem;"><strong>Note:</strong> Your crew roster profile will be created automatically with your account. Authentication is handled entirely through Steam - no password needed!</p>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn">Create Account & Join Crew</button>
                <a href="../steamauth/steamauth.php?logout" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
