<?php
// Alternative PHP upload limit configuration
// This file attempts to set upload limits programmatically

// Try to increase limits if possible
if (function_exists('ini_set')) {
    // Attempt to set upload limits (may not work on all servers)
    @ini_set('upload_max_filesize', '10M');
    @ini_set('post_max_size', '12M');
    @ini_set('memory_limit', '128M');
    @ini_set('max_execution_time', '300');
    @ini_set('max_input_time', '300');
}

echo "<h1>PHP Upload Configuration Fix Attempt</h1>";

echo "<h2>Current Settings After Adjustment Attempt</h2>";
echo "<table border='1' style='color: white; background: #333; border-collapse: collapse;'>";
echo "<tr><th style='padding: 8px;'>Setting</th><th style='padding: 8px;'>Current Value</th><th style='padding: 8px;'>Status</th></tr>";

$file_size_needed = 2490830; // Your file size in bytes

$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time')
];

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

foreach ($settings as $name => $value) {
    $status = '?';
    if ($name === 'upload_max_filesize') {
        $bytes = parse_size($value);
        $status = $bytes >= $file_size_needed ? '✅ OK' : '❌ TOO SMALL';
    } elseif ($name === 'post_max_size') {
        $bytes = parse_size($value);
        $status = $bytes >= $file_size_needed ? '✅ OK' : '❌ TOO SMALL';
    } else {
        $status = '✅';
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>$name</td>";
    echo "<td style='padding: 8px;'>$value</td>";
    echo "<td style='padding: 8px;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Solutions for Upload Limit Issues</h2>";

if (parse_size(ini_get('upload_max_filesize')) < $file_size_needed) {
    echo "<div style='background: #5a2d2d; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Upload Limit Still Too Small</h3>";
    echo "<p>Your file (2.4 MB) is still larger than the upload limit. Here are your options:</p>";
    echo "</div>";
    
    echo "<div style='background: #2d2d5a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 1: Use Image Compressor</h3>";
    echo "<p><a href='image_compressor.php' style='color: #66ccff;'>Click here to compress your image</a> and reduce its file size.</p>";
    echo "</div>";
    
    echo "<div style='background: #2d2d5a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 2: Contact Server Administrator</h3>";
    echo "<p>Ask your hosting provider to increase the following PHP settings:</p>";
    echo "<ul>";
    echo "<li><code>upload_max_filesize = 10M</code></li>";
    echo "<li><code>post_max_size = 12M</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #2d2d5a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 3: Edit php.ini Directly</h3>";
    echo "<p>If you have server access, edit the php.ini file located at:</p>";
    echo "<code>" . php_ini_loaded_file() . "</code>";
    echo "<p>Change the values and restart the web server.</p>";
    echo "</div>";
    
} else {
    echo "<div style='background: #2d5a2d; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ Upload Limits Look Good!</h3>";
    echo "<p>The current settings should allow your 2.4 MB file. Try uploading again!</p>";
    echo "<p><a href='upload_test.php' style='color: #66ccff;'>Test your upload here</a></p>";
    echo "</div>";
}

echo "<h2>Quick Alternative: Online Image Compression</h2>";
echo "<div style='background: #444; padding: 15px; border-radius: 5px;'>";
echo "<p>While we work on the server settings, you can use these online tools to compress your image:</p>";
echo "<ul>";
echo "<li><a href='https://tinypng.com' target='_blank' style='color: #66ccff;'>TinyPNG.com</a> - PNG compression</li>";
echo "<li><a href='https://compressor.io' target='_blank' style='color: #66ccff;'>Compressor.io</a> - Multi-format compression</li>";
echo "<li><a href='https://imagecompressor.com' target='_blank' style='color: #66ccff;'>ImageCompressor.com</a> - JPEG/PNG compression</li>";
echo "</ul>";
echo "<p>Compress your <code>asdasd.png</code> file to under 2MB and it should upload successfully.</p>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px; }
code { background: #222; padding: 2px 5px; border-radius: 3px; }
a { color: #66ccff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
