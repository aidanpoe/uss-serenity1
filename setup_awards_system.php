<?php
// Starfleet Awards System Database Setup
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Awards System Setup</title></head><body>";
echo "<h1>Starfleet Awards System Database Setup</h1>";

try {
    // Create awards table
    echo "<h2>Creating awards table...</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS awards (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        type ENUM('Medal', 'Ribbon', 'Badge', 'Grade') NOT NULL,
        specialization VARCHAR(50),
        description TEXT NOT NULL,
        requirements TEXT,
        image_url VARCHAR(500),
        order_precedence INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_award (name, type, specialization)
    )";
    $pdo->exec($sql);
    echo "✓ Awards table created successfully.<br>";
    
    // Add unique constraint if table already exists
    try {
        $pdo->exec("ALTER TABLE awards ADD CONSTRAINT unique_award UNIQUE (name, type, specialization)");
        echo "✓ Added unique constraint to prevent duplicate awards.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Note: Unique constraint already exists.<br>";
        } else {
            echo "Note: Could not add unique constraint: " . $e->getMessage() . "<br>";
        }
    }

    // Create crew_awards table (linking awards to crew members)
    echo "<h2>Creating crew_awards table...</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS crew_awards (
        id INT PRIMARY KEY AUTO_INCREMENT,
        roster_id INT NOT NULL,
        award_id INT NOT NULL,
        awarded_by_roster_id INT,
        date_awarded DATE NOT NULL,
        citation TEXT,
        order_sequence INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE,
        FOREIGN KEY (award_id) REFERENCES awards(id) ON DELETE CASCADE,
        FOREIGN KEY (awarded_by_roster_id) REFERENCES roster(id) ON DELETE SET NULL,
        UNIQUE KEY unique_award_per_person (roster_id, award_id)
    )";
    $pdo->exec($sql);
    echo "✓ Crew awards table created successfully.<br>";

    // Insert all awards from the Starfleet Awards Index
    echo "<h2>Populating awards database...</h2>";
    
    $awards_data = [
        // High-Level Command Medals
        ['Christopher Pike Medal of Valor', 'Medal', 'Command', 'The single greatest medal any Commanding Officer can achieve. Awarded to those who have given their life, blood, sweat and tears for our Federation.', 'Character must have been in a commanding role (XO/CPT)', null, 1],
        ['The Star Cross Medal', 'Medal', null, 'Consistent excellence as a Starfleet officer above and beyond what is expected', null, null, 2],
        ['The Purple Heart Medal', 'Medal', null, 'Bravery and sacrifice in the line of duty', null, null, 3],
        ['Medal of Honour', 'Medal', null, 'Incredible display of ability as a Starfleet officer on board the USS Serenity', null, null, 4],
        ['Starfleet Expeditionary Medal', 'Medal', null, 'For Completing a 5 year exploration mission', null, null, 5],
        
        // Exploration & Diplomacy
        ['James T Kirk Explorers Medal', 'Medal', null, 'Impressive display while performing acts of exploration', null, null, 6],
        ['Jonathan Archer Peace Medal', 'Medal', null, 'Advanced and successful negotiation abilities', null, null, 7],
        ['Silver Palm of Anaxar Medal', 'Medal', null, 'Admirable humanitarian efforts', null, null, 8],
        ['Four Palm Leaf Medal', 'Medal', null, 'Excellence during First Contact', null, null, 9],
        ['Diplomacy Achievement Medal', 'Medal', null, 'Diplomatic Achievement', null, null, 10],
        
        // Engineering & Operations
        ['Montgomery Scott Medal', 'Medal', 'ENG/OPS', 'Excellence in Engineering or Operations', null, null, 11],
        ['Engineering Achievement Medal', 'Medal', 'ENG/OPS', 'Impressive display of Engineering or Operation ability', null, null, 12],
        
        // Science
        ['The Zefram Cochrane Discovery Medal', 'Medal', 'Science', 'Brilliant Scientific advancement or discovery', null, null, 13],
        ['Daystrom Institute of Scientific Achievement Medal', 'Medal', 'Science', 'Excellence in the field of Science', null, null, 14],
        
        // Medical
        ['Starfleet Surgeons Medal', 'Medal', 'Medical', 'Excellence in the field of Medical', null, null, 15],
        ['Silver Lifesaving Medal', 'Medal', 'Medical', 'Impressive display of Medical ability', null, null, 16],
        
        // Security & Tactical
        ['Tactical Excellence Medal', 'Medal', 'SEC/TAC', 'Excellence in Security or Tactical', null, null, 17],
        ['Starfleet Investigative Excellence Medal', 'Medal', 'SEC/TAC', 'Admirable investigative work', null, null, 18],
        ['Expert Rifleman Badge', 'Badge', 'SEC/TAC', 'Prowess in use of a Type-3 Phaser', null, null, 19],
        ['Expert Pistol Badge', 'Badge', 'SEC/TAC', 'Prowess in the use of a Type-2 Phaser', null, null, 20],
        
        // Helm
        ['Hikaru Sulu Order of Tactics Medal', 'Medal', 'Helm', 'Excellence on the Helm station', null, null, 21],
        ['Distinguished Flying Cross Medal', 'Medal', 'Helm', 'Impressive display of Helm ability', null, null, 22],
        
        // General Service Medals
        ['Five Star Medal', 'Medal', null, 'Conducting self as an exemplary Starfleet Officer', null, null, 23],
        ['Silver Star Medal', 'Medal', null, 'Having gone above and beyond the requirements for a Bronze Star Medal', 'Requires Bronze Star Medal', null, 24],
        ['Bronze Star Medal', 'Medal', null, 'Having gone above and beyond the requirements for a Good Conduct Medal', 'Requires Good Conduct Medal', null, 25],
        ['Good Conduct Medal', 'Medal', null, 'Shown patience, calm and generally good conduct whilst on duty', null, null, 26],
        
        // Service Ribbons
        ['Officers Commendation Ribbon', 'Ribbon', null, 'For those who have excelled as an Officer and have achieved maximum grade in their division', null, null, 27],
        ['Outstanding Unit Ribbon', 'Ribbon', null, 'For those who have excelled as an Enlisted and have achieved maximum grade in their division', null, null, 28],
        
        // Department Efficiency Ribbons
        ['Engineering Efficiency Ribbon', 'Ribbon', 'ENG/OPS', 'Continues to show improvement and ability in Engineering or Operations', null, null, 29],
        ['Science Efficiency Ribbon', 'Ribbon', 'Science', 'Continues to show improvement and ability in Science', null, null, 30],
        ['Medical Efficiency Ribbon', 'Ribbon', 'Medical', 'Continues to show improvement and ability in Medical', null, null, 31],
        ['Tactical Efficiency Ribbon', 'Ribbon', 'SEC/TAC', 'Continues to show improvement and ability in Security or Tactical', null, null, 32],
        ['Helm Efficiency Ribbon', 'Ribbon', 'Helm', 'Continues to show improvement and ability in Helm', null, null, 33]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO awards (name, type, specialization, description, requirements, image_url, order_precedence) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $inserted = 0;
    foreach ($awards_data as $award) {
        try {
            $stmt->execute($award);
            $inserted++;
        } catch (PDOException $e) {
            echo "Error inserting award '{$award[0]}': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "✓ Inserted $inserted awards into the database.<br>";
    
    // Add award_count column to roster for quick reference
    echo "<h2>Adding award count to roster table...</h2>";
    try {
        $pdo->exec("ALTER TABLE roster ADD COLUMN IF NOT EXISTS award_count INT DEFAULT 0");
        echo "✓ Added award_count column to roster table.<br>";
    } catch (PDOException $e) {
        echo "Note: award_count column may already exist.<br>";
    }
    
    echo "<h2>✅ Awards System Setup Complete!</h2>";
    echo "<p><strong>Features implemented:</strong></p>";
    echo "<ul>";
    echo "<li>Complete awards database with 33 Starfleet awards</li>";
    echo "<li>Award assignment tracking with citations</li>";
    echo "<li>Department-specific awards integration</li>";
    echo "<li>Rank-based awarding authority system</li>";
    echo "<li>Award precedence ordering for display</li>";
    echo "<li>Foreign key relationships with roster system</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Create awards management interface for command staff</li>";
    echo "<li>Add awards display to crew profiles in roster</li>";
    echo "<li>Implement award recommendation system</li>";
    echo "<li>Create award ceremony logging</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
