<?php
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
							
							<!-- Crew Messaging System -->
							<div style="background: linear-gradient(135deg, rgba(85, 102, 255, 0.2), rgba(0,0,0,0.8)); padding: 1.5rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--blue);">
								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 2px solid var(--blue); padding-bottom: 1rem;">
									<h4 style="color: var(--blue); margin: 0;">🚀 Crew Communications</h4>
									<div style="display: flex; align-items: center; gap: 1rem;">
										<button onclick="toggleOnlineUsers()" style="background: var(--orange); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem; cursor: pointer;">
											<span id="online-count">0</span> Online
										</button>
										<button onclick="refreshMessages()" style="background: var(--blue); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem; cursor: pointer;">
											↻ Refresh
										</button>
									</div>
								</div>
								
								<!-- Online Users Panel -->
								<div id="online-users-panel" style="display: none; background: rgba(0,0,0,0.5); border: 1px solid var(--orange); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
									<h5 style="color: var(--orange); margin: 0 0 0.5rem 0;">Crew Members Online:</h5>
									<div id="online-users-list" style="color: var(--white); font-size: 0.9rem;"></div>
								</div>
								
								<!-- Messages Display -->
								<div id="messages-container" style="background: rgba(0,0,0,0.6); border: 2px solid var(--blue); border-radius: 10px; height: 300px; overflow-y: auto; padding: 1rem; margin-bottom: 1rem;">
									<div id="messages-loading" style="text-align: center; color: var(--blue); padding: 2rem;">
										🔄 Loading messages...
									</div>
									<div id="messages-list"></div>
								</div>
								
								<!-- Message Input -->
								<div style="display: flex; gap: 0.5rem;">
									<input type="text" id="message-input" placeholder="Type your message to the crew..." 
										   style="flex: 1; padding: 0.75rem; border: 2px solid var(--blue); border-radius: 5px; background: rgba(0,0,0,0.8); color: white; font-size: 1rem;"
										   maxlength="500">
									<button onclick="sendMessage()" id="send-button" 
											style="background: var(--blue); color: black; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">
										SEND
									</button>
								</div>
								<div style="text-align: right; margin-top: 0.5rem;">
									<small style="color: var(--bluey);">Max 500 characters | Auto-refresh every 10 seconds | Messages expire after 7 days</small>
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
									<button onclick="playSoundAndRedirect('audio2', 'pages/training.php')" class="lcars-quick-btn" style="background-color: var(--african-violet); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.2s ease;">
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
							</div>
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
					window.open('https://discord.gg/r5r38Md3Xb', '_blank');
					
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
			// Connect to GMOD server
			window.location.href = 'steam://connect/46.4.12.78:27015';
			// Close modal
			cancelShipBoarding();
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
	</script>
	
	<?php if (isLoggedIn()): ?>
	<!-- Crew Messaging System JavaScript -->
	<script>
		let messagesContainer = null;
		let messagesList = null;
		let messagesLoading = null;
		let messageInput = null;
		let sendButton = null;
		let lastMessageId = 0;
		let autoRefreshInterval = null;
		let onlineUsersPanel = null;
		let onlineUsersList = null;
		let onlineCount = null;
		
		// User department for delete permissions
		const userDepartment = '<?php echo isset($_SESSION['department']) ? strtolower($_SESSION['department']) : ''; ?>';
		const isCommand = (userDepartment === 'command');
		
		// Initialize messaging system when page loads
		document.addEventListener('DOMContentLoaded', function() {
			messagesContainer = document.getElementById('messages-container');
			messagesList = document.getElementById('messages-list');
			messagesLoading = document.getElementById('messages-loading');
			messageInput = document.getElementById('message-input');
			sendButton = document.getElementById('send-button');
			onlineUsersPanel = document.getElementById('online-users-panel');
			onlineUsersList = document.getElementById('online-users-list');
			onlineCount = document.getElementById('online-count');
			
			if (messageInput && sendButton) {
				// Load initial messages
				loadMessages();
				
				// Set up auto-refresh
				autoRefreshInterval = setInterval(function() {
					loadMessages(false); // Don't show loading indicator for auto-refresh
					loadOnlineUsers();
				}, 10000); // Refresh every 10 seconds
				
				// Load online users initially
				loadOnlineUsers();
				
				// Enter key to send message
				messageInput.addEventListener('keypress', function(e) {
					if (e.key === 'Enter' && !e.shiftKey) {
						e.preventDefault();
						sendMessage();
					}
				});
				
				// Character counter
				messageInput.addEventListener('input', function() {
					const remaining = 500 - this.value.length;
					const color = remaining < 50 ? 'var(--red)' : 'var(--bluey)';
					
					let counterElement = document.getElementById('char-counter');
					if (!counterElement) {
						counterElement = document.createElement('small');
						counterElement.id = 'char-counter';
						counterElement.style.position = 'absolute';
						counterElement.style.right = '0';
						counterElement.style.bottom = '-1.5rem';
						counterElement.style.fontSize = '0.8rem';
						
						// Make input container relative positioned
						this.parentElement.style.position = 'relative';
						this.parentElement.appendChild(counterElement);
					}
					
					counterElement.style.color = color;
					counterElement.textContent = remaining + ' characters remaining';
				});
			}
		});
		
		// Clean up interval when page unloads
		window.addEventListener('beforeunload', function() {
			if (autoRefreshInterval) {
				clearInterval(autoRefreshInterval);
			}
		});
		
		async function sendMessage() {
			if (!messageInput || !sendButton) return;
			
			const message = messageInput.value.trim();
			if (!message) return;
			
			// Disable send button temporarily
			sendButton.disabled = true;
			sendButton.textContent = 'SENDING...';
			
			try {
				const formData = new FormData();
				formData.append('action', 'send_message');
				formData.append('message', message);
				
				const response = await fetch('api/messaging.php', {
					method: 'POST',
					body: formData
				});
				
				const data = await response.json();
				
				if (data.success) {
					messageInput.value = '';
					loadMessages(false); // Refresh messages without loading indicator
					
					// Remove character counter
					const counter = document.getElementById('char-counter');
					if (counter) counter.remove();
				} else {
					alert('Error: ' + (data.error || 'Failed to send message'));
				}
			} catch (error) {
				console.error('Error sending message:', error);
				alert('Network error. Please try again.');
			} finally {
				sendButton.disabled = false;
				sendButton.textContent = 'SEND';
			}
		}
		
		async function loadMessages(showLoading = true) {
			if (!messagesList || !messagesLoading) return;
			
			if (showLoading) {
				messagesLoading.style.display = 'block';
				messagesList.style.display = 'none';
			}
			
			try {
				const response = await fetch('api/messaging.php?action=get_messages&limit=25');
				const data = await response.json();
				
				if (data.messages) {
					displayMessages(data.messages);
					if (showLoading) {
						messagesLoading.style.display = 'none';
						messagesList.style.display = 'block';
					}
				} else {
					throw new Error(data.error || 'Failed to load messages');
				}
			} catch (error) {
				console.error('Error loading messages:', error);
				if (showLoading) {
					messagesLoading.innerHTML = '❌ Error loading messages. <button onclick="loadMessages()" style="background: var(--red); color: black; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; cursor: pointer;">Retry</button>';
				}
			}
		}
		
		function displayMessages(messages) {
			if (!messagesList) return;
			
			if (messages.length === 0) {
				messagesList.innerHTML = '<div style="text-align: center; color: var(--bluey); padding: 2rem; font-style: italic;">No messages yet. Be the first to say something!</div>';
				return;
			}
			
			let html = '';
			messages.forEach(msg => {
				const isOwnMessage = msg.is_own_message;
				const bgColor = isOwnMessage ? 'rgba(85, 102, 255, 0.3)' : 'rgba(255, 255, 255, 0.05)';
				const borderColor = isOwnMessage ? 'var(--blue)' : 'rgba(255, 255, 255, 0.1)';
				
				html += `
					<div style="background: ${bgColor}; border: 1px solid ${borderColor}; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.5rem;">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
							<div style="display: flex; align-items: center; gap: 0.5rem;">
								<span style="color: var(--orange); font-weight: bold; font-size: 0.9rem;">
									${msg.sender_rank ? msg.sender_rank + ' ' : ''}${msg.sender_name}
								</span>
								<span style="background: var(--${getDepartmentColor(msg.sender_department)}); color: black; padding: 0.1rem 0.4rem; border-radius: 3px; font-size: 0.7rem; font-weight: bold;">
									${msg.sender_department || 'CREW'}
								</span>
							</div>
							<div style="display: flex; align-items: center; gap: 0.5rem;">
								<span style="color: var(--bluey); font-size: 0.8rem;">${msg.timestamp}</span>
								<span style="color: var(--orange); font-size: 0.7rem;" title="Message expires on ${msg.expires_at}">
									${msg.days_until_expiry}d
								</span>
								${(isOwnMessage || isCommand) ? `<button onclick="deleteMessage(${msg.id})" style="background: var(--red); color: black; border: none; padding: 0.1rem 0.3rem; border-radius: 3px; font-size: 0.7rem; cursor: pointer;" title="Delete message">🗑️</button>` : ''}
							</div>
						</div>
						<div style="color: white; line-height: 1.4; word-wrap: break-word;">
							${escapeHtml(msg.message)}
						</div>
					</div>
				`;
			});
			
			messagesList.innerHTML = html;
			
			// Auto-scroll to bottom
			if (messagesContainer) {
				messagesContainer.scrollTop = messagesContainer.scrollHeight;
			}
		}
		
		function getDepartmentColor(dept) {
			switch(dept) {
				case 'MED/SCI': return 'blue';
				case 'ENG/OPS': return 'orange';
				case 'SEC/TAC': return 'gold';
				case 'Command': return 'red';
				default: return 'bluey';
			}
		}
		
		function escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
		
		async function deleteMessage(messageId) {
			// Get the message element to check if it's the user's own message
			const messageElement = event.target.closest('div[style*="background:"]');
			const isOwnMsg = messageElement && messageElement.style.background.includes('85, 102, 255');
			
			let confirmText = 'Are you sure you want to delete this message?';
			if (isCommand && !isOwnMsg) {
				confirmText = 'As Command staff, you are about to delete another crew member\'s message. Are you sure?';
			}
			
			if (!confirm(confirmText)) return;
			
			try {
				const formData = new FormData();
				formData.append('action', 'delete_message');
				formData.append('message_id', messageId);
				
				const response = await fetch('api/messaging.php', {
					method: 'POST',
					body: formData
				});
				
				const data = await response.json();
				
				if (data.success) {
					loadMessages(false);
				} else {
					alert('Error: ' + (data.error || 'Failed to delete message'));
				}
			} catch (error) {
				console.error('Error deleting message:', error);
				alert('Network error. Please try again.');
			}
		}
		
		function refreshMessages() {
			loadMessages(true);
			loadOnlineUsers();
		}
		
		async function loadOnlineUsers() {
			try {
				const response = await fetch('api/messaging.php?action=get_online_users');
				const data = await response.json();
				
				if (data.online_users) {
					displayOnlineUsers(data.online_users);
				}
			} catch (error) {
				console.error('Error loading online users:', error);
			}
		}
		
		function displayOnlineUsers(users) {
			if (!onlineUsersList || !onlineCount) return;
			
			onlineCount.textContent = users.length;
			
			if (users.length === 0) {
				onlineUsersList.innerHTML = '<em>No other crew members currently online</em>';
			} else {
				let html = '';
				users.forEach(user => {
					html += `
						<div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
							<div>
								<span style="color: var(--orange); font-weight: bold;">
									${user.rank_name ? user.rank_name + ' ' : ''}${user.character_name}
								</span>
								<span style="background: var(--${getDepartmentColor(user.department)}); color: black; padding: 0.1rem 0.3rem; border-radius: 3px; font-size: 0.7rem; font-weight: bold; margin-left: 0.5rem;">
									${user.department || 'CREW'}
								</span>
							</div>
							<span style="color: var(--bluey); font-size: 0.8rem;">Active ${user.last_seen}</span>
						</div>
					`;
				});
				onlineUsersList.innerHTML = html;
			}
		}
		
		function toggleOnlineUsers() {
			if (!onlineUsersPanel) return;
			
			if (onlineUsersPanel.style.display === 'none') {
				onlineUsersPanel.style.display = 'block';
				loadOnlineUsers(); // Refresh when opening
			} else {
				onlineUsersPanel.style.display = 'none';
			}
		}
	</script>
	<?php endif; ?>
	
	<script type="text/javascript" src="assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
