<?php
require_once 'includes/config.php';

// Include our improved handleImageUpload function
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        if (isset($file['error'])) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("File size exceeds maximum allowed.");
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("File upload was incomplete.");
                case UPLOAD_ERR_NO_FILE:
                    return ''; // No file uploaded, which is OK
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("Server configuration error: no temp directory.");
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Server error: cannot write file.");
                default:
                    throw new Exception("Unknown upload error.");
            }
        }
        return '';
    }
    
    // Check file size (5MB limit)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception("Image file size must be less than 5MB. Your file is " . round($file['size'] / 1024 / 1024, 2) . "MB.");
    }
    
    // Get file extension and normalize it
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Only JPEG, PNG, GIF, and WebP images are allowed. You uploaded: " . strtoupper($file_extension));
    }
    
    // Additional MIME type validation (more comprehensive)
    $allowed_mime_types = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($detected_type, $allowed_mime_types) && !in_array($file['type'], $allowed_mime_types)) {
        throw new Exception("Invalid image file type detected. Expected image file, got: " . $detected_type);
    }
    
    // Verify it's actually an image by trying to get image info
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        throw new Exception("File is not a valid image or is corrupted.");
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = 'assets/crew_photos/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Could not create upload directory.");
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        throw new Exception("Upload directory is not writable.");
    }
    
    // Generate unique filename
    $filename = uniqid('test_') . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return 'assets/crew_photos/' . $filename;
    } else {
        throw new Exception("Failed to save uploaded image file.");
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #2d5a2d; border: 1px solid #4a8a4a; }
        .error { background: #5a2d2d; border: 1px solid #8a4a4a; }
        .info { background: #2d2d5a; border: 1px solid #4a4a8a; }
        form { background: #333; padding: 20px; border-radius: 10px; margin: 20px 0; }
        input[type="file"] { background: #000; color: #fff; padding: 10px; border: 1px solid #666; width: 100%; }
        button { background: #0066cc; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0088ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Image Upload Test - asdasd.png</h1>
        
        <?php if (isset($_POST['test_upload']) && isset($_FILES['test_image'])): ?>
            <div class="result info">
                <h3>Upload Test Results</h3>
                
                <?php
                $file = $_FILES['test_image'];
                echo "<strong>File Details:</strong><br>";
                echo "Original name: " . htmlspecialchars($file['name']) . "<br>";
                echo "File size: " . $file['size'] . " bytes (" . round($file['size'] / 1024 / 1024, 2) . " MB)<br>";
                echo "Browser MIME type: " . htmlspecialchars($file['type']) . "<br>";
                echo "Upload error code: " . $file['error'] . "<br>";
                echo "Temp file: " . htmlspecialchars($file['tmp_name']) . "<br>";
                
                if ($file['error'] === UPLOAD_ERR_OK) {
                    // Test server-side MIME detection
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $detected_type = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        echo "Server detected MIME: " . $detected_type . "<br>";
                    }
                    
                    // Test getimagesize
                    $image_info = getimagesize($file['tmp_name']);
                    if ($image_info !== false) {
                        echo "Image dimensions: " . $image_info[0] . " x " . $image_info[1] . "<br>";
                        echo "Image MIME (getimagesize): " . $image_info['mime'] . "<br>";
                    } else {
                        echo "<span style='color: #ff6666;'>getimagesize() failed - invalid image</span><br>";
                    }
                    
                    echo "<br><strong>Testing handleImageUpload function:</strong><br>";
                    
                    try {
                        $result = handleImageUpload($file);
                        if ($result) {
                            echo "<div class='result success'>";
                            echo "<strong>SUCCESS!</strong> Image uploaded successfully.<br>";
                            echo "Saved as: " . htmlspecialchars($result) . "<br>";
                            
                            // Show the uploaded image
                            if (file_exists($result)) {
                                echo "<br><img src='" . htmlspecialchars($result) . "' style='max-width: 300px; max-height: 300px; border: 2px solid #4a8a4a;'>";
                            }
                            echo "</div>";
                        } else {
                            echo "<div class='result info'>No file was uploaded (this is normal if no file was selected).</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='result error'>";
                        echo "<strong>UPLOAD FAILED:</strong> " . htmlspecialchars($e->getMessage());
                        echo "</div>";
                    }
                    
                } else {
                    echo "<div class='result error'>";
                    echo "<strong>Upload Error:</strong> ";
                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            echo "File size exceeds PHP upload_max_filesize limit.";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            echo "File size exceeds HTML form MAX_FILE_SIZE limit.";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            echo "File upload was incomplete.";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            echo "No file was uploaded.";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            echo "Server configuration error: no temp directory.";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            echo "Server error: cannot write file.";
                            break;
                        default:
                            echo "Unknown error code: " . $file['error'];
                    }
                    echo "</div>";
                }
                ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <h3>Test Upload asdasd.png</h3>
            <p>Select the file: <strong>c:\Users\edgar\Desktop\asdasd.png</strong></p>
            
            <label>Choose Image File:</label><br>
            <input type="file" name="test_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" required><br><br>
            
            <button type="submit" name="test_upload">Test Upload</button>
        </form>
        
        <div class="result info">
            <h3>Instructions:</h3>
            <ol>
                <li>Click "Choose Image File" above</li>
                <li>Navigate to your Desktop</li>
                <li>Select the file "asdasd.png"</li>
                <li>Click "Test Upload" to see detailed results</li>
            </ol>
        </div>
    </div>
</body>
</html>
