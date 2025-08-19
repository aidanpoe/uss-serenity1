<?php
// Test specific image file
$test_file = 'c:\Users\edgar\Desktop\asdasd.png';

echo "<h1>Testing Specific Image: asdasd.png</h1>";

if (!file_exists($test_file)) {
    echo "<p style='color: red;'>File does not exist: $test_file</p>";
    exit;
}

echo "<h2>File Properties</h2>";
echo "File exists: YES<br>";
echo "File size: " . filesize($test_file) . " bytes (" . round(filesize($test_file) / 1024 / 1024, 2) . " MB)<br>";
echo "File extension: " . pathinfo($test_file, PATHINFO_EXTENSION) . "<br>";

// Test MIME type detection
echo "<h2>MIME Type Detection</h2>";

// Method 1: Using finfo
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $test_file);
    finfo_close($finfo);
    echo "MIME type (finfo): " . $detected_type . "<br>";
} else {
    echo "finfo not available<br>";
}

// Method 2: Using getimagesize
$image_info = getimagesize($test_file);
if ($image_info !== false) {
    echo "Image info detected: YES<br>";
    echo "Width: " . $image_info[0] . "px<br>";
    echo "Height: " . $image_info[1] . "px<br>";
    echo "MIME type (getimagesize): " . $image_info['mime'] . "<br>";
    echo "Image type: ";
    switch($image_info[2]) {
        case IMAGETYPE_PNG: echo "PNG"; break;
        case IMAGETYPE_JPEG: echo "JPEG"; break;
        case IMAGETYPE_GIF: echo "GIF"; break;
        case IMAGETYPE_WEBP: echo "WEBP"; break;
        default: echo "Unknown (" . $image_info[2] . ")"; break;
    }
    echo "<br>";
} else {
    echo "<p style='color: red;'>getimagesize() failed - file is not a valid image</p>";
}

// Test our validation logic
echo "<h2>Validation Tests</h2>";

$file_extension = strtolower(pathinfo($test_file, PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

echo "File extension: $file_extension<br>";
echo "Extension allowed: " . (in_array($file_extension, $allowed_extensions) ? "YES" : "NO") . "<br>";

$max_size = 5 * 1024 * 1024; // 5MB
$file_size = filesize($test_file);
echo "Size check: " . ($file_size <= $max_size ? "PASS" : "FAIL") . "<br>";

// Test MIME type validation
$allowed_mime_types = [
    'image/jpeg',
    'image/jpg', 
    'image/png',
    'image/gif',
    'image/webp'
];

if (isset($detected_type)) {
    echo "MIME type allowed: " . (in_array($detected_type, $allowed_mime_types) ? "YES" : "NO") . "<br>";
}

// Show raw file header (first 20 bytes)
echo "<h2>File Header (first 20 bytes)</h2>";
$handle = fopen($test_file, 'rb');
$header = fread($handle, 20);
fclose($handle);

echo "Hex: ";
for ($i = 0; $i < strlen($header); $i++) {
    echo sprintf('%02X ', ord($header[$i]));
}
echo "<br>";

echo "ASCII: ";
for ($i = 0; $i < strlen($header); $i++) {
    $char = ord($header[$i]);
    echo ($char >= 32 && $char <= 126) ? chr($char) : '.';
}
echo "<br>";

// Test if we can copy it to temp directory
echo "<h2>Copy Test</h2>";
$temp_dir = 'temp_test/';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

$temp_file = $temp_dir . 'test_copy.png';
if (copy($test_file, $temp_file)) {
    echo "Copy successful: $temp_file<br>";
    echo "Copied file size: " . filesize($temp_file) . " bytes<br>";
    
    // Test if copied file works with getimagesize
    $copied_image_info = getimagesize($temp_file);
    if ($copied_image_info !== false) {
        echo "Copied file validates as image: YES<br>";
    } else {
        echo "Copied file validates as image: NO<br>";
    }
    
    // Clean up
    unlink($temp_file);
} else {
    echo "Copy failed<br>";
}

rmdir($temp_dir);

?>
