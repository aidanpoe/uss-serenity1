<?php
require_once 'includes/config.php';

// Steam login functions
function loginbutton($buttonstyle = "square") {
	echo "<a href='steamauth/steamauth.php?login' class='lcars-steam-button' onclick='playSoundAndRedirect(\"audio2\", \"steamauth/steamauth.php?login\")'>Sign in through Steam</a>";
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity 74714 - Main Computer</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="assets/classic.css">
	<style>
		.lcars-steam-button {
			background-color: var(--gold);
			color: black;
			border: none;
			padding: 1rem 2rem;
			border-radius: 10px;
			font-weight: bold;
			font-size: 1.1rem;
			text-decoration: none;
			display: inline-block;
			text-transform: uppercase;
			letter-spacing: 1px;
			transition: background-color 0.3s ease;
			cursor: pointer;
			border: 2px solid var(--gold);
		}
		.lcars-steam-button:hover {
			background-color: var(--orange);
			border-color: var(--orange);
			color: black;
		}
		.lcars-steam-button:active {
			background-color: var(--red);
			border-color: var(--red);
		}
	</style>
</head>
<body>
	<audio id="audio1" src="assets/beep1.mp3" preload="auto"></audio>
	<audio id="audio2" src="assets/beep2.mp3" preload="auto"></audio>
	<audio id="audio3" src="assets/beep3.mp3" preload="auto"></audio>
	<audio id="audio4" src="assets/beep4.mp3" preload="auto"></audio>
	<section class="wrap-standard" id="column-3">
		<div class="wrap">
			<div class="left-frame-top">
				<button onclick="playSoundAndRedirect('audio2', 'index.php')" class="panel-1-button">LCARS</button>
				<div class="panel-2">74<span class="hop">-714000</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">USS-SERENITY &#149; 74714</div>
				<div class="data-cascade-button-group">
					<div class="data-cascade-wrapper" id="default">
						<div class="data-column">
							<div class="dc-row-1">74</div>
							<div class="dc-row-1">714</div>
							<div class="dc-row-2">2401</div>
							<div class="dc-row-3">USS</div>
							<div class="dc-row-3">SERENITY</div>
							<div class="dc-row-4">NCC</div>
							<div class="dc-row-5">74714</div>
							<div class="dc-row-6">MAIN</div>
							<div class="dc-row-7">COMP</div>
						</div>
						<div class="data-column">
							<div class="dc-row-1">DECK</div>
							<div class="dc-row-1">01-15</div>
							<div class="dc-row-2">CREW</div>
							<div class="dc-row-3">450</div>
							<div class="dc-row-3">ACTIVE</div>
							<div class="dc-row-4">STATUS</div>
							<div class="dc-row-5">GREEN</div>
							<div class="dc-row-6">ALERT</div>
							<div class="dc-row-7">NORMAL</div>
						</div>
						<div class="data-column">
							<div class="dc-row-1">WARP</div>
							<div class="dc-row-1">CORE</div>
							<div class="dc-row-2">ONLINE</div>
							<div class="dc-row-3">IMPULSE</div>
							<div class="dc-row-3">READY</div>
							<div class="dc-row-4">SHIELDS</div>
							<div class="dc-row-5">100%</div>
							<div class="dc-row-6">WEAPONS</div>
							<div class="dc-row-7">READY</div>
						</div>
					</div>				
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', 'pages/roster.php')" style="background-color: var(--red);">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'pages/med_sci.php')" style="background-color: var(--blue);">MED/SCI</button>
						<button onclick="playSoundAndRedirect('audio2', 'pages/eng_ops.php')" style="background-color: var(--orange);">ENG/OPS</button>
						<button onclick="playSoundAndRedirect('audio2', 'pages/sec_tac.php')" style="background-color: var(--gold);">SEC/TAC</button>
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
					<div class="panel-3">SYS<span class="hop">-STATUS</span></div>
					<div class="panel-4">PWR<span class="hop">-ONLINE</span></div>
					<div class="panel-5">NAV<span class="hop">-READY</span></div>
					<div class="panel-6">COM<span class="hop">-ACTIVE</span></div>
					<div class="panel-7">SEC<span class="hop">-GREEN</span></div>
					<div class="panel-8">MED<span class="hop">-READY</span></div>
					<div class="panel-9">ENG<span class="hop">-NOMINAL</span></div>
				</div>
				<div>
					<div class="panel-10">LCARS<span class="hop">-24.1</span></div>
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
					<h1>Welcome to USS-Serenity</h1>
					<h2>NCC-74714 &#149; Main Computer Interface</h2>
					<h3 class="font-gold">Stardate <?php 
						// Calculate current stardate (Star Trek formula with 360 years added)
						$currentYear = (int)date('Y') + 360;
						$dayOfYear = (int)date('z') + 1; // z is 0-indexed, so add 1
						$stardate = ($currentYear - 2323) * 1000 + (($dayOfYear - 1) * 1000 / 365.25);
						echo number_format($stardate, 1);
					?> &#149; <?php echo date('F j, ') . ($currentYear); ?></h3>
					
					<div style="margin: 2rem 0;">
						<h4>Ship Status: All Systems Nominal</h4>
						<p class="go-big">Current Mission: Deep Space Exploration</p>
						
						<?php if (isLoggedIn()): ?>
							<div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
								<h4>Welcome, <?php echo htmlspecialchars(($_SESSION['rank'] ?? '') . ' ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
								<p>Department: <?php echo htmlspecialchars($_SESSION['department']); ?></p>
								<?php if (!empty($_SESSION['position'])): ?>
								<p>Position: <?php echo htmlspecialchars($_SESSION['position']); ?></p>
								<?php endif; ?>
								<div style="margin-top: 1rem;">
									<a href="pages/profile.php" style="color: var(--blue); margin-right: 1rem;">Edit Profile</a>
									<?php if (isset($_SESSION['steamid'])): ?>
									<a href="steamauth/steamauth.php?logout" style="color: var(--red);">Logout</a>
									<?php else: ?>
									<a href="pages/logout.php" style="color: var(--red);">Logout</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
						

						
						<div style="margin: 2rem 0;">
							<h4>Department Access:</h4>
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
								<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--red);">
									<h5 style="color: var(--red);">COMMAND</h5>
									<p>Strategic Operations & Leadership</p>
									<button onclick="playSoundAndRedirect('audio2', 'pages/command.php')" style="background-color: var(--red); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px;">ACCESS</button>
								</div>
								
								<div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--blue);">
									<h5 style="color: var(--blue);">MEDICAL/SCIENCE</h5>
									<p>Health Services & Research</p>
									<button onclick="playSoundAndRedirect('audio2', 'pages/med_sci.php')" style="background-color: var(--blue); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px;">ACCESS</button>
								</div>
								
								<div style="background: rgba(255, 136, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--orange);">
									<h5 style="color: var(--orange);">ENGINEERING/OPS</h5>
									<p>Ship Systems & Operations</p>
									<button onclick="playSoundAndRedirect('audio2', 'pages/eng_ops.php')" style="background-color: var(--orange); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px;">ACCESS</button>
								</div>
								
								<div style="background: rgba(255, 170, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--gold);">
									<h5 style="color: var(--gold);">SECURITY/TACTICAL</h5>
									<p>Ship Security & Defense</p>
									<button onclick="playSoundAndRedirect('audio2', 'pages/sec_tac.php')" style="background-color: var(--gold); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px;">ACCESS</button>
								</div>
							</div>
						</div>
						
						<?php if (!isLoggedIn()): ?>
						<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 10px; margin: 2rem 0; border: 2px solid var(--african-violet);">
							<h4>Staff Access</h4>
							<p>Access to administrative functions requires Steam authentication.</p>
							<div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; justify-content: center;">
								<div style="text-align: center;">
									<?php loginbutton("rectangle"); ?>
								</div>
							</div>
							<p style="margin-top: 1rem; font-size: 0.9rem; color: var(--bluey); text-align: center;">All crew members must use Steam to access department systems.</p>
						</div>
						<?php endif; ?>
						
						<div style="margin: 2rem 0;">
							<h4>Quick Access:</h4>
							<ul style="list-style: none; padding: 0;">
								<li style="margin: 0.5rem 0;">
									<a href="pages/roster.php" style="color: var(--bluey);">→ Ship's Roster</a>
								</li>
								<li style="margin: 0.5rem 0;">
									<a href="pages/reports.php" style="color: var(--bluey);">→ Department Reports</a>
								</li>
								<li style="margin: 0.5rem 0;">
									<a href="pages/training.php" style="color: var(--bluey);">→ Training Documents</a>
								</li>
								<li style="margin: 0.5rem 0;">
									<a href="https://docs.google.com/document/d/1MwVJZp0NW9SL85EVUFxCENwIrHGyL5uFuWvKGRoO6Sg/edit?tab=t.0" target="_blank" style="color: var(--bluey);">→ Rules of Play</a>
								</li>
								<li style="margin: 0.5rem 0;">
									<a href="#" onclick="alert('Link coming soon'); return false;" style="color: var(--bluey);">→ Discord</a>
								</li>
								<li style="margin: 0.5rem 0;">
									<a href="steam://connect/46.4.12.78:27015" style="color: var(--bluey);">→ Ship Boarding</a>
								</li>
							</ul>
						</div>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					LCARS Inspired Website Template by <a href="https://www.thelcars.com">www.TheLCARS.com</a>.				 		 
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
