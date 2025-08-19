<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<!-- Steam Auth Debug Started -->\n";

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
	echo "<!-- Login process started -->\n";
	
	try {
		require_once 'SteamConfig.php';
		echo "<!-- SteamConfig loaded -->\n";
		
		require_once 'openid.php';
		echo "<!-- OpenID library loaded -->\n";
		
		$openid = new LightOpenID($steamauth['domainname']);
		echo "<!-- OpenID object created with domain: " . htmlspecialchars($steamauth['domainname']) . " -->\n";
		
		if(!$openid->mode) {
			echo "<!-- No OpenID mode, redirecting to Steam -->\n";
			$openid->identity = 'https://steamcommunity.com/openid';
			$auth_url = $openid->authUrl();
			echo "<!-- Auth URL: " . htmlspecialchars($auth_url) . " -->\n";
			header('Location: ' . $auth_url);
			exit;
		} elseif ($openid->mode == 'cancel') {
			echo '<h1>Steam Authentication Cancelled</h1>';
			echo '<p>User has canceled authentication!</p>';
			echo '<a href="../index.php">Return to Homepage</a>';
		} else {
			echo "<!-- Validating OpenID response -->\n";
			if($openid->validate()) {
				echo "<!-- OpenID validation successful -->\n";
				$id = $openid->identity;
				$ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
				preg_match($ptn, $id, $matches);
				
				if (isset($matches[1])) {
					$_SESSION['steamid'] = $matches[1];
					echo "<!-- Steam ID captured: " . htmlspecialchars($matches[1]) . " -->\n";
					
					// Database connection for Steam authentication
					try {
						$pdo = new PDO(
							"mysql:host=localhost;port=3306;dbname=serenity;charset=utf8mb4", 
							"serenity", 
							"Os~886go4",
							[
								PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
								PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
								PDO::ATTR_EMULATE_PREPARES => false,
							]
						);
						echo "<!-- Database connection successful -->\n";
						
						// Check if user exists in USS Serenity database
						try {
							$stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.department, r.position, r.image_path 
								FROM users u 
								LEFT JOIN roster r ON u.id = r.user_id 
								WHERE u.steam_id = ?");
							$stmt->execute([$matches[1]]);
							$user = $stmt->fetch();
							echo "<!-- User lookup completed -->\n";
						} catch (PDOException $e) {
							echo "<!-- Database query failed, probably steam_id column doesn't exist: " . htmlspecialchars($e->getMessage()) . " -->\n";
							echo '<h1>Database Setup Required</h1>';
							echo '<p>The Steam integration database setup is required.</p>';
							echo '<p><a href="../setup_steam.php">Click here to setup Steam integration</a></p>';
							echo '<p><a href="../index.php">Return to Homepage</a></p>';
							exit;
						}
						
						if ($user) {
							echo "<!-- User found, logging in -->\n";
							// User exists, log them in
							$_SESSION['user_id'] = $user['id'];
							$_SESSION['username'] = $user['username'];
							$_SESSION['rank'] = $user['rank'];
							$_SESSION['first_name'] = $user['first_name'];
							$_SESSION['last_name'] = $user['last_name'];
							$_SESSION['department'] = $user['department'];
							$_SESSION['position'] = $user['position'];
							$_SESSION['image_path'] = $user['image_path'];
							
							// Update last login
							try {
								$updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
								$updateStmt->execute([$user['id']]);
							} catch (PDOException $e) {
								// Ignore if last_login column doesn't exist yet
							}
						} else {
							echo "<!-- New user, redirecting to registration -->\n";
							// New user - redirect to registration
							$_SESSION['pending_steam_id'] = $matches[1];
							header('Location: ../pages/steam_register.php');
							exit;
						}
						
						header('Location: '.$steamauth['loginpage']);
						exit;
						
					} catch(PDOException $e) {
						echo "<!-- Database connection failed: " . htmlspecialchars($e->getMessage()) . " -->\n";
						echo '<h1>Database Connection Error</h1>';
						echo '<p>Could not connect to database: ' . htmlspecialchars($e->getMessage()) . '</p>';
						echo '<p><a href="../index.php">Return to Homepage</a></p>';
					}
				} else {
					echo "<!-- Steam ID pattern match failed -->\n";
					echo '<h1>Steam ID Error</h1>';
					echo '<p>Could not extract Steam ID from response.</p>';
					echo '<p><a href="../index.php">Return to Homepage</a></p>';
				}
			} else {
				echo "<!-- OpenID validation failed -->\n";
				echo '<h1>Steam Authentication Failed</h1>';
				echo '<p>Steam authentication validation failed.</p>';
				echo '<p><a href="../index.php">Return to Homepage</a></p>';
			}
		}
	} catch(Exception $e) {
		echo "<!-- Exception caught: " . htmlspecialchars($e->getMessage()) . " -->\n";
		echo '<h1>Steam Authentication Error</h1>';
		echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
		echo '<p><a href="../index.php">Return to Homepage</a></p>';
	}
}

if (isset($_GET['logout'])){
	require_once 'SteamConfig.php';
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

echo "<!-- Steam Auth Debug Ended -->\n";
?>
