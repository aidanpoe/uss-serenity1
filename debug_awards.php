<?php
require_once 'includes/config.php';

echo "<h2>Database Debug Information</h2>";

try {
    // Check if we have a PDO connection
    if (!isset($pdo)) {
        echo "<p>ERROR: PDO connection not established</p>";
        exit;
    }
    
    echo "<p>✅ PDO connection established</p>";
    
    // Check database name
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "<p>Connected to database: <strong>" . $result['db_name'] . "</strong></p>";
    
    // Show all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Available Tables:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
    
    // Check if awards table exists
    if (in_array('awards', $tables)) {
        echo "<p>✅ Awards table exists</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE awards");
        $columns = $stmt->fetchAll();
        echo "<h3>Awards Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM awards");
        $result = $stmt->fetch();
        echo "<p>Records in awards table: <strong>" . $result['count'] . "</strong></p>";
        
        if ($result['count'] > 0) {
            // Show sample records
            $stmt = $pdo->query("SELECT * FROM awards LIMIT 5");
            $awards = $stmt->fetchAll();
            echo "<h3>Sample Awards:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Award Name</th><th>Description</th></tr>";
            foreach ($awards as $award) {
                echo "<tr>";
                echo "<td>" . $award['id'] . "</td>";
                echo "<td>" . htmlspecialchars($award['award_name']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($award['award_description'], 0, 100)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ Awards table is empty! Let's populate it...</p>";
            
            // Insert sample awards
            $sample_awards = [
                ['Starfleet Medal of Honor', 'The highest decoration awarded by Starfleet for exceptional gallantry and intrepidity at the risk of life above and beyond the call of duty.'],
                ['Starfleet Cross', 'Awarded for extraordinary heroism in action against an armed enemy of the Federation.'],
                ['Starfleet Commendation Medal', 'Awarded for heroic or meritorious achievement or service not involving participation in aerial flight.'],
                ['Purple Heart', 'Awarded to those wounded or killed while serving with Starfleet forces.'],
                ['Medal of Valor', 'Awarded for conspicuous gallantry and intrepidity in action involving actual conflict.'],
                ['Good Conduct Medal', 'Awarded for exemplary behavior, efficiency, and fidelity during three years of continuous service.'],
                ['Service Ribbon - 5 Years', 'Awarded for faithful service to Starfleet for five years.'],
                ['Service Ribbon - 10 Years', 'Awarded for faithful service to Starfleet for ten years.'],
                ['Combat Action Ribbon', 'Awarded to personnel who have participated in ground or space combat.'],
                ['Exploration Medal', 'Awarded for significant contributions to exploration and scientific discovery.']
            ];
            
            $insert_stmt = $pdo->prepare("INSERT INTO awards (award_name, award_description) VALUES (?, ?)");
            
            $inserted = 0;
            foreach ($sample_awards as $award) {
                try {
                    $insert_stmt->execute([$award[0], $award[1]]);
                    $inserted++;
                } catch (Exception $e) {
                    echo "<p>Error inserting " . $award[0] . ": " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<p>✅ Inserted <strong>$inserted</strong> awards into the database!</p>";
        }
        
    } else {
        echo "<p>❌ Awards table does NOT exist! Creating it...</p>";
        
        $create_sql = "CREATE TABLE awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            award_name VARCHAR(255) NOT NULL,
            award_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_award (award_name)
        )";
        
        $pdo->exec($create_sql);
        echo "<p>✅ Awards table created successfully!</p>";
        echo "<p>🔄 Please refresh this page to populate it with sample data.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
