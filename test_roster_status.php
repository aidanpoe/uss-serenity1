<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Show current roster with last_active data
    $stmt = $pdo->query("SELECT id, rank, first_name, last_name, department, last_active FROM roster ORDER BY id");
    $roster = $stmt->fetchAll();
    
    echo "<h2>Current Roster with Last Active Status</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Rank</th><th>Department</th><th>Last Active</th><th>Status</th></tr>";
    
    foreach ($roster as $member) {
        echo "<tr>";
        echo "<td>" . $member['id'] . "</td>";
        echo "<td>" . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($member['rank']) . "</td>";
        echo "<td>" . htmlspecialchars($member['department']) . "</td>";
        echo "<td>" . ($member['last_active'] ? $member['last_active'] : 'Never') . "</td>";
        
        // Calculate status
        if ($member['last_active']) {
            $last_active = new DateTime($member['last_active']);
            $now = new DateTime();
            $interval = $now->diff($last_active);
            
            if ($interval->days == 0 && $interval->h == 0 && $interval->i < 5) {
                $status = '<span style="color: green; font-weight: bold;">Online</span>';
            } elseif ($interval->days == 0) {
                $status = '<span style="color: orange;">' . $interval->h . 'h ' . $interval->i . 'm ago</span>';
            } elseif ($interval->days == 1) {
                $status = '<span style="color: orange;">1 day ago</span>';
            } elseif ($interval->days < 7) {
                $status = '<span style="color: orange;">' . $interval->days . ' days ago</span>';
            } else {
                $status = '<span style="color: red;">' . $last_active->format('M j, Y') . '</span>';
            }
        } else {
            $status = '<span style="color: gray;">Never logged in</span>';
        }
        
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
