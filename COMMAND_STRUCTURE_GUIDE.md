# Command Structure Editor - User Guide

## Overview
The Command Structure Editor is a Captain-only function that allows the commanding officer to assign personnel from the crew roster to specific command positions within the ship's hierarchy.

## Access
- **Location**: Command Center → Quick Actions → "Command Structure Editor" (Captain only)
- **Access Level**: Captain authorization required
- **URL**: `pages/command_structure_edit.php`

## Features

### 1. Position Assignment
- Captain can assign any crew member to command positions
- System automatically filters available personnel based on:
  - Department requirements
  - Minimum rank requirements
  - Current availability

### 2. Intelligent Filtering
The system ensures only qualified personnel can be assigned to positions:

**Command Positions:**
- Captain: Command Department, Captain rank
- First Officer: Command Department, Commander+
- Second Officer: Command Department, Lt. Commander+
- Third Officer: Command Department, Lieutenant+

**Department Head Positions:**
- Head of ENG/OPS: ENG/OPS or Command, Lt. Commander+
- Head of MED/SCI: MED/SCI or Command, Lt. Commander+
- Head of SEC/TAC: SEC/TAC or Command, Lt. Commander+

**Department Chief Positions:**
- Chief Engineer: ENG/OPS Department, Lieutenant+
- Chief Medical Officer: MED/SCI Department, Lieutenant+
- Security Chief: SEC/TAC Department, Lieutenant+

**Specialized Positions:**
- Operations Officer: ENG/OPS Department, Lt. JG+
- Chief Science Officer: MED/SCI Department, Lt. JG+
- Tactical Officer: SEC/TAC Department, Lt. JG+
- Helm Officer: ENG/OPS Department, Ensign+
- Intelligence Officer: SEC/TAC Department, Lt. JG+
- S.R.T. Leader: SEC/TAC Department, Lt. JG+

### 3. Visual Command Structure
- Hierarchical tree display showing chain of command
- Department color coding:
  - **Red**: Command positions
  - **Orange**: Engineering/Operations
  - **Blue**: Medical/Science
  - **Gold**: Security/Tactical
- Real-time updates when positions are assigned

### 4. Database Integration
- Position assignments are stored in the `roster` table `position` column
- Automatic clearing of previous assignments when reassigning positions
- Positions can be left vacant by selecting "Vacant Position"

## How to Use

### Assigning Personnel to Positions
1. Navigate to Command Center
2. Click "Command Structure Editor" (Captain only)
3. Click "Edit Assignment" on any position box
4. Select personnel from the filtered dropdown
5. Click "Save" to confirm assignment
6. Position will update immediately and reflect in the main roster

### Viewing Assignments
- All current assignments are visible in the command structure display
- Vacant positions are clearly marked
- Personnel information shows rank, name, and department

### Integration with Existing Systems
- Command structure display on main roster page updates automatically
- Personnel files show command positions when assigned
- All department pages respect command hierarchy

## Technical Details

### Database Schema
```sql
-- Position column in roster table
ALTER TABLE roster ADD COLUMN position VARCHAR(100);

-- Available positions
'Commanding Officer', 'First Officer', 'Second Officer', 'Third Officer',
'Head of ENG/OPS', 'Head of MED/SCI', 'Head of SEC/TAC',
'Chief Engineer', 'Chief Medical Officer', 'Security Chief',
'Operations Officer', 'Chief Science Officer', 'Tactical Officer',
'Helm Officer', 'Intelligence Officer', 'S.R.T. Leader'
```

### Security Features
- Captain-only access with session validation
- Position requirements prevent invalid assignments
- Rank hierarchy enforcement
- Department matching requirements

### Files Modified/Created
- **NEW**: `pages/command_structure_edit.php` - Main editor interface
- **UPDATED**: `pages/command.php` - Added editor button for Captain
- **EXISTING**: `pages/roster.php` - Already supports position display
- **EXISTING**: `setup_database.php` - Position column already defined

## Benefits
1. **Streamlined Command Management**: Easy assignment and reassignment of command positions
2. **Rank Compliance**: Automatic enforcement of Starfleet rank requirements
3. **Department Integration**: Ensures personnel are assigned to appropriate departments
4. **Visual Clarity**: Clear hierarchical display of command structure
5. **Real-time Updates**: Immediate reflection of changes across all systems

## Future Enhancements
- Position history tracking
- Temporary assignment capabilities
- Automated succession planning
- Integration with duty roster system
