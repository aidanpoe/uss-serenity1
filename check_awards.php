<?php
require_once 'includes/config.php';

try {
    // Check if awards table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'awards'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "Awards table does not exist!\n";
        exit;
    }
    
    // Count awards
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM awards');
    $result = $stmt->fetch();
    echo "Total awards in database: " . $result['count'] . "\n";
    
    // Show sample awards if any exist
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT award_name FROM awards LIMIT 10');
        $awards = $stmt->fetchAll();
        echo "Sample awards:\n";
        foreach ($awards as $award) {
            echo "- " . $award['award_name'] . "\n";
        }
    } else {
        echo "No awards found in database. Creating sample awards...\n";
        
        // Insert some sample Starfleet awards
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
            ['Training Excellence Ribbon', 'Awarded to personnel who demonstrate exceptional skill in training others.']
        ];
        
        $insert_stmt = $pdo->prepare("INSERT INTO awards (award_name, award_description) VALUES (?, ?)");
        
        foreach ($sample_awards as $award) {
            $insert_stmt->execute([$award[0], $award[1]]);
        }
        
        echo "Inserted " . count($sample_awards) . " sample awards into database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Check if it's a table doesn't exist error
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "Creating awards table...\n";
        
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS awards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                award_name VARCHAR(255) NOT NULL,
                award_description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_award (award_name)
            )");
            
            echo "Awards table created successfully!\n";
            echo "Please run this script again to populate with sample data.\n";
        } catch (Exception $create_error) {
            echo "Error creating table: " . $create_error->getMessage() . "\n";
        }
    }
}
?>
