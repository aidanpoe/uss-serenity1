<?php
require_once 'includes/config.php';

echo "<h1>Training System Database Migration</h1>";

try {
    $pdo = getConnection();
    
    echo "<h2>Step 1: Checking current table structure...</h2>";
    
    // Check if crew_competencies table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'crew_competencies'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ crew_competencies table exists</p>";
        
        // Check current structure
        $stmt = $pdo->query("DESCRIBE crew_competencies");
        $columns = $stmt->fetchAll();
        
        $hasUserId = false;
        $hasRosterId = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'user_id') {
                $hasUserId = true;
            }
            if ($column['Field'] === 'roster_id') {
                $hasRosterId = true;
            }
        }
        
        echo "<h3>Current columns:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} ({$column['Type']})</li>";
        }
        echo "</ul>";
        
        if ($hasUserId && !$hasRosterId) {
            echo "<h2>Step 2: Migrating from user_id to roster_id...</h2>";
            
            // First, add the roster_id column
            $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN roster_id INT NULL AFTER id");
            echo "<p>✅ Added roster_id column</p>";
            
            // Migrate data from user_id to roster_id by finding the active character for each user
            $stmt = $pdo->query("
                UPDATE crew_competencies cc 
                JOIN users u ON cc.user_id = u.id 
                JOIN roster r ON u.active_character_id = r.id 
                SET cc.roster_id = r.id 
                WHERE cc.roster_id IS NULL
            ");
            $migrated = $stmt->rowCount();
            echo "<p>✅ Migrated {$migrated} records from user_id to roster_id</p>";
            
            // For any remaining records without roster_id, try to find their first character
            $stmt = $pdo->query("
                UPDATE crew_competencies cc 
                JOIN users u ON cc.user_id = u.id 
                JOIN roster r ON u.id = r.user_id 
                SET cc.roster_id = r.id 
                WHERE cc.roster_id IS NULL 
                AND r.is_active = 1
                GROUP BY cc.id
            ");
            $additional = $stmt->rowCount();
            echo "<p>✅ Migrated {$additional} additional records using first active character</p>";
            
            // Check for any unmigrated records
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM crew_competencies WHERE roster_id IS NULL");
            $unmigrated = $stmt->fetch()['count'];
            
            if ($unmigrated > 0) {
                echo "<p style='color: orange;'>⚠️ {$unmigrated} records could not be migrated (users without characters)</p>";
                
                // Delete unmigrated records
                $pdo->exec("DELETE FROM crew_competencies WHERE roster_id IS NULL");
                echo "<p>🗑️ Deleted {$unmigrated} orphaned records</p>";
            }
            
            // Make roster_id NOT NULL
            $pdo->exec("ALTER TABLE crew_competencies MODIFY roster_id INT NOT NULL");
            echo "<p>✅ Made roster_id NOT NULL</p>";
            
            // Drop the old user_id column
            $pdo->exec("ALTER TABLE crew_competencies DROP COLUMN user_id");
            echo "<p>✅ Removed old user_id column</p>";
            
            // Add foreign key constraint
            $pdo->exec("ALTER TABLE crew_competencies ADD CONSTRAINT fk_cc_roster FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE");
            echo "<p>✅ Added foreign key constraint</p>";
            
            // Update unique constraint
            $pdo->exec("ALTER TABLE crew_competencies DROP INDEX IF EXISTS unique_assignment");
            $pdo->exec("ALTER TABLE crew_competencies ADD UNIQUE KEY unique_assignment (roster_id, module_id, is_current)");
            echo "<p>✅ Updated unique constraint</p>";
            
            echo "<h2>✅ Migration Complete!</h2>";
            
        } else if ($hasRosterId) {
            echo "<p style='color: green;'>✅ Table already uses roster_id - no migration needed</p>";
        } else {
            echo "<p style='color: red;'>❌ Table has neither user_id nor roster_id - needs manual review</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ crew_competencies table does not exist - will be created when setup script runs</p>";
    }
    
    echo "<h2>Step 3: Ensuring training_modules table exists...</h2>";
    
    // Check if training_modules table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'training_modules'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ training_modules table exists</p>";
        
        // Check module count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_modules");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Total training modules: {$count}</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ training_modules table does not exist - run setup script</p>";
    }
    
    echo "<h2>Next Steps:</h2>";
    echo "<ul>";
    echo "<li><a href='setup_training_competencies.php'>🔧 Run Training Setup Script</a></li>";
    echo "<li><a href='pages/training_modules.php'>📚 Access Training Modules</a></li>";
    echo "<li><a href='pages/training_assignment.php'>👥 Access Training Assignment</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
    background: #000;
    color: #00ff00;
}
h1, h2, h3 {
    color: #00ffff;
}
a {
    color: #ffff00;
    text-decoration: none;
    font-weight: bold;
}
a:hover {
    text-decoration: underline;
}
p {
    margin: 0.5rem 0;
}
ul {
    margin: 1rem 0;
}
</style>
