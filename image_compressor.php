<?php
echo "<h1>Image Compression Tool</h1>";

if (isset($_POST['compress']) && isset($_FILES['image_file'])) {
    $file = $_FILES['image_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "<h2>Processing Image...</h2>";
        
        $original_size = $file['size'];
        echo "Original size: " . number_format($original_size) . " bytes (" . round($original_size / 1024 / 1024, 2) . " MB)<br>";
        
        // Check if it's an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info !== false) {
            $width = $image_info[0];
            $height = $image_info[1];
            $type = $image_info[2];
            
            echo "Original dimensions: {$width} x {$height}<br>";
            echo "Image type: " . image_type_to_mime_type($type) . "<br>";
            
            // Create image resource
            $source = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($file['tmp_name']);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($file['tmp_name']);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($file['tmp_name']);
                    break;
            }
            
            if ($source) {
                $upload_dir = 'compressed/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Quality levels to try
                $qualities = [
                    'high' => 85,
                    'medium' => 70,
                    'low' => 50
                ];
                
                echo "<h3>Compression Results:</h3>";
                echo "<table border='1' style='color: white; background: #333; margin: 10px 0;'>";
                echo "<tr><th>Quality</th><th>File Size</th><th>Reduction</th><th>Download</th></tr>";
                
                foreach ($qualities as $label => $quality) {
                    $filename = 'compressed_' . $label . '_' . uniqid() . '.jpg';
                    $filepath = $upload_dir . $filename;
                    
                    // Save as JPEG with specified quality
                    if (imagejpeg($source, $filepath, $quality)) {
                        $new_size = filesize($filepath);
                        $reduction = round((($original_size - $new_size) / $original_size) * 100, 1);
                        
                        echo "<tr>";
                        echo "<td>" . ucfirst($label) . " ($quality%)</td>";
                        echo "<td>" . number_format($new_size) . " bytes (" . round($new_size / 1024 / 1024, 2) . " MB)</td>";
                        echo "<td>-{$reduction}%</td>";
                        echo "<td><a href='$filepath' download='compressed_{$label}_asdasd.jpg' style='color: #66ccff;'>Download</a></td>";
                        echo "</tr>";
                    }
                }
                
                echo "</table>";
                
                // Also create a resized version if image is large
                if ($width > 800 || $height > 600) {
                    echo "<h3>Resized Versions:</h3>";
                    
                    $new_width = min(800, $width);
                    $new_height = ($height * $new_width) / $width;
                    
                    $resized = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    
                    $resized_filename = 'resized_' . uniqid() . '.jpg';
                    $resized_filepath = $upload_dir . $resized_filename;
                    
                    if (imagejpeg($resized, $resized_filepath, 80)) {
                        $resized_size = filesize($resized_filepath);
                        $resized_reduction = round((($original_size - $resized_size) / $original_size) * 100, 1);
                        
                        echo "<p>Resized to {$new_width} x " . round($new_height) . " pixels<br>";
                        echo "Size: " . number_format($resized_size) . " bytes (" . round($resized_size / 1024 / 1024, 2) . " MB)<br>";
                        echo "Reduction: -{$resized_reduction}%<br>";
                        echo "<a href='$resized_filepath' download='resized_asdasd.jpg' style='color: #66ccff;'>Download Resized Version</a></p>";
                    }
                    
                    imagedestroy($resized);
                }
                
                imagedestroy($source);
                
                echo "<div style='background: #2d5a2d; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3>✅ Success!</h3>";
                echo "<p>Your compressed images are ready for download. Choose the version that gives you the best balance of file size and quality.</p>";
                echo "<p><strong>Tip:</strong> For profile photos, the 'medium' quality usually provides good results while staying under upload limits.</p>";
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'>Failed to create image resource. Unsupported image type.</p>";
            }
        } else {
            echo "<p style='color: red;'>Invalid image file.</p>";
        }
    } else {
        echo "<p style='color: red;'>Upload error: " . $file['error'] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Compression Tool</title>
    <style>
        body { font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        form { background: #333; padding: 20px; border-radius: 10px; margin: 20px 0; }
        input[type="file"] { background: #000; color: #fff; padding: 10px; border: 1px solid #666; width: 100%; }
        button { background: #0066cc; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0088ff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border: 1px solid #666; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_POST['compress'])): ?>
        <div style="background: #444; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Problem with asdasd.png</h3>
            <p>Your image file (2.4 MB) is too large for the current PHP upload limit. This tool will compress it to make it uploadable.</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <h3>Compress Your Image</h3>
            <p>Select your image file (asdasd.png) and we'll create compressed versions that will work with the upload system.</p>
            
            <label>Select Image File:</label><br>
            <input type="file" name="image_file" accept="image/*" required><br><br>
            
            <button type="submit" name="compress">Compress Image</button>
        </form>
        
        <div style="background: #444; padding: 15px; border-radius: 5px;">
            <h3>How This Works:</h3>
            <ul>
                <li>Uploads your image temporarily</li>
                <li>Creates compressed versions at different quality levels</li>
                <li>Provides download links for the compressed files</li>
                <li>You can then use the compressed version on the website</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
