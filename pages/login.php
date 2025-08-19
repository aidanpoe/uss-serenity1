<?php
require_once '../includes/config.php';

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Invalid credentials. Access denied.';
            }
        } catch (Exception $e) {
            $error = 'System error. Please contact system administrator.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Staff Login</title>
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
				<div class="panel-2">AUTH<span class="hop">-LOGIN</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">AUTHENTICATION &#149; REQUIRED</div>
				<div class="data-cascade-button-group">
					<div class="data-cascade-wrapper" id="default">
						<div class="data-column">
							<div class="dc-row-1">SEC</div>
							<div class="dc-row-1">LEVEL</div>
							<div class="dc-row-2">7</div>
							<div class="dc-row-3">AUTH</div>
							<div class="dc-row-3">REQ</div>
							<div class="dc-row-4">STAFF</div>
							<div class="dc-row-5">ONLY</div>
							<div class="dc-row-6">SECURE</div>
							<div class="dc-row-7">LOGIN</div>
						</div>
					</div>				
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--african-violet);">LOGIN</button>
						<button onclick="playSoundAndRedirect('audio2', 'reports.php')">REPORTS</button>
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
					<div class="panel-3">SEC<span class="hop">-AUTH</span></div>
					<div class="panel-4">ENCR<span class="hop">-256</span></div>
					<div class="panel-5">PROT<span class="hop">-ACTV</span></div>
					<div class="panel-6">USER<span class="hop">-VERIFY</span></div>
					<div class="panel-7">PASS<span class="hop">-CHECK</span></div>
					<div class="panel-8">LOG<span class="hop">-AUDIT</span></div>
					<div class="panel-9">SYS<span class="hop">-SECURE</span></div>
				</div>
				<div>
					<div class="panel-10">AUTH<span class="hop">-SYS</span></div>
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
					<h1>Staff Authentication</h1>
					<h2>USS-Serenity Security Access</h2>
					
					<?php if ($error): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<h4 style="color: var(--red);">ACCESS DENIED</h4>
						<p><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0; max-width: 500px;">
						<form method="POST" action="">
							<div style="margin-bottom: 1.5rem;">
								<label for="username" style="display: block; color: var(--bluey); margin-bottom: 0.5rem; font-weight: bold;">USERNAME:</label>
								<input type="text" id="username" name="username" required 
									   style="width: 100%; padding: 0.75rem; font-size: 1.1rem; background: black; color: var(--space-white); border: 2px solid var(--bluey); border-radius: 5px;"
									   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
							</div>
							
							<div style="margin-bottom: 2rem;">
								<label for="password" style="display: block; color: var(--orange); margin-bottom: 0.5rem; font-weight: bold;">PASSWORD:</label>
								<input type="password" id="password" name="password" required 
									   style="width: 100%; padding: 0.75rem; font-size: 1.1rem; background: black; color: var(--space-white); border: 2px solid var(--orange); border-radius: 5px;">
							</div>
							
							<button type="submit" 
									style="background-color: var(--african-violet); color: black; border: none; padding: 1rem 2rem; border-radius: 10px; font-size: 1.2rem; font-weight: bold; cursor: pointer; width: 100%;"
									onclick="document.getElementById('audio2').play();">
								AUTHENTICATE
							</button>
						</form>
					</div>
					
					<div style="background: rgba(85, 102, 255, 0.2); padding: 1.5rem; border-radius: 10px; border: 2px solid var(--blue);">
						<h4 style="color: var(--blue);">Default Login Credentials</h4>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
							<div>
								<strong>Captain:</strong><br>
								Username: Poe<br>
								Password: Class390
							</div>
							<div>
								<strong>Engineering:</strong><br>
								Username: torres<br>
								Password: engineering123
							</div>
							<div>
								<strong>Medical:</strong><br>
								Username: mccoy<br>
								Password: medical456
							</div>
							<div>
								<strong>Security:</strong><br>
								Username: worf<br>
								Password: security789
							</div>
						</div>
					</div>
					
					<p style="margin-top: 2rem; color: var(--gray);">
						<em>All login attempts are logged and monitored for security purposes.</em>
					</p>
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
