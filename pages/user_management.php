<?php
require_once '../includes/config.php';

// Require Captain authorization
if (!isLoggedIn() || !hasPermission('Captain')) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Handle password reset
    if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $user_id = $_POST['user_id'];
        
        // Get user info
        $stmt = $pdo->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Reset password to username and set force_password_change flag
            $new_password = $user['username'];
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 1 WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            
            $success = "Password reset for {$user['first_name']} {$user['last_name']} (username: {$user['username']}). New password is their username. They must change it on next login.";
        } else {
            $error = "User not found.";
        }
    }
    
    // Handle account status toggle
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET active = NOT active WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $success = "User account status updated.";
    }
    
    // Handle force password change toggle
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_force_change') {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET force_password_change = NOT force_password_change WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $success = "Password change requirement updated.";
    }
    
    // Get all users with their roster information
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.active, u.force_password_change, u.last_login, u.created_at,
               r.first_name, r.last_name, r.rank, r.department, r.position
        FROM users u
        LEFT JOIN roster r ON u.roster_id = r.id
        ORDER BY r.department, r.rank, r.last_name, r.first_name
    ");
    $stmt->execute();
    $all_users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>USS-Serenity NCC-74714 | User Management</title>
	<link rel="stylesheet" href="../assets/classic.css">
</head>
<body>
	<audio id="audio1" preload="auto"><source src="../assets/beep1.mp3" type="audio/mpeg"></audio>
	<audio id="audio2" preload="auto"><source src="../assets/beep2.mp3" type="audio/mpeg"></audio>
	<audio id="audio3" preload="auto"><source src="../assets/beep3.mp3" type="audio/mpeg"></audio>

	<section class="lcars-layout">
		<div class="left-frame">
			<div>
				<a href="../index.php" onclick="playSound('audio2')"><div class="banner">USS-SERENITY<br><span class="banner-2">NCC-74714</span></div></a>
			</div>
			<div class="panel-1">
				<div class="panel-1a"><a href="../index.php" onclick="playSound('audio2')">HOME</a></div>
				<div class="panel-1b"><a href="roster.php" onclick="playSound('audio2')">ROSTER</a></div>
				<div class="panel-1c"><a href="reports.php" onclick="playSound('audio2')">REPORTS</a></div>
				<div class="panel-1d"><a href="command.php" onclick="playSound('audio2')">COMMAND</a></div>
			</div>
			<div class="gap-1">
				<div class="gap-1a">
					<a href="logout.php" onclick="playSound('audio2')"><div class="gap-1a-1">LOGOUT</div></a>
				</div>
				<div class="gap-1b">
					<div class="gap-1b-1">
						<div class="gap-1b-1a">CURRENT USER</div>
						<div class="gap-1b-1b"><?php echo strtoupper(htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name'])); ?></div>
						<div class="gap-1b-1c"><?php echo strtoupper(htmlspecialchars($_SESSION['department'])); ?></div>
					</div>
				</div>
			</div>
			<div class="gap-2">
				<div class="gap-2a"> </div>
				<div class="gap-2b">
					<div class="gap-2b-1">STARDATE</div>
					<div class="gap-2b-2" id="stardate"><?php echo date('ymd') . '.' . (date('z') + 1); ?></div>
				</div>
			</div>
			<div class="panel-2">
				<div class="panel-2a"><a href="command.php" onclick="playSound('audio2')">COMMAND</a></div>
				<div class="panel-2b"><a href="eng_ops.php" onclick="playSound('audio2')">ENG/OPS</a></div>
				<div class="panel-2c"><a href="med_sci.php" onclick="playSound('audio2')">MED/SCI</a></div>
				<div class="panel-2d"><a href="sec_tac.php" onclick="playSound('audio2')">SEC/TAC</a></div>
			</div>
		</div>
		<div class="right-frame">
			<div class="right-frame-2">
				<main>
					<?php if ($success): ?>
					<div style="background: rgba(0, 255, 0, 0.1); color: #00ff00; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid #00ff00;">
						<?php echo htmlspecialchars($success); ?>
					</div>
					<?php endif; ?>

					<?php if ($error): ?>
					<div style="background: rgba(255, 0, 0, 0.1); color: #ff6666; padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid #ff6666;">
						<?php echo htmlspecialchars($error); ?>
					</div>
					<?php endif; ?>

					<h1>User Account Management</h1>
					<h2>Captain Authorization Level</h2>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>User Accounts Overview</h3>
						<p style="color: var(--orange);">⚠️ Captain-level functions: Reset passwords, activate/deactivate accounts, force password changes.</p>
						
						<div style="overflow-x: auto;">
							<table style="width: 100%; border-collapse: collapse; color: white;">
								<thead>
									<tr style="background: #333;">
										<th style="padding: 10px; border: 1px solid #666; text-align: left;">Name</th>
										<th style="padding: 10px; border: 1px solid #666; text-align: left;">Username</th>
										<th style="padding: 10px; border: 1px solid #666; text-align: left;">Department</th>
										<th style="padding: 10px; border: 1px solid #666; text-align: center;">Status</th>
										<th style="padding: 10px; border: 1px solid #666; text-align: center;">Last Login</th>
										<th style="padding: 10px; border: 1px solid #666; text-align: center;">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($all_users as $user): ?>
									<tr style="<?php echo $user['active'] ? '' : 'background: rgba(255,0,0,0.1);'; ?>">
										<td style="padding: 10px; border: 1px solid #666;">
											<?php if ($user['first_name'] && $user['last_name']): ?>
												<?php echo htmlspecialchars($user['rank'] . ' ' . $user['first_name'] . ' ' . $user['last_name']); ?>
												<?php if ($user['position']): ?>
													<br><small style="color: #999;"><?php echo htmlspecialchars($user['position']); ?></small>
												<?php endif; ?>
											<?php else: ?>
												<em style="color: #999;">No roster entry</em>
											<?php endif; ?>
										</td>
										<td style="padding: 10px; border: 1px solid #666;">
											<?php echo htmlspecialchars($user['username']); ?>
											<?php if ($user['force_password_change']): ?>
												<br><span style="color: var(--orange); font-size: 0.8em;">⚠️ Must change password</span>
											<?php endif; ?>
										</td>
										<td style="padding: 10px; border: 1px solid #666;">
											<?php echo htmlspecialchars($user['department'] ?? 'None'); ?>
										</td>
										<td style="padding: 10px; border: 1px solid #666; text-align: center;">
											<?php if ($user['active']): ?>
												<span style="color: #66ff66;">✅ Active</span>
											<?php else: ?>
												<span style="color: #ff6666;">❌ Inactive</span>
											<?php endif; ?>
										</td>
										<td style="padding: 10px; border: 1px solid #666; text-align: center;">
											<?php if ($user['last_login']): ?>
												<?php echo date('M j, Y', strtotime($user['last_login'])); ?>
											<?php else: ?>
												<em style="color: #999;">Never</em>
											<?php endif; ?>
										</td>
										<td style="padding: 10px; border: 1px solid #666; text-align: center;">
											<div style="display: flex; flex-direction: column; gap: 5px;">
												<!-- Password Reset -->
												<form method="POST" style="margin: 0;" onsubmit="return confirm('Reset password for <?php echo htmlspecialchars($user['username']); ?>? New password will be their username.')">
													<input type="hidden" name="action" value="reset_password">
													<input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
													<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem; width: 100%;">
														Reset Password
													</button>
												</form>
												
												<!-- Toggle Active Status -->
												<form method="POST" style="margin: 0;" onsubmit="return confirm('Toggle account status for <?php echo htmlspecialchars($user['username']); ?>?')">
													<input type="hidden" name="action" value="toggle_status">
													<input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
													<button type="submit" style="background-color: <?php echo $user['active'] ? 'var(--red)' : 'var(--bluey)'; ?>; color: black; border: none; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem; width: 100%;">
														<?php echo $user['active'] ? 'Deactivate' : 'Activate'; ?>
													</button>
												</form>
												
												<!-- Toggle Force Password Change -->
												<form method="POST" style="margin: 0;" onsubmit="return confirm('Toggle password change requirement for <?php echo htmlspecialchars($user['username']); ?>?')">
													<input type="hidden" name="action" value="toggle_force_change">
													<input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
													<button type="submit" style="background-color: var(--orange); color: black; border: none; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem; width: 100%;">
														<?php echo $user['force_password_change'] ? 'Remove Force' : 'Force Change'; ?>
													</button>
												</form>
											</div>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>User Management Guidelines</h3>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
							<div style="background: #333; padding: 1rem; border-radius: 10px;">
								<h4 style="color: var(--gold);">Password Reset</h4>
								<ul style="color: #ccc; font-size: 0.9rem;">
									<li>Resets user password to their username</li>
									<li>Forces password change on next login</li>
									<li>Use for forgotten passwords or security issues</li>
								</ul>
							</div>
							<div style="background: #333; padding: 1rem; border-radius: 10px;">
								<h4 style="color: var(--bluey);">Account Status</h4>
								<ul style="color: #ccc; font-size: 0.9rem;">
									<li>Active: User can log in and access systems</li>
									<li>Inactive: User cannot log in</li>
									<li>Use for temporary suspensions</li>
								</ul>
							</div>
							<div style="background: #333; padding: 1rem; border-radius: 10px;">
								<h4 style="color: var(--orange);">Force Password Change</h4>
								<ul style="color: #ccc; font-size: 0.9rem;">
									<li>Requires password change on next login</li>
									<li>Limits access until password is updated</li>
									<li>Automatic after password reset</li>
								</ul>
							</div>
						</div>
					</div>
					
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0;">
						<h3>Quick Actions</h3>
						<div style="display: flex; gap: 1rem; flex-wrap: wrap;">
							<a href="roster.php" style="background-color: var(--bluey); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">
								Manage Roster
							</a>
							<a href="database_admin.php" style="background-color: var(--african-violet); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">
								Database Admin
							</a>
							<a href="reports.php" style="background-color: var(--gold); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; text-decoration: none; display: inline-block;">
								View Reports
							</a>
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
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
