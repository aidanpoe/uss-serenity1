<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        department ENUM('Captain', 'Command', 'MED/SCI', 'ENG/OPS', 'SEC/TAC') NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create roster table
    $pdo->exec("CREATE TABLE IF NOT EXISTS roster (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rank ENUM('Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petter Officer', 'Senior Chief Petty Officer', 'Master Chief Petty Officer', 'Command Master Chief Petty Officer', 'Warrant officer', 'Ensign', 'Lieutenant Junior Grade', 'Lieutenant', 'Lieutenant Commander', 'Commander', 'Captain') NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        species VARCHAR(50) NOT NULL,
        department ENUM('Command', 'MED/SCI', 'ENG/OPS', 'SEC/TAC') NOT NULL,
        position VARCHAR(100),
        image_path VARCHAR(255),
        phaser_training TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create medical_records table
    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        roster_id INT NOT NULL,
        condition_description TEXT NOT NULL,
        treatment TEXT,
        reported_by VARCHAR(100),
        status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (roster_id) REFERENCES roster(id) ON DELETE CASCADE
    )");
    
    // Create science_reports table
    $pdo->exec("CREATE TABLE IF NOT EXISTS science_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        reported_by VARCHAR(100),
        status ENUM('Open', 'In Progress', 'Completed') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create fault_reports table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fault_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_type ENUM('Deck', 'Hull', 'Jefferies Tube') NOT NULL,
        deck_number INT,
        room VARCHAR(100),
        jefferies_tube_number VARCHAR(50),
        access_point VARCHAR(100),
        fault_description TEXT NOT NULL,
        reported_by_roster_id INT,
        status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
        resolution_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (reported_by_roster_id) REFERENCES roster(id) ON DELETE SET NULL
    )");
    
    // Create security_reports table
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        incident_type ENUM('Crime', 'Security Concern', 'Arrest') NOT NULL,
        description TEXT NOT NULL,
        involved_roster_id INT,
        reported_by VARCHAR(100),
        status ENUM('Open', 'Under Investigation', 'Resolved') DEFAULT 'Open',
        resolution_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (involved_roster_id) REFERENCES roster(id) ON DELETE SET NULL
    )");
    
    // Create command_suggestions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS command_suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        suggestion_title VARCHAR(200) NOT NULL,
        suggestion_description TEXT NOT NULL,
        submitted_by VARCHAR(100),
        status ENUM('Open', 'Under Review', 'Implemented', 'Rejected') DEFAULT 'Open',
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create training_documents table
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department ENUM('MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Command') NOT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Insert default users
    $default_users = [
        ['Poe', 'Class390', 'Captain', 'James', 'Poe'],
        ['torres', 'engineering123', 'ENG/OPS', 'B\'Elanna', 'Torres'],
        ['mccoy', 'medical456', 'MED/SCI', 'Leonard', 'McCoy'],
        ['worf', 'security789', 'SEC/TAC', 'Worf', 'Son of Mogh'],
        ['riker', 'command101', 'Command', 'William', 'Riker'],
        ['data', 'android202', 'ENG/OPS', 'Data', 'Soong'],
        ['troi', 'counselor303', 'MED/SCI', 'Deanna', 'Troi'],
        ['laforge', 'warp404', 'ENG/OPS', 'Geordi', 'La Forge']
    ];
    
    foreach ($default_users as $user) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, department, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user[0], password_hash($user[1], PASSWORD_DEFAULT), $user[2], $user[3], $user[4]]);
    }
    
    // Insert default roster positions
    $default_roster = [
        ['Captain', 'James', 'Poe', 'Human', 'Command', 'Commanding Officer'],
        ['Commander', 'William', 'Riker', 'Human', 'Command', 'First Officer'],
        ['Lieutenant Commander', 'Data', 'Soong', 'Android', 'ENG/OPS', 'Operations Officer'],
        ['Lieutenant Commander', 'Geordi', 'La Forge', 'Human', 'ENG/OPS', 'Chief Engineer'],
        ['Lieutenant Commander', 'Worf', 'Son of Mogh', 'Klingon', 'SEC/TAC', 'Security Chief'],
        ['Lieutenant Commander', 'Leonard', 'McCoy', 'Human', 'MED/SCI', 'Chief Medical Officer'],
        ['Lieutenant', 'Deanna', 'Troi', 'Betazoid', 'MED/SCI', 'Counselor'],
        ['Lieutenant', 'B\'Elanna', 'Torres', 'Half-Klingon', 'ENG/OPS', 'Engineer']
    ];
    
    foreach ($default_roster as $person) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO roster (rank, first_name, last_name, species, department, position) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($person);
    }
    
    echo "Database setup completed successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
