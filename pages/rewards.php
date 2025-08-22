<?php
require_once '../includes/config.php';

// Update last active timestamp for current character
updateLastActive();

// Fetch all available awards from database
$awards_query = "SELECT 
    name as award_name, 
    description as award_description,
    id,
    type,
    specialization,
    minimum_rank
FROM awards 
ORDER BY name ASC";

try {
    $awards_result = $pdo->query($awards_query);
    $awards = $awards_result->fetchAll(PDO::FETCH_ASSOC);
    
    // Remove duplicates by award_name if any exist
    $unique_awards = [];
    $seen_names = [];
    foreach ($awards as $award) {
        if (!in_array($award['award_name'], $seen_names)) {
            $unique_awards[] = $award;
            $seen_names[] = $award['award_name'];
        }
    }
    $awards = $unique_awards;
    
} catch (PDOException $e) {
    $awards = [];
    error_log("Awards query error: " . $e->getMessage());
}

// Group awards by type and specialization from database
$award_categories = [
    'Medals' => [],
    'Ribbons' => [],
    'Badges' => [],
    'Grades' => [],
    'Other' => []
];

foreach ($awards as $award) {
    $type = $award['type'] ?? 'Other';
    $name = $award['award_name'];
    
    // Use the database type field for categorization
    if ($type === 'Medal') {
        $award_categories['Medals'][] = $award;
    } elseif ($type === 'Ribbon') {
        $award_categories['Ribbons'][] = $award;
    } elseif ($type === 'Badge') {
        $award_categories['Badges'][] = $award;
    } elseif ($type === 'Grade') {
        $award_categories['Grades'][] = $award;
    } else {
        $award_categories['Other'][] = $award;
    }
}

// Remove empty categories
$award_categories = array_filter($award_categories, function($category) {
    return !empty($category);
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>USS-Serenity 74714 - Rewards & Commendations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../assets/classic.css">
    <style>
        .awards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .award-card {
            background: rgba(0,0,0,0.6);
            border: 2px solid var(--blue);
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .award-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(85, 102, 255, 0.3);
            border-color: var(--gold);
        }
        
        .award-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--blue), var(--gold));
        }
        
        .award-name {
            color: var(--gold);
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-align: center;
            border-bottom: 1px solid var(--blue);
            padding-bottom: 0.5rem;
        }
        
        .award-description {
            color: var(--bluey);
            line-height: 1.5;
            text-align: justify;
        }
        
        .category-section {
            margin: 3rem 0;
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            padding: 2rem;
            border: 2px solid var(--orange);
        }
        
        .category-title {
            color: var(--orange);
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 3px solid var(--orange);
            padding-bottom: 1rem;
        }
        
        .search-container {
            margin: 2rem 0;
            text-align: center;
        }
        
        .search-input {
            background: black;
            border: 2px solid var(--blue);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1rem;
            width: 100%;
            max-width: 400px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }
        
        .total-awards {
            text-align: center;
            color: var(--gold);
            font-size: 1.2rem;
            margin: 1rem 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <section class="wrap-standard" id="column-3">
        <div class="wrap">
            <div class="left-frame-top">
                <button onclick="playSoundAndRedirect('audio2', '../index.php')" class="panel-1-button">LCARS</button>
                <div class="panel-2">74<span class="hop">-714000</span></div>
            </div>
            <div class="right-frame-top">
                <div class="banner">USS-SERENITY &#149; REWARDS</div>
                <div class="data-cascade-button-group">
                    <div class="data-cascade-wrapper" id="default">
                        <div class="data-column">
                            <div class="dc-row-1">AWARDS</div>
                            <div class="dc-row-1">SYSTEM</div>
                            <div class="dc-row-2">ACTIVE</div>
                            <div class="dc-row-3">COMMEND</div>
                            <div class="dc-row-3">ATIONS</div>
                            <div class="dc-row-4">MERIT</div>
                            <div class="dc-row-5">HONOR</div>
                            <div class="dc-row-6">SERVICE</div>
                            <div class="dc-row-7">VALOR</div>
                        </div>
                        <div class="data-column">
                            <div class="dc-row-1">TOTAL</div>
                            <div class="dc-row-1"><?php echo count($awards); ?></div>
                            <div class="dc-row-2">AWARDS</div>
                            <div class="dc-row-3">AVAIL</div>
                            <div class="dc-row-3">ABLE</div>
                            <div class="dc-row-4">STATUS</div>
                            <div class="dc-row-5">READY</div>
                            <div class="dc-row-6">ACCESS</div>
                            <div class="dc-row-7">GRANTED</div>
                        </div>
                        <div class="data-column">
                            <div class="dc-row-1">STARFLEET</div>
                            <div class="dc-row-1">COMMAND</div>
                            <div class="dc-row-2">APPROVED</div>
                            <div class="dc-row-3">SYSTEM</div>
                            <div class="dc-row-3">ONLINE</div>
                            <div class="dc-row-4">DATABASE</div>
                            <div class="dc-row-5">ACTIVE</div>
                            <div class="dc-row-6">RECORDS</div>
                            <div class="dc-row-7">READY</div>
                        </div>
                    </div>                
                    <nav> 
                        <button onclick="playSoundAndRedirect('audio2', '../index.php')" style="background-color: var(--blue);">HOME</button>
                        <button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--red);">ROSTER</button>
                        <button onclick="playSoundAndRedirect('audio2', 'command.php')" style="background-color: var(--orange);">COMMAND</button>
                        <button onclick="playSoundAndRedirect('audio2', 'awards_management.php')" style="background-color: var(--gold);">MANAGE</button>
                    </nav>
                </div>
                <div class="bar-panel first-bar-panel">
                    <div class="bar-1"></div>
                    <div class="bar-2"></div>
                    <div class="bar-3"></div>
                    <div class="bar-4"></div>
                    <div class="bar-5"></div>
                </div>
            </div>
        </div>
        <div class="wrap" id="gap">
            <div class="left-frame">
                <button onclick="topFunction(); playSoundAndRedirect('audio4', '#')" id="topBtn"><span class="hop">screen</span> top</button>
                <div>
                    <div class="panel-3">SYS<span class="hop">-STATUS</span></div>
                    <div class="panel-4">PWR<span class="hop">-ONLINE</span></div>
                    <div class="panel-5">NAV<span class="hop">-READY</span></div>
                    <div class="panel-6">COM<span class="hop">-ACTIVE</span></div>
                    <div class="panel-7">SEC<span class="hop">-GREEN</span></div>
                    <div class="panel-8">MED<span class="hop">-READY</span></div>
                    <div class="panel-9">ENG<span class="hop">-NOMINAL</span></div>
                </div>
                <div>
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
                    <h1>🏅 Starfleet Rewards & Commendations</h1>
                    <h2>USS-Serenity NCC-74714 &#149; Awards Database</h2>
                    
                    <div class="total-awards">
                        📊 Total Available Awards: <?php echo count($awards); ?>
                    </div>
                    
                    <div class="search-container">
                        <input type="text" 
                               id="searchInput" 
                               class="search-input" 
                               placeholder="🔍 Search awards by name or description..."
                               onkeyup="filterAwards()">
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); padding: 2rem; border-radius: 15px; border: 2px solid var(--gold); margin: 2rem 0; text-align: center;">
                        <h3 style="color: var(--gold); margin-bottom: 1rem;">ℹ️ About This System</h3>
                        <p style="color: var(--bluey); line-height: 1.6;">
                            This database contains all awards and commendations available for crew members of the USS-Serenity. 
                            Awards are granted by Command personnel in recognition of exceptional service, valor, and dedication to Starfleet principles.
                        </p>
                        <p style="color: var(--gold); font-weight: bold; margin-top: 1rem;">
                            🌟 Have you witnessed exceptional service? <a href="command.php" style="color: var(--orange);">Recommend an award!</a>
                        </p>
                    </div>

                    <?php if (empty($awards)): ?>
                        <div style="text-align: center; color: var(--red); font-size: 1.2rem; margin: 3rem 0;">
                            ⚠️ No awards found in the database.
                        </div>
                    <?php else: ?>
                        <?php foreach ($award_categories as $category_name => $category_awards): ?>
                            <?php if (!empty($category_awards)): ?>
                                <div class="category-section" data-category="<?php echo strtolower($category_name); ?>">
                                    <h2 class="category-title"><?php echo $category_name; ?> Awards</h2>
                                    <div class="awards-grid">
                                        <?php foreach ($category_awards as $award): ?>
                                            <div class="award-card" data-award-name="<?php echo strtolower($award['award_name']); ?>" data-award-description="<?php echo strtolower($award['award_description']); ?>">
                                                <div class="award-name">
                                                    🏅 <?php echo htmlspecialchars($award['award_name']); ?>
                                                </div>
                                                <?php if (!empty($award['specialization'])): ?>
                                                <div style="color: var(--orange); font-size: 0.9rem; margin-bottom: 0.5rem; text-align: center;">
                                                    📋 <?php echo htmlspecialchars($award['specialization']); ?>
                                                </div>
                                                <?php endif; ?>
                                                <div class="award-description">
                                                    <?php echo htmlspecialchars($award['award_description']); ?>
                                                </div>
                                                <?php if (!empty($award['minimum_rank'])): ?>
                                                <div style="color: var(--blue); font-size: 0.8rem; margin-top: 1rem; text-align: center; border-top: 1px solid var(--blue); padding-top: 0.5rem;">
                                                    👤 Minimum Rank: <?php echo htmlspecialchars($award['minimum_rank']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </main>
                <footer>
                    USS-Serenity NCC-74714 &copy; 2401 Starfleet Command<br>
                    Awards Database - Honoring Excellence in Service
                </footer> 
            </div>
        </div>
    </section>
    
    <script>
        function filterAwards() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const awardCards = document.querySelectorAll('.award-card');
            const categories = document.querySelectorAll('.category-section');
            
            let visibleCount = 0;
            
            // Show/hide individual award cards
            awardCards.forEach(card => {
                const name = card.getAttribute('data-award-name');
                const description = card.getAttribute('data-award-description');
                
                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide category sections based on whether they have visible awards
            categories.forEach(category => {
                const visibleCards = category.querySelectorAll('.award-card[style="display: block;"], .award-card:not([style*="display: none"])');
                if (searchTerm === '' || visibleCards.length > 0) {
                    category.style.display = 'block';
                } else {
                    category.style.display = 'none';
                }
            });
            
            // If search term is empty, reset to show all
            if (searchTerm === '') {
                awardCards.forEach(card => card.style.display = 'block');
                categories.forEach(category => category.style.display = 'block');
            }
        }
        
        // Add sound effects
        function playSoundAndRedirect(audioId, url) {
            const audio = document.getElementById ? document.getElementById(audioId) : null;
            if (audio) {
                audio.play();
                setTimeout(() => {
                    window.location.href = url;
                }, 200);
            } else {
                window.location.href = url;
            }
        }
    </script>
    
    <!-- Audio elements for LCARS sounds -->
    <audio id="audio1" src="../assets/beep1.mp3" preload="auto"></audio>
    <audio id="audio2" src="../assets/beep2.mp3" preload="auto"></audio>
    <audio id="audio3" src="../assets/beep3.mp3" preload="auto"></audio>
    <audio id="audio4" src="../assets/beep4.mp3" preload="auto"></audio>
    
    <script type="text/javascript" src="../assets/lcars.js"></script>
    <div class="headtrim"> </div>
    <div class="baseboard"> </div>
</body>
</html>
