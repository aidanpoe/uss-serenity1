<?php
require_once '../includes/config.php';

// Check if user has security department access, command, or is Captain
if (!hasPermission('SEC/TAC') && !hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Get pre-selected crew member if coming from their profile
    $preselected_crew_id = $_GET['crew_id'] ?? '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $crew_id = $_POST['crew_id'];
        $incident_type = $_POST['incident_type'];
        $incident_date = $_POST['incident_date'];
        $location = $_POST['location'];
        $description = $_POST['description'];
        $investigation_status = $_POST['investigation_status'];
        $investigating_officer = $_POST['investigating_officer'];
        $evidence_notes = $_POST['evidence_notes'];
        $witnesses = $_POST['witnesses'];
        $punishment_type = $_POST['punishment_type'];
        $punishment_details = $_POST['punishment_details'];
        $classification = $_POST['classification'];
        $created_by = $_SESSION['user_id'];
        
        // Insert the criminal record
        $stmt = $pdo->prepare("
            INSERT INTO criminal_records (
                crew_id, incident_type, incident_date, location, description,
                investigation_status, investigating_officer, evidence_notes, witnesses,
                punishment_type, punishment_details, classification, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([
            $crew_id, $incident_type, $incident_date, $location, $description,
            $investigation_status, $investigating_officer, $evidence_notes, $witnesses,
            $punishment_type, $punishment_details, $classification, $created_by
        ])) {
            $message = "Criminal record added successfully!";
            // Clear form data
            $_POST = [];
        } else {
            $error = "Failed to add criminal record.";
        }
    }
    
    // Get crew members for dropdown
    $crew_stmt = $pdo->query("
        SELECT id, first_name, last_name, rank, department 
        FROM roster 
        WHERE status = 'Active' 
        ORDER BY rank, last_name, first_name
    ");
    $crew_members = $crew_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current user info for investigating officer default
    $user_stmt = $pdo->prepare("
        SELECT r.first_name, r.last_name, r.rank 
        FROM users u 
        JOIN roster r ON u.crew_id = r.id 
        WHERE u.id = ?
    ");
    $user_stmt->execute([$_SESSION['user_id']]);
    $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $default_officer = $current_user ? $current_user['rank'] . ' ' . $current_user['first_name'] . ' ' . $current_user['last_name'] : '';
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Criminal Record - USS-Serenity</title>
    <link rel="stylesheet" href="../TEMPLATE/assets/classic.css">
    <style>
        .form-container {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--gold);
            border-radius: 10px;
            padding: 2rem;
            margin: 1rem 0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: var(--gold);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            background: black;
            color: white;
            border: 1px solid var(--gold);
            border-radius: 5px;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .submit-btn {
            background: var(--red);
            color: black;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1.1rem;
        }
        .submit-btn:hover {
            background: var(--orange);
        }
        .back-btn {
            background: var(--blue);
            color: black;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .back-btn:hover {
            background: var(--green);
        }
        .message {
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid var(--green);
            color: var(--green);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid var(--red);
            color: var(--red);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .classification-info {
            font-size: 0.9rem;
            color: var(--orange);
            font-style: italic;
            margin-top: 0.25rem;
        }
        .required {
            color: var(--red);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>USS-Serenity NCC-74714</h1>
            <h2>🚨 Security Department - Add Criminal Record</h2>
        </header>
        
        <nav>
            <ul>
                <li><a href="../index.php">🏠 Home</a></li>
                <li><a href="sec_tac.php">🛡️ Security</a></li>
                <li><a href="criminal_records.php">📋 Criminal Records</a></li>
                <li><a href="roster.php">👥 Roster</a></li>
                <?php if (hasPermission('Captain')): ?>
                <li><a href="command.php">⭐ Command</a></li>
                <?php endif; ?>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>
        
        <main>
            <a href="criminal_records.php" class="back-btn">← Back to Criminal Records</a>
            
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <h3>📝 New Criminal Record Entry</h3>
                <p style="color: var(--gold);"><em>⚠️ All fields marked with <span class="required">*</span> are required</em></p>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <!-- Crew Member Selection -->
                        <div class="form-group">
                            <label for="crew_id">Subject Personnel <span class="required">*</span></label>
                            <select name="crew_id" id="crew_id" required>
                                <option value="">Select Crew Member</option>
                                <?php foreach ($crew_members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" <?php echo (($preselected_crew_id && $preselected_crew_id == $member['id']) || (isset($_POST['crew_id']) && $_POST['crew_id'] == $member['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['rank'] . ' ' . $member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['department'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Incident Type -->
                        <div class="form-group">
                            <label for="incident_type">Incident Type <span class="required">*</span></label>
                            <select name="incident_type" id="incident_type" required>
                                <option value="">Select Type</option>
                                <option value="Minor Violation" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Minor Violation') ? 'selected' : ''; ?>>Minor Violation</option>
                                <option value="Major Violation" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Major Violation') ? 'selected' : ''; ?>>Major Violation</option>
                                <option value="Court Martial" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Court Martial') ? 'selected' : ''; ?>>Court Martial</option>
                                <option value="Disciplinary Action" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Disciplinary Action') ? 'selected' : ''; ?>>Disciplinary Action</option>
                                <option value="Criminal Investigation" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Criminal Investigation') ? 'selected' : ''; ?>>Criminal Investigation</option>
                                <option value="Security Breach" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Security Breach') ? 'selected' : ''; ?>>Security Breach</option>
                                <option value="Assault" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Assault') ? 'selected' : ''; ?>>Assault</option>
                                <option value="Insubordination" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Insubordination') ? 'selected' : ''; ?>>Insubordination</option>
                                <option value="Theft" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Theft') ? 'selected' : ''; ?>>Theft</option>
                                <option value="Other" <?php echo (isset($_POST['incident_type']) && $_POST['incident_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <!-- Incident Date -->
                        <div class="form-group">
                            <label for="incident_date">Incident Date <span class="required">*</span></label>
                            <input type="date" name="incident_date" id="incident_date" value="<?php echo isset($_POST['incident_date']) ? htmlspecialchars($_POST['incident_date']) : date('Y-m-d'); ?>" required>
                        </div>
                        
                        <!-- Location -->
                        <div class="form-group">
                            <label for="location">Location <span class="required">*</span></label>
                            <input type="text" name="location" id="location" placeholder="e.g., Deck 5 - Security Office" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
                        </div>
                        
                        <!-- Investigation Status -->
                        <div class="form-group">
                            <label for="investigation_status">Investigation Status <span class="required">*</span></label>
                            <select name="investigation_status" id="investigation_status" required>
                                <option value="">Select Status</option>
                                <option value="Pending" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Active" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Completed" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Closed" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                <option value="Dismissed" <?php echo (isset($_POST['investigation_status']) && $_POST['investigation_status'] == 'Dismissed') ? 'selected' : ''; ?>>Dismissed</option>
                            </select>
                        </div>
                        
                        <!-- Investigating Officer -->
                        <div class="form-group">
                            <label for="investigating_officer">Investigating Officer <span class="required">*</span></label>
                            <input type="text" name="investigating_officer" id="investigating_officer" placeholder="Rank First Last" value="<?php echo isset($_POST['investigating_officer']) ? htmlspecialchars($_POST['investigating_officer']) : htmlspecialchars($default_officer); ?>" required>
                        </div>
                        
                        <!-- Classification -->
                        <div class="form-group">
                            <label for="classification">Security Classification <span class="required">*</span></label>
                            <select name="classification" id="classification" required>
                                <option value="">Select Classification</option>
                                <option value="Public" <?php echo (isset($_POST['classification']) && $_POST['classification'] == 'Public') ? 'selected' : ''; ?>>Public</option>
                                <option value="Restricted" <?php echo (isset($_POST['classification']) && $_POST['classification'] == 'Restricted') ? 'selected' : ''; ?>>Restricted</option>
                                <option value="Classified" <?php echo (isset($_POST['classification']) && $_POST['classification'] == 'Classified') ? 'selected' : ''; ?>>Classified</option>
                            </select>
                            <div class="classification-info">
                                Public: Viewable by all security staff • Restricted: Command+ only • Classified: Captain only
                            </div>
                        </div>
                        
                        <!-- Punishment Type -->
                        <div class="form-group">
                            <label for="punishment_type">Punishment Type</label>
                            <select name="punishment_type" id="punishment_type">
                                <option value="">None/Pending</option>
                                <option value="Verbal Warning" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Verbal Warning') ? 'selected' : ''; ?>>Verbal Warning</option>
                                <option value="Written Reprimand" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Written Reprimand') ? 'selected' : ''; ?>>Written Reprimand</option>
                                <option value="Suspension" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Suspension') ? 'selected' : ''; ?>>Suspension</option>
                                <option value="Demotion" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Demotion') ? 'selected' : ''; ?>>Demotion</option>
                                <option value="Confinement" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Confinement') ? 'selected' : ''; ?>>Confinement</option>
                                <option value="Discharge" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Discharge') ? 'selected' : ''; ?>>Discharge</option>
                                <option value="Court Martial" <?php echo (isset($_POST['punishment_type']) && $_POST['punishment_type'] == 'Court Martial') ? 'selected' : ''; ?>>Court Martial</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Full Width Fields -->
                    <div class="form-group">
                        <label for="description">Incident Description <span class="required">*</span></label>
                        <textarea name="description" id="description" placeholder="Detailed description of the incident..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="evidence_notes">Evidence & Investigation Notes</label>
                        <textarea name="evidence_notes" id="evidence_notes" placeholder="Physical evidence, witness statements, investigation findings..."><?php echo isset($_POST['evidence_notes']) ? htmlspecialchars($_POST['evidence_notes']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="witnesses">Witnesses</label>
                        <textarea name="witnesses" id="witnesses" placeholder="List of witnesses with ranks and names..."><?php echo isset($_POST['witnesses']) ? htmlspecialchars($_POST['witnesses']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="punishment_details">Punishment Details</label>
                        <textarea name="punishment_details" id="punishment_details" placeholder="Specific details of punishment, duration, conditions..."><?php echo isset($_POST['punishment_details']) ? htmlspecialchars($_POST['punishment_details']) : ''; ?></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="submit-btn">🚨 Add Criminal Record</button>
                    </div>
                </form>
            </div>
        </main>
        
        <footer>
            USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
            LCARS Inspired Website Template by <a href="https://www.thelcars.com">www.TheLCARS.com</a>.
        </footer>
    </div>
    
    <script src="../TEMPLATE/assets/lcars.js"></script>
</body>
</html>
