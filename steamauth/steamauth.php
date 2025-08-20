<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function logoutbutton() {
	echo "<form action='' method='get'><button name='logout' type='submit' style='background-color: var(--red); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px;'>Logout</button></form>"; //logout button
}

function loginbutton($buttonstyle = "square") {
	$button['rectangle'] = "01";
	$button['square'] = "02";
	$button = "<a href='steamauth/steamauth.php?login'><img src='https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_".$button[$buttonstyle].".png'></a>";
	
	echo $button;
}

if (isset($_GET['login'])){
	require_once 'openid.php';
	try {
		require_once 'SteamConfig.php';
		$openid = new LightOpenID($steamauth['domainname']);
		
		if(!$openid->mode) {
			$openid->identity = 'https://steamcommunity.com/openid';
			header('Location: ' . $openid->authUrl());
			exit;
		} elseif ($openid->mode == 'cancel') {
			echo 'User has canceled authentication!';
		} else {
			if($openid->validate()) { 
				$id = $openid->identity;
				$ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
				preg_match($ptn, $id, $matches);
				
				$_SESSION['steamid'] = $matches[1];
				
				// Database connection for Steam authentication
				try {
					require_once '../includes/secure_config.php';
					$pdo = new PDO(
						"mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
						DB_USERNAME, 
						DB_PASSWORD,
						[
							PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
							PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
							PDO::ATTR_EMULATE_PREPARES => false,
						]
					);
				} catch(PDOException $e) {
					error_log("Steam auth database connection failed: " . $e->getMessage());
					die("Authentication service unavailable. Please try again later.");
				}
				
				// Check if user exists in USS Serenity database
				$stmt = $pdo->prepare("
					SELECT u.*, r.rank, r.first_name, r.last_name, r.department as roster_department, r.position, r.image_path 
					FROM users u 
					LEFT JOIN roster r ON u.active_character_id = r.id 
					WHERE u.steam_id = ? AND u.active = 1
				");
				$stmt->execute([$matches[1]]);
				$user = $stmt->fetch();
				
				if ($user) {
					// User exists, log them in with their active character
					$_SESSION['user_id'] = $user['id'];
					$_SESSION['username'] = $user['username'];
					$_SESSION['steamid'] = $matches[1]; // Ensure steamid is set
					
					// Set character data if they have an active character
					if ($user['first_name']) {
						$_SESSION['rank'] = $user['rank'];
						$_SESSION['first_name'] = $user['first_name'];
						$_SESSION['last_name'] = $user['last_name'];
						$_SESSION['position'] = $user['position'];
						$_SESSION['image_path'] = $user['image_path'];
						$_SESSION['roster_department'] = $user['roster_department'];
						$_SESSION['character_id'] = $user['active_character_id']; // Store character ID for last_active tracking
						
						// Map roster department to proper permission group
						$permission_dept = $user['department']; // Start with user's stored department
						
						// Override with mapped department based on character's roster department
						switch($user['roster_department']) {
							case 'Medical':
							case 'Science':
								$permission_dept = 'MED/SCI';
								break;
							case 'Engineering':
							case 'Operations':
								$permission_dept = 'ENG/OPS';
								break;
							case 'Security':
							case 'Tactical':
								$permission_dept = 'SEC/TAC';
								break;
							case 'Command':
								$permission_dept = 'Command';
								break;
						}
						
						// Update user's permission group in database if needed
						if ($permission_dept !== $user['department']) {
							$updateDeptStmt = $pdo->prepare("UPDATE users SET department = ? WHERE id = ?");
							$updateDeptStmt->execute([$permission_dept, $user['id']]);
						}
						
						$_SESSION['department'] = $permission_dept;
						
						// Update last_active timestamp for the character
						if ($user['active_character_id']) {
							$lastActiveStmt = $pdo->prepare("UPDATE roster SET last_active = NOW() WHERE id = ?");
							$lastActiveStmt->execute([$user['active_character_id']]);
						}
					} else {
						// No active character, just set the stored department
						$_SESSION['department'] = $user['department'];
					}
					
					// Update last login
					$updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
					$updateStmt->execute([$user['id']]);
				} else {
					// New user - redirect to registration
					$_SESSION['pending_steam_id'] = $matches[1];
					header('Location: ../pages/steam_register.php');
					exit;
				}
				
				header('Location: '.$steamauth['loginpage']);
				exit;
			} else {
				echo "User is not logged in.\n";
			}
		}
	} catch(ErrorException $e) {
		echo $e->getMessage();
	}
}

if (isset($_GET['logout'])){
	require_once 'SteamConfig.php';
	
	// Update last_active to show user logged out (set to past time to avoid "online" status)
	if (isset($_SESSION['character_id'])) {
		try {
			require_once '../includes/secure_config.php';
			$pdo = new PDO(
				"mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
				DB_USERNAME, 
				DB_PASSWORD,
				[
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
				]
			);
			
			// Set last_active to 10 minutes ago to ensure it doesn't show as "online"
			$stmt = $pdo->prepare("UPDATE roster SET last_active = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
			$stmt->execute([$_SESSION['character_id']]);
		} catch (Exception $e) {
			// Silent fail - don't break logout if this fails
		}
	}
	
	session_unset();
	session_destroy();
	header('Location: '.$steamauth['logoutpage']);
	exit;
}

if (isset($_GET['update'])){
	unset($_SESSION['steam_uptodate']);
	require_once 'userInfo.php';
	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

// Version 4.0

?>
