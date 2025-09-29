<?php
/**
 * Showcase Configuration
 * 
 * This file enables showcase mode for portfolio demonstration purposes.
 */

// Set showcase mode flag
define('SHOWCASE_MODE', true);

// Initialize showcase mode
if (SHOWCASE_MODE) {
    
    // Mock session data for showcase
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set up fake session data for showcase
    if (!isset($_SESSION['showcase_initialized'])) {
        $_SESSION['user_id'] = 999999;
        $_SESSION['steamid'] = '76561198000000000';
        $_SESSION['first_name'] = 'John';
        $_SESSION['last_name'] = 'Doe';
        $_SESSION['rank'] = 'Captain';
        $_SESSION['position'] = 'Commanding Officer';
        $_SESSION['department'] = 'Command';
        $_SESSION['roster_department'] = 'Command';
        $_SESSION['character_id'] = 999999;
        $_SESSION['is_invisible'] = 0;
        $_SESSION['showcase_initialized'] = true;
    }
    
}

// Function to check if we should prevent POST operations
function isShowcaseReadOnly() {
    return defined('SHOWCASE_MODE') && SHOWCASE_MODE;
}

// Function to show showcase notice
function showShowcaseNotice() {
    if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
        echo '<div style="background: rgba(255, 136, 0, 0.2); border: 2px solid var(--orange); border-radius: 10px; padding: 1rem; margin: 1rem 0; text-align: center;">
            <strong style="color: var(--orange);">🚧 SHOWCASE MODE 🚧</strong><br>
            <span style="color: var(--bluey);">This is a portfolio demonstration. All interactive features are disabled.</span>
        </div>';
    }
}

// Safe PDO wrapper for showcase mode
class ShowcaseSafePDO {
    private $realPdo;
    
    public function __construct($pdo) {
        $this->realPdo = $pdo;
    }
    
    public function prepare($statement) {
        // Check if this is a dangerous statement
        $statement_lower = strtolower(trim($statement));
        if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
            if (strpos($statement_lower, 'delete') !== false ||
                strpos($statement_lower, 'update') !== false ||
                strpos($statement_lower, 'insert') !== false ||
                strpos($statement_lower, 'drop') !== false ||
                strpos($statement_lower, 'truncate') !== false ||
                strpos($statement_lower, 'alter') !== false) {
                // Return a safe mock statement
                return new ShowcaseSafeStatement();
            }
        }
        // For read operations, use the real PDO
        return $this->realPdo->prepare($statement);
    }
    
    public function query($statement) {
        $statement_lower = strtolower(trim($statement));
        if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
            if (strpos($statement_lower, 'select') === 0) {
                return $this->realPdo->query($statement);
            }
            // Block all non-SELECT operations
            return new ShowcaseSafeStatement();
        }
        return $this->realPdo->query($statement);
    }
    
    public function exec($statement) {
        if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
            return true; // Fake success but do nothing
        }
        return $this->realPdo->exec($statement);
    }
    
    public function lastInsertId() {
        return $this->realPdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return true; // Always succeed but don't actually start transaction in showcase
    }
    
    public function commit() {
        return true; // Always succeed but don't actually commit in showcase
    }
    
    public function rollBack() {
        return true; // Always succeed but don't actually rollback in showcase
    }
}

// Safe PDO Statement wrapper
class ShowcaseSafeStatement {
    public function execute($params = []) {
        return true; // Always succeed but don't actually execute
    }
    
    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        // Return sample data for showcase
        return [
            'id' => 1,
            'rank' => 'Captain',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'species' => 'Human',
            'department' => 'Command',
            'position' => 'Commanding Officer'
        ];
    }
    
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        // Return sample roster data
        return [
            [
                'id' => 1,
                'rank' => 'Captain',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'species' => 'Human',
                'department' => 'Command',
                'position' => 'Commanding Officer'
            ],
            [
                'id' => 2,
                'rank' => 'Commander',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'species' => 'Human',
                'department' => 'Medical',
                'position' => 'Chief Medical Officer'
            ]
        ];
    }
    
    public function rowCount() {
        return 1;
    }
    
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR) {
        return true;
    }
    
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR) {
        return true;
    }
}

// Enhanced security for showcase mode - prevent ALL data modification attempts
if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
    // Prevent POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_GET['showcase_message'])) {
            $current_url = $_SERVER['REQUEST_URI'];
            $separator = strpos($current_url, '?') !== false ? '&' : '?';
            header("Location: {$current_url}{$separator}showcase_message=1");
            exit();
        }
    }
    
    // Prevent dangerous GET parameters
    $dangerous_params = ['delete', 'remove', 'clear', 'reset', 'destroy', 'drop', 'truncate'];
    foreach ($dangerous_params as $param) {
        if (isset($_GET[$param]) || isset($_REQUEST[$param])) {
            if (!isset($_GET['showcase_message'])) {
                $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                header("Location: {$current_url}?showcase_message=1");
                exit();
            }
        }
    }
    
    // Prevent action-based operations that might be dangerous
    if ((isset($_GET['action']) || isset($_REQUEST['action'])) && !isset($_GET['showcase_message'])) {
        $action = $_GET['action'] ?? $_REQUEST['action'] ?? '';
        if (strpos(strtolower($action), 'delete') !== false || 
            strpos(strtolower($action), 'remove') !== false ||
            strpos(strtolower($action), 'clear') !== false) {
            $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            header("Location: {$current_url}?showcase_message=1");
            exit();
        }
    }
}

// Function to display showcase POST message
function displayShowcaseMessage() {
    if (isset($_GET['showcase_message']) && $_GET['showcase_message'] == '1') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                showShowcasePostModal();
            }, 500);
        });
        
        function showShowcasePostModal() {
            // Create modal similar to existing ones
            const modal = document.createElement("div");
            modal.id = "showcasePostModal";
            modal.style.cssText = "display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;";
            
            modal.innerHTML = `
                <div style="background: linear-gradient(135deg, #000000, #1a1a2e); border: 3px solid var(--orange); border-radius: 15px; padding: 2rem; max-width: 500px; text-align: center; box-shadow: 0 0 30px rgba(255, 136, 0, 0.5);">
                    <div style="border-bottom: 2px solid var(--orange); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                        <h3 style="color: var(--orange); margin: 0; font-size: 1.3rem;">LCARS - SHOWCASE MODE</h3>
                    </div>
                    <div style="margin: 1.5rem 0; color: var(--bluey); font-size: 1rem; line-height: 1.5;">
                        <p style="margin: 0;">This is a portfolio demonstration site.</p>
                        <p style="margin: 0.5rem 0 0 0; font-weight: bold;">Data modification features are disabled for security.</p>
                    </div>
                    <div style="margin-top: 2rem;">
                        <button onclick="closeShowcasePostModal()" style="background: var(--blue); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;">ACKNOWLEDGE</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Fade in
            modal.style.opacity = "0";
            setTimeout(() => {
                modal.style.transition = "opacity 0.3s ease";
                modal.style.opacity = "1";
            }, 10);
        }
        
        function closeShowcasePostModal() {
            const modal = document.getElementById("showcasePostModal");
            if (modal) {
                modal.style.transition = "opacity 0.3s ease";
                modal.style.opacity = "0";
                setTimeout(() => {
                    modal.remove();
                    // Remove the showcase_message parameter from URL
                    const url = new URL(window.location);
                    url.searchParams.delete("showcase_message");
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }, 300);
            }
        }
        </script>';
    }
}
?>