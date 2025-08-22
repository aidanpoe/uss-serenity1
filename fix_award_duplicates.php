<?php
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Fix Award Duplicates</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:2rem;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h1>🏅 Fix Award Duplicates</h1>";

try {
    // Step 1: Check current duplicates
    echo "<h2>Step 1: Checking for duplicates...</h2>";
    $dup_stmt = $pdo->query("
        SELECT name, type, specialization, COUNT(*) as count 
        FROM awards 
        GROUP BY name, type, specialization 
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    $duplicates = $dup_stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "<p class='success'>✅ No duplicates found!</p>";
    } else {
        echo "<p class='error'>❌ Found " . count($duplicates) . " duplicate award groups:</p>";
        echo "<ul>";
        foreach ($duplicates as $dup) {
            $spec = $dup['specialization'] ? " ({$dup['specialization']})" : "";
            echo "<li>{$dup['name']} ({$dup['type']}){$spec} - {$dup['count']} entries</li>";
        }
        echo "</ul>";
        
        // Step 2: Remove duplicates
        echo "<h2>Step 2: Removing duplicates...</h2>";
        $remove_stmt = $pdo->prepare("
            DELETE a1 FROM awards a1
            INNER JOIN awards a2 
            WHERE a1.id > a2.id 
            AND a1.name = a2.name 
            AND a1.type = a2.type
            AND (a1.specialization = a2.specialization OR (a1.specialization IS NULL AND a2.specialization IS NULL))
        ");
        
        $remove_stmt->execute();
        $removed = $remove_stmt->rowCount();
        echo "<p class='success'>✅ Removed {$removed} duplicate entries</p>";
    }
    
    // Step 3: Add unique constraint if not exists
    echo "<h2>Step 3: Adding unique constraint...</h2>";
    try {
        $pdo->exec("ALTER TABLE awards ADD CONSTRAINT unique_award_name_type UNIQUE (name, type, specialization)");
        echo "<p class='success'>✅ Added unique constraint to prevent future duplicates</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='info'>ℹ️ Unique constraint already exists</p>";
        } else {
            echo "<p class='error'>❌ Error adding constraint: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Step 4: Final count
    echo "<h2>Step 4: Final verification...</h2>";
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM awards");
    $total = $total_stmt->fetchColumn();
    echo "<p class='success'>✅ Total awards in database: {$total}</p>";
    
    // Check for any remaining duplicates
    $final_dup_stmt = $pdo->query("
        SELECT COUNT(*) as dup_count FROM (
            SELECT name, type, specialization, COUNT(*) as count 
            FROM awards 
            GROUP BY name, type, specialization 
            HAVING COUNT(*) > 1
        ) as dups
    ");
    $remaining_dups = $final_dup_stmt->fetchColumn();
    
    if ($remaining_dups == 0) {
        echo "<p class='success'>✅ No remaining duplicates!</p>";
        echo "<h2>🎉 Success! Awards system is now clean.</h2>";
        echo "<p><a href='pages/awards_management.php' style='color:blue;'>→ Go to Awards Management</a></p>";
    } else {
        echo "<p class='error'>❌ Still {$remaining_dups} duplicate groups remaining</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><a href='check_award_duplicates.php'>Check Current Awards</a> | ";
echo "<a href='pages/awards_management.php'>Awards Management</a></p>";
echo "</body></html>";
?>
