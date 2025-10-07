<?php
require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>USS-VOYAGER - Cargo Bay Foreign Key Fix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" type="text/css" href="assets/classic.css">
</head>
<body>
    <section class="wrap-standard" id="column-3">
        <div class="wrap">
            <div class="left-frame-top">
                <button onclick="window.location.href='index.php'" class="panel-1-button">LCARS</button>
                <div class="panel-2">FIX<span class="hop">-FK</span></div>
            </div>
            <div class="right-frame-top">
                <div class="banner">USS-VOYAGER &#149; CARGO BAY FOREIGN KEY FIX</div>
            </div>
        </div>
        
        <div class="wrap" id="gap">
            <div class="left-frame">
                <button onclick="window.location.href='index.php'" id="topBtn"><span class="hop">main</span> menu</button>
                <div>
                    <div class="panel-3">FK<span class="hop">-FIX</span></div>
                    <div class="panel-4">SYS<span class="hop">-READY</span></div>
                </div>
            </div>
            
            <div class="right-frame">
                <main>
                    <h1>Cargo Bay Foreign Key Constraint Fix</h1>
                    <p>This script will fix the foreign key constraint issues in the cargo_logs table.</p>
                    
                    <?php
                    try {
                        echo "<h3>Step 1: Checking current foreign key constraints...</h3>";
                        
                        // Get current foreign key constraints
                        $stmt = $pdo->query("
                            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'cargo_logs' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ");
                        $constraints = $stmt->fetchAll();
                        
                        foreach ($constraints as $constraint) {
                            echo "<p>Found constraint: {$constraint['CONSTRAINT_NAME']} on {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</p>";
                            
                            // Drop the existing foreign key constraint
                            $drop_sql = "ALTER TABLE cargo_logs DROP FOREIGN KEY {$constraint['CONSTRAINT_NAME']}";
                            $pdo->exec($drop_sql);
                            echo "<p style='color: var(--orange);'>✅ Dropped constraint: {$constraint['CONSTRAINT_NAME']}</p>";
                        }
                        
                        echo "<h3>Step 2: Adding improved foreign key constraint...</h3>";
                        
                        // Add a new foreign key constraint that allows NULL values
                        $pdo->exec("ALTER TABLE cargo_logs MODIFY COLUMN inventory_id INT NULL");
                        echo "<p style='color: var(--blue);'>✅ Modified inventory_id column to allow NULL values</p>";
                        
                        // Add the foreign key constraint with SET NULL on delete
                        $pdo->exec("ALTER TABLE cargo_logs ADD CONSTRAINT fk_cargo_logs_inventory FOREIGN KEY (inventory_id) REFERENCES cargo_inventory(id) ON DELETE SET NULL ON UPDATE CASCADE");
                        echo "<p style='color: var(--blue);'>✅ Added new foreign key constraint with ON DELETE SET NULL</p>";
                        
                        echo "<h3>Step 3: Adding item tracking columns...</h3>";
                        
                        // Add columns to preserve item information even after deletion
                        try {
                            $pdo->exec("ALTER TABLE cargo_logs ADD COLUMN item_name_snapshot VARCHAR(255) DEFAULT NULL");
                            echo "<p style='color: var(--blue);'>✅ Added item_name_snapshot column</p>";
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                                echo "<p style='color: var(--african-violet);'>ℹ️ item_name_snapshot column already exists</p>";
                            } else {
                                throw $e;
                            }
                        }
                        
                        try {
                            $pdo->exec("ALTER TABLE cargo_logs ADD COLUMN area_name_snapshot VARCHAR(255) DEFAULT NULL");
                            echo "<p style='color: var(--blue);'>✅ Added area_name_snapshot column</p>";
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                                echo "<p style='color: var(--african-violet);'>ℹ️ area_name_snapshot column already exists</p>";
                            } else {
                                throw $e;
                            }
                        }
                        
                        echo "<h3>Step 4: Updating existing log entries...</h3>";
                        
                        // Update existing logs with item and area information
                        $update_sql = "
                            UPDATE cargo_logs cl 
                            LEFT JOIN cargo_inventory ci ON cl.inventory_id = ci.id 
                            LEFT JOIN cargo_areas ca ON ci.area_id = ca.id 
                            SET 
                                cl.item_name_snapshot = ci.item_name,
                                cl.area_name_snapshot = ca.area_name
                            WHERE cl.item_name_snapshot IS NULL
                        ";
                        $stmt = $pdo->prepare($update_sql);
                        $stmt->execute();
                        $updated = $stmt->rowCount();
                        
                        if ($updated > 0) {
                            echo "<p style='color: var(--blue);'>✅ Updated {$updated} existing log entries with item/area snapshots</p>";
                        } else {
                            echo "<p style='color: var(--african-violet);'>ℹ️ No existing log entries needed updating</p>";
                        }
                        
                        echo "<h3 style='color: var(--blue);'>🎉 Foreign Key Constraint Fix Complete!</h3>";
                        echo "<div style='background: rgba(0,0,0,0.3); border: 1px solid var(--blue); padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
                        echo "<h4>What was fixed:</h4>";
                        echo "<ul>";
                        echo "<li>✅ Foreign key constraint now uses ON DELETE SET NULL instead of CASCADE</li>";
                        echo "<li>✅ Log entries will preserve item information even after items are deleted</li>";
                        echo "<li>✅ inventory_id column can now be NULL for deleted items</li>";
                        echo "<li>✅ Added snapshot columns to preserve item and area names</li>";
                        echo "</ul>";
                        echo "</div>";
                        
                    } catch (PDOException $e) {
                        echo "<p style='color: var(--red);'>❌ Error fixing foreign key constraints: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    ?>
                    
                    <div style="margin: 2rem 0;">
                        <a href="pages/cargo_bay.php" style="background: var(--blue); color: black; padding: 1rem 2rem; text-decoration: none; border-radius: 15px; font-weight: bold; margin-right: 1rem;">
                            Test Cargo Bay Management
                        </a>
                        <a href="index.php" style="background: var(--orange); color: black; padding: 1rem 2rem; text-decoration: none; border-radius: 15px; font-weight: bold;">
                            Return to Main Computer
                        </a>
                    </div>
                </main>
            </div>
        </div>
    </section>
</body>
</html>
