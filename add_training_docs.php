<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    // Default training documents
    $training_docs = [
        [
            'department' => 'MED/SCI',
            'title' => 'Emergency Medical Procedures',
            'content' => "EMERGENCY MEDICAL PROCEDURES - USS Voyager

1. MEDICAL EMERGENCIES
   - Immediate assessment using tricorder
   - Stabilize patient using available equipment
   - Contact CMO or senior medical officer
   - Transport to Sickbay if condition permits

2. BIOLOGICAL CONTAMINATION
   - Activate emergency containment protocols
   - Isolate affected areas using force fields
   - Decontamination procedures per Starfleet Medical
   - Report to Bridge and Medical immediately

3. RADIATION EXPOSURE
   - Move personnel to safe distance
   - Administer hyronalin if available
   - Monitor for radiation sickness symptoms
   - Emergency transport to Sickbay

4. SICKBAY EVACUATION
   - Secure all patients for transport
   - Activate emergency medical hologram if available
   - Transfer critical patients to Cargo Bay 2
   - Maintain life support systems

Remember: In emergency situations, any crew member may need to perform basic first aid. Familiarize yourself with tricorder medical functions and emergency medical kit contents."
        ],
        [
            'department' => 'MED/SCI',
            'title' => 'Science Laboratory Safety Protocols',
            'content' => "SCIENCE LABORATORY SAFETY PROTOCOLS

1. GENERAL SAFETY
   - Always wear appropriate protective equipment
   - Never work alone with hazardous materials
   - Report all incidents immediately
   - Keep emergency equipment accessible

2. CHEMICAL HANDLING
   - Check material safety data sheets before use
   - Use proper containment procedures
   - Dispose of waste according to regulations
   - Ventilation systems must be operational

3. BIOLOGICAL SAMPLES
   - Maintain sterile environment
   - Use isolation protocols for unknown specimens
   - Regular decontamination of work surfaces
   - Proper specimen labeling and storage

4. ENERGY EXPERIMENTS
   - Clear safety perimeter of 10 meters
   - Shield generators must be active
   - Emergency shutdown procedures posted
   - Senior science officer approval required

5. EMERGENCY PROCEDURES
   - Containment field activation
   - Emergency evacuation routes
   - Decontamination protocols
   - Communication with Bridge"
        ],
        [
            'department' => 'ENG/OPS',
            'title' => 'Warp Core Safety Procedures',
            'content' => "WARP CORE SAFETY PROCEDURES - USS Voyager

WARNING: Warp core operations require Level 3 engineering certification or higher.

1. GENERAL SAFETY
   - Maximum personnel limit: 4 in engineering at any time
   - Radiation monitoring badges required
   - Emergency transporter tags must be worn
   - Direct visual contact with warp core limited to 30 minutes

2. ROUTINE MAINTENANCE
   - Core must be at less than 50% power
   - Magnetic constrictors engaged
   - Backup power systems online
   - Chief Engineer approval required

3. EMERGENCY PROCEDURES
   - Automatic ejection system armed at all times
   - Manual ejection requires two-key authorization
   - Evacuation of Decks 10-12 in case of breach
   - Emergency bulkhead sealing protocols

4. POWER REGULATION
   - Monitor antimatter flow rates continuously
   - Dilithium crystal alignment must remain stable
   - Plasma temperature warnings at 2000 degrees K
   - Emergency shutdown if containment drops below 98%

5. JEFFERIES TUBE ACCESS
   - Always work in pairs
   - Portable life support required
   - Communication check every 15 minutes
   - Emergency beacon activation procedures"
        ],
        [
            'department' => 'ENG/OPS',
            'title' => 'Bridge Operations Manual',
            'content' => "BRIDGE OPERATIONS MANUAL

1. DUTY STATIONS
   - Command: Captain and senior staff only
   - CONN: Flight control and navigation
   - OPS: Ship operations and communications
   - Tactical: Weapons and shields
   - Science: Sensors and analysis

2. STANDARD PROCEDURES
   - Status reports every 4 hours
   - All commands logged in ship's computer
   - Emergency protocols always accessible
   - Senior officer present at all times

3. RED ALERT PROCEDURES
   - All stations manned immediately
   - Shield and weapons systems online
   - Damage control teams standing by
   - Emergency protocols activated

4. YELLOW ALERT PROCEDURES
   - Enhanced sensor sweeps
   - Security teams on standby
   - Department heads report to stations
   - Crew prepared for emergency

5. COMMUNICATION PROTOCOLS
   - Starfleet channels monitored continuously
   - Emergency frequencies scanned
   - Subspace message protocols
   - Internal communication priorities

6. OPERATIONAL SECURITY
   - Computer access levels enforced
   - Biometric verification required
   - Security lockdowns when necessary
   - Classified information protection"
        ],
        [
            'department' => 'SEC/TAC',
            'title' => 'Phaser Training and Safety',
            'content' => "PHASER TRAINING AND SAFETY PROTOCOLS

1. PHASER TYPES
   - Type I: Personal defense, stun settings only
   - Type II: Standard duty phaser, multiple settings
   - Type III: Phaser rifle, maximum security situations

2. SAFETY PROCEDURES
   - Never point phaser at another person except in emergency
   - Check power cell before each use
   - Safety engaged when not in use
   - Report malfunctions immediately

3. SETTINGS AND APPLICATIONS
   - Setting 1-2: Light stun (non-lethal)
   - Setting 3-4: Heavy stun (unconscious)
   - Setting 5-7: Heat/disruption (non-personnel)
   - Settings 8+: Emergency authorization only

4. TRAINING REQUIREMENTS
   - Type I: Basic safety course (4 hours)
   - Type II: Advanced training (8 hours + range time)
   - Type III: Tactical certification (16 hours + field exercises)

5. STORAGE AND MAINTENANCE
   - Secure storage in armory
   - Regular maintenance checks
   - Power cell rotation schedule
   - Cleaning and calibration procedures

6. EMERGENCY PROCEDURES
   - Phaser overload warnings
   - Power cell failure protocols
   - Weapon malfunction response
   - Security breach procedures

REMEMBER: Phasers are defensive weapons. Use of force must be justified and proportional to the threat."
        ],
        [
            'department' => 'SEC/TAC',
            'title' => 'Security Alert Procedures',
            'content' => "SECURITY ALERT PROCEDURES

1. SECURITY CONDITION LEVELS
   - Green: Normal operations, standard patrols
   - Yellow: Heightened awareness, increased patrols
   - Red: Active threat, all security personnel active
   - Black: Ship compromised, lockdown procedures

2. INTRUDER ALERT
   - Seal affected sections using force fields
   - Security teams respond within 2 minutes
   - Verify identity of all personnel in area
   - Report status to Security Chief every 5 minutes

3. BOARDING PARTY PROTOCOLS
   - Repel boarders using minimum necessary force
   - Protect vital ship systems (Bridge, Engineering, Sickbay)
   - Establish defensive positions
   - Coordinate with Tactical for ship's weapons

4. BRIG PROCEDURES
   - Maximum security for dangerous prisoners
   - Force field maintenance and backup power
   - Regular welfare checks
   - Legal rights and medical care

5. WEAPONS INVENTORY
   - Daily inventory of all weapons
   - Secure storage protocols
   - Access authorization levels
   - Emergency weapon distribution

6. PATROL DUTIES
   - Regular deck-by-deck security sweeps
   - Restricted area monitoring
   - Personnel identification verification
   - Incident reporting procedures

7. TACTICAL ANALYSIS
   - Threat assessment protocols
   - Strategic recommendations to Command
   - Defensive position planning
   - Ship vulnerability analysis"
        ],
        [
            'department' => 'Command',
            'title' => 'Command Decision Matrix',
            'content' => "COMMAND DECISION MATRIX - USS Voyager

1. COMMAND AUTHORITY
   - Captain: Ultimate authority on all matters
   - First Officer: Acting Captain when CO unavailable
   - Second Officer: Command succession line
   - Department Heads: Authority within their departments

2. EMERGENCY COMMAND DECISIONS
   - Immediate threat to ship: Captain or senior officer
   - Medical emergency: CMO has medical authority
   - Engineering crisis: Chief Engineer's technical expertise
   - Security threat: Security Chief tactical authority

3. DECISION CONSULTATION
   - Major strategic decisions: Senior staff input
   - Departmental issues: Relevant department head
   - Personnel matters: Consider all stakeholders
   - Starfleet regulations: Always take precedence

4. DOCUMENTATION REQUIREMENTS
   - All major decisions logged in Captain's log
   - Department heads maintain detailed records
   - Incident reports for unusual circumstances
   - Starfleet reports as required

5. DELEGATION PRINCIPLES
   - Clear authority and responsibility
   - Appropriate expertise and experience
   - Regular reporting and updates
   - Support and resources provided

6. CRISIS MANAGEMENT
   - Assess situation quickly but thoroughly
   - Gather expert opinions from relevant staff
   - Make clear, decisive action
   - Communicate decisions effectively

7. CREW WELFARE
   - Regular morale assessments
   - Address concerns promptly
   - Recognition of exceptional service
   - Professional development opportunities"
        ],
        [
            'department' => 'Command',
            'title' => 'Starfleet Regulations Summary',
            'content' => "STARFLEET REGULATIONS SUMMARY

KEY REGULATIONS FOR COMMAND STAFF

1. PRIME DIRECTIVE (General Order 1)
   - No interference with natural development of civilizations
   - Applies especially to pre-warp societies
   - Captain's discretion in interpretation
   - Violations subject to court martial

2. CHAIN OF COMMAND
   - Clear succession of command authority
   - Orders must be lawful and within regulations
   - Right to appeal unlawful orders
   - Temporary command transfers

3. CREW RIGHTS AND RESPONSIBILITIES
   - Right to fair treatment and due process
   - Responsibility to follow lawful orders
   - Freedom of expression within limits
   - Privacy rights and limitations

4. DIPLOMATIC PROTOCOLS
   - Ambassador has authority in diplomatic matters
   - Captain responsible for ship safety
   - Cultural sensitivity requirements
   - First contact procedures

5. SCIENTIFIC ETHICS
   - Respect for sentient life in all forms
   - Environmental protection standards
   - Research ethics and consent
   - Technology sharing limitations

6. EMERGENCY POWERS
   - Captain may suspend normal procedures
   - Must be justified by circumstances
   - Time limits on emergency authority
   - Post-incident review required

7. INTER-SHIP RELATIONS
   - Cooperation with other Starfleet vessels
   - Support for ships in distress
   - Resource sharing protocols
   - Joint mission coordination

Remember: These regulations exist to guide ethical decision-making while maintaining operational effectiveness."
        ]
    ];
    
    // Insert training documents
    foreach ($training_docs as $doc) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO training_documents (department, title, content, created_by) VALUES (?, ?, ?, 1)");
        $stmt->execute([$doc['department'], $doc['title'], $doc['content']]);
    }
    
    echo "Default training documents have been added successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
