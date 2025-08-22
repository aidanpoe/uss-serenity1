<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_duplicates'])) {
    try {
        echo "<h3>Removing duplicate awards...</h3>\n";
        
        // Find duplicates and keep only the first one (lowest ID)
        $remove_stmt = $pdo->prepare("
            DELETE a1 FROM awards a1
            INNER JOIN awards a2 
            WHERE a1.id > a2.id 
            AND a1.name = a2.name 
            AND a1.type = a2.type
        ");
        
        $affected = $remove_stmt->execute();
        $rows_affected = $remove_stmt->rowCount();
        
        echo "<p style='color: green;'>✅ Removed " . $rows_affected . " duplicate awards!</p>\n";
        echo "<p><a href='check_award_duplicates.php'>Check results</a></p>\n";
        echo "<p><a href='pages/awards_management.php'>Back to Awards Management</a></p>\n";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error removing duplicates: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    // Show form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Remove Duplicate Awards</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
            .button { background: #dc3545; color: white; padding: 1rem 2rem; border: none; border-radius: 5px; cursor: pointer; }
            .button:hover { background: #c82333; }
        </style>
    </head>
    <body>
        <h1>Remove Duplicate Awards</h1>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> This will permanently remove duplicate awards from the database. 
            Only the first occurrence of each duplicate will be kept (by ID). 
            Make sure you have a database backup before proceeding.
        </div>
        
        <form method="POST">
            <button type="submit" name="remove_duplicates" class="button" onclick="return confirm('Are you sure you want to remove duplicate awards? This cannot be undone!')">
                🗑️ Remove Duplicate Awards
            </button>
        </form>
        
        <p><a href="check_award_duplicates.php">Check for duplicates first</a></p>
        <p><a href="pages/awards_management.php">Back to Awards Management</a></p>
    </body>
    </html>
    <?php
}
?>
