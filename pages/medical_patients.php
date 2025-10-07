<?php
require_once '../includes/config.php';

// Check if user has medical department access or is Captain
if (!hasPermission('MED/SCI') && !hasPermission('Captain')) {
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
    
    // Get filtered roster with medical record counts
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COUNT(mr.id) as total_medical_records,
               COUNT(CASE WHEN mr.status != 'Resolved' AND mr.status != 'Deceased' THEN 1 END) as open_medical_records,
               MAX(mr.created_at) as last_medical_record
        FROM roster r 
        LEFT JOIN medical_records mr ON r.id = mr.roster_id 
        $where_clause
        GROUP BY r.id
        ORDER BY r.status, r.last_name, r.first_name
    ");
    $stmt->execute($params);
    $patients = $stmt->fetchAll();
    
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
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>USS-VOYAGER - Medical Patient Search</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<link rel="stylesheet" type="text/css" href="../assets/classic.css">
	<style>
		.search-container {
			background: rgba(85, 102, 255, 0.1);
			padding: 2rem;
			border-radius: 15px;
			margin: 2rem 0;
			border: 2px solid var(--blue);
		}
		.filter-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 1rem;
			margin: 1rem 0;
		}
		.patient-card {
			background: rgba(0,0,0,0.7);
			padding: 1.5rem;
			border-radius: 10px;
			margin: 1rem 0;
			border-left: 4px solid var(--blue);
			transition: all 0.3s ease;
		}
		.patient-card:hover {
			background: rgba(85, 102, 255, 0.1);
			border-left-color: var(--green);
		}
		.patient-card.deceased {
			border-left-color: var(--red);
			background: rgba(204, 68, 68, 0.1);
		}
		.patient-card.missing {
			border-left-color: var(--orange);
			background: rgba(255, 165, 0, 0.1);
		}
		.patient-card.transferred {
			border-left-color: var(--bluey);
			background: rgba(102, 153, 255, 0.1);
		}
		.patient-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 1rem;
		}
		.medical-status {
			display: inline-block;
			padding: 0.25rem 0.5rem;
			border-radius: 3px;
			font-size: 0.8rem;
			font-weight: bold;
			margin: 0.25rem;
		}
		.status-green { background: var(--green); color: black; }
		.status-yellow { background: var(--orange); color: black; }
		.status-red { background: var(--red); color: black; }
		.status-active { background: var(--green); color: black; }
		.status-deceased { background: var(--red); color: white; }
		.status-missing { background: var(--orange); color: black; }
		.status-transferred { background: var(--blue); color: black; }
		.search-stats {
			background: rgba(0,0,0,0.5);
			padding: 1rem;
			border-radius: 10px;
			margin: 1rem 0;
			text-align: center;
		}
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
				<div class="panel-2">MEDICAL<span class="hop">-SEARCH</span></div>
			</div>
			<div class="right-frame-top">
				<div class="banner">PATIENT DATABASE &#149; MEDICAL SEARCH</div>
				<div class="data-cascade-button-group">
					<nav> 
						<button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
						<button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
						<button onclick="playSoundAndRedirect('audio2', 'med_sci.php')">MEDICAL</button>
						<button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--blue);">PATIENTS</button>
						<button onclick="playSoundAndRedirect('audio2', 'medical_resolved.php')">RESOLVED</button>
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
					<div class="panel-3">PATIENT<span class="hop">-DB</span></div>
					<div class="panel-4">SEARCH<span class="hop">-ACTIVE</span></div>
					<div class="panel-5">FILTER<span class="hop">-SET</span></div>
					<div class="panel-6">MEDICAL<span class="hop">-<?php echo count($patients); ?></span></div>
					<div class="panel-7">RECORDS<span class="hop">-LIVE</span></div>
					<div class="panel-8">ACCESS<span class="hop">-GRANTED</span></div>
					<div class="panel-9">REAL<span class="hop">-TIME</span></div>
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
					<h1>Medical Patient Database</h1>
					<h2>Patient Search & Medical History Access</h2>
					
					<?php if (isset($error)): ?>
					<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
						<p style="color: var(--red);"><?php echo htmlspecialchars($error); ?></p>
					</div>
					<?php endif; ?>
					
					<!-- Search and Filter Interface -->
					<div class="search-container">
						<h3>Patient Search & Filter System</h3>
						<p style="color: var(--blue);"><em>Search crew members and access their medical records</em></p>
						
						<form method="GET" action="">
							<div class="filter-grid">
								<!-- Name Search -->
								<div>
									<label style="color: var(--blue); font-weight: bold;">Search by Name:</label>
									<input type="text" name="search_name" placeholder="First or Last Name" value="<?php echo htmlspecialchars($search_name); ?>" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue); border-radius: 5px;">
								</div>
								
								<!-- Species Filter -->
								<div>
									<label style="color: var(--blue); font-weight: bold;">Species:</label>
									<select name="filter_species" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue); border-radius: 5px;">
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
									<label style="color: var(--blue); font-weight: bold;">Rank:</label>
									<select name="filter_rank" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue); border-radius: 5px;">
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
									<label style="color: var(--blue); font-weight: bold;">Department:</label>
									<select name="filter_department" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue); border-radius: 5px;">
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
									<label style="color: var(--blue); font-weight: bold;">Status:</label>
									<select name="filter_status" style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--blue); border-radius: 5px;">
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
								<button type="submit" style="background-color: var(--blue); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem;">
									🔍 SEARCH PATIENTS
								</button>
								<a href="?" style="background-color: var(--orange); color: black; border: none; padding: 0.8rem 2rem; border-radius: 5px; font-size: 1.1rem; margin: 0.5rem; text-decoration: none; display: inline-block;">
									🔄 CLEAR FILTERS
								</a>
							</div>
						</form>
					</div>
					
					<!-- Search Results -->
					<div class="search-stats">
						<h4>Search Results: <?php echo count($patients); ?> patients found</h4>
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
					
					<!-- Patient Results -->
					<?php if (empty($patients)): ?>
					<div style="background: rgba(204, 68, 68, 0.2); padding: 2rem; border-radius: 10px; text-align: center; margin: 2rem 0;">
						<h4 style="color: var(--red);">No Patients Found</h4>
						<p>No crew members match your search criteria. Try adjusting your filters.</p>
					</div>
					<?php else: ?>
					<div class="patient-grid">
						<?php foreach ($patients as $patient): ?>
						<div class="patient-card <?php echo strtolower($patient['status'] ?? 'active'); ?>">
							<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
								<div>
									<?php if ($patient['image_path'] && file_exists('../' . $patient['image_path'])): ?>
									<img src="../<?php echo htmlspecialchars($patient['image_path']); ?>" alt="<?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; float: left; margin-right: 1rem; border: 2px solid <?php echo ($patient['status'] === 'Deceased') ? 'var(--red)' : 'var(--blue)'; ?>;">
									<?php endif; ?>
									<div>
										<h4 style="color: <?php echo ($patient['status'] === 'Deceased') ? 'var(--red)' : 'var(--blue)'; ?>; margin: 0;">
											<?php echo htmlspecialchars($patient['rank'] . ' ' . $patient['first_name'] . ' ' . $patient['last_name']); ?>
											<?php if ($patient['status'] === 'Deceased'): ?>
											<span style="color: var(--red); font-size: 0.8rem;">💀</span>
											<?php endif; ?>
										</h4>
										<p style="margin: 0.25rem 0; color: var(--orange);">
											<?php echo htmlspecialchars($patient['species']); ?> - <?php echo htmlspecialchars($patient['department']); ?>
										</p>
										<?php if ($patient['position']): ?>
										<p style="margin: 0.25rem 0; font-style: italic; color: var(--bluey);">
											<?php echo htmlspecialchars($patient['position']); ?>
										</p>
										<?php endif; ?>
									</div>
								</div>
								
								<!-- Status and Medical Indicators -->
								<div style="text-align: right;">
									<span class="medical-status status-<?php echo strtolower($patient['status'] ?? 'active'); ?>">
										<?php echo $patient['status'] ?? 'Active'; ?>
									</span>
									<br>
									<?php if ($patient['status'] !== 'Deceased'): ?>
										<?php if ($patient['total_medical_records'] == 0): ?>
										<span class="medical-status status-green">Clean Record</span>
										<?php elseif ($patient['open_medical_records'] == 0): ?>
										<span class="medical-status status-green">All Resolved</span>
										<?php elseif ($patient['open_medical_records'] <= 2): ?>
										<span class="medical-status status-yellow"><?php echo $patient['open_medical_records']; ?> Open</span>
										<?php else: ?>
										<span class="medical-status status-red"><?php echo $patient['open_medical_records']; ?> Open</span>
										<?php endif; ?>
									<?php else: ?>
										<span class="medical-status status-deceased">Final Record</span>
									<?php endif; ?>
								</div>
							</div>
							
							<div style="clear: both; border-top: 1px solid var(--gray); padding-top: 1rem; margin-top: 1rem;">
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.5rem; margin-bottom: 1rem;">
									<div>
										<strong style="color: var(--blue);">Total Records:</strong><br>
										<span style="color: var(--green);"><?php echo $patient['total_medical_records']; ?></span>
									</div>
									<div>
										<strong style="color: var(--blue);">Open Cases:</strong><br>
										<span style="color: <?php echo $patient['open_medical_records'] > 0 ? 'var(--red)' : 'var(--green)'; ?>;">
											<?php echo $patient['open_medical_records']; ?>
										</span>
									</div>
									<div>
										<strong style="color: var(--blue);">Last Record:</strong><br>
										<span style="color: var(--orange);">
											<?php echo $patient['last_medical_record'] ? formatICDateOnly($patient['last_medical_record']) : 'None'; ?>
										</span>
									</div>
								</div>
								
								<div style="text-align: center;">
									<a href="medical_history.php?crew_id=<?php echo $patient['id']; ?>" style="background-color: <?php echo ($patient['status'] === 'Deceased') ? 'var(--red)' : 'var(--blue)'; ?>; color: <?php echo ($patient['status'] === 'Deceased') ? 'white' : 'black'; ?>; border: none; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; margin: 0.25rem; display: inline-block; font-size: 0.9rem;">
										<?php echo ($patient['status'] === 'Deceased') ? '💀 Final Medical Record' : '📋 View Medical History'; ?>
									</a>
									<?php if ($patient['status'] !== 'Deceased' && $patient['open_medical_records'] > 0): ?>
									<a href="med_sci.php#patient-<?php echo $patient['id']; ?>" style="background-color: var(--red); color: black; border: none; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; margin: 0.25rem; display: inline-block; font-size: 0.9rem;">
										⚠️ View Open Cases
									</a>
									<?php endif; ?>
									<?php if ($patient['status'] === 'Deceased' && $patient['date_of_death']): ?>
									<div style="margin-top: 0.5rem; color: var(--red); font-size: 0.8rem;">
										Deceased: <?php echo formatICDateOnly($patient['date_of_death']); ?>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					
					<!-- Quick Actions -->
					<div style="background: rgba(0,0,0,0.5); padding: 2rem; border-radius: 15px; margin: 2rem 0; text-align: center;">
						<h3>Quick Medical Actions</h3>
						<div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
							<button onclick="playSoundAndRedirect('audio2', 'med_sci.php')" style="background-color: var(--blue); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
								📝 Medical Reports
							</button>
							<button onclick="playSoundAndRedirect('audio2', 'medical_resolved.php')" style="background-color: var(--green); color: black; border: none; padding: 1rem 1.5rem; border-radius: 5px; font-size: 1rem;">
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
					Medical Patient Database - Authorized Medical Personnel Only
				</footer> 
			</div>
		</div>
	</section>	
	<script type="text/javascript" src="../assets/lcars.js"></script>
	<div class="headtrim"> </div>
	<div class="baseboard"> </div>
</body>
</html>
