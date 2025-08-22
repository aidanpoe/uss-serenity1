<?php
/**
 * Test script to demonstrate the IC date formatting system
 */

require_once 'includes/config.php';

echo "<h2>In-Character Date Formatting System Test</h2>";

// Test the formatICDate function with current time
$current_datetime = date('Y-m-d H:i:s');
$sample_datetime = '2025-08-22 17:58:00';

echo "<h3>Function Testing:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px;'>Function</th>";
echo "<th style='padding: 10px;'>Input (OOC)</th>";
echo "<th style='padding: 10px;'>Output (IC)</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>formatICDateTime()</strong></td>";
echo "<td style='padding: 10px;'>" . $sample_datetime . "</td>";
echo "<td style='padding: 10px;'>" . formatICDateTime($sample_datetime) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>formatICDateOnly()</strong></td>";
echo "<td style='padding: 10px;'>" . $sample_datetime . "</td>";
echo "<td style='padding: 10px;'>" . formatICDateOnly($sample_datetime) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>formatICDate() with custom format</strong></td>";
echo "<td style='padding: 10px;'>" . $sample_datetime . "</td>";
echo "<td style='padding: 10px;'>" . formatICDate($sample_datetime, 'M j, Y g:i A') . "</td>";
echo "</tr>";

echo "</table>";

echo "<h3>Example Transformations:</h3>";
echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>Medical Report Example:</h4>";
echo "<p><strong>Before (OOC):</strong><br>";
echo "Ensign Adam Yuri<br>";
echo "Reported: 2025-08-22 17:58<br>";
echo "By: Ensign Adam Yuri</p>";

echo "<p><strong>After (IC):</strong><br>";
echo "Ensign Adam Yuri<br>";
echo "Reported: " . formatICDateTime('2025-08-22 17:58') . "<br>";
echo "By: Ensign Adam Yuri</p>";
echo "</div>";

echo "<h3>Files Updated with IC Date Formatting:</h3>";
echo "<ul>";
echo "<li>✅ <strong>pages/med_sci.php</strong> - Medical reports and science reports</li>";
echo "<li>✅ <strong>pages/eng_ops.php</strong> - Fault reports</li>";
echo "<li>✅ <strong>pages/sec_tac.php</strong> - Security incident reports</li>";
echo "<li>✅ <strong>pages/medical_history.php</strong> - Medical record timestamps and updates</li>";
echo "<li>✅ <strong>pages/engineering_resolved.php</strong> - Resolved engineering issues</li>";
echo "<li>✅ <strong>pages/security_resolved.php</strong> - Resolved security reports</li>";
echo "<li>✅ <strong>pages/criminal_history.php</strong> - Criminal record creation dates</li>";
echo "<li>✅ <strong>pages/character_auditor_management.php</strong> - Assignment dates</li>";
echo "</ul>";

echo "<h3>What's Excluded:</h3>";
echo "<ul>";
echo "<li>🚫 <strong>Last Active timestamps</strong> - These remain in OOC time for gameplay functionality</li>";
echo "<li>🚫 <strong>System/admin timestamps</strong> - Backend operations remain in OOC time</li>";
echo "<li>🚫 <strong>GDPR and legal timestamps</strong> - Must remain in real time for compliance</li>";
echo "</ul>";

echo "<h3>Technical Details:</h3>";
echo "<p><strong>Date Calculation:</strong> Adds exactly 360 years (360 × 365.25 × 24 × 60 × 60 seconds) to any timestamp</p>";
echo "<p><strong>Usage:</strong> Simply replace <code>date('Y-m-d H:i', strtotime(\$datetime))</code> with <code>formatICDateTime(\$datetime)</code></p>";
echo "<p><strong>Formats Available:</strong></p>";
echo "<ul>";
echo "<li><code>formatICDateTime(\$datetime)</code> - Full date and time (Y-m-d H:i)</li>";
echo "<li><code>formatICDateOnly(\$datetime)</code> - Date only (Y-m-d)</li>";
echo "<li><code>formatICDate(\$datetime, 'format')</code> - Custom format</li>";
echo "</ul>";

echo "<h3>Consistency Check:</h3>";
$test_dates = [
    '2025-01-01 12:00:00',
    '2025-06-15 09:30:00', 
    '2025-12-31 23:59:59'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px;'>OOC Date</th>";
echo "<th style='padding: 10px;'>IC Date</th>";
echo "<th style='padding: 10px;'>Year Difference</th>";
echo "</tr>";

foreach ($test_dates as $test_date) {
    $ic_date = formatICDateTime($test_date);
    echo "<tr>";
    echo "<td style='padding: 10px;'>" . $test_date . "</td>";
    echo "<td style='padding: 10px;'>" . $ic_date . "</td>";
    echo "<td style='padding: 10px;'>+" . (explode('-', $ic_date)[0] - explode('-', $test_date)[0]) . " years</td>";
    echo "</tr>";
}
echo "</table>";

?>
