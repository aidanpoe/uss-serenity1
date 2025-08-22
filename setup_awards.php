<?php
require_once 'includes/config.php';

echo "<h2>Awards Table Setup</h2>";

try {
    // Check if awards table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'awards'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<p>Creating awards table...</p>";
        $create_sql = "CREATE TABLE awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            award_name VARCHAR(255) NOT NULL,
            award_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_award (award_name)
        )";
        $pdo->exec($create_sql);
        echo "<p>✅ Awards table created!</p>";
    } else {
        echo "<p>✅ Awards table exists</p>";
    }
    
    // Check current count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM awards");
    $result = $stmt->fetch();
    echo "<p>Current awards count: " . $result['count'] . "</p>";
    
    // Always insert sample awards (will skip duplicates due to UNIQUE constraint)
    echo "<p>Inserting sample awards...</p>";
    
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
        ['Exploration Medal', 'Awarded for significant contributions to exploration and scientific discovery.'],
        ['Diplomatic Service Medal', 'Awarded for exceptional service in diplomatic missions.'],
        ['Medical Service Cross', 'Awarded to medical personnel for extraordinary service in saving lives.'],
        ['Engineering Excellence Award', 'Awarded for outstanding technical innovation and problem-solving.'],
        ['Security Service Medal', 'Awarded for exceptional service in maintaining ship and crew security.'],
        ['Training Excellence Ribbon', 'Awarded to personnel who demonstrate exceptional skill in training others.'],
        ['Scientific Achievement Medal', 'Awarded for significant contributions to scientific research and discovery.'],
        ['Leadership Excellence Award', 'Awarded to officers who demonstrate exceptional leadership qualities.'],
        ['Bravery Citation', 'Awarded for acts of courage in the face of danger.'],
        ['Humanitarian Service Medal', 'Awarded for exceptional service in humanitarian missions.'],
        ['Fleet Service Ribbon', 'Awarded for exemplary service aboard a Starfleet vessel.']
    ];
    
    $insert_stmt = $pdo->prepare("INSERT IGNORE INTO awards (award_name, award_description) VALUES (?, ?)");
    
    $inserted = 0;
    foreach ($sample_awards as $award) {
        try {
            $insert_stmt->execute([$award[0], $award[1]]);
            if ($insert_stmt->rowCount() > 0) {
                $inserted++;
                echo "<p>✅ Inserted: " . $award[0] . "</p>";
            } else {
                echo "<p>⚠️ Skipped (already exists): " . $award[0] . "</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error inserting " . $award[0] . ": " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p><strong>Successfully inserted $inserted new awards!</strong></p>";
    
    // Final count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM awards");
    $result = $stmt->fetch();
    echo "<p>Final awards count: <strong>" . $result['count'] . "</strong></p>";
    
    echo "<p><a href='pages/rewards.php' style='background: #00f; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>View Rewards Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
