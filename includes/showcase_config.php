<?php
/**
 * Showcase Configuration
 * 
 * This file modifies the authentication system to make the website fully accessible
 * in read-only mode for portfolio demonstration purposes.
 */

// Set showcase mode flag
define('SHOWCASE_MODE', true);

// Override authentication functions for showcase mode
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
    
    // Override isLoggedIn function
    function isLoggedIn() {
        return true; // Always return true in showcase mode
    }
    
    // Override hasPermission function
    function hasPermission($required_department) {
        return true; // Always return true in showcase mode
    }
    
    // Override canEditPersonnelFiles function
    function canEditPersonnelFiles() {
        return true; // Always return true in showcase mode
    }
    
    // Override isDepartmentHead function
    function isDepartmentHead($department) {
        return true; // Always return true in showcase mode
    }
    
    // Override canPromoteDemote function
    function canPromoteDemote($department = null) {
        return true; // Always return true in showcase mode
    }
    
    // Override requirePermission function to do nothing
    function requirePermission($required_department) {
        return; // Do nothing in showcase mode
    }
    
    // Override getUserDepartment function
    function getUserDepartment() {
        return 'Command'; // Always return Command in showcase mode
    }
    
    // Override getCurrentCharacter function
    function getCurrentCharacter() {
        return [
            'id' => 999999,
            'character_id' => 999999,
            'rank' => 'Captain',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'species' => 'Human',
            'roster_department' => 'Command',
            'position' => 'Commanding Officer',
            'image_path' => null,
            'character_name' => 'Captain John Doe',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Override getUserCharacters function
    function getUserCharacters($user_id = null) {
        return [
            [
                'id' => 999999,
                'rank' => 'Captain',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'species' => 'Human',
                'department' => 'Command',
                'position' => 'Commanding Officer',
                'image_path' => null,
                'character_name' => 'Captain John Doe',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'is_current_character' => 1
            ]
        ];
    }
    
    // Override canCreateCharacter function
    function canCreateCharacter($user_id = null) {
        return false; // Don't allow creating characters in showcase mode
    }
    
    // Override isInvisibleUser function
    function isInvisibleUser() {
        return false; // User is visible in showcase mode
    }
    
    // Override isUserInvisible function
    function isUserInvisible($user_id) {
        return false; // All users visible in showcase mode
    }
    
    // Override switchCharacter function
    function switchCharacter($character_id) {
        return true; // Always successful in showcase mode
    }
    
    // Override updateLastActive function to do nothing
    function updateLastActive() {
        return; // Do nothing in showcase mode
    }
    
    // Override logAuditorAction function to do nothing
    function logAuditorAction($character_id, $action_type, $table_name, $record_id, $additional_data = null) {
        return true; // Always successful but do nothing in showcase mode
    }
    
    // Override getAuditorActivityLog function
    function getAuditorActivityLog($limit = 50) {
        return []; // Return empty array in showcase mode
    }
    
    // Override getCurrentUserFullName function
    function getCurrentUserFullName() {
        return 'Captain John Doe';
    }
    
    // Override getConnection function to provide mock data if needed
    function getConnection() {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                DB_USERNAME, 
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            );
            return $pdo;
        } catch(PDOException $e) {
            // In showcase mode, create a mock PDO-like object
            return new ShowcaseMockPDO();
        }
    }
    
    // Function to check if we should prevent POST operations
    function isShowcaseReadOnly() {
        return SHOWCASE_MODE;
    }
    
    // Function to show showcase notice
    function showShowcaseNotice() {
        if (SHOWCASE_MODE) {
            echo '<div style="background: rgba(255, 136, 0, 0.2); border: 2px solid var(--orange); border-radius: 10px; padding: 1rem; margin: 1rem 0; text-align: center;">
                <strong style="color: var(--orange);">🚧 SHOWCASE MODE 🚧</strong><br>
                <span style="color: var(--bluey);">This is a portfolio demonstration. All interactive features are disabled.</span>
            </div>';
        }
    }
}

// Mock PDO class for showcase mode
class ShowcaseMockPDO {
    public function prepare($statement) {
        return new ShowcaseMockStatement();
    }
    
    public function query($statement) {
        return new ShowcaseMockStatement();
    }
    
    public function exec($statement) {
        return true;
    }
    
    public function lastInsertId() {
        return 999999;
    }
    
    public function beginTransaction() {
        return true;
    }
    
    public function commit() {
        return true;
    }
    
    public function rollBack() {
        return true;
    }
}

// Mock PDO Statement class
class ShowcaseMockStatement {
    private $mockData = [];
    
    public function execute($params = []) {
        return true;
    }
    
    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        // Return sample data based on common queries
        return [
            'id' => 999999,
            'user_id' => 999999,
            'character_id' => 999999,
            'rank' => 'Captain',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'species' => 'Human',
            'department' => 'Command',
            'position' => 'Commanding Officer',
            'image_path' => null,
            'character_name' => 'Captain John Doe',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'is_current_character' => 1,
            'steamid' => '76561198000000000'
        ];
    }
    
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        // Return array of sample data
        return [
            [
                'id' => 1,
                'rank' => 'Captain',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'species' => 'Human',
                'department' => 'Command',
                'position' => 'Commanding Officer',
                'image_path' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'rank' => 'Commander',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'species' => 'Human',
                'department' => 'Medical',
                'position' => 'Chief Medical Officer',
                'image_path' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'rank' => 'Lieutenant Commander',
                'first_name' => 'Mike',
                'last_name' => 'Johnson',
                'species' => 'Human',
                'department' => 'Engineering',
                'position' => 'Chief Engineer',
                'image_path' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s')
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

// Add POST prevention for showcase mode
if (SHOWCASE_MODE && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Instead of processing POST, show a showcase message and redirect back
    if (!isset($_GET['showcase_message'])) {
        $current_url = $_SERVER['REQUEST_URI'];
        $separator = strpos($current_url, '?') !== false ? '&' : '?';
        header("Location: {$current_url}{$separator}showcase_message=1");
        exit();
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