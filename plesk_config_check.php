<?php
echo "<h1>Plesk Server Configuration Check</h1>";

echo "<h2>Server Environment</h2>";
echo "<table border='1' style='color: white; background: #333; border-collapse: collapse;'>";
echo "<tr><th style='padding: 8px;'>Setting</th><th style='padding: 8px;'>Value</th></tr>";

$server_info = [
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'PHP Version' => phpversion(),
    'Operating System' => php_uname(),
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Server Admin' => $_SERVER['SERVER_ADMIN'] ?? 'Unknown',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown'
];

foreach ($server_info as $key => $value) {
    echo "<tr><td style='padding: 8px;'>$key</td><td style='padding: 8px;'>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

echo "<h2>PHP Upload Configuration</h2>";
echo "<div style='background: #444; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Current Settings vs. Recommended (for 2.4MB file)</h3>";
echo "</div>";

echo "<table border='1' style='color: white; background: #333; border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='padding: 8px;'>Setting</th><th style='padding: 8px;'>Current</th><th style='padding: 8px;'>Recommended</th><th style='padding: 8px;'>Status</th></tr>";

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

$file_size_needed = 2490830; // Your asdasd.png file size
$settings_check = [
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '10M', 'critical' => true],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '12M', 'critical' => true],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '128M', 'critical' => false],
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '300', 'critical' => false],
    'max_input_time' => ['current' => ini_get('max_input_time'), 'recommended' => '300', 'critical' => false],
    'file_uploads' => ['current' => ini_get('file_uploads') ? 'On' : 'Off', 'recommended' => 'On', 'critical' => true],
    'max_file_uploads' => ['current' => ini_get('max_file_uploads'), 'recommended' => '20', 'critical' => false]
];

$critical_issues = 0;

foreach ($settings_check as $setting => $info) {
    $status = '✅ OK';
    $status_color = '#66ff66';
    
    if ($setting === 'upload_max_filesize' || $setting === 'post_max_size') {
        $current_bytes = parse_size($info['current']);
        if ($current_bytes < $file_size_needed) {
            $status = '❌ TOO SMALL';
            $status_color = '#ff6666';
            if ($info['critical']) $critical_issues++;
        }
    } elseif ($setting === 'file_uploads') {
        if ($info['current'] !== 'On') {
            $status = '❌ DISABLED';
            $status_color = '#ff6666';
            if ($info['critical']) $critical_issues++;
        }
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>$setting</td>";
    echo "<td style='padding: 8px;'>{$info['current']}</td>";
    echo "<td style='padding: 8px;'>{$info['recommended']}</td>";
    echo "<td style='padding: 8px; color: $status_color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

if ($critical_issues > 0) {
    echo "<div style='background: #5a2d2d; padding: 15px; border-radius: 5px; margin: 20px 0; border: 2px solid #ff6666;'>";
    echo "<h3>❌ Critical Issues Found ($critical_issues)</h3>";
    echo "<p>Your file upload will fail with current settings. Your 2.4MB file requires larger limits.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #2d5a2d; padding: 15px; border-radius: 5px; margin: 20px 0; border: 2px solid #66ff66;'>";
    echo "<h3>✅ Configuration Looks Good!</h3>";
    echo "<p>Current settings should allow your 2.4MB file upload.</p>";
    echo "</div>";
}

echo "<h2>Plesk-Specific Fix Instructions</h2>";

echo "<div style='background: #2d4a5a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Step 1: Access Plesk Control Panel</h3>";
echo "<ol>";
echo "<li>Log into your Plesk control panel</li>";
echo "<li>Navigate to <strong>Websites & Domains</strong></li>";
echo "<li>Find your domain and click on it</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #2d4a5a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Step 2: Modify PHP Settings</h3>";
echo "<ol>";
echo "<li>Click on <strong>PHP Settings</strong></li>";
echo "<li>Find the following settings and change them:</li>";
echo "<ul>";
echo "<li><code>upload_max_filesize</code> → <strong>10M</strong></li>";
echo "<li><code>post_max_size</code> → <strong>12M</strong></li>";
echo "<li><code>memory_limit</code> → <strong>128M</strong> (if available)</li>";
echo "</ul>";
echo "<li>Click <strong>OK</strong> or <strong>Apply</strong></li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #2d4a5a; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Step 3: Test Upload</h3>";
echo "<ol>";
echo "<li>Wait 1-2 minutes for changes to take effect</li>";
echo "<li><a href='upload_test.php' style='color: #66ccff;'>Test your image upload here</a></li>";
echo "<li>If still failing, try the <a href='image_compressor.php' style='color: #66ccff;'>image compressor</a></li>";
echo "</ol>";
echo "</div>";

echo "<h2>Alternative: Database Direct Access</h2>";
echo "<div style='background: #444; padding: 15px; border-radius: 5px;'>";
echo "<p>Since you have database access, you can also:</p>";
echo "<ul>";
echo "<li>Use phpMyAdmin in Plesk to directly manage data</li>";
echo "<li>Use the <a href='database_admin.php' style='color: #66ccff;'>Database Admin Tool</a> I created</li>";
echo "<li>Backup important data before making changes</li>";
echo "</ul>";
echo "</div>";

echo "<h2>File System Check</h2>";
$upload_dirs = ['assets/crew_photos/', 'uploads/'];
echo "<table border='1' style='color: white; background: #333; border-collapse: collapse;'>";
echo "<tr><th style='padding: 8px;'>Directory</th><th style='padding: 8px;'>Exists</th><th style='padding: 8px;'>Writable</th><th style='padding: 8px;'>Files</th></tr>";

foreach ($upload_dirs as $dir) {
    $exists = is_dir($dir);
    $writable = $exists ? is_writable($dir) : false;
    $file_count = 0;
    
    if ($exists) {
        $files = glob($dir . '*');
        $file_count = count($files);
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>$dir</td>";
    echo "<td style='padding: 8px;'>" . ($exists ? '✅ Yes' : '❌ No') . "</td>";
    echo "<td style='padding: 8px;'>" . ($writable ? '✅ Yes' : '❌ No') . "</td>";
    echo "<td style='padding: 8px;'>$file_count</td>";
    echo "</tr>";
}
echo "</table>";

?>

<style>
body { font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px; }
table { margin: 10px 0; }
code { background: #222; padding: 2px 5px; border-radius: 3px; }
a { color: #66ccff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
