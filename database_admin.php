<?php
require_once 'includes/config.php';

// Security check - only allow from localhost or if user is logged in as Captain
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Captain') {
    if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
        die("Access denied. Captain authorization required.");
    }
}

echo "<h1>USS Voyager Database Management Tool</h1>";
echo "<p style='color: #orange;'>⚠️ Captain-level access required. Use with caution.</p>";

try {
    $pdo = getConnection();
    
    // Get database info
    echo "<h2>Database Overview</h2>";
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $db_info = $stmt->fetch();
    echo "Current Database: <strong>" . $db_info['current_db'] . "</strong><br>";
    
    // Table overview
    echo "<h3>Tables and Record Counts</h3>";
    echo "<table border='1' style='color: white; background: #333; border-collapse: collapse; width: 100%;'>";
    echo "<tr><th style='padding: 8px;'>Table</th><th style='padding: 8px;'>Records</th><th style='padding: 8px;'>Actions</th></tr>";
    
    $tables = ['users', 'roster', 'patient_records', 'criminal_records', 'reports'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            $status = "✅";
        } catch (Exception $e) {
            $count = "Table not found";
            $status = "❌";
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>$status $table</td>";
        echo "<td style='padding: 8px;'>$count</td>";
        echo "<td style='padding: 8px;'>";
        if ($count !== "Table not found") {
            echo "<a href='?view_table=$table' style='color: #66ccff; margin-right: 10px;'>View Data</a>";
            echo "<a href='?backup_table=$table' style='color: #66ccff;'>Backup</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Image path analysis
    echo "<h3>Image Path Analysis</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM roster");
    $total_crew = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as with_images FROM roster WHERE image_path IS NOT NULL AND image_path != ''");
    $crew_with_images = $stmt->fetch()['with_images'];
    
    echo "Total crew members: <strong>$total_crew</strong><br>";
    echo "Crew with photos: <strong>$crew_with_images</strong><br>";
    echo "Missing photos: <strong>" . ($total_crew - $crew_with_images) . "</strong><br>";
    
    // Check for broken image paths
    echo "<h4>Image Path Validation</h4>";
    $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, image_path FROM roster WHERE image_path IS NOT NULL AND image_path != ''");
    $crew_images = $stmt->fetchAll();
    
    $broken_paths = 0;
    echo "<table border='1' style='color: white; background: #333; border-collapse: collapse; width: 100%; font-size: 0.9em;'>";
    echo "<tr><th style='padding: 5px;'>Name</th><th style='padding: 5px;'>Image Path</th><th style='padding: 5px;'>Status</th></tr>";
    
    foreach ($crew_images as $crew) {
        $file_exists = file_exists($crew['image_path']);
        if (!$file_exists) $broken_paths++;
        
        echo "<tr>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($crew['name']) . "</td>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($crew['image_path']) . "</td>";
        echo "<td style='padding: 5px;'>" . ($file_exists ? "✅ Found" : "❌ Missing") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($broken_paths > 0) {
        echo "<p style='color: #ff6666;'>⚠️ Found $broken_paths broken image paths. <a href='?fix_broken_paths=1' style='color: #66ccff;'>Click to clean up</a></p>";
    }
    
    // Handle actions
    if (isset($_GET['view_table'])) {
        $table = $_GET['view_table'];
        echo "<h3>Data from table: $table</h3>";
        
        try {
            $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 20");
            $rows = $stmt->fetchAll();
            
            if ($rows) {
                echo "<table border='1' style='color: white; background: #333; border-collapse: collapse; width: 100%; font-size: 0.8em; overflow-x: auto;'>";
                
                // Headers
                echo "<tr>";
                foreach (array_keys($rows[0]) as $column) {
                    echo "<th style='padding: 5px; background: #222;'>" . htmlspecialchars($column) . "</th>";
                }
                echo "</tr>";
                
                // Data (limit to first 20 rows)
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        $display_value = htmlspecialchars(substr($value, 0, 50));
                        if (strlen($value) > 50) $display_value .= "...";
                        echo "<td style='padding: 5px;'>$display_value</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p><em>Showing first 20 records only.</em></p>";
            } else {
                echo "<p>No data found in table.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    if (isset($_GET['fix_broken_paths'])) {
        echo "<h3>Cleaning up broken image paths...</h3>";
        $stmt = $pdo->prepare("UPDATE roster SET image_path = NULL WHERE image_path IS NOT NULL AND image_path != ''");
        
        // Check each image path
        $stmt = $pdo->query("SELECT id, image_path FROM roster WHERE image_path IS NOT NULL AND image_path != ''");
        $crew_images = $stmt->fetchAll();
        
        $cleaned = 0;
        foreach ($crew_images as $crew) {
            if (!file_exists($crew['image_path'])) {
                $update_stmt = $pdo->prepare("UPDATE roster SET image_path = NULL WHERE id = ?");
                $update_stmt->execute([$crew['id']]);
                $cleaned++;
            }
        }
        
        echo "<p style='color: #66ff66;'>✅ Cleaned up $cleaned broken image paths.</p>";
    }
    
    if (isset($_GET['backup_table'])) {
        $table = $_GET['backup_table'];
        echo "<h3>Backup table: $table</h3>";
        
        try {
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $backup_data = [
                'table' => $table,
                'timestamp' => date('Y-m-d H:i:s'),
                'record_count' => count($rows),
                'data' => $rows
            ];
            
            $filename = "backup_{$table}_" . date('Y-m-d_H-i-s') . ".json";
            file_put_contents($filename, json_encode($backup_data, JSON_PRETTY_PRINT));
            
            echo "<p style='color: #66ff66;'>✅ Backup created: <a href='$filename' download style='color: #66ccff;'>$filename</a></p>";
            echo "<p>Records backed up: " . count($rows) . "</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Backup failed: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px; }
table { margin: 10px 0; }
th { background: #333; }
a { color: #66ccff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>

<div style="background: #444; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h3>Plesk Server Management</h3>
    <p><strong>To fix upload limits:</strong></p>
    <ol>
        <li>Open Plesk Control Panel</li>
        <li>Go to "Websites & Domains" → "PHP Settings"</li>
        <li>Set upload_max_filesize to <strong>10M</strong></li>
        <li>Set post_max_size to <strong>12M</strong></li>
        <li>Click "Apply"</li>
    </ol>
    
    <p><strong>Database Access:</strong></p>
    <ul>
        <li>Use phpMyAdmin in Plesk for direct database access</li>
        <li>Or use this tool for basic management</li>
        <li>Backup important data regularly</li>
    </ul>
</div>
