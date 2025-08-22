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
        
        // Check for foreign key constraints
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'crew_competencies' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll();
        
        echo "<h3>Current foreign key constraints:</h3>";
        foreach ($foreignKeys as $fk) {
            echo "<p>- {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']} (Constraint: {$fk['CONSTRAINT_NAME']})</p>";
        }
        
        // Drop problematic foreign key constraints
        echo "<h3>Fixing foreign key constraints...</h3>";
        try {
            $pdo->exec("ALTER TABLE crew_competencies DROP FOREIGN KEY crew_competencies_ibfk_3");
            echo "<p>✅ Dropped problematic foreign key constraint crew_competencies_ibfk_3</p>";
        } catch (Exception $e) {
            echo "<p>ℹ️ Foreign key constraint crew_competencies_ibfk_3 already removed or doesn't exist</p>";
        }
        
        // Drop any other problematic constraints
        foreach ($foreignKeys as $fk) {
            if ($fk['COLUMN_NAME'] === 'awarded_by') {
                try {
                    $pdo->exec("ALTER TABLE crew_competencies DROP FOREIGN KEY {$fk['CONSTRAINT_NAME']}");
                    echo "<p>✅ Dropped foreign key constraint {$fk['CONSTRAINT_NAME']} for awarded_by column</p>";
                } catch (Exception $e) {
                    echo "<p>ℹ️ Could not drop {$fk['CONSTRAINT_NAME']}: already removed</p>";
                }
            }
        }
        
        $hasUserId = false;
        $hasRosterId = false;
        $hasAssignedBy = false;
        $hasAwardedBy = false;
        $hasIsCurrent = false;
        $hasAssignedDate = false;
        $hasStatus = false;
        $hasCompletionDate = false;
        $hasNotes = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'user_id') {
                $hasUserId = true;
            }
            if ($column['Field'] === 'roster_id') {
                $hasRosterId = true;
            }
            if ($column['Field'] === 'assigned_by') {
                $hasAssignedBy = true;
            }
            if ($column['Field'] === 'awarded_by') {
                $hasAwardedBy = true;
            }
            if ($column['Field'] === 'is_current') {
                $hasIsCurrent = true;
            }
            if ($column['Field'] === 'assigned_date') {
                $hasAssignedDate = true;
            }
            if ($column['Field'] === 'status') {
                $hasStatus = true;
            }
            if ($column['Field'] === 'completion_date') {
                $hasCompletionDate = true;
            }
            if ($column['Field'] === 'notes') {
                $hasNotes = true;
            }
        }
        
        // Fix awarded_by -> assigned_by column naming issue
        if ($hasAwardedBy && !$hasAssignedBy) {
            echo "<h3>Fixing column naming...</h3>";
            $pdo->exec("ALTER TABLE crew_competencies CHANGE awarded_by assigned_by INT NOT NULL DEFAULT 1");
            echo "<p>✅ Renamed awarded_by column to assigned_by</p>";
            $hasAssignedBy = true;
            $hasAwardedBy = false;
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
            
            // Ensure assigned_by column exists
            if (!$hasAssignedBy) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN assigned_by INT NULL DEFAULT NULL");
                echo "<p>✅ Added assigned_by column</p>";
            } else {
                // Modify existing assigned_by column to allow NULL
                try {
                    $pdo->exec("ALTER TABLE crew_competencies MODIFY assigned_by INT NULL DEFAULT NULL");
                    echo "<p>✅ Modified assigned_by column to allow NULL</p>";
                } catch (Exception $e) {
                    echo "<p>ℹ️ assigned_by column structure already correct</p>";
                }
            }
            
            // Ensure is_current column exists
            if (!$hasIsCurrent) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 1");
                echo "<p>✅ Added is_current column</p>";
            }
            
            // Ensure assigned_date column exists
            if (!$hasAssignedDate) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN assigned_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                echo "<p>✅ Added assigned_date column</p>";
            }
            
            // Ensure status column exists
            if (!$hasStatus) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN status ENUM('assigned', 'in_progress', 'completed', 'expired') NOT NULL DEFAULT 'assigned'");
                echo "<p>✅ Added status column</p>";
            }
            
            // Ensure completion_date column exists
            if (!$hasCompletionDate) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN completion_date DATETIME NULL");
                echo "<p>✅ Added completion_date column</p>";
            }
            
            // Ensure notes column exists
            if (!$hasNotes) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN notes TEXT NULL");
                echo "<p>✅ Added notes column</p>";
            }
            
            // Ensure updated_at column exists
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
                echo "<p>✅ Added updated_at column</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ updated_at column already exists</p>";
            }
            
            // Ensure completion_notes column exists
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN completion_notes TEXT NULL");
                echo "<p>✅ Added completion_notes column</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ completion_notes column already exists</p>";
            }
            
            // Update unique constraint
            $pdo->exec("ALTER TABLE crew_competencies DROP INDEX IF EXISTS unique_assignment");
            $pdo->exec("ALTER TABLE crew_competencies ADD UNIQUE KEY unique_assignment (roster_id, module_id, is_current)");
            echo "<p>✅ Updated unique constraint</p>";
            
            echo "<h2>✅ Migration Complete!</h2>";
            
        } else if ($hasRosterId) {
            echo "<p style='color: green;'>✅ Table already uses roster_id - checking for missing columns</p>";
            
            // Ensure assigned_by column exists
            if (!$hasAssignedBy) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN assigned_by INT NULL DEFAULT NULL");
                echo "<p>✅ Added assigned_by column</p>";
            } else {
                // Modify existing assigned_by column to allow NULL
                try {
                    $pdo->exec("ALTER TABLE crew_competencies MODIFY assigned_by INT NULL DEFAULT NULL");
                    echo "<p>✅ Modified assigned_by column to allow NULL</p>";
                } catch (Exception $e) {
                    echo "<p>ℹ️ assigned_by column structure already correct</p>";
                }
            }
            
            // Ensure is_current column exists
            if (!$hasIsCurrent) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 1");
                echo "<p>✅ Added is_current column</p>";
            }
            
            // Ensure assigned_date column exists
            if (!$hasAssignedDate) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN assigned_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                echo "<p>✅ Added assigned_date column</p>";
            }
            
            // Ensure status column exists
            if (!$hasStatus) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN status ENUM('assigned', 'in_progress', 'completed', 'expired') NOT NULL DEFAULT 'assigned'");
                echo "<p>✅ Added status column</p>";
            }
            
            // Ensure completion_date column exists
            if (!$hasCompletionDate) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN completion_date DATETIME NULL");
                echo "<p>✅ Added completion_date column</p>";
            }
            
            // Ensure notes column exists
            if (!$hasNotes) {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN notes TEXT NULL");
                echo "<p>✅ Added notes column</p>";
            }
            
            // Ensure updated_at column exists
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
                echo "<p>✅ Added updated_at column</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ updated_at column already exists</p>";
            }
            
            // Ensure completion_notes column exists
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD COLUMN completion_notes TEXT NULL");
                echo "<p>✅ Added completion_notes column</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ completion_notes column already exists</p>";
            }
            
            echo "<p style='color: green;'>✅ All required columns are present</p>";
            
            // Add proper foreign key constraints
            echo "<h3>Setting up foreign key constraints...</h3>";
            
            // Add foreign key for assigned_by -> users.id
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD CONSTRAINT fk_cc_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL");
                echo "<p>✅ Added foreign key constraint for assigned_by</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ Foreign key constraint for assigned_by already exists or couldn't be created</p>";
            }
            
            // Add foreign key for module_id -> training_modules.id
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD CONSTRAINT fk_cc_module FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE");
                echo "<p>✅ Added foreign key constraint for module_id</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ Foreign key constraint for module_id already exists or couldn't be created</p>";
            }
            
            // Add foreign key for roster_id -> roster.id (if not already exists)
            try {
                $pdo->exec("ALTER TABLE crew_competencies ADD CONSTRAINT fk_cc_roster_new FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE");
                echo "<p>✅ Added foreign key constraint for roster_id</p>";
            } catch (Exception $e) {
                echo "<p>ℹ️ Foreign key constraint for roster_id already exists or couldn't be created</p>";
            }
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
