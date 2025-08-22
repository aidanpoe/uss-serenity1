<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Setting Up Training Competency System</h2>";
    
    // Create training_modules table for available training courses
    echo "<h3>Creating training_modules table...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_name VARCHAR(255) NOT NULL,
        module_code VARCHAR(50) NOT NULL UNIQUE,
        department ENUM('MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Command', 'All') NOT NULL,
        description TEXT,
        prerequisites TEXT,
        certification_level ENUM('Basic', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Basic',
        is_active TINYINT(1) DEFAULT 1,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_department (department),
        INDEX idx_active (is_active),
        INDEX idx_code (module_code)
    )");
    echo "<p style='color: green;'>✅ training_modules table created</p>";
    
    // Create crew_competencies table to track who has what training
    echo "<h3>Creating crew_competencies table...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS crew_competencies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        roster_id INT NOT NULL,
        module_id INT NOT NULL,
        awarded_by INT NOT NULL,
        completion_date DATE NOT NULL,
        expiry_date DATE NULL,
        certification_level ENUM('Basic', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Basic',
        notes TEXT,
        is_current TINYINT(1) DEFAULT 1,
        awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE,
        FOREIGN KEY (awarded_by) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_competency (roster_id, module_id),
        INDEX idx_roster (roster_id),
        INDEX idx_module (module_id),
        INDEX idx_current (is_current),
        INDEX idx_expiry (expiry_date)
    )");
    echo "<p style='color: green;'>✅ crew_competencies table created</p>";
    
    // Insert some default training modules
    echo "<h3>Adding default training modules...</h3>";
    
    $defaultModules = [
        // Medical/Science
        ['First Aid Certification', 'MED-FA-001', 'MED/SCI', 'Basic medical emergency response and first aid procedures', null, 'Basic'],
        ['Advanced Medical Training', 'MED-ADV-001', 'MED/SCI', 'Advanced medical procedures and emergency medicine', 'First Aid Certification', 'Advanced'],
        ['Surgical Procedures', 'MED-SURG-001', 'MED/SCI', 'Surgical training and operating procedures', 'Advanced Medical Training', 'Expert'],
        ['Xenobiology Studies', 'SCI-XENO-001', 'MED/SCI', 'Study of alien life forms and xenobiological protocols', null, 'Intermediate'],
        ['Stellar Cartography', 'SCI-CART-001', 'MED/SCI', 'Star mapping and astronomical navigation', null, 'Intermediate'],
        
        // Engineering/Operations
        ['Warp Core Maintenance', 'ENG-WARP-001', 'ENG/OPS', 'Warp core systems maintenance and safety procedures', null, 'Intermediate'],
        ['Transporter Operations', 'OPS-TRANS-001', 'ENG/OPS', 'Transporter system operation and safety protocols', null, 'Basic'],
        ['Shield Systems', 'ENG-SHLD-001', 'ENG/OPS', 'Deflector shield operations and maintenance', null, 'Intermediate'],
        ['Emergency Repairs', 'ENG-EMRG-001', 'ENG/OPS', 'Emergency engineering repair procedures', 'Warp Core Maintenance', 'Advanced'],
        ['Computer Systems', 'OPS-COMP-001', 'ENG/OPS', 'Computer core maintenance and data management', null, 'Basic'],
        
        // Security/Tactical
        ['Phaser Training', 'SEC-PHAS-001', 'SEC/TAC', 'Hand phaser operation and safety protocols', null, 'Basic'],
        ['Tactical Systems', 'TAC-SYS-001', 'SEC/TAC', 'Ship tactical systems and weapons control', 'Phaser Training', 'Intermediate'],
        ['Combat Training', 'SEC-COMB-001', 'SEC/TAC', 'Hand-to-hand combat and self-defense', null, 'Intermediate'],
        ['Security Protocols', 'SEC-PROT-001', 'SEC/TAC', 'Ship security procedures and protocols', null, 'Basic'],
        ['Boarding Party Operations', 'TAC-BOARD-001', 'SEC/TAC', 'Away team tactical operations', 'Combat Training,Phaser Training', 'Advanced'],
        
        // Command
        ['Bridge Operations', 'CMD-BRIDGE-001', 'Command', 'Bridge duty and command procedures', null, 'Intermediate'],
        ['Leadership Training', 'CMD-LEAD-001', 'Command', 'Command leadership and decision making', null, 'Advanced'],
        ['Starfleet Regulations', 'CMD-REG-001', 'Command', 'Starfleet regulations and protocols', null, 'Basic'],
        ['Diplomatic Protocols', 'CMD-DIPL-001', 'Command', 'First contact and diplomatic procedures', 'Starfleet Regulations', 'Advanced'],
        
        // Universal/All Departments
        ['EVA Certification', 'ALL-EVA-001', 'All', 'Extra-vehicular activity and spacewalk certification', null, 'Intermediate'],
        ['Emergency Procedures', 'ALL-EMRG-001', 'All', 'General emergency procedures and evacuation protocols', null, 'Basic'],
        ['Starfleet Academy Graduate', 'ALL-ACAD-001', 'All', 'Starfleet Academy graduation certification', null, 'Basic'],
        ['Cultural Sensitivity', 'ALL-CULT-001', 'All', 'Multi-species cultural awareness training', null, 'Basic'],
    ];
    
    // Get a command user ID for created_by (or use ID 1 as default)
    $stmt = $pdo->query("SELECT id FROM users WHERE department = 'Command' LIMIT 1");
    $commandUser = $stmt->fetch();
    $createdBy = $commandUser ? $commandUser['id'] : 1;
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO training_modules 
        (module_name, module_code, department, description, prerequisites, certification_level, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $addedCount = 0;
    foreach ($defaultModules as $module) {
        $stmt->execute([
            $module[0], // module_name
            $module[1], // module_code
            $module[2], // department
            $module[3], // description
            $module[4], // prerequisites
            $module[5], // certification_level
            $createdBy
        ]);
        if ($stmt->rowCount() > 0) {
            $addedCount++;
        }
    }
    
    echo "<p style='color: green;'>✅ Added $addedCount default training modules</p>";
    
    // Display created modules
    echo "<h3>Training Modules Created:</h3>";
    $stmt = $pdo->query("
        SELECT module_name, module_code, department, certification_level, description 
        FROM training_modules 
        ORDER BY department, certification_level, module_name
    ");
    $modules = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #333;'>";
    echo "<th>Module Name</th><th>Code</th><th>Department</th><th>Level</th><th>Description</th>";
    echo "</tr>";
    
    $currentDept = '';
    foreach ($modules as $module) {
        if ($currentDept !== $module['department']) {
            $currentDept = $module['department'];
            echo "<tr style='background: #444; font-weight: bold;'>";
            echo "<td colspan='5'>{$module['department']} DEPARTMENT</td>";
            echo "</tr>";
        }
        
        echo "<tr>";
        echo "<td style='font-weight: bold;'>" . htmlspecialchars($module['module_name']) . "</td>";
        echo "<td>" . htmlspecialchars($module['module_code']) . "</td>";
        echo "<td>" . htmlspecialchars($module['department']) . "</td>";
        echo "<td>" . htmlspecialchars($module['certification_level']) . "</td>";
        echo "<td>" . htmlspecialchars($module['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>✅ Database tables created successfully</li>";
    echo "<li>✅ Default training modules added</li>";
    echo "<li>🔄 Command interface for managing training modules</li>";
    echo "<li>🔄 Training assignment interface for Command</li>";
    echo "<li>🔄 Crew roster integration to display competencies</li>";
    echo "</ul>";
    
    echo "<h3>Summary:</h3>";
    echo "<p><strong>training_modules:</strong> " . count($modules) . " training courses available</p>";
    echo "<p><strong>crew_competencies:</strong> Ready to track individual crew training</p>";
    echo "<p><strong>Integration:</strong> Ready to integrate with roster and command pages</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
