<?php
require_once 'includes/config.php';

echo "<h1>Garry's Mod Server Test</h1>";
echo "<p>Testing connection to 46.4.12.78:27015...</p>";

// Test basic connectivity first
echo "<h2>1. Basic Connectivity Test</h2>";
$ip = '46.4.12.78';
$port = 27015;
$timeout = 5;

// Try UDP socket creation
$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if ($socket) {
    echo "<p>✅ UDP socket created successfully</p>";
    
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
    
    $testPacket = "test";
    $result = socket_sendto($socket, $testPacket, strlen($testPacket), 0, $ip, $port);
    
    if ($result !== false) {
        echo "<p>✅ Can send UDP packets to server</p>";
    } else {
        echo "<p>❌ Cannot send UDP packets: " . socket_strerror(socket_last_error()) . "</p>";
    }
    
    socket_close($socket);
} else {
    echo "<p>❌ Failed to create UDP socket: " . socket_strerror(socket_last_error()) . "</p>";
}

// Test with different query methods
echo "<h2>2. Source Engine Queries</h2>";

$start_time = microtime(true);
$gmodData = getGmodPlayersOnline();
$end_time = microtime(true);
$query_time = round(($end_time - $start_time) * 1000, 2);

echo "<p><strong>Query Time:</strong> {$query_time}ms</p>";

if (isset($gmodData['error'])) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($gmodData['error']) . "</p>";
    
    if (isset($gmodData['server_reachable'])) {
        echo "<p style='color: orange;'>🟡 Server is online but has queries disabled</p>";
        echo "<p>This is common for Garry's Mod servers for security reasons.</p>";
    }
} else {
    echo "<p style='color: green;'><strong>Success!</strong> Found {$gmodData['count']} player(s) online</p>";
    
    if (isset($gmodData['info_only'])) {
        echo "<p style='color: orange;'>ℹ️ Only player count available (player names not accessible)</p>";
    }
    
    if ($gmodData['count'] > 0 && !empty($gmodData['players'])) {
        echo "<h3>Players Online:</h3>";
        echo "<ul>";
        foreach ($gmodData['players'] as $player) {
            echo "<li>" . htmlspecialchars($player) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Server:</strong> " . htmlspecialchars($gmodData['server']) . "</p>";
}

// Test alternative method
echo "<h2>3. Alternative Connection Test</h2>";
$curlResult = getGmodPlayersViaCurl();
if ($curlResult) {
    echo "<p style='color: green;'>✅ HTTP API method worked!</p>";
    echo "<p>Found {$curlResult['count']} players via HTTP API</p>";
} else {
    echo "<p style='color: orange;'>ℹ️ No HTTP API available (this is normal)</p>";
}

// Network troubleshooting info
echo "<h2>4. Troubleshooting Information</h2>";
echo "<p><strong>Common Issues:</strong></p>";
echo "<ul>";
echo "<li><strong>Server queries disabled:</strong> Many Garry's Mod servers disable queries for security</li>";
echo "<li><strong>Firewall blocking:</strong> UDP packets might be blocked by hosting provider</li>";
echo "<li><strong>Server configuration:</strong> Server might require specific query settings</li>";
echo "</ul>";

echo "<p><strong>Possible Solutions:</strong></p>";
echo "<ul>";
echo "<li>Ask server admin to enable <code>sv_allowdownload 1</code> and <code>sv_allowupload 1</code></li>";
echo "<li>Check if server has <code>sv_lan 0</code> set</li>";
echo "<li>Verify server is actually running on port 27015</li>";
echo "<li>Consider using a web-based server status API instead</li>";
echo "</ul>";

echo '<p><a href="index.php">Return to Homepage</a> | <a href="">Refresh Test</a>';

// Show admin panel link if user has command permission
if (hasPermission('Command')) {
    echo ' | <a href="server_admin.php">Server Admin Panel</a>';
}

echo '</p>';
?>
