<?php
require_once '../includes/config.php';

// Update last active timestamp for current character
updateLastActive();

// Helper function to log training actions
function logTrainingAction($file_id, $action, $notes = '') {
    if (!isLoggedIn()) return;
    
    try {
        $pdo = getConnection();
        
        // Get user info for audit
        $character_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
        $user_rank = $_SESSION['rank'] ?? '';
        $user_dept = $_SESSION['department'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $pdo->prepare("
            INSERT INTO training_audit 
            (file_id, action, performed_by, character_name, user_rank, user_department, 
             ip_address, user_agent, additional_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $file_id, $action, $_SESSION['user_id'], $character_name, 
            $user_rank, $user_dept, $ip_address, $user_agent, $notes
        ]);
    } catch (Exception $e) {
        error_log("Training audit error: " . $e->getMessage());
    }
}

// Check if user can manage files for a department
function canManageTrainingFiles($department) {
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    $user_rank = $_SESSION['rank'] ?? '';
    
    // Command can manage all departments
    if ($user_dept === 'Command' || $user_rank === 'Captain' || $user_rank === 'Commander') {
        return true;
    }
    
    // Users can only manage their own department
    return $user_dept === $department;
}

$success = '';
$error = '';

// Handle file upload
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    if (!isLoggedIn()) {
        $error = "Authentication required.";
    } elseif (!canManageTrainingFiles($_POST['department'])) {
        $error = "Access denied. You can only upload files to your department.";
    } elseif (!isset($_FILES['training_file']) || $_FILES['training_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed. Please try again.";
    } else {
        try {
            $pdo = getConnection();
            $pdo->beginTransaction();
            
            $file = $_FILES['training_file'];
            $department = $_POST['department'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            
            // Validate file
            $max_size = 50 * 1024 * 1024; // 50MB limit
            if ($file['size'] > $max_size) {
                throw new Exception("File size exceeds 50MB limit.");
            }
            
            // Allowed file types
            $allowed_types = [
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain', 'application/rtf', 'application/vnd.oasis.opendocument.text',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'video/mp4', 'video/avi', 'video/quicktime', 'video/x-ms-wmv',
                'image/jpeg', 'image/png', 'image/gif',
                'application/zip', 'application/x-rar-compressed'
            ];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("File type not allowed. Please upload PDF, Office documents, images, videos, or archives only.");
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $ext;
            $dept_folder = str_replace('/', '-', $department);
            $upload_path = "../training_files/$dept_folder/$filename";
            
            // Create department folder if needed
            $dept_dir = "../training_files/$dept_folder";
            if (!file_exists($dept_dir)) {
                mkdir($dept_dir, 0755, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception("Failed to save uploaded file.");
            }
            
            // Insert file record
            $stmt = $pdo->prepare("
                INSERT INTO training_files 
                (department, title, description, filename, original_filename, file_size, file_type, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $department, $title, $description, $filename, 
                $file['name'], $file['size'], $mime_type, $_SESSION['user_id']
            ]);
            
            $file_id = $pdo->lastInsertId();
            
            // Log the upload
            logTrainingAction($file_id, 'upload', "File: {$file['name']}, Size: " . number_format($file['size']) . " bytes");
            
            $pdo->commit();
            $success = "Training file uploaded successfully!";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
            
            // Clean up file if it was created
            if (isset($upload_path) && file_exists($upload_path)) {
                unlink($upload_path);
            }
        }
    }
}

// Handle file deletion
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    if (!isLoggedIn()) {
        $error = "Authentication required.";
    } else {
        try {
            $pdo = getConnection();
            $file_id = (int)$_POST['file_id'];
            
            // Get file info
            $stmt = $pdo->prepare("
                SELECT tf.*, u.first_name, u.last_name 
                FROM training_files tf
                LEFT JOIN users u ON tf.uploaded_by = u.id 
                WHERE tf.id = ? AND tf.is_deleted = 0
            ");
            $stmt->execute([$file_id]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new Exception("File not found.");
            }
            
            if (!canManageTrainingFiles($file['department'])) {
                throw new Exception("Access denied. You can only delete files from your department.");
            }
            
            // Move file to deleted folder
            $dept_folder = str_replace('/', '-', $file['department']);
            $current_path = "../training_files/$dept_folder/" . $file['filename'];
            $deleted_path = "../training_files/deleted/" . $file['filename'];
            
            if (!file_exists('../training_files/deleted')) {
                mkdir('../training_files/deleted', 0755, true);
            }
            
            if (file_exists($current_path)) {
                rename($current_path, $deleted_path);
            }
            
            // Mark as deleted and schedule permanent deletion (90 days)
            $deletion_date = date('Y-m-d H:i:s', strtotime('+90 days'));
            $stmt = $pdo->prepare("
                UPDATE training_files 
                SET is_deleted = 1, deleted_by = ?, deleted_date = NOW(), scheduled_deletion = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $deletion_date, $file_id]);
            
            // Log the deletion
            logTrainingAction($file_id, 'delete', "Scheduled for permanent deletion on $deletion_date");
            
            $success = "File moved to deleted folder. It will be permanently deleted in 90 days.";
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle file download
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    try {
        $pdo = getConnection();
        $file_id = (int)$_GET['download'];
        
        // Get file info and check access
        $stmt = $pdo->prepare("
            SELECT * FROM training_files 
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();
        
        if (!$file) {
            throw new Exception("File not found or has been deleted.");
        }
        
        // Check department access
        $user_dept = getUserDepartment();
        if ($user_dept !== 'Command' && $user_dept !== $file['department']) {
            throw new Exception("Access denied. You can only download files from your department or if you're Command.");
        }
        
        $dept_folder = str_replace('/', '-', $file['department']);
        $file_path = "../training_files/$dept_folder/" . $file['filename'];
        
        if (!file_exists($file_path)) {
            throw new Exception("File not found on server.");
        }
        
        // Update download count
        $stmt = $pdo->prepare("UPDATE training_files SET download_count = download_count + 1 WHERE id = ?");
        $stmt->execute([$file_id]);
        
        // Log the download
        if (isLoggedIn()) {
            logTrainingAction($file_id, 'download');
            
            // Log in access log
            $stmt = $pdo->prepare("
                INSERT INTO training_access_log (file_id, accessed_by, access_type, ip_address, user_agent) 
                VALUES (?, ?, 'download', ?, ?)
            ");
            $stmt->execute([
                $file_id, $_SESSION['user_id'], 
                $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
        
        // Send file
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($file_path);
        exit;
        
    } catch (Exception $e) {
        $error = "Download failed: " . $e->getMessage();
    }
}

try {
    $pdo = getConnection();
    
    // Get training files by department
    $departments = ['MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Command'];
    $training_files = [];
    
    foreach ($departments as $dept) {
        $stmt = $pdo->prepare("
            SELECT tf.*, 
                   u.first_name, u.last_name,
                   COUNT(tal.id) as view_count
            FROM training_files tf 
            LEFT JOIN users u ON tf.uploaded_by = u.id 
            LEFT JOIN training_access_log tal ON tf.id = tal.file_id
            WHERE tf.department = ? AND tf.is_deleted = 0
            GROUP BY tf.id
            ORDER BY tf.upload_date DESC
        ");
        $stmt->execute([$dept]);
        $training_files[$dept] = $stmt->fetchAll();
    }
    
    // Get audit log for authorized users
    $audit_logs = [];
    if (isLoggedIn() && (getUserDepartment() === 'Command' || $_SESSION['rank'] === 'Captain')) {
        $stmt = $pdo->prepare("
            SELECT ta.*, tf.title, tf.department, tf.original_filename
            FROM training_audit ta
            JOIN training_files tf ON ta.file_id = tf.id
            ORDER BY ta.action_date DESC
            LIMIT 50
        ");
        $stmt->execute();
        $audit_logs = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-Serenity - Training Management System</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		body {
			background: black !important;
			background-color: black !important;
		}
		
		/* Override LCARS bar colors to black */
		.bar-1, .bar-2, .bar-3, .bar-4, .bar-5,
		.bar-6, .bar-7, .bar-8, .bar-9, .bar-10 {
			background: black !important;
		}
		
		.bar-panel {
			background: black !important;
		}
		
		.department-section {
			margin: 2rem 0;
			border-radius: 15px;
			padding: 2rem;
			border: 2px solid;
			background: rgba(0,0,0,0.7);
		}
		.med-sci { border-color: var(--blue); }
		.eng-ops { border-color: var(--orange); }
		.sec-tac { border-color: var(--gold); }
		.command { border-color: var(--red); }
		
		.file-card {
			background: rgba(0,0,0,0.8);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border: 1px solid rgba(255,255,255,0.2);
			transition: all 0.3s ease;
		}
		.file-card:hover {
			border-color: var(--bluey);
			transform: translateY(-2px);
		}
		
		.file-meta {
			font-size: 0.85rem;
			opacity: 0.8;
			margin: 0.5rem 0;
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 1rem;
		}
		
		.file-actions {
			margin-top: 1rem;
			display: flex;
			gap: 0.5rem;
			flex-wrap: wrap;
		}
		
		.btn-download {
			background: var(--blue);
			color: black;
			border: none;
			padding: 0.5rem 1rem;
			border-radius: 5px;
			text-decoration: none;
			display: inline-block;
			font-weight: bold;
			transition: all 0.2s ease;
		}
		.btn-download:hover {
			background: var(--bluey);
			transform: scale(1.05);
		}
		
		.btn-delete {
			background: var(--red);
			color: black;
			border: none;
			padding: 0.5rem 1rem;
			border-radius: 5px;
			font-weight: bold;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		.btn-delete:hover {
			background: #ff5555;
			transform: scale(1.05);
		}
		
		.upload-form {
			background: rgba(0,0,0,0.9);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--african-violet);
		}
		
		.form-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 1rem;
			margin-bottom: 1rem;
		}
		
		.form-group {
			display: flex;
			flex-direction: column;
		}
		
		.form-group label {
			color: var(--african-violet);
			margin-bottom: 0.5rem;
			font-weight: bold;
		}
		
		.form-input {
			padding: 0.75rem;
			background: rgba(0,0,0,0.8);
			color: white;
			border: 2px solid var(--african-violet);
			border-radius: 5px;
			font-size: 1rem;
		}
		
		.form-input:focus {
			border-color: var(--bluey);
			outline: none;
			box-shadow: 0 0 10px rgba(85, 102, 255, 0.3);
		}
		
		.file-upload-area {
			border: 2px dashed var(--african-violet);
			border-radius: 10px;
			padding: 2rem;
			text-align: center;
			background: rgba(0,0,0,0.5);
			transition: all 0.3s ease;
		}
		
		.file-upload-area:hover {
			border-color: var(--bluey);
			background: rgba(85, 102, 255, 0.1);
		}
		
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
			margin: 2rem 0;
		}
		
		.stat-card {
			background: rgba(0,0,0,0.8);
			padding: 1.5rem;
			border-radius: 10px;
			text-align: center;
			border: 2px solid;
		}
		
		.stat-card.blue { border-color: var(--blue); }
		.stat-card.orange { border-color: var(--orange); }
		.stat-card.gold { border-color: var(--gold); }
		.stat-card.red { border-color: var(--red); }
		
		.stat-number {
			font-size: 2rem;
			font-weight: bold;
			margin-bottom: 0.5rem;
		}
		
		.audit-log {
			background: rgba(0,0,0,0.9);
			border-radius: 10px;
			padding: 1.5rem;
			margin: 2rem 0;
			border: 2px solid var(--african-violet);
			max-height: 500px;
			overflow-y: auto;
		}
		
		.audit-entry {
			padding: 0.75rem;
			border-bottom: 1px solid rgba(255,255,255,0.1);
			display: grid;
			grid-template-columns: auto 1fr auto auto;
			gap: 1rem;
			align-items: center;
		}
		
		.audit-entry:last-child {
			border-bottom: none;
		}
		
		.action-badge {
			padding: 0.25rem 0.75rem;
			border-radius: 15px;
			font-size: 0.8rem;
			font-weight: bold;
			text-transform: uppercase;
		}
		
		.action-upload { background: var(--blue); color: black; }
		.action-download { background: var(--green); color: black; }
		.action-delete { background: var(--red); color: black; }
		
		.tab-container {
			margin: 2rem 0;
		}
		
		.tab-buttons {
			display: flex;
			gap: 0.5rem;
			margin-bottom: 1rem;
		}
		
		.tab-button {
			background: rgba(0,0,0,0.8);
			color: var(--bluey);
			border: 2px solid var(--bluey);
			padding: 0.75rem 1.5rem;
			border-radius: 5px;
			cursor: pointer;
			transition: all 0.3s ease;
		}
		
		.tab-button.active {
			background: var(--bluey);
			color: black;
		}
		
		.tab-content {
			display: none;
		}
		
		.tab-content.active {
			display: block;
		}
		
		@media (max-width: 768px) {
			.form-grid {
				grid-template-columns: 1fr;
			}
			.file-meta {
				grid-template-columns: 1fr;
			}
			.stats-grid {
				grid-template-columns: 1fr 1fr;
			}
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
					<h1>Training Management System</h1>
					<h2>USS-Serenity Educational Resources & File Repository</h2>
					
					<?php if (isset($success)): ?>
					<div style="background: rgba(76, 175, 80, 0.2); border: 2px solid var(--green); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--green); font-weight: bold;">✅ <?php echo htmlspecialchars($success); ?></p>
					</div>
					<?php endif; ?>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(244, 67, 54, 0.2); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red); font-weight: bold;">❌ <?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Training System Statistics -->
					<div class="stats-grid">
						<?php 
						$total_files = array_sum(array_map('count', $training_files));
						?>
						<div class="stat-card blue">
							<div class="stat-number" style="color: var(--blue);"><?php echo count($training_files['MED/SCI']); ?></div>
							<div>Medical/Science Files</div>
						</div>
						<div class="stat-card orange">
							<div class="stat-number" style="color: var(--orange);"><?php echo count($training_files['ENG/OPS']); ?></div>
							<div>Engineering/Ops Files</div>
						</div>
						<div class="stat-card gold">
							<div class="stat-number" style="color: var(--gold);"><?php echo count($training_files['SEC/TAC']); ?></div>
							<div>Security/Tactical Files</div>
						</div>
						<div class="stat-card red">
							<div class="stat-number" style="color: var(--red);"><?php echo count($training_files['Command']); ?></div>
							<div>Command Files</div>
						</div>
					</div>
					
					<?php if (isLoggedIn()): ?>
					<!-- File Upload Form -->
					<div class="upload-form">
						<h3>📤 Upload Training File</h3>
						<p style="color: var(--african-violet);"><em>Authorized Personnel Only - Department-Specific Access Control</em></p>
						
						<form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
							<input type="hidden" name="action" value="upload_file">
							
							<div class="form-grid">
								<div class="form-group">
									<label>Target Department:</label>
									<select name="department" required class="form-input">
										<option value="">Select Department</option>
										<?php if (canManageTrainingFiles('MED/SCI')): ?>
										<option value="MED/SCI">Medical/Science</option>
										<?php endif; ?>
										<?php if (canManageTrainingFiles('ENG/OPS')): ?>
										<option value="ENG/OPS">Engineering/Operations</option>
										<?php endif; ?>
										<?php if (canManageTrainingFiles('SEC/TAC')): ?>
										<option value="SEC/TAC">Security/Tactical</option>
										<?php endif; ?>
										<?php if (canManageTrainingFiles('Command')): ?>
										<option value="Command">Command</option>
										<?php endif; ?>
									</select>
								</div>
								<div class="form-group">
									<label>File Title:</label>
									<input type="text" name="title" required class="form-input" placeholder="Enter descriptive title">
								</div>
							</div>
							
							<div class="form-group">
								<label>Description:</label>
								<textarea name="description" class="form-input" rows="3" placeholder="Brief description of the training material"></textarea>
							</div>
							
							<div class="file-upload-area">
								<input type="file" name="training_file" required id="fileInput" style="display: none;">
								<div onclick="document.getElementById('fileInput').click()" style="cursor: pointer;">
									<div style="font-size: 3rem; margin-bottom: 1rem;">📁</div>
									<div style="font-size: 1.2rem; margin-bottom: 0.5rem;">Click to Select Training File</div>
									<div style="font-size: 0.9rem; opacity: 0.8;">
										Supported: PDF, DOC/DOCX, TXT, XLS/XLSX, PPT/PPTX, MP4, Images, ZIP/RAR<br>
										Maximum file size: 50MB
									</div>
								</div>
								<div id="fileName" style="margin-top: 1rem; font-weight: bold; color: var(--blue);"></div>
							</div>
							
							<button type="submit" style="background: var(--african-violet); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; margin-top: 1rem; font-weight: bold; cursor: pointer;">
								🚀 Upload Training File
							</button>
						</form>
					</div>
					<?php endif; ?>
					
					<!-- Tab Container for Files and Audit -->
					<div class="tab-container">
						<div class="tab-buttons">
							<button class="tab-button active" onclick="showTab('files')">📚 Training Files</button>
							<?php if (isLoggedIn() && (getUserDepartment() === 'Command' || $_SESSION['rank'] === 'Captain')): ?>
							<button class="tab-button" onclick="showTab('audit')">🔍 Audit Log</button>
							<?php endif; ?>
						</div>
						
						<!-- Training Files Tab -->
						<div id="files" class="tab-content active">
							<!-- Medical/Science Department -->
							<div class="department-section med-sci">
								<h3 style="color: var(--blue);">🏥 Medical/Science Department</h3>
								<?php if (empty($training_files['MED/SCI'])): ?>
								<p style="color: var(--blue);"><em>No training files available.</em></p>
								<?php else: ?>
								<?php foreach ($training_files['MED/SCI'] as $file): ?>
								<div class="file-card">
									<h4>📄 <?php echo htmlspecialchars($file['title']); ?></h4>
									<?php if ($file['description']): ?>
									<p style="opacity: 0.9; margin: 0.5rem 0;"><?php echo htmlspecialchars($file['description']); ?></p>
									<?php endif; ?>
									<div class="file-meta">
										<div>
											<strong>Uploaded by:</strong> <?php echo htmlspecialchars(($file['first_name'] ?? 'Unknown') . ' ' . ($file['last_name'] ?? 'User')); ?><br>
											<strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($file['upload_date'])); ?>
										</div>
										<div>
											<strong>File:</strong> <?php echo htmlspecialchars($file['original_filename']); ?><br>
											<strong>Size:</strong> <?php echo number_format($file['file_size'] / 1024, 1); ?> KB | 
											<strong>Downloads:</strong> <?php echo $file['download_count']; ?>
										</div>
									</div>
									<div class="file-actions">
										<?php if (getUserDepartment() === 'Command' || getUserDepartment() === 'MED/SCI'): ?>
										<a href="?download=<?php echo $file['id']; ?>" class="btn-download">⬇️ Download</a>
										<?php endif; ?>
										<?php if (canManageTrainingFiles('MED/SCI')): ?>
										<form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this file? It will be moved to deleted folder and permanently removed in 90 days.');">
											<input type="hidden" name="action" value="delete_file">
											<input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
											<button type="submit" class="btn-delete">🗑️ Delete</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
								<?php endforeach; ?>
								<?php endif; ?>
							</div>
							
							<!-- Engineering/Operations Department -->
							<div class="department-section eng-ops">
								<h3 style="color: var(--orange);">⚙️ Engineering/Operations Department</h3>
								<?php if (empty($training_files['ENG/OPS'])): ?>
								<p style="color: var(--orange);"><em>No training files available.</em></p>
								<?php else: ?>
								<?php foreach ($training_files['ENG/OPS'] as $file): ?>
								<div class="file-card">
									<h4>📄 <?php echo htmlspecialchars($file['title']); ?></h4>
									<?php if ($file['description']): ?>
									<p style="opacity: 0.9; margin: 0.5rem 0;"><?php echo htmlspecialchars($file['description']); ?></p>
									<?php endif; ?>
									<div class="file-meta">
										<div>
											<strong>Uploaded by:</strong> <?php echo htmlspecialchars(($file['first_name'] ?? 'Unknown') . ' ' . ($file['last_name'] ?? 'User')); ?><br>
											<strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($file['upload_date'])); ?>
										</div>
										<div>
											<strong>File:</strong> <?php echo htmlspecialchars($file['original_filename']); ?><br>
											<strong>Size:</strong> <?php echo number_format($file['file_size'] / 1024, 1); ?> KB | 
											<strong>Downloads:</strong> <?php echo $file['download_count']; ?>
										</div>
									</div>
									<div class="file-actions">
										<?php if (getUserDepartment() === 'Command' || getUserDepartment() === 'ENG/OPS'): ?>
										<a href="?download=<?php echo $file['id']; ?>" class="btn-download">⬇️ Download</a>
										<?php endif; ?>
										<?php if (canManageTrainingFiles('ENG/OPS')): ?>
										<form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this file? It will be moved to deleted folder and permanently removed in 90 days.');">
											<input type="hidden" name="action" value="delete_file">
											<input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
											<button type="submit" class="btn-delete">🗑️ Delete</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
								<?php endforeach; ?>
								<?php endif; ?>
							</div>
							
							<!-- Security/Tactical Department -->
							<div class="department-section sec-tac">
								<h3 style="color: var(--gold);">🛡️ Security/Tactical Department</h3>
								<?php if (empty($training_files['SEC/TAC'])): ?>
								<p style="color: var(--gold);"><em>No training files available.</em></p>
								<?php else: ?>
								<?php foreach ($training_files['SEC/TAC'] as $file): ?>
								<div class="file-card">
									<h4>📄 <?php echo htmlspecialchars($file['title']); ?></h4>
									<?php if ($file['description']): ?>
									<p style="opacity: 0.9; margin: 0.5rem 0;"><?php echo htmlspecialchars($file['description']); ?></p>
									<?php endif; ?>
									<div class="file-meta">
										<div>
											<strong>Uploaded by:</strong> <?php echo htmlspecialchars(($file['first_name'] ?? 'Unknown') . ' ' . ($file['last_name'] ?? 'User')); ?><br>
											<strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($file['upload_date'])); ?>
										</div>
										<div>
											<strong>File:</strong> <?php echo htmlspecialchars($file['original_filename']); ?><br>
											<strong>Size:</strong> <?php echo number_format($file['file_size'] / 1024, 1); ?> KB | 
											<strong>Downloads:</strong> <?php echo $file['download_count']; ?>
										</div>
									</div>
									<div class="file-actions">
										<?php if (getUserDepartment() === 'Command' || getUserDepartment() === 'SEC/TAC'): ?>
										<a href="?download=<?php echo $file['id']; ?>" class="btn-download">⬇️ Download</a>
										<?php endif; ?>
										<?php if (canManageTrainingFiles('SEC/TAC')): ?>
										<form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this file? It will be moved to deleted folder and permanently removed in 90 days.');">
											<input type="hidden" name="action" value="delete_file">
											<input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
											<button type="submit" class="btn-delete">🗑️ Delete</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
								<?php endforeach; ?>
								<?php endif; ?>
							</div>
							
							<!-- Command Department -->
							<div class="department-section command">
								<h3 style="color: var(--red);">⭐ Command Department</h3>
								<?php if (empty($training_files['Command'])): ?>
								<p style="color: var(--red);"><em>No training files available.</em></p>
								<?php else: ?>
								<?php foreach ($training_files['Command'] as $file): ?>
								<div class="file-card">
									<h4>📄 <?php echo htmlspecialchars($file['title']); ?></h4>
									<?php if ($file['description']): ?>
									<p style="opacity: 0.9; margin: 0.5rem 0;"><?php echo htmlspecialchars($file['description']); ?></p>
									<?php endif; ?>
									<div class="file-meta">
										<div>
											<strong>Uploaded by:</strong> <?php echo htmlspecialchars(($file['first_name'] ?? 'Unknown') . ' ' . ($file['last_name'] ?? 'User')); ?><br>
											<strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($file['upload_date'])); ?>
										</div>
										<div>
											<strong>File:</strong> <?php echo htmlspecialchars($file['original_filename']); ?><br>
											<strong>Size:</strong> <?php echo number_format($file['file_size'] / 1024, 1); ?> KB | 
											<strong>Downloads:</strong> <?php echo $file['download_count']; ?>
										</div>
									</div>
									<div class="file-actions">
										<a href="?download=<?php echo $file['id']; ?>" class="btn-download">⬇️ Download</a>
										<?php if (canManageTrainingFiles('Command')): ?>
										<form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this file? It will be moved to deleted folder and permanently removed in 90 days.');">
											<input type="hidden" name="action" value="delete_file">
											<input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
											<button type="submit" class="btn-delete">🗑️ Delete</button>
										</form>
										<?php endif; ?>
									</div>
								</div>
								<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
						
						<?php if (isLoggedIn() && (getUserDepartment() === 'Command' || $_SESSION['rank'] === 'Captain')): ?>
						<!-- Audit Log Tab -->
						<div id="audit" class="tab-content">
							<div class="audit-log">
								<h3>🔍 Training System Audit Log</h3>
								<p style="color: var(--african-violet); margin-bottom: 1.5rem;"><em>Command-Level Access - Last 50 Actions</em></p>
								
								<?php if (empty($audit_logs)): ?>
								<p style="opacity: 0.8;"><em>No audit entries found.</em></p>
								<?php else: ?>
								<?php foreach ($audit_logs as $log): ?>
								<div class="audit-entry">
									<div class="action-badge action-<?php echo $log['action']; ?>">
										<?php echo strtoupper($log['action']); ?>
									</div>
									<div>
										<strong><?php echo htmlspecialchars($log['title']); ?></strong><br>
										<span style="opacity: 0.8;">
											by <?php echo htmlspecialchars($log['character_name'] ?: 'Unknown'); ?> 
											(<?php echo htmlspecialchars($log['user_rank'] ?: 'No Rank'); ?>) - 
											<?php echo htmlspecialchars($log['department']); ?>
										</span>
									</div>
									<div style="font-size: 0.8rem; opacity: 0.7;">
										<?php echo date('M j, Y', strtotime($log['action_date'])); ?>
									</div>
									<div style="font-size: 0.8rem; opacity: 0.7;">
										<?php echo date('H:i', strtotime($log['action_date'])); ?>
									</div>
								</div>
								<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
						<?php endif; ?>
					</div>
					
					<div style="background: rgba(85, 102, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin: 2rem 0; border: 2px solid var(--blue);">
						<h4>🎓 Training System Information</h4>
						<ul style="color: var(--bluey); list-style: none; padding: 0;">
							<li style="margin: 0.5rem 0;">→ <strong>Department Access:</strong> Users can only access files from their department or Command</li>
							<li style="margin: 0.5rem 0;">→ <strong>File Management:</strong> Upload and delete permissions based on department assignment</li>
							<li style="margin: 0.5rem 0;">→ <strong>Audit Trail:</strong> All uploads, downloads, and deletions are logged for security</li>
							<li style="margin: 0.5rem 0;">→ <strong>Deleted Files:</strong> Moved to secure folder and permanently deleted after 90 days</li>
							<li style="margin: 0.5rem 0;">→ <strong>File Types:</strong> PDF, Office documents, images, videos, and compressed archives supported</li>
							<li style="margin: 0.5rem 0;">→ <strong>Size Limit:</strong> Maximum file size is 50MB per upload</li>
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
	<script>
		// File upload preview
		document.getElementById('fileInput').addEventListener('change', function(e) {
			const file = e.target.files[0];
			const fileNameDiv = document.getElementById('fileName');
			
			if (file) {
				const size = (file.size / (1024 * 1024)).toFixed(2);
				fileNameDiv.innerHTML = `
					<div style="color: var(--blue);">
						📄 Selected: ${file.name}<br>
						📊 Size: ${size} MB
					</div>
				`;
			} else {
				fileNameDiv.innerHTML = '';
			}
		});
		
		// Tab switching functionality
		function showTab(tabName) {
			// Hide all tab contents
			const tabContents = document.querySelectorAll('.tab-content');
			tabContents.forEach(content => {
				content.classList.remove('active');
			});
			
			// Remove active from all tab buttons
			const tabButtons = document.querySelectorAll('.tab-button');
			tabButtons.forEach(button => {
				button.classList.remove('active');
			});
			
			// Show selected tab
			document.getElementById(tabName).classList.add('active');
			
			// Activate clicked button
			event.target.classList.add('active');
		}
		
		// Form validation
		document.getElementById('uploadForm').addEventListener('submit', function(e) {
			const fileInput = document.getElementById('fileInput');
			const title = document.querySelector('input[name="title"]').value.trim();
			const department = document.querySelector('select[name="department"]').value;
			
			if (!fileInput.files[0]) {
				e.preventDefault();
				alert('Please select a file to upload.');
				return false;
			}
			
			if (!title) {
				e.preventDefault();
				alert('Please enter a title for the training file.');
				return false;
			}
			
			if (!department) {
				e.preventDefault();
				alert('Please select a department.');
				return false;
			}
			
			const file = fileInput.files[0];
			const maxSize = 50 * 1024 * 1024; // 50MB
			
			if (file.size > maxSize) {
				e.preventDefault();
				alert('File size exceeds 50MB limit. Please choose a smaller file.');
				return false;
			}
			
			// Show loading state
			const submitBtn = e.target.querySelector('button[type="submit"]');
			submitBtn.innerHTML = '⏳ Uploading...';
			submitBtn.disabled = true;
		});
		
		// Auto-refresh page after successful upload (to show new file)
		<?php if (isset($success) && strpos($success, 'uploaded') !== false): ?>
		setTimeout(function() {
			if (window.location.hash) {
				window.location.reload();
			}
		}, 2000);
		<?php endif; ?>
		
		// Confirmation for deletions
		function confirmDelete(fileName) {
			return confirm(`Are you sure you want to delete "${fileName}"?\n\nThis action will:\n• Move the file to deleted folder\n• Schedule permanent deletion in 90 days\n• Log the action in audit trail\n\nThis cannot be undone after 90 days.`);
		}
		
		// Download tracking (visual feedback)
		document.querySelectorAll('.btn-download').forEach(button => {
			button.addEventListener('click', function() {
				this.innerHTML = '⬇️ Downloading...';
				setTimeout(() => {
					this.innerHTML = '⬇️ Download';
				}, 2000);
			});
		});
	</script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
