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
            
            // Map roster department to user department permissions
            $user_department = '';
            $roster_department = '';
            switch($department) {
                case 'Medical':
                case 'Science':
                    $user_department = 'MED/SCI';
                    $roster_department = 'MED/SCI';
                    break;
                case 'Engineering':
                case 'Operations':
                    $user_department = 'ENG/OPS';
                    $roster_department = 'ENG/OPS';
                    break;
                case 'Security':
                case 'Tactical':
                    $user_department = 'SEC/TAC';
                    $roster_department = 'SEC/TAC';
                    break;
                case 'Command':
                    $user_department = 'Command';
                    $roster_department = 'Command';
                    break;
                default:
                    $user_department = 'SEC/TAC'; // Default fallback
                    $roster_department = 'SEC/TAC';
                    break;
            }
            
            // Create user account with proper department permissions
            $stmt = $pdo->prepare("INSERT INTO users (username, steam_id, department, active, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$username, $_SESSION['pending_steam_id'], $user_department]);
            $user_id = $pdo->lastInsertId();
            
            // Create roster entry and link to user
            $character_name = $first_name . ' ' . $last_name;
            $stmt = $pdo->prepare("INSERT INTO roster (rank, first_name, last_name, species, department, user_id, character_name, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$rank, $first_name, $last_name, $species, $roster_department, $user_id, $character_name]);
            $character_id = $pdo->lastInsertId();
            
            // Set this as the user's active character
            $stmt = $pdo->prepare("UPDATE users SET active_character_id = ? WHERE id = ?");
            $stmt->execute([$character_id, $user_id]);
            
            $pdo->commit();
            
            // Log the user in with proper session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['rank'] = $rank;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['department'] = $user_department; // User permission department (MED/SCI, ENG/OPS, SEC/TAC)
            $_SESSION['roster_department'] = $roster_department; // Roster display department
            $_SESSION['steamid'] = $_SESSION['pending_steam_id']; // Set steamid for session
            
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
        
        .department-info {
            background: rgba(85, 102, 255, 0.1);
            border: 1px solid var(--blue);
            color: var(--blue);
            padding: 0.75rem;
            border-radius: 5px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            display: none;
        }
    </style>
    <script>
        function updateDepartmentInfo() {
            const dept = document.getElementById('department').value;
            const infoDiv = document.getElementById('department-info');
            
            let message = '';
            switch(dept) {
                case 'Medical':
                case 'Science':
                    message = 'Access Group: <strong>MED/SCI</strong> - Medical and Science systems access';
                    break;
                case 'Engineering':
                case 'Operations':
                    message = 'Access Group: <strong>ENG/OPS</strong> - Engineering and Operations systems access';
                    break;
                case 'Security':
                case 'Tactical':
                    message = 'Access Group: <strong>SEC/TAC</strong> - Security and Tactical systems access';
                    break;
                default:
                    message = '';
                    break;
            }
            
            if (message) {
                infoDiv.innerHTML = message;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
    </script>
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
                <select id="rank" name="rank">
                    <option value="">-- Select Rank --</option>
                    <option value="Crewman 3rd Class" <?php echo ($_POST['rank'] ?? '') == 'Crewman 3rd Class' ? 'selected' : ''; ?>>Crewman 3rd Class</option>
                    <option value="Crewman 2nd Class" <?php echo ($_POST['rank'] ?? '') == 'Crewman 2nd Class' ? 'selected' : ''; ?>>Crewman 2nd Class</option>
                    <option value="Crewman 1st Class" <?php echo ($_POST['rank'] ?? '') == 'Crewman 1st Class' ? 'selected' : ''; ?>>Crewman 1st Class</option>
                    <option value="Petty Officer 3rd Class" <?php echo ($_POST['rank'] ?? '') == 'Petty Officer 3rd Class' ? 'selected' : ''; ?>>Petty Officer 3rd Class</option>
                    <option value="Petty Officer 2nd Class" <?php echo ($_POST['rank'] ?? '') == 'Petty Officer 2nd Class' ? 'selected' : ''; ?>>Petty Officer 2nd Class</option>
                    <option value="Petty Officer 1st Class" <?php echo ($_POST['rank'] ?? '') == 'Petty Officer 1st Class' ? 'selected' : ''; ?>>Petty Officer 1st Class</option>
                    <option value="Chief Petty Officer" <?php echo ($_POST['rank'] ?? '') == 'Chief Petty Officer' ? 'selected' : ''; ?>>Chief Petty Officer</option>
                    <option value="Senior Chief Petty Officer" <?php echo ($_POST['rank'] ?? '') == 'Senior Chief Petty Officer' ? 'selected' : ''; ?>>Senior Chief Petty Officer</option>
                    <option value="Master Chief Petty Officer" <?php echo ($_POST['rank'] ?? '') == 'Master Chief Petty Officer' ? 'selected' : ''; ?>>Master Chief Petty Officer</option>
                    <option value="Command Master Chief Petty Officer" <?php echo ($_POST['rank'] ?? '') == 'Command Master Chief Petty Officer' ? 'selected' : ''; ?>>Command Master Chief Petty Officer</option>
                    <option value="Warrant Officer" <?php echo ($_POST['rank'] ?? '') == 'Warrant Officer' ? 'selected' : ''; ?>>Warrant Officer</option>
                    <option value="Ensign" <?php echo ($_POST['rank'] ?? '') == 'Ensign' ? 'selected' : ''; ?>>Ensign</option>
                    <option value="Lieutenant Junior Grade" <?php echo ($_POST['rank'] ?? '') == 'Lieutenant Junior Grade' ? 'selected' : ''; ?>>Lieutenant Junior Grade</option>
                </select>
                <small style="color: var(--blue);">Your Starfleet rank (optional). Command ranks are restricted.</small>
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
                <select id="department" name="department" required onchange="updateDepartmentInfo()">
                    <option value="">-- Select Department --</option>
                    <option value="Medical" <?php echo ($_POST['department'] ?? '') == 'Medical' ? 'selected' : ''; ?>>Medical</option>
                    <option value="Science" <?php echo ($_POST['department'] ?? '') == 'Science' ? 'selected' : ''; ?>>Science</option>
                    <option value="Engineering" <?php echo ($_POST['department'] ?? '') == 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                    <option value="Operations" <?php echo ($_POST['department'] ?? '') == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                    <option value="Security" <?php echo ($_POST['department'] ?? '') == 'Security' ? 'selected' : ''; ?>>Security</option>
                    <option value="Tactical" <?php echo ($_POST['department'] ?? '') == 'Tactical' ? 'selected' : ''; ?>>Tactical</option>
                </select>
                <div id="department-info" class="department-info"></div>
                <small style="color: var(--blue);">Your department assignment determines your system access permissions.</small>
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
    
    <script>
        // Initialize department info on page load
        updateDepartmentInfo();
    </script>
</body>
</html>
