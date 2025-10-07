<?php
require_once '../includes/config.php';

// Check if user has Command access or is Captain - using same logic as admin_management.php
if (!hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: ../login.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get user activity summary
    $page = $_GET['page'] ?? 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Search functionality
    $search = $_GET['search'] ?? '';
    $whereClause = '';
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE u.username LIKE ? OR u.steam_id LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    
    // Get users with login information
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.steam_id,
            u.department,
            u.active,
            u.created_at,
            u.last_login,
            r.first_name,
            r.last_name,
            r.rank,
            CASE 
                WHEN u.last_login IS NULL THEN 'Never logged in'
                WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'Online now'
                WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Today'
                WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'This week'
                WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'This month'
                WHEN u.last_login > DATE_SUB(NOW(), INTERVAL 12 MONTH) THEN 'This year'
                ELSE 'Over a year ago'
            END as activity_status,
            TIMESTAMPDIFF(DAY, u.last_login, NOW()) as days_since_login
        FROM users u
        LEFT JOIN roster r ON u.active_character_id = r.id
        $whereClause
        ORDER BY u.last_login DESC, u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $searchParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($searchParams);
    $users = $stmt->fetchAll();
    
    // Get total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereClause");
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
    
    // Get activity statistics
    $stats = [
        'total_users' => $totalUsers,
        'active_today' => 0,
        'active_week' => 0,
        'active_month' => 0,
        'inactive_24_months' => 0,
        'never_logged_in' => 0
    ];
    
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as active_today,
            SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_week,
            SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_month,
            SUM(CASE WHEN last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 24 MONTH) THEN 1 ELSE 0 END) as inactive_24_months,
            SUM(CASE WHEN last_login IS NULL THEN 1 ELSE 0 END) as never_logged_in
        FROM users
    ");
    
    $statsResult = $statsStmt->fetch();
    $stats = array_merge($stats, $statsResult ?: []);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Logs - USS Voyager Admin</title>
    <link rel="stylesheet" href="../assets/lcars.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: rgba(255, 153, 0, 0.1);
            border: 2px solid var(--gold);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--gold);
        }
        .stat-label {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .search-box {
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid var(--blue);
        }
        .search-input {
            width: 300px;
            padding: 0.5rem;
            margin-right: 1rem;
            background: black;
            color: var(--blue);
            border: 1px solid var(--blue);
            border-radius: 5px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background: rgba(0, 0, 0, 0.8);
        }
        .user-table th,
        .user-table td {
            padding: 1rem;
            text-align: left;
            border: 1px solid var(--blue);
        }
        .user-table th {
            background: var(--blue);
            color: black;
            font-weight: bold;
        }
        .status-online { color: #00ff00; font-weight: bold; }
        .status-recent { color: #ffff00; }
        .status-inactive { color: #ff6600; }
        .status-very-inactive { color: #ff0000; }
        .status-never { color: #666666; }
        .pagination {
            text-align: center;
            margin: 2rem 0;
        }
        .pagination a {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            background: var(--blue);
            color: black;
            text-decoration: none;
            border-radius: 5px;
        }
        .pagination .current {
            background: var(--gold);
            font-weight: bold;
        }
        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 10px;
            border: 2px solid var(--gold);
            background: rgba(255, 153, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>🔍 User Activity & Login Logs</h1>
        <p>Administrative interface for monitoring user login activity and account status.</p>
        
        <?php if (isset($error)): ?>
        <div class="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <!-- Activity Statistics -->
        <h2>📊 Activity Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_today']; ?></div>
                <div class="stat-label">Active Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_week']; ?></div>
                <div class="stat-label">Active This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_month']; ?></div>
                <div class="stat-label">Active This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['inactive_24_months']; ?></div>
                <div class="stat-label">Inactive 24+ Months</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['never_logged_in']; ?></div>
                <div class="stat-label">Never Logged In</div>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" class="search-input" placeholder="Search username or Steam ID..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="action-button">Search</button>
                <?php if (!empty($search)): ?>
                <a href="?<?php echo http_build_query(['page' => 1]); ?>" class="action-button">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- User Activity Table -->
        <h2>👥 User Login Activity</h2>
        <table class="user-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Character</th>
                    <th>Department</th>
                    <th>Last Login</th>
                    <th>Activity Status</th>
                    <th>Account Created</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                        <small style="color: #999;">Steam: <?php echo htmlspecialchars($user['steam_id']); ?></small>
                    </td>
                    <td>
                        <?php if ($user['first_name'] && $user['last_name']): ?>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?><br>
                            <small style="color: #999;"><?php echo htmlspecialchars($user['rank'] ?: 'No rank'); ?></small>
                        <?php else: ?>
                            <span style="color: #666;">No character</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['department'] ?: 'None'); ?></td>
                    <td>
                        <?php if ($user['last_login']): ?>
                            <?php echo date('M j, Y H:i', strtotime($user['last_login'])); ?><br>
                            <small style="color: #999;">
                                <?php 
                                if ($user['days_since_login'] == 0) {
                                    echo 'Today';
                                } elseif ($user['days_since_login'] == 1) {
                                    echo '1 day ago';
                                } else {
                                    echo $user['days_since_login'] . ' days ago';
                                }
                                ?>
                            </small>
                        <?php else: ?>
                            <span style="color: #666;">Never</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $statusClass = 'status-inactive';
                        switch ($user['activity_status']) {
                            case 'Online now':
                                $statusClass = 'status-online';
                                break;
                            case 'Today':
                                $statusClass = 'status-recent';
                                break;
                            case 'This week':
                            case 'This month':
                                $statusClass = 'status-inactive';
                                break;
                            case 'Never logged in':
                                $statusClass = 'status-never';
                                break;
                            default:
                                $statusClass = 'status-very-inactive';
                        }
                        ?>
                        <span class="<?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($user['activity_status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['active']): ?>
                            <span style="color: #00ff00;">Active</span>
                        <?php else: ?>
                            <span style="color: #ff6600;">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $queryParams = ['page' => $i]; if (!empty($search)) $queryParams['search'] = $search; ?>
                <a href="?<?php echo http_build_query($queryParams); ?>" 
                   class="<?php echo $i == $page ? 'current' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
        <!-- Information Panel -->
        <div class="alert">
            <h3>📋 Login Tracking Information</h3>
            <p><strong>Current System:</strong> Basic login tracking via <code>last_login</code> timestamp in users table.</p>
            <p><strong>Data Available:</strong></p>
            <ul>
                <li>Last login timestamp for each user</li>
                <li>Account creation date</li>
                <li>Current activity status</li>
                <li>Days since last login</li>
            </ul>
            <p><strong>GDPR Compliance:</strong> Login timestamps older than 12 months are automatically anonymized by the daily cleanup script.</p>
            <p><strong>Privacy:</strong> No IP addresses are logged (privacy by design).</p>
        </div>
        
        <div style="text-align: center; margin: 2rem 0;">
            <a href="../index.php" class="action-button">← Back to Main Site</a>
        </div>
    </div>
</body>
</html>
