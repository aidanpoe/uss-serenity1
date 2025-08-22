# Starfleet Auditor System - Implementation Guide

## Overview
The Starfleet Auditor system provides invisible, full-access OOC (Out of Character) moderation accounts that can be assigned only by Captains. These accounts have complete system access but remain hidden from all roster displays and crew listings.

## Features Implemented

### 1. Database Structure
- **New Department**: Added "Starfleet Auditor" to users.department ENUM
- **Invisibility Flags**: Added `is_invisible` column to both `users` and `roster` tables
- **Audit Trail**: Created `auditor_assignments` table to track all assignments and revocations

### 2. Permission System Updates
- **Full Access**: Starfleet Auditors bypass all permission checks (hasPermission always returns true)
- **Invisibility Functions**: Added `isInvisibleUser()` and `isUserInvisible()` helper functions
- **Captain-Only**: Only users with Captain permissions can assign/revoke auditor status

### 3. User Interface
- **Management Page**: `/pages/auditor_management.php` - Captain-only interface
- **Command Integration**: Added auditor management link to command center
- **Assignment Tracking**: Shows who assigned auditors and when
- **Role Revocation**: Captains can restore users to normal departments

### 4. Invisibility Implementation
Updated all roster queries across the website to exclude invisible users:
- **Main Roster** (`roster.php`)
- **Department Pages** (`med_sci.php`, `eng_ops.php`, `sec_tac.php`)
- **Award Management** (`awards_management.php`)
- **Command Center** (`command.php`)

## Setup Instructions

### 1. Run Database Migration
Execute `setup_starfleet_auditor.php` to:
- Add "Starfleet Auditor" to users.department enum
- Create `is_invisible` columns
- Set up `auditor_assignments` tracking table

### 2. File Updates
All necessary files have been updated to:
- Grant full permissions to Starfleet Auditors
- Exclude invisible users from public listings
- Provide Captain management interface

## Usage Guide

### For Captains:
1. **Access Management**: Visit Command Center → "Starfleet Auditor Management"
2. **Assign Auditor**: Select user, add notes, confirm assignment
3. **Revoke Access**: Choose new department, confirm revocation
4. **View History**: See all current auditors and assignment details

### For Auditors:
1. **Full Access**: Can access all website areas regardless of character department
2. **Invisible Status**: Won't appear in rosters or crew lists
3. **Normal Login**: Use regular login process
4. **Character Creation**: Can still create characters if needed

## Security Features

### Assignment Control
- **Captain-Only**: Only users with Captain permissions can manage auditors
- **Confirmation Required**: Assignment/revocation requires explicit confirmation
- **Audit Trail**: All actions logged with timestamps and user IDs

### Invisibility Enforcement
- **Roster Exclusion**: Hidden from all public crew listings
- **Database Level**: Filtered at query level, not display level
- **Comprehensive Coverage**: Applied to all user-facing lists

### Access Management
- **Permission Bypass**: Auditors can access restricted areas
- **Department Independence**: Access not tied to character department
- **Full System Access**: Can view/edit all content types

## Database Schema

### Users Table Updates
```sql
ALTER TABLE users MODIFY COLUMN department ENUM('Captain', 'Command', 'MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Starfleet Auditor') NOT NULL;
ALTER TABLE users ADD COLUMN is_invisible TINYINT(1) DEFAULT 0;
```

### Roster Table Updates
```sql
ALTER TABLE roster ADD COLUMN is_invisible TINYINT(1) DEFAULT 0;
```

### Auditor Assignments Table
```sql
CREATE TABLE auditor_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_by_user_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Testing Checklist

### After Implementation:
- [ ] Run database migration script
- [ ] Test Captain access to auditor management
- [ ] Assign test auditor and verify full access
- [ ] Confirm auditor is invisible in roster
- [ ] Test auditor access to all department pages
- [ ] Verify revocation restores normal permissions
- [ ] Check audit trail functionality

### Security Verification:
- [ ] Non-captains cannot access management page
- [ ] Auditors don't appear in any crew listings
- [ ] Permission system correctly grants full access
- [ ] Assignment/revocation logging works

## Maintenance Notes

### Regular Tasks:
- Review active auditor assignments periodically
- Verify audit trail integrity
- Monitor for any visibility leaks in new features

### Future Considerations:
- Consider adding auditor activity logging
- Implement time-limited auditor assignments
- Add bulk auditor management features

## Benefits

### For Administration:
- **Invisible Moderation**: OOC oversight without IC disruption
- **Full Access Control**: Complete system visibility for moderation
- **Accountability**: Full audit trail of all assignments

### for Roleplay Community:
- **IC Integrity**: Moderators don't appear in character rosters
- **Seamless Oversight**: Background moderation capabilities
- **Professional Management**: Proper separation of OOC roles

The Starfleet Auditor system provides a professional, secure, and invisible moderation solution that maintains the integrity of the roleplay environment while ensuring comprehensive administrative oversight.
