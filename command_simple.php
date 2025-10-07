<?php
require_once '../includes/config.php';

// Update last active timestamp for current character  
updateLastActive();

// Initialize variables
$error = '';
$success = '';
$suggestions = [];
$award_recommendations = [];

// Handle suggestion submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_suggestion') {
    if (!isLoggedIn()) {
        $error = "You must be logged in to submit suggestions.";
    } else {
        try {
            $submitted_by = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO command_suggestions (suggestion_title, suggestion_description, submitted_by) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['suggestion_title'], $_POST['suggestion_description'], $submitted_by]);
            $success = "Suggestion submitted successfully.";
        } catch (Exception $e) {
            $error = "Error submitting suggestion: " . $e->getMessage();
        }
    }
}

// Handle award recommendation submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_award_recommendation') {
    if (!isLoggedIn()) {
        $error = "You must be logged in to submit award recommendations.";
    } else {
        try {
            $submitted_by = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
            $pdo = getConnection();
            
            // Create table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS award_recommendations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                recommended_person VARCHAR(255) NOT NULL,
                recommended_award VARCHAR(255) NOT NULL,
                justification TEXT NOT NULL,
                submitted_by VARCHAR(255) NOT NULL,
                status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                reviewed_by VARCHAR(255),
                review_notes TEXT,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_at TIMESTAMP NULL
            )");
            
            $stmt = $pdo->prepare("INSERT INTO award_recommendations (recommended_person, recommended_award, justification, submitted_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['recommended_person'], $_POST['recommended_award'], $_POST['justification'], $submitted_by]);
            $success = "Award recommendation submitted successfully.";
        } catch (Exception $e) {
            $error = "Error submitting award recommendation: " . $e->getMessage();
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get command structure
    $stmt = $pdo->prepare("SELECT * FROM roster WHERE position IN ('Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer') ORDER BY FIELD(position, 'Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer')");
    $stmt->execute();
    $command_officers = $stmt->fetchAll();
    
    // Get data for command users
    if (hasPermission('Command')) {
        // Get suggestions
        try {
            $stmt = $pdo->prepare("SELECT * FROM command_suggestions ORDER BY status ASC, created_at DESC");
            $stmt->execute();
            $suggestions = $stmt->fetchAll();
        } catch (Exception $e) {
            $suggestions = [];
        }
        
        // Get award recommendations
        try {
            $stmt = $pdo->prepare("SELECT * FROM award_recommendations ORDER BY status ASC, submitted_at DESC");
            $stmt->execute();
            $award_recommendations = $stmt->fetchAll();
        } catch (Exception $e) {
            $award_recommendations = [];
        }
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>USS-VOYAGER 74656 - Command Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../assets/classic.css">
</head>
<body>
    <h1>Command Center - Test Version</h1>
    
    <?php if ($success): ?>
        <div style="background: green; color: white; padding: 10px; margin: 10px 0;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: red; color: white; padding: 10px; margin: 10px 0;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <h2>🏅 Award Recommendation Form</h2>
    <?php if (isLoggedIn()): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="submit_award_recommendation">
            <p>
                <label>Crew Member to Recommend:</label><br>
                <input type="text" name="recommended_person" required style="width: 300px;">
            </p>
            <p>
                <label>Suggested Award:</label><br>
                <input type="text" name="recommended_award" required style="width: 300px;">
            </p>
            <p>
                <label>Justification:</label><br>
                <textarea name="justification" required rows="4" style="width: 300px;"></textarea>
            </p>
            <p>
                <label>Recommended By:</label><br>
                <input type="text" value="<?php echo htmlspecialchars(trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))); ?>" readonly style="width: 300px; background: #f0f0f0;">
            </p>
            <p>
                <button type="submit">Submit Recommendation</button>
            </p>
        </form>
    <?php else: ?>
        <p>You must be logged in to submit award recommendations.</p>
        <p><a href="../index.php">Return to Login</a></p>
    <?php endif; ?>
    
    <?php if (hasPermission('Command') && !empty($award_recommendations)): ?>
        <h2>Award Recommendations Management</h2>
        <?php foreach ($award_recommendations as $rec): ?>
            <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
                <h3><?php echo htmlspecialchars($rec['recommended_person']); ?></h3>
                <p><strong>Award:</strong> <?php echo htmlspecialchars($rec['recommended_award']); ?></p>
                <p><strong>Justification:</strong> <?php echo htmlspecialchars($rec['justification']); ?></p>
                <p><strong>Submitted by:</strong> <?php echo htmlspecialchars($rec['submitted_by']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($rec['status']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <p><a href="../index.php">Return to Home</a></p>
</body>
</html>
