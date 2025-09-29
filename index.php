<?php
// Include security headers
require_once 'includes/security_headers.php';
require_once 'includes/config.php';

// Update last active timestamp for current character
updateLastActive();

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
		
		/* LCARS Quick Access Button Hover Effects */
		.lcars-quick-btn:hover {
			transform: scale(1.05);
			box-shadow: 0 4px 8px rgba(0,0,0,0.4);
		}
	</style>
</head>
<body>
	<audio id="audio1" src="assets/beep1.mp3" preload="auto"></audio>
	<audio id="audio2" src="assets/beep2.mp3" preload="auto"></audio>
	<audio id="audio3" src="assets/beep3.mp3" preload="auto"></audio>
	<audio id="audio4" src="assets/beep4.mp3" preload="auto"></audio>
	
	<?php if (isset($_GET['account_deleted']) && $_GET['account_deleted'] == '1'): ?>
	<!-- Account Deletion Confirmation -->
	<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center;">
		<div style="background: rgba(0,0,0,0.95); padding: 3rem; border-radius: 15px; border: 3px solid var(--green); max-width: 600px; text-align: center;">
			<h2 style="color: var(--green); margin-bottom: 2rem;">✅ Account Successfully Deleted</h2>
			<p style="color: white; font-size: 1.2rem; margin-bottom: 2rem;">
				Your USS Serenity account and personal data have been permanently deleted in compliance with GDPR requirements.
			</p>
			<p style="color: var(--blue); margin-bottom: 2rem;">
				Thank you for being part of our community. Live long and prosper! 🖖
			</p>
			<button onclick="this.parentElement.parentElement.style.display='none'" 
				style="background-color: var(--green); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; font-size: 1.1rem; font-weight: bold; cursor: pointer;">
				Acknowledge
			</button>
		</div>
	</div>
	<?php endif; ?>
	
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
						<button onclick="playSoundAndRedirect('audio2', 'pages/cargo_bay.php')" style="background-color: var(--african-violet);">CARGO BAY</button>
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
					
					<?php showShowcaseNotice(); ?>
					<h3 class="font-gold">Stardate <?php 
						// Calculate current stardate (Star Trek formula with 360 years added)
						$currentYear = (int)date('Y') + 360;
						$dayOfYear = (int)date('z') + 1; // z is 0-indexed, so add 1
						$stardate = ($currentYear - 2323) * 1000 + (($dayOfYear - 1) * 1000 / 365.25);
						echo number_format($stardate, 1);
					?> &#149; <?php echo date('F j, ') . ($currentYear); ?></h3>
					
					<div style="margin: 2rem 0;">
						<h4>Ship Status: All Systems Nominal</h4>
						<p class="go-big">This website now functions as a portfolio, serving solely as a showcase and product advertisement. For further information, please visit <a href="http://0161.org" style="color: var(--blue); text-decoration: underline;">0161.org</a></p>
						
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
									<button onclick="showFeatureDisabledMessage()" class='lcars-steam-button' style="background-color: #666; color: #ccc; border: 2px solid #666; cursor: not-allowed;">Feature Disabled - Showcase Only</button>
								</div>
							</div>
							<p style="margin-top: 1rem; font-size: 0.9rem; color: var(--bluey); text-align: center;">This is a portfolio demonstration site.</p>
						</div>
						<?php endif; ?>
						
						<div style="margin: 2rem 0;">
							<h4>Quick Access:</h4>
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
								<div style="background: rgba(85, 102, 255, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--blue);">
									<h5 style="color: var(--blue); margin: 0 0 0.5rem 0;">Ship's Roster</h5>
									<button onclick="playSoundAndRedirect('audio2', 'pages/roster.php')" class="lcars-quick-btn" style="background-color: var(--blue); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(255, 136, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--orange);">
									<h5 style="color: var(--orange); margin: 0 0 0.5rem 0;">Department Reports</h5>
									<button onclick="playSoundAndRedirect('audio2', 'pages/reports.php')" class="lcars-quick-btn" style="background-color: var(--orange); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(153, 102, 204, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--african-violet);">
									<h5 style="color: var(--african-violet); margin: 0 0 0.5rem 0;">Training Documents</h5>
									<button onclick="window.open('http://0161.org/', '_blank')" class="lcars-quick-btn" style="background-color: var(--african-violet); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(204, 68, 68, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--red);">
									<h5 style="color: var(--red); margin: 0 0 0.5rem 0;">Rules of Play</h5>
									<button onclick="window.open('https://docs.google.com/document/d/1MwVJZp0NW9SL85EVUFxCENwIrHGyL5uFuWvKGRoO6Sg/edit?tab=t.0', '_blank')" class="lcars-quick-btn" style="background-color: var(--red); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(114, 137, 218, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid #7289DA;">
									<h5 style="color: #7289DA; margin: 0 0 0.5rem 0;">Discord</h5>
									<button onclick="showDiscordModal(); return false;" class="lcars-quick-btn" style="background-color: #7289DA; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(255, 170, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--gold);">
									<h5 style="color: var(--gold); margin: 0 0 0.5rem 0;">Ship Boarding</h5>
									<button onclick="showShipBoardingConfirm(); return false;" class="lcars-quick-btn" style="background-color: var(--gold); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(153, 102, 204, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--african-violet);">
									<h5 style="color: var(--african-violet); margin: 0 0 0.5rem 0;">Cargo Bay</h5>
									<button onclick="playSoundAndRedirect('audio2', 'pages/cargo_bay.php')" class="lcars-quick-btn" style="background-color: var(--african-violet); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
								<div style="background: rgba(255, 215, 0, 0.2); padding: 1rem; border-radius: 10px; border: 2px solid var(--gold);">
									<h5 style="color: var(--gold); margin: 0 0 0.5rem 0;">🏅 Rewards Database</h5>
									<button onclick="playSoundAndRedirect('audio2', 'pages/rewards.php')" class="lcars-quick-btn" style="background-color: var(--gold); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
										ACCESS
									</button>
								</div>
							</div>
						</div>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					LCARS Inspired Website Template by <a href="https://www.thelcars.com">www.TheLCARS.com</a><br>
					<div style="margin-top: 1rem; font-size: 0.8rem;">
						<a href="privacy-policy.html" style="color: var(--blue); margin: 0 1rem;">Privacy Policy</a>
						<a href="terms-of-service.html" style="color: var(--blue); margin: 0 1rem;">Terms of Service</a>
						<?php if (isLoggedIn()): ?>
						<a href="pages/data_rights.php" style="color: var(--gold); margin: 0 1rem;">Your Data Rights</a>
						<?php endif; ?>
						<span style="color: var(--green);">GDPR Compliant</span>
					</div>
				</footer> 
			</div>
		</div>
	</section>
	
	<!-- LCARS Discord Modal -->
	<div id="discordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
		<div style="background: linear-gradient(135deg, #000000, #1a1a2e); border: 3px solid var(--orange); border-radius: 15px; padding: 2rem; max-width: 500px; text-align: center; box-shadow: 0 0 30px rgba(255, 136, 0, 0.5);">
			<div style="border-bottom: 2px solid var(--orange); padding-bottom: 1rem; margin-bottom: 1.5rem;">
				<h3 style="color: var(--orange); margin: 0; font-size: 1.3rem;">LCARS - COMMUNICATION CHANNEL</h3>
			</div>
			<div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1.1rem; line-height: 1.5;">
				<p style="margin: 0; font-weight: bold;">Taking you to Discord</p>
				<p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Please stand by...</p>
			</div>
		</div>
	</div>
	
	<!-- LCARS Ship Boarding Confirmation Modal -->
	<div id="shipBoardingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
		<div style="background: linear-gradient(135deg, #000000, #1a1a2e); border: 3px solid var(--orange); border-radius: 15px; padding: 2rem; max-width: 500px; text-align: center; box-shadow: 0 0 30px rgba(255, 136, 0, 0.5);">
			<div style="border-bottom: 2px solid var(--orange); padding-bottom: 1rem; margin-bottom: 1.5rem;">
				<h3 style="color: var(--orange); margin: 0; font-size: 1.3rem;">LCARS - TRANSPORT AUTHORIZATION</h3>
			</div>
			<div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1rem; line-height: 1.5;">
				<p style="margin: 0;">If you are already on the server, you will reconnect.</p>
				<p style="margin: 0.5rem 0 0 0; font-weight: bold;">Are you sure you want to do this?</p>
			</div>
			<div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
				<button onclick="confirmShipBoarding()" style="background: var(--blue); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">YES - TRANSPORT</button>
				<button onclick="cancelShipBoarding()" style="background: var(--red); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">NO - CANCEL</button>
			</div>
		</div>
	</div>
	
	<!-- LCARS Feature Disabled Modal -->
	<div id="featureDisabledModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
		<div style="background: linear-gradient(135deg, #000000, #1a1a2e); border: 3px solid var(--red); border-radius: 15px; padding: 2rem; max-width: 500px; text-align: center; box-shadow: 0 0 30px rgba(204, 68, 68, 0.5);">
			<div style="border-bottom: 2px solid var(--red); padding-bottom: 1rem; margin-bottom: 1.5rem;">
				<h3 style="color: var(--red); margin: 0; font-size: 1.3rem;">LCARS - FEATURE DISABLED</h3>
			</div>
			<div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1rem; line-height: 1.5;">
				<p style="margin: 0;">This is a portfolio demonstration site.</p>
				<p style="margin: 0.5rem 0 0 0; font-weight: bold;">Authentication features have been disabled for showcase purposes.</p>
			</div>
			<div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
				<button onclick="closeFeatureDisabledModal()" style="background: var(--blue); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">ACKNOWLEDGE</button>
			</div>
		</div>
	</div>
	
	<script>
		function showDiscordModal() {
			const modal = document.getElementById('discordModal');
			modal.style.display = 'flex';
			modal.style.opacity = '0';
			setTimeout(() => {
				modal.style.transition = 'opacity 0.3s ease';
				modal.style.opacity = '1';
				
				// After 2 seconds, redirect to Discord and fade out
				setTimeout(() => {
					// Open Discord link in new tab
					window.open('https://discord.gg/WuvgeRah67', '_blank');
					
					// Fade out modal
					modal.style.transition = 'opacity 0.5s ease';
					modal.style.opacity = '0';
					setTimeout(() => {
						modal.style.display = 'none';
					}, 500);
				}, 2000);
			}, 10);
		}
		
		function closeDiscordModal() {
			const modal = document.getElementById('discordModal');
			modal.style.transition = 'opacity 0.3s ease';
			modal.style.opacity = '0';
			setTimeout(() => {
				modal.style.display = 'none';
			}, 300);
		}
		
		function showShipBoardingConfirm() {
			const modal = document.getElementById('shipBoardingModal');
			modal.style.display = 'flex';
			modal.style.opacity = '0';
			setTimeout(() => {
				modal.style.transition = 'opacity 0.3s ease';
				modal.style.opacity = '1';
			}, 10);
		}
		
		function confirmShipBoarding() {
			// Show configuration message instead of connecting to server
			const modal = document.getElementById('shipBoardingModal');
			const modalContent = modal.querySelector('div');
			
			// Change content to configuration message
			modalContent.innerHTML = `
				<div style="border-bottom: 2px solid var(--orange); padding-bottom: 1rem; margin-bottom: 1.5rem;">
					<h3 style="color: var(--orange); margin: 0; font-size: 1.3rem;">LCARS - TRANSPORT SYSTEM</h3>
				</div>
				<div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1rem; line-height: 1.5;">
					<p style="margin: 0;">This function can be configured to load custom game servers etc.</p>
					<p style="margin: 0.5rem 0 0 0; font-weight: bold;">Perfect for connecting to multiplayer games or applications!</p>
				</div>
				<div style="margin-top: 2rem;">
					<button onclick="cancelShipBoarding()" style="background: var(--blue); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">ACKNOWLEDGE</button>
				</div>
			`;
		}
		
		function cancelShipBoarding() {
			const modal = document.getElementById('shipBoardingModal');
			const modalContent = modal.querySelector('div');
			
			// Change content to farewell message
			modalContent.innerHTML = `
				<div style="border-bottom: 2px solid var(--orange); padding-bottom: 1rem; margin-bottom: 1.5rem;">
					<h3 style="color: var(--orange); margin: 0; font-size: 1.3rem;">LCARS - TRANSPORT CANCELLED</h3>
				</div>
				<div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1.2rem; font-weight: bold;">
					<p style="margin: 0;">Enjoy your shift onboard!</p>
				</div>
			`;
			
			// Fade out after 2 seconds
			setTimeout(() => {
				modal.style.transition = 'opacity 0.5s ease';
				modal.style.opacity = '0';
				setTimeout(() => {
					modal.style.display = 'none';
					// Reset modal content for next time
					modalContent.innerHTML = `
						<div style="border-bottom: 2px solid var(--orange); padding-bottom: 1rem; margin-bottom: 1.5rem;">
							<h3 style="color: var(--orange); margin: 0; font-size: 1.3rem;">LCARS - TRANSPORT AUTHORIZATION</h3>
						</div>
						<div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1rem; line-height: 1.5;">
							<p style="margin: 0;">If you are already on the server, you will reconnect.</p>
							<p style="margin: 0.5rem 0 0 0; font-weight: bold;">Are you sure you want to do this?</p>
						</div>
						<div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
							<button onclick="confirmShipBoarding()" style="background: var(--blue); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">YES - TRANSPORT</button>
							<button onclick="cancelShipBoarding()" style="background: var(--red); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">NO - CANCEL</button>
						</div>
					`;
				}, 500);
			}, 2000);
		}
		
		function showFeatureDisabledMessage() {
			const modal = document.getElementById('featureDisabledModal');
			modal.style.display = 'flex';
			modal.style.opacity = '0';
			setTimeout(() => {
				modal.style.transition = 'opacity 0.3s ease';
				modal.style.opacity = '1';
			}, 10);
		}
		
		function closeFeatureDisabledModal() {
			const modal = document.getElementById('featureDisabledModal');
			modal.style.transition = 'opacity 0.3s ease';
			modal.style.opacity = '0';
			setTimeout(() => {
				modal.style.display = 'none';
			}, 300);
		}
	</script>
	
	<script type="text/javascript" src="assets/lcars.js"></script>
	<?php displayShowcaseMessage(); ?>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
