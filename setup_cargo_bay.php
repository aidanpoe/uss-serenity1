<?php
// Setup script for Cargo Bay Management System
require_once 'includes/config.php';

echo "<h1>USS Voyager - Cargo Bay Database Setup</h1>";

try {
    // Create cargo_areas table
    $sql = "CREATE TABLE IF NOT EXISTS cargo_areas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        area_name VARCHAR(100) NOT NULL,
        area_code VARCHAR(20) NOT NULL UNIQUE,
        description TEXT,
        department_access VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($pdo->exec($sql) !== FALSE) {
        echo "<p>✅ Table 'cargo_areas' created successfully</p>";
    } else {
        echo "<p>❌ Error creating cargo_areas table</p>";
    }

    // Create cargo_inventory table
    $sql = "CREATE TABLE IF NOT EXISTS cargo_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        area_id INT,
        item_name VARCHAR(255) NOT NULL,
        item_description TEXT,
        quantity INT DEFAULT 0,
        min_quantity INT DEFAULT 5,
        unit_type VARCHAR(50) DEFAULT 'pieces',
        added_by VARCHAR(255),
        added_department VARCHAR(100),
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (area_id) REFERENCES cargo_areas(id) ON DELETE CASCADE
    )";
    
    if ($pdo->exec($sql) !== FALSE) {
        echo "<p>✅ Table 'cargo_inventory' created successfully</p>";
    } else {
        echo "<p>❌ Error creating cargo_inventory table</p>";
    }
    
    // Add unit_type column if it doesn't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE cargo_inventory ADD COLUMN unit_type VARCHAR(50) DEFAULT 'pieces'");
        echo "<p>✅ Added 'unit_type' column to cargo_inventory table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p>ℹ️ Column 'unit_type' already exists in cargo_inventory table</p>";
        } else {
            echo "<p>⚠️ Note: Could not add unit_type column - " . $e->getMessage() . "</p>";
        }
    }

    // Create cargo_logs table
    $sql = "CREATE TABLE IF NOT EXISTS cargo_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT,
        action VARCHAR(50),
        quantity_change INT,
        previous_quantity INT,
        new_quantity INT,
        performed_by VARCHAR(255),
        performer_department VARCHAR(100),
        reason TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inventory_id) REFERENCES cargo_inventory(id) ON DELETE CASCADE
    )";
    
    if ($pdo->exec($sql) !== FALSE) {
        echo "<p>✅ Table 'cargo_logs' created successfully</p>";
    } else {
        echo "<p>❌ Error creating cargo_logs table</p>";
    }

    // Insert default cargo areas
    $areas = [
        ['MED/SCI Shelf Unit', 'MEDSCI', 'Large shelf unit for medical and scientific supplies', 'MED/SCI'],
        ['ENG/OPS Shelf Unit 1', 'ENGOPS1', 'Engineering and Operations storage shelf 1', 'ENG/OPS'],
        ['ENG/OPS Shelf Unit 2', 'ENGOPS2', 'Engineering and Operations storage shelf 2', 'ENG/OPS'],
        ['ENG/OPS Shelf Unit 3', 'ENGOPS3', 'Engineering and Operations storage shelf 3', 'ENG/OPS'],
        ['SEC/TAC Upper Level', 'SECTAC', 'Security and Tactical storage area on upper level', 'SEC/TAC'],
        ['Miscellaneous Items', 'MISC', 'General storage area for miscellaneous items', 'MED/SCI,ENG/OPS,SEC/TAC']
    ];

    foreach ($areas as $area) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO cargo_areas (area_name, area_code, description, department_access) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$area[0], $area[1], $area[2], $area[3]])) {
            echo "<p>✅ Added cargo area: " . $area[0] . "</p>";
        } else {
            echo "<p>❌ Error adding cargo area " . $area[0] . "</p>";
        }
    }

    echo "<br><h2>🎉 Cargo Bay Management System Setup Complete!</h2>";
    echo "<p><a href='pages/cargo_bay.php'>Access Cargo Bay Management</a></p>";
    echo "<p><a href='index.php'>Return to Main Computer</a></p>";

} catch (Exception $e) {
    echo "<p>❌ Setup failed: " . $e->getMessage() . "</p>";
}
?>
