<?php
require_once '../includes/config.php';

// Check if user has security department access, command, or is Captain
if (!hasPermission('SEC/TAC') && !hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: login.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Handle search and filter parameters
    $search_name = $_GET['search_name'] ?? '';
    $filter_species = $_GET['filter_species'] ?? '';
    $filter_rank = $_GET['filter_rank'] ?? '';
    $filter_department = $_GET['filter_department'] ?? '';
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_classification = $_GET['filter_classification'] ?? '';
    
    // Build the query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search_name)) {
        $where_conditions[] = "(r.first_name LIKE ? OR r.last_name LIKE ?)";
        $params[] = "%$search_name%";
        $params[] = "%$search_name%";
    }
    
    if (!empty($filter_species)) {
        $where_conditions[] = "r.species = ?";
        $params[] = $filter_species;
    }
    
    if (!empty($filter_rank)) {
        $where_conditions[] = "r.rank = ?";
        $params[] = $filter_rank;
    }
    
    if (!empty($filter_department)) {
        $where_conditions[] = "r.department = ?";
        $params[] = $filter_department;
    }
    
    if (!empty($filter_status)) {
        $where_conditions[] = "r.status = ?";
        $params[] = $filter_status;
    }
    
    // Build the main query
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get filtered roster with criminal record counts
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COUNT(cr.id) as total_criminal_records,
               COUNT(CASE WHEN cr.status = 'Under Investigation' THEN 1 END) as open_investigations,
               COUNT(CASE WHEN cr.status LIKE 'Closed - Guilty%' THEN 1 END) as guilty_records,
               MAX(cr.created_at) as last_criminal_record,
               MAX(cr.incident_date) as last_incident_date
        FROM roster r 
        LEFT JOIN criminal_records cr ON r.id = cr.roster_id 
        $where_clause
        GROUP BY r.id
        ORDER BY r.status, total_criminal_records DESC, r.last_name, r.first_name
    ");
    $stmt->execute($params);
    $suspects = $stmt->fetchAll();
    
    // Get filter options for dropdowns
    $stmt = $pdo->prepare("SELECT DISTINCT species FROM roster ORDER BY species");
    $stmt->execute();
    $species_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT rank FROM roster ORDER BY rank");
    $stmt->execute();
    $rank_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT department FROM roster ORDER BY department");
    $stmt->execute();
    $department_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get status options
    $status_options = ['Active', 'Deceased', 'Missing', 'Transferred'];
    
    // Get classification options
    $classification_options = ['Public', 'Restricted', 'Classified'];
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-VOYAGER - Criminal Records Database</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.search-container {
			background: rgba(255, 165, 0, 0.1);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--gold);
		}
		.filter-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
			margin: 1rem 0;
		}
		.suspect-card {
			background: rgba(0,0,0,0.7);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 4px solid var(--gold);
			transition: all 0.3s ease;
		}
		.suspect-card:hover {
			background: rgba(255, 165, 0, 0.1);
			border-left-color: var(--red);
		}
		.suspect-card.high-risk {
			border-left-color: var(--red);
			background: rgba(204, 68, 68, 0.1);
		}
		.suspect-card.medium-risk {
			border-left-color: var(--orange);
			background: rgba(255, 165, 0, 0.1);
		}
		.suspect-card.clean-record {
			border-left-color: var(--green);
			background: rgba(0, 255, 0, 0.05);
		}
		.suspect-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
			gap: 1rem;
		}
		.criminal-status {
			display: inline-block;
			padding: 0.25rem 0.5rem;
			border-radius: 3px;
			font-size: 0.8rem;
			font-weight: bold;
			margin: 0.25rem;
		}
		.status-clean { background: var(--green); color: black; }
		.status-minor { background: var(--orange); color: black; }
		.status-major { background: var(--red); color: white; }
		.status-investigation { background: var(--blue); color: black; }
		.status-classified { background: var(--purple); color: white; }
		.search-stats {
			background: rgba(0,0,0,0.5);
			padding: 1rem;
			border-radius: 10px;
			margin: 1rem 0;
			text-align: center;
		}
		.classification-badge {
			font-size: 0.7rem;
			padding: 0.2rem 0.4rem;
			border-radius: 2px;
			margin-left: 0.5rem;
		}
		.class-public { background: var(--green); color: black; }
		.class-restricted { background: var(--orange); color: black; }
		.class-classified { background: var(--red); color: white; }
	</style>
</head>
<body>
	<audio id="audio1" src="../assets/beep1.mp3" preload="auto"></audio>
	<audio id="audio2" src="../assets/beep2.mp3" preload="auto"></audio>
	<audio id="audio3" src="../assets/beep3.mp3" preload="auto"></audio>
	<audio id="audio4" src="../assets/beep4.mp3" preload="auto"></audio>
	<section class="wrap-standard" id="column-3">
		<div class="wrap">
			<div class="left-frame-top">
				<button onclick="playSoundAndRedirect('audio2', '../index.php')" class="panel-1-button">LCARS</button>
				<div class="panel-2">SEC<span class="hop">-CRIM</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">CRIMINAL RECORDS &#149; SECURITY DATABASE</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')">SECURITY</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--gold);">CRIMINAL</button>
						<button onclick="playSoundAndRedirect('audio2', 'security_resolved.php')">RESOLVED</button>
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
					<div class="panel-3">CRIM<span class="hop">-DB</span></div>
					<div class="panel-4">SEARCH<span class="hop">-ACTIVE</span></div>
					<div class="panel-5">FILTER<span class="hop">-SET</span></div>
					<div class="panel-6">RECORDS<span class="hop">-<?php echo count($suspects); ?></span></div>
					<div class="panel-7">SECURE<span class="hop">-ONLY</span></div>
					<div class="panel-8">ACCESS<span class="hop">-GRANTED</span></div>
					<div class="panel-9">CLASSIFIED<span class="hop">-DATA</span></div>
				</div>
				<div>
					<div class="panel-10">STATUS<span class="hop">-READY</span></div>
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
					<h1>Criminal Records Database</h1>
					<h2>Security Personnel Search & Investigation Access</h2>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Search and Filter Interface -->
					<div class="search-container">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
							<h3>🔍 Criminal Records Search & Filter System</h3>
							<a href="add_criminal_record.php" style="background-color: var(--red); color: black; padding: 0.5rem 1rem; text-decoration: none; border-radius: 5px; font-weight: bold;">
								➕ Add Criminal Record
							</a>
						</div>
						<p style="color: var(--gold);"><em>⚠️ RESTRICTED ACCESS - Security/Command Personnel Only</em></p>
						
						<form method="GET" action="">
							<div class="filter-grid">
								<!-- Name Search -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Search by Name:</label>
									<input type="text" name="search_name" placeholder="First or Last Name" value="<?php echo htmlspecialchars($search_name); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
								</div>
								
								<!-- Species Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Species:</label>
									<select name="filter_species" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="">All Species</option>
										<?php foreach ($species_options as $species): ?>
										<option value="<?php echo htmlspecialchars($species); ?>" <?php echo ($filter_species === $species) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($species); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<!-- Rank Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Rank:</label>
									<select name="filter_rank" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="">All Ranks</option>
										<?php foreach ($rank_options as $rank): ?>
										<option value="<?php echo htmlspecialchars($rank); ?>" <?php echo ($filter_rank === $rank) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($rank); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<!-- Department Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Department:</label>
									<select name="filter_department" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="">All Departments</option>
										<?php foreach ($department_options as $dept): ?>
										<option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filter_department === $dept) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($dept); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								
								<!-- Status Filter -->
								<div>
									<label style="color: var(--gold); font-weight: bold;">Personnel Status:</label>
									<select name="filter_status" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--gold); border-radius: 5px;">
										<option value="">All Status</option>
										<?php foreach ($status_options as $status): ?>
										<option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filter_status === $status) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($status); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							
							<div style="text-align: center; margin: 1.5rem 0;">
								<button type="submit" style="background-color: var(--gold); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem;">
									🔍 SEARCH RECORDS
								</button>
								<a href="?" style="background-color: var(--orange); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem; text-decoration: none; display: inline-block;">
									🔄 CLEAR FILTERS
								</a>
							</div>
						</form>
					</div>
					
					<!-- Search Results -->
					<div class="search-stats">
						<h4>Search Results: <?php echo count($suspects); ?> personnel records found</h4>
						<?php if (!empty($search_name) || !empty($filter_species) || !empty($filter_rank) || !empty($filter_department) || !empty($filter_status)): ?>
						<p style="color: var(--orange);">
							Active filters: 
							<?php if (!empty($search_name)): ?>Name: "<?php echo htmlspecialchars($search_name); ?>" <?php endif; ?>
							<?php if (!empty($filter_species)): ?>Species: <?php echo htmlspecialchars($filter_species); ?> <?php endif; ?>
							<?php if (!empty($filter_rank)): ?>Rank: <?php echo htmlspecialchars($filter_rank); ?> <?php endif; ?>
							<?php if (!empty($filter_department)): ?>Department: <?php echo htmlspecialchars($filter_department); ?> <?php endif; ?>
							<?php if (!empty($filter_status)): ?>Status: <?php echo htmlspecialchars($filter_status); ?> <?php endif; ?>
						</p>
						<?php endif; ?>
					</div>
					
					<!-- Personnel Criminal Records -->
					<?php if (empty($suspects)): ?>
					<div style="background: rgba(204, 68, 68, 0.2); padding: 2rem; border-radius: 10px; text-align: center; margin: 2rem 0;">
						<h4 style="color: var(--red);">No Personnel Found</h4>
						<p>No crew members match your search criteria. Try adjusting your filters.</p>
					</div>
					<?php else: ?>
					<div class="suspect-grid">
						<?php foreach ($suspects as $suspect): ?>
						<?php 
							$risk_class = 'clean-record';
							if ($suspect['total_criminal_records'] > 0) {
								if ($suspect['guilty_records'] >= 3 || $suspect['open_investigations'] >= 2) {
									$risk_class = 'high-risk';
								} elseif ($suspect['guilty_records'] >= 1 || $suspect['open_investigations'] >= 1) {
									$risk_class = 'medium-risk';
								}
							}
						?>
						<div class="suspect-card <?php echo $risk_class; ?>">
							<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
								<div>
									<?php if ($suspect['image_path'] && file_exists('../' . $suspect['image_path'])): ?>
									<img src="../<?php echo htmlspecialchars($suspect['image_path']); ?>" alt="<?php echo htmlspecialchars($suspect['first_name'] . ' ' . $suspect['last_name']); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; float: left; margin-right: 1rem; border: 2px solid <?php echo ($risk_class === 'high-risk') ? 'var(--red)' : (($risk_class === 'medium-risk') ? 'var(--orange)' : 'var(--gold)'); ?>;">
									<?php endif; ?>
									<div>
										<h4 style="color: <?php echo ($risk_class === 'high-risk') ? 'var(--red)' : (($risk_class === 'medium-risk') ? 'var(--orange)' : 'var(--gold)'); ?>; margin: 0;">
											<?php echo htmlspecialchars($suspect['rank'] . ' ' . $suspect['first_name'] . ' ' . $suspect['last_name']); ?>
											<?php if ($suspect['status'] === 'Deceased'): ?>
											<span style="color: var(--red); font-size: 0.8rem;">💀</span>
											<?php endif; ?>
										</h4>
										<p style="margin: 0.25rem 0; color: var(--orange);">
											<?php echo htmlspecialchars($suspect['species']); ?> - <?php echo htmlspecialchars($suspect['department']); ?>
										</p>
										<?php if ($suspect['position']): ?>
										<p style="margin: 0.25rem 0; font-style: italic; color: var(--bluey);">
											<?php echo htmlspecialchars($suspect['position']); ?>
										</p>
										<?php endif; ?>
									</div>
								</div>
								
								<!-- Criminal Status Indicators -->
								<div style="text-align: right;">
									<?php if ($suspect['total_criminal_records'] == 0): ?>
									<span class="criminal-status status-clean">Clean Record</span>
									<?php else: ?>
									<span class="criminal-status status-<?php echo ($suspect['guilty_records'] >= 3) ? 'major' : (($suspect['guilty_records'] >= 1) ? 'minor' : 'investigation'); ?>">
										<?php echo $suspect['total_criminal_records']; ?> Record<?php echo $suspect['total_criminal_records'] > 1 ? 's' : ''; ?>
									</span>
									<?php endif; ?>
									<?php if ($suspect['open_investigations'] > 0): ?>
									<br><span class="criminal-status status-investigation"><?php echo $suspect['open_investigations']; ?> Open Investigation<?php echo $suspect['open_investigations'] > 1 ? 's' : ''; ?></span>
									<?php endif; ?>
								</div>
							</div>
							
							<div style="clear: both; border-top: 1px solid var(--gray); padding-top: 1rem; margin-top: 1rem;">
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.5rem; margin-bottom: 1rem;">
									<div>
										<strong style="color: var(--gold);">Total Records:</strong><br>
										<span style="color: <?php echo ($suspect['total_criminal_records'] > 0) ? 'var(--red)' : 'var(--green)'; ?>;"><?php echo $suspect['total_criminal_records']; ?></span>
									</div>
									<div>
										<strong style="color: var(--gold);">Guilty Verdicts:</strong><br>
										<span style="color: <?php echo ($suspect['guilty_records'] > 0) ? 'var(--red)' : 'var(--green)'; ?>;">
											<?php echo $suspect['guilty_records']; ?>
										</span>
									</div>
									<div>
										<strong style="color: var(--gold);">Open Cases:</strong><br>
										<span style="color: <?php echo ($suspect['open_investigations'] > 0) ? 'var(--orange)' : 'var(--green)'; ?>;">
											<?php echo $suspect['open_investigations']; ?>
										</span>
									</div>
									<div>
										<strong style="color: var(--gold);">Last Incident:</strong><br>
										<span style="color: var(--orange);">
											<?php echo $suspect['last_incident_date'] ? formatICDateOnly($suspect['last_incident_date']) : 'None'; ?>
										</span>
									</div>
								</div>
								
								<div style="text-align: center;">
									<a href="criminal_history.php?crew_id=<?php echo $suspect['id']; ?>" style="background-color: <?php echo ($suspect['total_criminal_records'] > 0) ? 'var(--red)' : 'var(--gold)'; ?>; color: <?php echo ($suspect['total_criminal_records'] > 0) ? 'white' : 'black'; ?>; border: none; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; margin: 0.25rem; display: inline-block; font-size: 0.9rem;">
										🗂️ View Criminal Record
									</a>
									<?php if ($suspect['open_investigations'] > 0): ?>
									<a href="sec_tac.php#suspect-<?php echo $suspect['id']; ?>" style="background-color: var(--orange); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; margin: 0.25rem; display: inline-block; font-size: 0.9rem;">
										⚠️ Active Investigations
									</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					
					<!-- Quick Security Actions -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0; text-align: center;">
						<h3>Quick Security Actions</h3>
						<div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'sec_tac.php')" style="background-color: var(--gold); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								🛡️ Security Reports
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'security_resolved.php')" style="background-color: var(--green); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								✅ Resolved Cases
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'roster.php')" style="background-color: var(--orange); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								👥 Full Roster
							</button>
						</div>
					</div>
				</main>
				<footer>
					USS-VOYAGER NCC-74656 &copy; 2401 Starfleet Command<br>
					Criminal Records Database - Restricted Access - Security Personnel Only
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
