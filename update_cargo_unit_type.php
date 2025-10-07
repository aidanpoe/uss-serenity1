<?php
require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>USS-VOYAGER - Cargo Bay Unit Type Update</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" type="text/css" href="assets/classic.css">
</head>
<body>
    <section class="wrap-standard" id="column-3">
        <div class="wrap">
            <div class="left-frame-top">
                <button onclick="window.location.href='index.php'" class="panel-1-button">LCARS</button>
                <div class="panel-2">UPDATE<span class="hop">-DB</span></div>
            </div>
            <div class="right-frame-top">
                <div class="banner">USS-VOYAGER &#149; CARGO BAY UNIT TYPE UPDATE</div>
            </div>
        </div>
        
        <div class="wrap" id="gap">
            <div class="left-frame">
                <button onclick="window.location.href='index.php'" id="topBtn"><span class="hop">main</span> menu</button>
                <div>
                    <div class="panel-3">DB<span class="hop">-UPDATE</span></div>
                    <div class="panel-4">SYS<span class="hop">-READY</span></div>
                </div>
            </div>
            
            <div class="right-frame">
                <main>
                    <h1>Cargo Bay Unit Type Database Update</h1>
                    
                    <?php
                    try {
                        // Check if unit_type column exists
                        $stmt = $pdo->query("SHOW COLUMNS FROM cargo_inventory LIKE 'unit_type'");
                        $column_exists = $stmt->rowCount() > 0;
                        
                        if ($column_exists) {
                            echo "<p style='color: var(--blue);'>ℹ️ Unit type column already exists in cargo_inventory table</p>";
                        } else {
                            // Add unit_type column
                            $pdo->exec("ALTER TABLE cargo_inventory ADD COLUMN unit_type VARCHAR(50) DEFAULT 'pieces'");
                            echo "<p style='color: var(--blue);'>✅ Successfully added 'unit_type' column to cargo_inventory table</p>";
                            
                            // Update existing items to have default unit type
                            $update_stmt = $pdo->prepare("UPDATE cargo_inventory SET unit_type = 'pieces' WHERE unit_type IS NULL OR unit_type = ''");
                            $update_stmt->execute();
                            $updated_rows = $update_stmt->rowCount();
                            
                            if ($updated_rows > 0) {
                                echo "<p style='color: var(--blue);'>✅ Updated {$updated_rows} existing items with default unit type 'pieces'</p>";
                            }
                        }
                        
                        // Verify the column structure
                        $stmt = $pdo->query("DESCRIBE cargo_inventory");
                        $columns = $stmt->fetchAll();
                        
                        echo "<h3>Current cargo_inventory table structure:</h3>";
                        echo "<div style='background: rgba(0,0,0,0.3); border: 1px solid var(--blue); padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
                        echo "<table style='width: 100%; border-collapse: collapse;'>";
                        echo "<tr style='background: var(--blue); color: black;'>";
                        echo "<th style='padding: 0.5rem; border: 1px solid black;'>Field</th>";
                        echo "<th style='padding: 0.5rem; border: 1px solid black;'>Type</th>";
                        echo "<th style='padding: 0.5rem; border: 1px solid black;'>Default</th>";
                        echo "</tr>";
                        
                        foreach ($columns as $column) {
                            $highlight = ($column['Field'] === 'unit_type') ? 'background: rgba(255,170,0,0.2);' : '';
                            echo "<tr style='{$highlight}'>";
                            echo "<td style='padding: 0.5rem; border: 1px solid var(--blue); color: var(--blue);'>{$column['Field']}</td>";
                            echo "<td style='padding: 0.5rem; border: 1px solid var(--blue); color: var(--blue);'>{$column['Type']}</td>";
                            echo "<td style='padding: 0.5rem; border: 1px solid var(--blue); color: var(--blue);'>{$column['Default']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "</div>";
                        
                        echo "<h3 style='color: var(--blue);'>🎉 Database Update Complete!</h3>";
                        echo "<p>Your cargo bay system should now work properly with unit types.</p>";
                        
                    } catch (PDOException $e) {
                        echo "<p style='color: var(--red);'>❌ Error updating database: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    ?>
                    
                    <div style="margin: 2rem 0;">
                        <a href="pages/cargo_bay.php" style="background: var(--blue); color: black; padding: 1rem 2rem; text-decoration: none; border-radius: 15px; font-weight: bold; margin-right: 1rem;">
                            Access Cargo Bay Management
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
