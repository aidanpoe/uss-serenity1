<?php
// Test page to verify awards system setup
echo "<h1>Awards System Test</h1>";
echo "<p>Testing the awards system integration...</p>";

try {
    require_once 'includes/config.php';
    
    // Check if awards tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'awards'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ Awards table exists</p>";
        
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM awards");
        $count = $count_stmt->fetch()['count'];
        echo "<p>✓ Awards table contains $count awards</p>";
    } else {
        echo "<p>❌ Awards table does not exist - please run setup_awards_system.php</p>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'crew_awards'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ Crew awards table exists</p>";
        
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM crew_awards");
        $count = $count_stmt->fetch()['count'];
        echo "<p>✓ Crew awards table contains $count award assignments</p>";
    } else {
        echo "<p>❌ Crew awards table does not exist - please run setup_awards_system.php</p>";
    }
    
    // Check if roster has award_count column
    $columns_stmt = $pdo->query("DESCRIBE roster");
    $columns = $columns_stmt->fetchAll();
    $has_award_count = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'award_count') {
            $has_award_count = true;
            break;
        }
    }
    
    if ($has_award_count) {
        echo "<p>✓ Roster table has award_count column</p>";
    } else {
        echo "<p>❌ Roster table missing award_count column - please run setup_awards_system.php</p>";
    }
    
    echo "<h2>Integration Test</h2>";
    echo "<p><a href='pages/awards_management.php'>Test Awards Management</a></p>";
    echo "<p><a href='pages/roster.php'>Test Roster with Awards</a></p>";
    echo "<p><a href='api/get_awards.php?roster_id=1'>Test Awards API</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
