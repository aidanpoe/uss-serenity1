<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in and has appropriate permissions
if (!isLoggedIn() || !hasPermission('Command')) {
    header('Location: index.php');
    exit();
}

$success = '';
$error = '';

// Handle manual status updates
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_online':
                $playerCount = isset($_POST['player_count']) ? (int)$_POST['player_count'] : 0;
                $playerList = [];
                
                // Parse player list if provided
                if (!empty($_POST['player_list'])) {
                    $playerList = array_filter(array_map('trim', explode("\n", $_POST['player_list'])));
                }
                
                $result = updateServerStatusManually($playerCount, $playerList, true);
                $success = "Server status updated: {$playerCount} players online";
                break;
                
            case 'update_offline':
                $result = updateServerStatusManually(null, null, false);
                $success = "Server marked as offline";
                break;
                
            case 'clear_cache':
                $cacheFile = __DIR__ . '/cache/server_status.json';
                if (file_exists($cacheFile)) {
                    unlink($cacheFile);
                    $success = "Cache cleared - will use automatic detection";
                } else {
                    $error = "No cache file to clear";
                }
                break;
        }
    }
}

// Get current status
$currentStatus = getGmodPlayersOnline();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Status Admin - USS Serenity</title>
    <link rel="stylesheet" type="text/css" href="assets/classic.css">
    <style>
        .admin-form {
            background: rgba(0,0,0,0.3);
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            border: 2px solid var(--gold);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--gold);
            background: rgba(0,0,0,0.5);
            color: var(--gold);
            border-radius: 3px;
        }
        .btn {
            background-color: var(--gold);
            color: black;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 0.5rem;
        }
        .btn-red {
            background-color: var(--red);
            color: white;
        }
        .btn-blue {
            background-color: var(--blue);
            color: black;
        }
        .status-display {
            background: rgba(255, 170, 0, 0.1);
            padding: 1rem;
            border-radius: 10px;
            border: 2px solid var(--gold);
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <section class="classic">
        <div class="frame">
            <div class="left-frame">
                <div>
                    <div class="panel-1">01<span class="hop">-7109</span></div>
                    <div class="panel-2">02<span class="hop">-121903</span></div>
                    <div class="panel-3">03<span class="hop">-041969</span></div>
                    <div class="panel-4">04<span class="hop">-081974</span></div>
                    <div class="panel-5">05<span class="hop">-101277</span></div>
                    <div class="panel-6">06<span class="hop">-061985</span></div>
                    <div class="panel-7">07<span class="hop">-1201</span></div>
                    <div class="panel-8">08<span class="hop">-2020</span></div>
                    <div class="panel-9">09<span class="hop">-311</span></div>
                    <div class="panel-10">LCARS<span class="hop">-24.1</span></div>
                </div>
            </div>
            <div class="right-frame">
                <div class="bar-panel">
                    <div class="bar-6"></div>
                    <div class="bar-7"></div>
                    <div class="bar-8"></div>
                    <div class="bar-9"></div>
                    <div class="bar-10"></div>
                </div>
                <main>
                    <h1>Server Status Administration</h1>
                    <h2>Garry's Mod Server Management</h2>
                    
                    <?php if ($success): ?>
                        <div style="background: rgba(0,255,0,0.2); padding: 1rem; border-radius: 5px; color: var(--gold); margin: 1rem 0;">
                            ✅ <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div style="background: rgba(255,0,0,0.2); padding: 1rem; border-radius: 5px; color: var(--red); margin: 1rem 0;">
                            ❌ <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="status-display">
                        <h3>Current Server Status</h3>
                        <?php if (isset($currentStatus['count'])): ?>
                            <p><strong><?php echo $currentStatus['count']; ?> players online</strong></p>
                            <?php if (!empty($currentStatus['players'])): ?>
                                <p>Players: <?php echo implode(', ', array_map('htmlspecialchars', $currentStatus['players'])); ?></p>
                            <?php endif; ?>
                        <?php elseif (isset($currentStatus['message'])): ?>
                            <p><?php echo htmlspecialchars($currentStatus['message']); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($currentStatus['status'] ?? 'Unknown'); ?></p>
                        <?php if (isset($currentStatus['manual_update']) && $currentStatus['manual_update']): ?>
                            <p style="color: var(--blue);">📝 Manually updated: <?php echo htmlspecialchars($currentStatus['updated_at'] ?? 'Unknown'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="admin-form">
                        <h3>Update Server Status - Players Online</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_online">
                            
                            <div class="form-group">
                                <label for="player_count">Number of Players:</label>
                                <input type="number" id="player_count" name="player_count" min="0" max="100" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="player_list">Player Names (one per line, optional):</label>
                                <textarea id="player_list" name="player_list" rows="5" placeholder="Player1&#10;Player2&#10;Player3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn">Update Online Status</button>
                        </form>
                    </div>
                    
                    <div class="admin-form">
                        <h3>Server Management</h3>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_offline">
                            <button type="submit" class="btn btn-red">Mark Server Offline</button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="btn btn-blue">Clear Cache & Use Auto-Detection</button>
                        </form>
                    </div>
                    
                    <div style="margin: 2rem 0;">
                        <h3>Instructions</h3>
                        <ul style="color: var(--gold);">
                            <li><strong>Manual Updates:</strong> Use this when you know the current server status</li>
                            <li><strong>Player Count:</strong> Enter the number of players currently online</li>
                            <li><strong>Player Names:</strong> Optionally list player names (one per line)</li>
                            <li><strong>Cache Duration:</strong> Manual updates last for 1 minute before reverting to auto-detection</li>
                            <li><strong>Auto-Detection:</strong> System automatically tries to detect server status when no manual update is active</li>
                        </ul>
                    </div>
                    
                    <div style="margin: 2rem 0;">
                        <a href="index.php" style="color: var(--blue);">← Return to Homepage</a> |
                        <a href="test_gmod.php" style="color: var(--blue);">Test Server Connection</a>
                    </div>
                </main>
                
                <footer>
                    USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
                    LCARS Inspired Website Template by <a href="https://www.thelcars.com">www.TheLCARS.com</a>.
                </footer>
            </div>
        </div>
    </section>
    <script type="text/javascript" src="assets/lcars.js"></script>
</body>
</html>
