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
				} catch(PDOException $e) {
					die("Connection failed: " . $e->getMessage());
				}
				
				// Check if user exists in USS Serenity database
				$stmt = $pdo->prepare("SELECT u.*, r.rank, r.first_name, r.last_name, r.department, r.position, r.image_path 
					FROM users u 
					LEFT JOIN roster r ON u.id = r.user_id 
					WHERE u.steam_id = ?");
				$stmt->execute([$matches[1]]);
				$user = $stmt->fetch();
				
				if ($user) {
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
