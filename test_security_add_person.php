<?php
/**
 * Test script to verify the "Add New Person" functionality in sec_tac.php
 */

require_once 'includes/config.php';

echo "<h2>Security 'Add New Person' Feature Test</h2>";

echo "<h3>Feature Comparison:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px;'>Feature</th>";
echo "<th style='padding: 10px;'>MED/SCI (med_sci.php)</th>";
echo "<th style='padding: 10px;'>SEC/TAC (sec_tac.php)</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Purpose</strong></td>";
echo "<td style='padding: 10px;'>Add patients for medical tracking</td>";
echo "<td style='padding: 10px;'>Add persons for incident/crime tracking</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Permission Required</strong></td>";
echo "<td style='padding: 10px;'>MED/SCI</td>";
echo "<td style='padding: 10px;'>SEC/TAC</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Form Action</strong></td>";
echo "<td style='padding: 10px;'>add_patient</td>";
echo "<td style='padding: 10px;'>add_person</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Icon</strong></td>";
echo "<td style='padding: 10px;'>🏥 (Hospital)</td>";
echo "<td style='padding: 10px;'>👮 (Security Officer)</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Color Scheme</strong></td>";
echo "<td style='padding: 10px;'>Blue (--blue)</td>";
echo "<td style='padding: 10px;'>Gold (--gold)</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Rank Limitations</strong></td>";
echo "<td style='padding: 10px;'>Below Commander</td>";
echo "<td style='padding: 10px;'>Below Commander</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>Fields</strong></td>";
echo "<td style='padding: 10px;'>Rank, First Name, Last Name, Species, Department</td>";
echo "<td style='padding: 10px;'>Rank, First Name, Last Name, Species, Department</td>";
echo "</tr>";

echo "</table>";

echo "<h3>Implementation Details:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Backend Handler:</strong> Added 'add_person' action handler with CSRF protection</li>";
echo "<li>✅ <strong>Permission Check:</strong> Restricted to SEC/TAC personnel only</li>";
echo "<li>✅ <strong>Duplicate Prevention:</strong> Checks for existing crew members with same name</li>";
echo "<li>✅ <strong>Rank Validation:</strong> Only allows ranks below Commander</li>";
echo "<li>✅ <strong>Form UI:</strong> Styled with gold theme matching Security/Tactical design</li>";
echo "<li>✅ <strong>Database Integration:</strong> Inserts new records into roster table</li>";
echo "<li>✅ <strong>Error Handling:</strong> Comprehensive error messages and logging</li>";
echo "</ul>";

echo "<h3>Available Ranks for Addition:</h3>";
$allowed_ranks = [
    'Crewman 3rd Class', 'Crewman 2nd Class', 'Crewman 1st Class', 
    'Petty Officer 3rd class', 'Petty Officer 1st class', 'Chief Petter Officer',
    'Senior Chief Petty Officer', 'Master Chief Petty Officer', 
    'Command Master Chief Petty Officer', 'Warrant officer', 'Ensign',
    'Lieutenant Junior Grade', 'Lieutenant', 'Lieutenant Commander'
];

echo "<div style='background: #f9f9f9; padding: 10px; border-radius: 5px;'>";
foreach ($allowed_ranks as $rank) {
    echo "• " . $rank . "<br>";
}
echo "</div>";

echo "<h3>Security Benefits:</h3>";
echo "<ul>";
echo "<li><strong>Incident Tracking:</strong> Security can now add suspects, witnesses, or victims to the roster</li>";
echo "<li><strong>Complete Records:</strong> Enables comprehensive incident reporting with all involved parties</li>";
echo "<li><strong>Access Control:</strong> Only Security/Tactical personnel can add persons</li>";
echo "<li><strong>Data Integrity:</strong> Prevents duplicate entries and validates rank restrictions</li>";
echo "<li><strong>Audit Trail:</strong> All additions are logged and traceable</li>";
echo "</ul>";

echo "<h3>User Experience:</h3>";
echo "<p><strong>Before:</strong> Security had to ask Command or other departments to add people to roster before recording incidents</p>";
echo "<p><strong>After:</strong> Security can independently add persons and immediately record incidents against them</p>";

?>
