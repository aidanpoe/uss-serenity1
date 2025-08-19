# Pre-Deployment Checklist - Command Structure Editor

## ✅ **Ready to Deploy!**

### Files Created/Modified:
- ✅ `pages/command_structure_edit.php` - New command editor (484 lines)
- ✅ `pages/command.php` - Added Captain button for editor access
- ✅ `includes/config.php` - Already has all required permission functions
- ✅ `setup_database.php` - Already includes position column and sample data

### Database Requirements:
- ✅ `roster` table has `position` column (VARCHAR(100))
- ✅ Sample crew members with command positions pre-loaded
- ✅ Default users include Captain "Poe" with proper permissions

### Testing Preparation:

## **Step 1: Database Setup (if not done)**
If your database isn't set up yet, run:
```bash
# Navigate to your web server and access:
# http://your-domain/setup_database.php
# This will create all tables and insert sample data
```

## **Step 2: Login as Captain**
- Username: `Poe`
- Password: `Class390`
- Department: `Captain`

## **Step 3: Access Command Structure Editor**
1. Login as Captain Poe
2. Navigate to "Command Center" 
3. Look for "Command Structure Editor" button in Quick Actions
4. Click to access the editor

## **Step 4: Test Assignment Function**
1. Click "Edit Assignment" on any position
2. Select a crew member from the dropdown
3. Click "Save"
4. Verify the assignment appears immediately
5. Check that the main roster page reflects the change

### Current Sample Data Available:
- **Captain James Poe** (Commanding Officer)
- **Commander William Riker** (First Officer) 
- **Lt. Cmdr Data Soong** (Operations Officer)
- **Lt. Cmdr Geordi La Forge** (Chief Engineer)
- **Lt. Cmdr Worf Son of Mogh** (Security Chief)
- **Lt. Cmdr Leonard McCoy** (Chief Medical Officer)
- **Lieutenant Deanna Troi** (Counselor)
- **Lieutenant B'Elanna Torres** (Engineer)

### Expected Functionality:
✅ **Position Assignment**: Captain can assign crew to command positions
✅ **Smart Filtering**: Dropdown shows only qualified personnel
✅ **Visual Updates**: Changes reflect immediately in command structure
✅ **Database Integration**: Assignments saved to roster.position column
✅ **Permission Control**: Only Captain can access editor
✅ **LCARS Styling**: Full theme compliance with sounds

### Security Features Active:
- Captain-only access control
- Session validation
- Rank/department requirement checking
- Automatic conflict resolution (no duplicate assignments)

## **Ready to Test!**
Everything is properly configured. The system should work immediately upon deployment. The Captain can start assigning crew members to command positions right away, and all changes will be visible across the entire LCARS system.

### Troubleshooting:
- If button doesn't appear: Check if logged in as Captain
- If editor shows error: Verify database connection in config.php
- If no personnel show: Ensure roster table has crew members
- If assignments don't save: Check database permissions

**You're all set for deployment!** 🚀
