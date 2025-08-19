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
    $roster_id = $_POST['roster_id'] ?? null;
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    // Check if username already exists
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Username already exists";
        }
    }
    
    if (empty($errors)) {
        try {
            // Create user account (Steam authentication only, no password needed)
            $stmt = $pdo->prepare("INSERT INTO users (username, steam_id, active, created_at) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$username, $_SESSION['pending_steam_id']]);
            $user_id = $pdo->lastInsertId();
            
            // Link to roster if selected
            if ($roster_id) {
                $stmt = $pdo->prepare("UPDATE roster SET user_id = ? WHERE id = ?");
                $stmt->execute([$user_id, $roster_id]);
            }
            
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
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

// Get available roster entries (not linked to users)
$stmt = $pdo->prepare("SELECT id, rank, first_name, last_name, department, position FROM roster WHERE user_id IS NULL ORDER BY rank, last_name");
$stmt->execute();
$available_roster = $stmt->fetchAll();
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
        <h1 style="text-align: center; color: var(--blue); margin-bottom: 2rem;">Complete Your Registration</h1>
        
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
            <strong>Welcome to USS Serenity!</strong> Your Steam account has been verified. Please create a username and optionally link to your crew roster entry.
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
            
            <?php if (!empty($available_roster)): ?>
                <div class="form-group">
                    <label for="roster_id">Link to Roster Entry (Optional)</label>
                    <select id="roster_id" name="roster_id">
                        <option value="">-- Select your crew member entry --</option>
                        <?php foreach ($available_roster as $member): ?>
                            <option value="<?php echo $member['id']; ?>" <?php echo ($_POST['roster_id'] ?? '') == $member['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(($member['rank'] ? $member['rank'] . ' ' : '') . $member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['department'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--blue);">If your character appears in the roster, link your account to it. You can change this later.</small>
                </div>
            <?php endif; ?>
            
            <div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 5px; margin: 1rem 0; border: 1px solid var(--blue);">
                <p style="margin: 0; color: var(--blue); font-size: 0.9rem;"><strong>Note:</strong> Authentication is handled entirely through Steam. You don't need to create a password - just use your Steam login!</p>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn">Complete Registration</button>
                <a href="../steamauth/steamauth.php?logout" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
