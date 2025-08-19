<?php
require_once 'includes/config.php';

echo "<h1>Image Upload Debug</h1>";

try {
    $pdo = getConnection();
    
    // Check crew_photos directory
    $photos_dir = 'assets/crew_photos/';
    echo "<h2>Directory Check</h2>";
    echo "Photos directory exists: " . (is_dir($photos_dir) ? "YES" : "NO") . "<br>";
    echo "Photos directory writable: " . (is_writable($photos_dir) ? "YES" : "NO") . "<br>";
    
    if (is_dir($photos_dir)) {
        $files = scandir($photos_dir);
        echo "Files in directory: " . count($files) - 2 . "<br>"; // -2 for . and ..
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "- $file<br>";
            }
        }
    }
    
    // Check roster entries with images
    echo "<h2>Roster Image Paths</h2>";
    $stmt = $pdo->query("SELECT id, first_name, last_name, image_path FROM roster WHERE image_path IS NOT NULL AND image_path != ''");
    $crew_with_images = $stmt->fetchAll();
    
    if (empty($crew_with_images)) {
        echo "No crew members have image paths in the database.<br>";
    } else {
        foreach ($crew_with_images as $member) {
            echo "ID: {$member['id']}, Name: {$member['first_name']} {$member['last_name']}<br>";
            echo "Image Path: {$member['image_path']}<br>";
            echo "File exists: " . (file_exists($member['image_path']) ? "YES" : "NO") . "<br>";
            echo "Full path exists: " . (file_exists('pages/' . $member['image_path']) ? "YES (from pages/)" : "NO") . "<br>";
            echo "<br>";
        }
    }
    
    // Check all roster entries
    echo "<h2>All Roster Entries</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM roster");
    $total = $stmt->fetch();
    echo "Total roster entries: {$total['total']}<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<form method="POST" enctype="multipart/form-data" style="margin-top: 20px; border: 1px solid #ccc; padding: 20px;">
    <h3>Test Image Upload</h3>
    <input type="file" name="test_image" accept="image/*" required><br><br>
    <button type="submit" name="test_upload">Test Upload</button>
</form>

<?php
if (isset($_POST['test_upload']) && isset($_FILES['test_image'])) {
    echo "<h3>Upload Test Results</h3>";
    
    $file = $_FILES['test_image'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . " bytes<br>";
    echo "File type: " . $file['type'] . "<br>";
    echo "Error code: " . $file['error'] . "<br>";
    echo "Temp file: " . $file['tmp_name'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/crew_photos/';
        $filename = 'test_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $upload_path = $upload_dir . $filename;
        
        echo "Upload directory: $upload_dir<br>";
        echo "Target filename: $filename<br>";
        echo "Full upload path: $upload_path<br>";
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo "<strong>SUCCESS: File uploaded successfully!</strong><br>";
            echo "File saved as: $upload_path<br>";
            echo "File exists check: " . (file_exists($upload_path) ? "YES" : "NO") . "<br>";
            
            if (file_exists($upload_path)) {
                echo "<img src='$upload_path' style='max-width: 200px; max-height: 200px; border: 2px solid green;'><br>";
            }
        } else {
            echo "<strong>FAILED: Could not move uploaded file</strong><br>";
        }
    } else {
        echo "<strong>Upload error code: " . $file['error'] . "</strong><br>";
    }
}
?>
