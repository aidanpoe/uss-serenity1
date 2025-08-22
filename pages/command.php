<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Start session first
    session_start();
    
    // Include required files
    require_once '../includes/config.php';
    require_once '../includes/department_training.php';
    
    // Update last active timestamp for current character
    updateLastActive();
    
    // Initialize variables
    $success = '';
    $error = '';
    
    // Handle department training if user has permission
    if (hasPermission('Command')) {
        handleDepartmentTraining('Command');
    }
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Handle suggestion submission
        if (isset($_POST['action']) && $_POST['action'] === 'submit_suggestion') {
            if (!isLoggedIn()) {
                $error = "You must be logged in to submit suggestions.";
            } else {
                try {
                    $submitted_by = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
                    
                    $pdo = getConnection();
                    
                    // Create command_suggestions table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS command_suggestions (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        suggestion_title VARCHAR(255) NOT NULL,
                        suggestion_description TEXT NOT NULL,
                        submitted_by VARCHAR(255) NOT NULL,
                        submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        status ENUM('pending', 'reviewed', 'implemented') DEFAULT 'pending'
                    )");
                    
                    $stmt = $pdo->prepare("INSERT INTO command_suggestions (suggestion_title, suggestion_description, submitted_by) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['suggestion_title'] ?? '',
                        $_POST['suggestion_description'] ?? '',
                        $submitted_by
                    ]);
                    $success = "Suggestion submitted successfully.";
                } catch (Exception $e) {
                    $error = "Error submitting suggestion: " . $e->getMessage();
                }
            }
        }
        
        // Handle award recommendation submission
        if (isset($_POST['action']) && $_POST['action'] === 'submit_award_recommendation') {
            if (!isLoggedIn()) {
                $error = "You must be logged in to submit award recommendations.";
            } else {
                try {
                    $submitted_by = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
                    
                    $pdo = getConnection();
                    
                    // Create award_recommendations table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS award_recommendations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        recommended_person VARCHAR(255) NOT NULL,
                        award_id INT NOT NULL,
                        reason TEXT NOT NULL,
                        submitted_by VARCHAR(255) NOT NULL,
                        submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        status ENUM('pending', 'approved', 'denied') DEFAULT 'pending'
                    )");
                    
                    $stmt = $pdo->prepare("INSERT INTO award_recommendations (recommended_person, award_id, reason, submitted_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['recommended_person'] ?? '',
                        $_POST['award_id'] ?? 0,
                        $_POST['reason'] ?? '',
                        $submitted_by
                    ]);
                    $success = "Award recommendation submitted successfully.";
                } catch (Exception $e) {
                    $error = "Error submitting award recommendation: " . $e->getMessage();
                }
            }
        }
        
        // Handle award approval/denial (for command staff)
        if (isset($_POST['action']) && $_POST['action'] === 'process_recommendation') {
            if (!hasPermission('admin') && !hasPermission('command')) {
                $error = "You don't have permission to process recommendations.";
            } else {
                try {
                    $pdo = getConnection();
                    $recommendation_id = $_POST['recommendation_id'] ?? 0;
                    $decision = $_POST['decision'] ?? '';
                    
                    if ($decision === 'approve') {
                        // Get recommendation details
                        $stmt = $pdo->prepare("SELECT * FROM award_recommendations WHERE id = ?");
                        $stmt->execute([$recommendation_id]);
                        $recommendation = $stmt->fetch();
                        
                        if ($recommendation) {
                            // Add to crew_awards
                            $assigned_by = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
                            
                            $stmt = $pdo->prepare("INSERT INTO crew_awards (recipient_name, award_id, date_awarded, reason, assigned_by) VALUES (?, ?, NOW(), ?, ?)");
                            $stmt->execute([
                                $recommendation['recommended_person'],
                                $recommendation['award_id'],
                                $recommendation['reason'],
                                $assigned_by
                            ]);
                            
                            // Update recommendation status
                            $stmt = $pdo->prepare("UPDATE award_recommendations SET status = 'approved' WHERE id = ?");
                            $stmt->execute([$recommendation_id]);
                            
                            $success = "Award recommendation approved and award granted.";
                        }
                    } elseif ($decision === 'deny') {
                        $stmt = $pdo->prepare("UPDATE award_recommendations SET status = 'denied' WHERE id = ?");
                        $stmt->execute([$recommendation_id]);
                        $success = "Award recommendation denied.";
                    }
                } catch (Exception $e) {
                    $error = "Error processing recommendation: " . $e->getMessage();
                }
            }
        }
    }
    
} catch (Exception $e) {
    die("Fatal error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Center - USS Serenity</title>
    <link rel="stylesheet" href="../assets/lcars.css">
    <link rel="stylesheet" href="../assets/lower-decks.css">
    <style>
        .command-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section {
            background: linear-gradient(145deg, #1a1a2e, #16213e);
            border: 2px solid #ff9900;
            border-radius: 20px;
            margin: 20px 0;
            padding: 20px;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        .form-group label {
            display: block;
            color: #ff9900;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            background: #000011;
            color: #ff9900;
            border: 1px solid #ff9900;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            box-sizing: border-box;
        }
        
        .btn {
            background: linear-gradient(145deg, #ff9900, #cc7700);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            margin: 10px 5px;
        }
        
        .btn:hover {
            background: linear-gradient(145deg, #cc7700, #ff9900);
        }
        
        .btn-approve {
            background: linear-gradient(145deg, #00aa00, #007700);
            color: white;
        }
        
        .btn-deny {
            background: linear-gradient(145deg, #aa0000, #770000);
            color: white;
        }
        
        .success {
            background: #004400;
            color: #00ff00;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .error {
            background: #440000;
            color: #ff0000;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .recommendation-card {
            background: #0a0a1a;
            border: 1px solid #ff9900;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #ff9900;
            margin-bottom: 20px;
        }
        
        .tab {
            background: #1a1a2e;
            color: #ff9900;
            border: none;
            padding: 15px 25px;
            cursor: pointer;
            border-top: 2px solid #ff9900;
            border-left: 2px solid #ff9900;
            border-right: 2px solid #ff9900;
            font-weight: bold;
        }
        
        .tab.active {
            background: #ff9900;
            color: #000;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</head>
<body>
    <div class="lcars-header">
        <h1>🖖 USS SERENITY - COMMAND CENTER</h1>
    </div>
    
    <div class="command-container">
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('recommend-tab')">🏅 Recommend Award</button>
            <button class="tab" onclick="showTab('suggest-tab')">💡 Submit Suggestion</button>
            <?php if (hasPermission('admin') || hasPermission('command')): ?>
            <button class="tab" onclick="showTab('review-tab')">⚖️ Review Recommendations</button>
            <button class="tab" onclick="showTab('manage-tab')">⚙️ Management</button>
            <?php endif; ?>
        </div>
        
        <div id="recommend-tab" class="tab-content active">
            <div class="section">
                <h2>🏅 Recommend Award</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="submit_award_recommendation">
                    
                    <div class="form-group">
                        <label for="recommended_person">Recommended Person:</label>
                        <input type="text" id="recommended_person" name="recommended_person" 
                               value="<?php echo htmlspecialchars(trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="award_id">Award:</label>
                        <select id="award_id" name="award_id" required>
                            <option value="">Select an award...</option>
                            <?php
                            try {
                                $pdo = getConnection();
                                $stmt = $pdo->query("SELECT id, name FROM awards ORDER BY name");
                                while ($award = $stmt->fetch()) {
                                    echo '<option value="' . htmlspecialchars($award['id']) . '">' . htmlspecialchars($award['name']) . '</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading awards</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Recommendation:</label>
                        <textarea id="reason" name="reason" rows="4" required placeholder="Describe why this person deserves this award..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Submit Recommendation</button>
                </form>
            </div>
        </div>
        
        <div id="suggest-tab" class="tab-content">
            <div class="section">
                <h2>💡 Submit Suggestion</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="submit_suggestion">
                    
                    <div class="form-group">
                        <label for="suggestion_title">Suggestion Title:</label>
                        <input type="text" id="suggestion_title" name="suggestion_title" required placeholder="Brief title for your suggestion">
                    </div>
                    
                    <div class="form-group">
                        <label for="suggestion_description">Description:</label>
                        <textarea id="suggestion_description" name="suggestion_description" rows="4" required placeholder="Detailed description of your suggestion..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Submit Suggestion</button>
                </form>
            </div>
        </div>
        
        <?php if (hasPermission('admin') || hasPermission('command')): ?>
        <div id="review-tab" class="tab-content">
            <div class="section">
                <h2>⚖️ Review Award Recommendations</h2>
                <?php
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->query("
                        SELECT ar.*, a.name as award_name 
                        FROM award_recommendations ar 
                        JOIN awards a ON ar.award_id = a.id 
                        WHERE ar.status = 'pending' 
                        ORDER BY ar.submission_date DESC
                    ");
                    
                    if ($stmt->rowCount() > 0) {
                        while ($rec = $stmt->fetch()) {
                            echo '<div class="recommendation-card">';
                            echo '<h3>' . htmlspecialchars($rec['award_name']) . '</h3>';
                            echo '<p><strong>Recommended Person:</strong> ' . htmlspecialchars($rec['recommended_person']) . '</p>';
                            echo '<p><strong>Submitted By:</strong> ' . htmlspecialchars($rec['submitted_by']) . '</p>';
                            echo '<p><strong>Date:</strong> ' . htmlspecialchars($rec['submission_date']) . '</p>';
                            echo '<p><strong>Reason:</strong> ' . htmlspecialchars($rec['reason']) . '</p>';
                            
                            echo '<form method="POST" style="display: inline-block; margin-right: 10px;">';
                            echo '<input type="hidden" name="action" value="process_recommendation">';
                            echo '<input type="hidden" name="recommendation_id" value="' . $rec['id'] . '">';
                            echo '<input type="hidden" name="decision" value="approve">';
                            echo '<button type="submit" class="btn btn-approve">✓ Approve</button>';
                            echo '</form>';
                            
                            echo '<form method="POST" style="display: inline-block;">';
                            echo '<input type="hidden" name="action" value="process_recommendation">';
                            echo '<input type="hidden" name="recommendation_id" value="' . $rec['id'] . '">';
                            echo '<input type="hidden" name="decision" value="deny">';
                            echo '<button type="submit" class="btn btn-deny">✗ Deny</button>';
                            echo '</form>';
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="color: #ff9900;">No pending recommendations.</p>';
                    }
                } catch (Exception $e) {
                    echo '<p style="color: #ff0000;">Error loading recommendations: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <div id="manage-tab" class="tab-content">
            <div class="section">
                <h2>⚙️ Management</h2>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="location.href='awards_management.php'" class="btn">Manage Awards</button>
                    <button onclick="location.href='../index.php'" class="btn">Return to Home</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
