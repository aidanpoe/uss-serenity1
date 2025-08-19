<?php
require_once '../includes/config.php';

// Handle adding new training document
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_training') {
    if (isLoggedIn()) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO training_documents (department, title, content, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['department'],
                $_POST['title'],
                $_POST['content'],
                $_SESSION['user_id']
            ]);
            $success = "Training document added successfully.";
        } catch (Exception $e) {
            $error = "Error adding document: " . $e->getMessage();
        }
    } else {
        $error = "Login required to add training documents.";
    }
}

try {
    $pdo = getConnection();
    
    // Get training documents by department
    $departments = ['MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Command'];
    $training_docs = [];
    
    foreach ($departments as $dept) {
        $stmt = $pdo->prepare("
            SELECT td.*, u.first_name, u.last_name 
            FROM training_documents td 
            LEFT JOIN users u ON td.created_by = u.id 
            WHERE td.department = ? 
            ORDER BY td.created_at DESC
        ");
        $stmt->execute([$dept]);
        $training_docs[$dept] = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Training Documents</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.department-section {
			margin: 2rem 0;
			border-radius: 15px;
			padding: 2rem;
			border: 2px solid;
		}
		.med-sci { border-color: var(--blue); background: rgba(85, 102, 255, 0.1); }
		.eng-ops { border-color: var(--orange); background: rgba(255, 136, 0, 0.1); }
		.sec-tac { border-color: var(--gold); background: rgba(255, 170, 0, 0.1); }
		.command { border-color: var(--red); background: rgba(204, 68, 68, 0.1); }
		
		.doc-card {
			background: rgba(0,0,0,0.5);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border: 1px solid rgba(255,255,255,0.2);
		}
		.doc-meta {
			font-size: 0.9rem;
			opacity: 0.8;
			margin-bottom: 1rem;
		}
		.doc-content {
			max-height: 300px;
			overflow-y: auto;
			padding: 1rem;
			background: rgba(0,0,0,0.3);
			border-radius: 5px;
			border: 1px solid rgba(255,255,255,0.1);
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
				<div class="panel-2">TRAIN<span class="hop">-DOCS</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">TRAINING &#149; DOCUMENTS</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'reports.php')">REPORTS</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--african-violet);">TRAINING</button>
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
					<div class="panel-3">DOCS<span class="hop">-LIB</span></div>
					<div class="panel-4">PROC<span class="hop">-STD</span></div>
					<div class="panel-5">SAFE<span class="hop">-PROT</span></div>
					<div class="panel-6">TRAIN<span class="hop">-MAT</span></div>
					<div class="panel-7">CERT<span class="hop">-REQ</span></div>
					<div class="panel-8">GUIDE<span class="hop">-LNS</span></div>
					<div class="panel-9">STAR<span class="hop">-FLEET</span></div>
				</div>
				<div>
					<div class="panel-10">EDU<span class="hop">-CTR</span></div>
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
					<h1>Training Documents</h1>
					<h2>USS-Serenity Educational Resources</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--blue);"><?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isLoggedIn()): ?>
					<!-- Add Training Document Form -->
					<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--african-violet);">
						<h3>Add Training Document</h3>
						<p style="color: var(--african-violet);"><em>Authorized Personnel Only</em></p>
						
						<form method="POST" action="">
							<input type="hidden" name="action" value="add_training">
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
								<div>
									<label style="color: var(--african-violet);">Department:</label>
									<select name="department" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
										<option value="">Select Department</option>
										<option value="MED/SCI">Medical/Science</option>
										<option value="ENG/OPS">Engineering/Operations</option>
										<option value="SEC/TAC">Security/Tactical</option>
										<option value="Command">Command</option>
									</select>
								</div>
								<div>
									<label style="color: var(--african-violet);">Document Title:</label>
									<input type="text" name="title" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);">
								</div>
							</div>
							<div style="margin-bottom: 1rem;">
								<label style="color: var(--african-violet);">Content:</label>
								<textarea name="content" required rows="8" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--african-violet);" placeholder="Enter training document content..."></textarea>
							</div>
							<button type="submit" style="background-color: var(--african-violet); color: black; border: none; padding: 1rem 2rem; border-radius: 5px;">Add Document</button>
						</form>
					</div>
					<?php endif; ?>
					
					<!-- Medical/Science Training -->
					<div class="department-section med-sci">
						<h3 style="color: var(--blue);">Medical/Science Department</h3>
						<?php if (empty($training_docs['MED/SCI'])): ?>
						<p style="color: var(--blue);"><em>No training documents available.</em></p>
						<?php else: ?>
						<?php foreach ($training_docs['MED/SCI'] as $doc): ?>
						<div class="doc-card">
							<h4><?php echo htmlspecialchars($doc['title']); ?></h4>
							<div class="doc-meta">
								Created by: <?php echo htmlspecialchars(($doc['first_name'] ?? 'Unknown') . ' ' . ($doc['last_name'] ?? 'User')); ?> | 
								Date: <?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?>
							</div>
							<div class="doc-content">
								<?php echo nl2br(htmlspecialchars($doc['content'])); ?>
							</div>
						</div>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<!-- Engineering/Operations Training -->
					<div class="department-section eng-ops">
						<h3 style="color: var(--orange);">Engineering/Operations Department</h3>
						<?php if (empty($training_docs['ENG/OPS'])): ?>
						<p style="color: var(--orange);"><em>No training documents available.</em></p>
						<?php else: ?>
						<?php foreach ($training_docs['ENG/OPS'] as $doc): ?>
						<div class="doc-card">
							<h4><?php echo htmlspecialchars($doc['title']); ?></h4>
							<div class="doc-meta">
								Created by: <?php echo htmlspecialchars(($doc['first_name'] ?? 'Unknown') . ' ' . ($doc['last_name'] ?? 'User')); ?> | 
								Date: <?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?>
							</div>
							<div class="doc-content">
								<?php echo nl2br(htmlspecialchars($doc['content'])); ?>
							</div>
						</div>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<!-- Security/Tactical Training -->
					<div class="department-section sec-tac">
						<h3 style="color: var(--gold);">Security/Tactical Department</h3>
						<?php if (empty($training_docs['SEC/TAC'])): ?>
						<p style="color: var(--gold);"><em>No training documents available.</em></p>
						<?php else: ?>
						<?php foreach ($training_docs['SEC/TAC'] as $doc): ?>
						<div class="doc-card">
							<h4><?php echo htmlspecialchars($doc['title']); ?></h4>
							<div class="doc-meta">
								Created by: <?php echo htmlspecialchars(($doc['first_name'] ?? 'Unknown') . ' ' . ($doc['last_name'] ?? 'User')); ?> | 
								Date: <?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?>
							</div>
							<div class="doc-content">
								<?php echo nl2br(htmlspecialchars($doc['content'])); ?>
							</div>
						</div>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<!-- Command Training -->
					<div class="department-section command">
						<h3 style="color: var(--red);">Command Department</h3>
						<?php if (empty($training_docs['Command'])): ?>
						<p style="color: var(--red);"><em>No training documents available.</em></p>
						<?php else: ?>
						<?php foreach ($training_docs['Command'] as $doc): ?>
						<div class="doc-card">
							<h4><?php echo htmlspecialchars($doc['title']); ?></h4>
							<div class="doc-meta">
								Created by: <?php echo htmlspecialchars(($doc['first_name'] ?? 'Unknown') . ' ' . ($doc['last_name'] ?? 'User')); ?> | 
								Date: <?php echo date('Y-m-d H:i', strtotime($doc['created_at'])); ?>
							</div>
							<div class="doc-content">
								<?php echo nl2br(htmlspecialchars($doc['content'])); ?>
							</div>
						</div>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
					
					<div style="background: rgba(85, 102, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0;">
						<h4>Training Information</h4>
						<ul style="color: var(--bluey); list-style: none; padding: 0;">
							<li style="margin: 0.5rem 0;">→ All crew members are required to complete department-specific training</li>
							<li style="margin: 0.5rem 0;">→ Training documents are updated regularly to reflect current procedures</li>
							<li style="margin: 0.5rem 0;">→ Questions about training should be directed to department heads</li>
							<li style="margin: 0.5rem 0;">→ Certification requirements vary by department and position</li>
						</ul>
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
