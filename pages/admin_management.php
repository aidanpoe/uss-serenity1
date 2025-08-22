<?php
require_once '../includes/config.php';

// Check if user has command access or is Captain
if (!hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Handle account deletion
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        if (hasPermission('Captain') || hasPermission('Command')) {
            try {
                $pdo->beginTransaction();
                
                // Get user and roster info before deletion
                $stmt = $pdo->prepare("SELECT u.*, r.first_name as roster_first, r.last_name as roster_last FROM users u LEFT JOIN roster r ON u.roster_id = r.id WHERE u.id = ?");
                $stmt->execute([$_POST['user_id']]);
                $user_to_delete = $stmt->fetch();
                
                if ($user_to_delete) {
                    // Delete user account
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    // Optionally delete roster entry if it exists and user confirms
                    if (isset($_POST['delete_roster']) && $_POST['delete_roster'] === 'yes' && $user_to_delete['roster_id']) {
                        $stmt = $pdo->prepare("DELETE FROM roster WHERE id = ?");
                        $stmt->execute([$user_to_delete['roster_id']]);
                    }
                    
                    $pdo->commit();
                    $success = "Account for " . htmlspecialchars($user_to_delete['first_name'] . ' ' . $user_to_delete['last_name']) . " has been deleted successfully.";
                } else {
                    $error = "User not found.";
                }
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Error deleting account: " . $e->getMessage();
            }
        } else {
            $error = "Insufficient permissions to delete accounts.";
        }
    }
    
    // Handle marking crew member as deceased
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'mark_deceased') {
        if (hasPermission('MED/SCI') || hasPermission('Captain') || hasPermission('Command')) {
            try {
                $pdo->beginTransaction();
                
                // Update roster status to deceased
                $stmt = $pdo->prepare("UPDATE roster SET status = 'Deceased', date_of_death = ?, cause_of_death = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['date_of_death'],
                    $_POST['cause_of_death'],
                    $_POST['roster_id']
                ]);
                
                // Add final medical record
                $stmt = $pdo->prepare("INSERT INTO medical_records (roster_id, condition_description, treatment, reported_by, status) VALUES (?, ?, ?, ?, 'Deceased')");
                $stmt->execute([
                    $_POST['roster_id'],
                    "Crew member declared deceased. Cause: " . $_POST['cause_of_death'],
                    "Final medical record - crew member deceased",
                    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']
                ]);
                
                // Close any open medical records
                $stmt = $pdo->prepare("UPDATE medical_records SET status = 'Resolved' WHERE roster_id = ? AND status IN ('Open', 'In Progress')");
                $stmt->execute([$_POST['roster_id']]);
                
                $pdo->commit();
                $success = "Crew member has been marked as deceased and medical records updated.";
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Error updating deceased status: " . $e->getMessage();
            }
        } else {
            $error = "Insufficient permissions to mark crew as deceased.";
        }
    }
    
    // Handle reactivating crew member
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'reactivate_crew') {
        if (hasPermission('Captain') || hasPermission('Command')) {
            try {
                $stmt = $pdo->prepare("UPDATE roster SET status = 'Active', date_of_death = NULL, cause_of_death = NULL WHERE id = ?");
                $stmt->execute([$_POST['roster_id']]);
                
                $success = "Crew member has been reactivated.";
            } catch (Exception $e) {
                $error = "Error reactivating crew member: " . $e->getMessage();
            }
        }
    }
    
    // Get all users for account management
    $stmt = $pdo->prepare("
        SELECT u.*, r.first_name as roster_first, r.last_name as roster_last, r.rank, r.department as roster_dept, r.status as roster_status 
        FROM users u 
        LEFT JOIN roster r ON u.roster_id = r.id 
        ORDER BY u.department, u.last_name, u.first_name
    ");
    $stmt->execute();
    $all_users = $stmt->fetchAll();
    
    // Get all roster members for deceased management
    $stmt = $pdo->prepare("SELECT * FROM roster ORDER BY status, department, rank, last_name, first_name");
    $stmt->execute();
    $all_crew = $stmt->fetchAll();
    
    // Get deceased crew members
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE status = 'Deceased' ORDER BY date_of_death DESC");
    $stmt->execute();
    $deceased_crew = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Account & Personnel Management</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.management-container {
			background: rgba(85, 102, 255, 0.1);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--blue);
		}
		.user-card, .crew-card {
			background: rgba(0,0,0,0.7);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 4px solid var(--blue);
		}
		.deceased-card {
			border-left-color: var(--red);
			background: rgba(204, 68, 68, 0.1);
		}
		.management-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
			gap: 1rem;
		}
		.danger-zone {
			background: rgba(204, 68, 68, 0.2);
			border: 2px solid var(--red);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
		}
		.status-badge {
			display: inline-block;
			padding: 0.25rem 0.5rem;
			border-radius: 3px;
			font-size: 0.8rem;
			font-weight: bold;
			margin: 0.25rem;
		}
		.status-active { background: var(--green); color: black; }
		.status-deceased { background: var(--red); color: white; }
		.status-missing { background: var(--orange); color: black; }
		.status-transferred { background: var(--blue); color: black; }
		.tab-container {
			margin: 2rem 0;
		}
		.tab-buttons {
			display: flex;
			gap: 0.5rem;
			margin-bottom: 1rem;
		}
		.tab-button {
			padding: 0.8rem 1.5rem;
			background: rgba(0,0,0,0.5);
			color: var(--blue);
			border: 1px solid var(--blue);
			border-radius: 5px;
			cursor: pointer;
			transition: all 0.3s ease;
		}
		.tab-button.active {
			background: var(--blue);
			color: black;
		}
		.tab-content {
			display: none;
		}
		.tab-content.active {
			display: block;
		}
	</style>
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
				<div class="panel-2">ADMIN<span class="hop">-MGMT</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">ACCOUNT MANAGEMENT &#149; PERSONNEL STATUS</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--red);">ADMIN</button>
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
					<div class="panel-3">ADMIN<span class="hop">-CTRL</span></div>
					<div class="panel-4">USERS<span class="hop">-<?php echo count($all_users); ?></span></div>
					<div class="panel-5">CREW<span class="hop">-<?php echo count($all_crew); ?></span></div>
					<div class="panel-6">DECEASED<span class="hop">-<?php echo count($deceased_crew); ?></span></div>
					<div class="panel-7">STATUS<span class="hop">-MGMT</span></div>
					<div class="panel-8">ACCESS<span class="hop">-GRANTED</span></div>
					<div class="panel-9">ADMIN<span class="hop">-ONLY</span></div>
				</div>
				<div>
					<div class="panel-10">SECURE<span class="hop">-MODE</span></div>
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
					<h1>Account & Personnel Management</h1>
					<h2>Administrative Controls - Command Access Only</h2>
					
					<?php if ($success): ?>
					<div style="background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--blue);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if ($error): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Tab Navigation -->
					<div class="tab-container">
						<div class="tab-buttons">
							<button class="tab-button active" onclick="showTab('accounts')">👥 Account Management</button>
							<button class="tab-button" onclick="showTab('logs')">🔍 Login Logs</button>
							<button class="tab-button" onclick="showTab('deceased')">💀 Deceased Status</button>
							<button class="tab-button" onclick="showTab('memorial')">🕯️ Memorial Registry</button>
						</div>
						
						<!-- Account Management Tab -->
						<div id="accounts" class="tab-content active">
							<div class="management-container">
								<h3>User Account Management</h3>
								<p style="color: var(--blue);"><em>Manage user accounts and permissions - Command/Captain access only</em></p>
								
								<div class="management-grid">
									<?php foreach ($all_users as $user): ?>
									<div class="user-card">
										<div style="display: flex; justify-content: space-between; align-items: start;">
											<div>
												<h4 style="color: var(--blue); margin: 0;">
													<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
												</h4>
												<p style="margin: 0.25rem 0; color: var(--orange);">
													Username: <?php echo htmlspecialchars($user['username']); ?>
												</p>
												<p style="margin: 0.25rem 0; color: var(--green);">
													Department: <?php echo htmlspecialchars($user['department']); ?>
												</p>
												<?php if ($user['roster_first']): ?>
												<p style="margin: 0.25rem 0; font-style: italic; color: var(--bluey);">
													Roster: <?php echo htmlspecialchars($user['rank'] . ' ' . $user['roster_first'] . ' ' . $user['roster_last']); ?>
													<span class="status-badge status-<?php echo strtolower($user['roster_status'] ?? 'active'); ?>">
														<?php echo $user['roster_status'] ?? 'Active'; ?>
													</span>
												</p>
												<?php endif; ?>
											</div>
											
											<div>
												<small style="color: var(--gray);">
													Created: <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
												</small>
											</div>
										</div>
										
										<?php if (hasPermission('Captain') || hasPermission('Command')): ?>
										<div class="danger-zone" style="margin-top: 1rem;">
											<h5 style="color: var(--red); margin: 0 0 0.5rem 0;">⚠️ Delete Account</h5>
											<form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone!');">
												<input type="hidden" name="action" value="delete_account">
												<input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
												<div style="margin: 0.5rem 0;">
													<label style="color: var(--red);">
														<input type="checkbox" name="delete_roster" value="yes" style="margin-right: 0.5rem;">
														Also delete roster entry (if linked)
													</label>
												</div>
												<button type="submit" style="background-color: var(--red); color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.9rem;">
													🗑️ DELETE ACCOUNT
												</button>
											</form>
										</div>
										<?php endif; ?>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
						
						<!-- Login Logs Tab -->
						<div id="logs" class="tab-content">
							<div class="management-container">
								<h3>User Login Activity & Logs</h3>
								<p style="color: var(--blue);"><em>Monitor user login activity and account status - Command access only</em></p>
								
								<div style="text-align: center; margin: 2rem 0;">
									<a href="admin_login_logs.php" class="action-button" style="background-color: var(--blue); color: black; padding: 1rem 2rem; text-decoration: none; border-radius: 10px; font-size: 1.1rem;">
										🔍 View Detailed Login Logs
									</a>
								</div>
								
								<div style="background: rgba(255, 153, 0, 0.1); border: 2px solid var(--gold); border-radius: 10px; padding: 1.5rem; margin: 1rem 0;">
									<h4 style="color: var(--gold); margin-top: 0;">📊 Quick Activity Summary</h4>
									<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
										<?php
										// Quick stats for this page
										$quickStats = $pdo->query("
											SELECT 
												COUNT(*) as total,
												SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as today,
												SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week,
												SUM(CASE WHEN last_login IS NULL THEN 1 ELSE 0 END) as never
											FROM users
										")->fetch();
										?>
										<div style="text-align: center; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 5px;">
											<div style="font-size: 1.5rem; color: var(--gold);"><?php echo $quickStats['total']; ?></div>
											<div>Total Users</div>
										</div>
										<div style="text-align: center; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 5px;">
											<div style="font-size: 1.5rem; color: var(--green);"><?php echo $quickStats['today']; ?></div>
											<div>Active Today</div>
										</div>
										<div style="text-align: center; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 5px;">
											<div style="font-size: 1.5rem; color: var(--blue);"><?php echo $quickStats['week']; ?></div>
											<div>Active This Week</div>
										</div>
										<div style="text-align: center; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 5px;">
											<div style="font-size: 1.5rem; color: var(--red);"><?php echo $quickStats['never']; ?></div>
											<div>Never Logged In</div>
										</div>
									</div>
									
									<h4 style="color: var(--gold);">Available Information:</h4>
									<ul style="color: var(--blue);">
										<li><strong>Last Login Times:</strong> When each user last accessed the system</li>
										<li><strong>Activity Status:</strong> Real-time activity classification (Online, Today, This Week, etc.)</li>
										<li><strong>Account Age:</strong> When accounts were created</li>
										<li><strong>Inactive Detection:</strong> Users who haven't logged in for extended periods</li>
										<li><strong>GDPR Compliance:</strong> Automatic cleanup of old login data (12+ months)</li>
									</ul>
									
									<h4 style="color: var(--gold);">Privacy Features:</h4>
									<ul style="color: var(--green);">
										<li>✅ No IP addresses logged (privacy by design)</li>
										<li>✅ Automatic data retention enforcement</li>
										<li>✅ GDPR compliant logging practices</li>
										<li>✅ Admin-only access to login information</li>
									</ul>
								</div>
							</div>
						</div>
						
						<!-- Deceased Status Management Tab -->
						<div id="deceased" class="tab-content">
							<div class="management-container">
								<h3>Mark Crew Member as Deceased</h3>
								<p style="color: var(--red);"><em>Medical/Command access only - Updates crew status and medical records</em></p>
								
								<form method="POST" action="" style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 10px; border: 2px solid var(--red);">
									<input type="hidden" name="action" value="mark_deceased">
									
									<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
										<div>
											<label style="color: var(--red); font-weight: bold;">Select Crew Member:</label>
											<select name="roster_id" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--red); border-radius: 5px;">
												<option value="">Select Crew Member...</option>
												<?php foreach ($all_crew as $crew): ?>
													<?php if ($crew['status'] === 'Active'): ?>
													<option value="<?php echo $crew['id']; ?>">
														<?php echo htmlspecialchars($crew['rank'] . ' ' . $crew['first_name'] . ' ' . $crew['last_name'] . ' (' . $crew['department'] . ')'); ?>
													</option>
													<?php endif; ?>
												<?php endforeach; ?>
											</select>
										</div>
										
										<div>
											<label style="color: var(--red); font-weight: bold;">Date of Death:</label>
											<input type="date" name="date_of_death" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--red); border-radius: 5px;">
										</div>
									</div>
									
									<div style="margin-bottom: 1rem;">
										<label style="color: var(--red); font-weight: bold;">Cause of Death:</label>
										<textarea name="cause_of_death" required rows="3" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--red); border-radius: 5px;" placeholder="Describe the cause of death..."></textarea>
									</div>
									
									<div style="text-align: center;">
										<button type="submit" onclick="return confirm('Are you sure you want to mark this crew member as deceased? This will update their status and close all medical records.');" style="background-color: var(--red); color: white; border: none; padding: 1rem 2rem; border-radius: 5px; font-size: 1.1rem;">
											💀 MARK AS DECEASED
										</button>
									</div>
								</form>
							</div>
						</div>
						
						<!-- Memorial Registry Tab -->
						<div id="memorial" class="tab-content">
							<div class="management-container">
								<h3>Memorial Registry</h3>
								<p style="color: var(--orange);"><em>Remembering our fallen crew members</em></p>
								
								<?php if (empty($deceased_crew)): ?>
								<div style="text-align: center; padding: 2rem; color: var(--green);">
									<h4>🌟 No crew members currently listed as deceased</h4>
									<p>All crew members are accounted for and in active service.</p>
								</div>
								<?php else: ?>
								<div class="management-grid">
									<?php foreach ($deceased_crew as $deceased): ?>
									<div class="crew-card deceased-card">
										<div style="text-align: center; margin-bottom: 1rem;">
											<?php if ($deceased['image_path'] && file_exists('../' . $deceased['image_path'])): ?>
											<img src="../<?php echo htmlspecialchars($deceased['image_path']); ?>" alt="<?php echo htmlspecialchars($deceased['first_name'] . ' ' . $deceased['last_name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--red);">
											<?php endif; ?>
										</div>
										
										<div style="text-align: center;">
											<h4 style="color: var(--red); margin: 0;">
												<?php echo htmlspecialchars($deceased['rank'] . ' ' . $deceased['first_name'] . ' ' . $deceased['last_name']); ?>
											</h4>
											<p style="margin: 0.25rem 0; color: var(--orange);">
												<?php echo htmlspecialchars($deceased['species']); ?> - <?php echo htmlspecialchars($deceased['department']); ?>
											</p>
											<?php if ($deceased['position']): ?>
											<p style="margin: 0.25rem 0; font-style: italic; color: var(--bluey);">
												<?php echo htmlspecialchars($deceased['position']); ?>
											</p>
											<?php endif; ?>
											
											<div style="border-top: 1px solid var(--red); padding-top: 1rem; margin-top: 1rem;">
												<p style="color: var(--red); font-weight: bold;">
													Date of Death: <?php echo $deceased['date_of_death'] ? date('F j, Y', strtotime($deceased['date_of_death'])) : 'Unknown'; ?>
												</p>
												<?php if ($deceased['cause_of_death']): ?>
												<p style="color: var(--orange); font-size: 0.9rem;">
													Cause: <?php echo htmlspecialchars($deceased['cause_of_death']); ?>
												</p>
												<?php endif; ?>
											</div>
											
											<?php if (hasPermission('Captain') || hasPermission('Command')): ?>
											<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--gray);">
												<form method="POST" action="" onsubmit="return confirm('Are you sure you want to reactivate this crew member?');">
													<input type="hidden" name="action" value="reactivate_crew">
													<input type="hidden" name="roster_id" value="<?php echo $deceased['id']; ?>">
													<button type="submit" style="background-color: var(--green); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; font-size: 0.8rem;">
														🔄 Reactivate (Admin)
													</button>
												</form>
											</div>
											<?php endif; ?>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</main>
				<footer>
					USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
					Administrative Management System - Authorized Personnel Only
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<script>
		function showTab(tabName) {
			// Hide all tab contents
			const tabContents = document.querySelectorAll('.tab-content');
			const tabButtons = document.querySelectorAll('.tab-button');
			
			tabContents.forEach(content => content.classList.remove('active'));
			tabButtons.forEach(button => button.classList.remove('active'));
			
			// Show selected tab
			document.getElementById(tabName).classList.add('active');
			event.target.classList.add('active');
		}
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
