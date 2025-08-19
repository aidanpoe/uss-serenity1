<?php
echo "<h1>PHP Upload Configuration Check</h1>";

echo "<h2>Current PHP Settings</h2>";
echo "<table border='1' style='color: white; background: #333; padding: 10px;'>";
echo "<tr><th>Setting</th><th>Current Value</th><th>Recommended</th></tr>";

$settings = [
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '10M'],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '12M'],
    'max_file_uploads' => ['current' => ini_get('max_file_uploads'), 'recommended' => '20'],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '128M'],
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '30'],
    'max_input_time' => ['current' => ini_get('max_input_time'), 'recommended' => '60']
];

foreach ($settings as $name => $values) {
    $status = '';
    if ($name === 'upload_max_filesize') {
        $current_bytes = parse_size($values['current']);
        $file_size = 2490830; // Your file size
        $status = $current_bytes >= $file_size ? '✓ OK' : '✗ TOO SMALL';
    }
    
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td>{$values['current']} $status</td>";
    echo "<td>{$values['recommended']}</td>";
    echo "</tr>";
}

echo "</table>";

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

echo "<h2>File Upload Test</h2>";
echo "Your file size: 2,490,830 bytes (2.4 MB)<br>";
echo "Current upload limit: " . ini_get('upload_max_filesize') . " (" . parse_size(ini_get('upload_max_filesize')) . " bytes)<br>";

if (parse_size(ini_get('upload_max_filesize')) < 2490830) {
    echo "<p style='color: red; font-weight: bold;'>❌ PROBLEM: Your file is larger than the PHP upload limit!</p>";
    echo "<h3>Solutions:</h3>";
    echo "<ol>";
    echo "<li><strong>Increase PHP limits</strong> (if you have server access)</li>";
    echo "<li><strong>Compress the image</strong> to reduce file size</li>";
    echo "<li><strong>Use a different image</strong> that's smaller</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ Upload limit should be sufficient</p>";
}

echo "<h2>PHP Configuration File Location</h2>";
echo "Loaded php.ini file: " . php_ini_loaded_file() . "<br>";

$additional_inis = php_ini_scanned_files();
if ($additional_inis) {
    echo "Additional .ini files: " . $additional_inis . "<br>";
}

echo "<h2>How to Fix Upload Limits</h2>";
echo "<div style='background: #444; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Method 1: Edit php.ini file</h3>";
echo "<p>Find your php.ini file and change these values:</p>";
echo "<code style='background: #222; padding: 5px; display: block;'>";
echo "upload_max_filesize = 10M<br>";
echo "post_max_size = 12M<br>";
echo "memory_limit = 128M<br>";
echo "</code>";
echo "<p>Then restart your web server (Apache/Nginx/IIS)</p>";
echo "</div>";

echo "<div style='background: #444; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Method 2: .htaccess file (if using Apache)</h3>";
echo "<p>Create/edit .htaccess in your website root:</p>";
echo "<code style='background: #222; padding: 5px; display: block;'>";
echo "php_value upload_max_filesize 10M<br>";
echo "php_value post_max_size 12M<br>";
echo "php_value memory_limit 128M<br>";
echo "</code>";
echo "</div>";

echo "<div style='background: #444; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Method 3: Reduce Image Size</h3>";
echo "<p>Alternatively, you can:</p>";
echo "<ul>";
echo "<li>Compress the PNG file using tools like TinyPNG.com</li>";
echo "<li>Convert to JPEG with lower quality</li>";
echo "<li>Resize the image to smaller dimensions</li>";
echo "</ul>";
echo "</div>";

?>
