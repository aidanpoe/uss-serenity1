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

// Add POST prevention for showcase mode
if (defined('SHOWCASE_MODE') && SHOWCASE_MODE && $_SERVER['REQUEST_METHOD'] === 'POST') {
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