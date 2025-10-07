# USS Voyager Cargo Bay Management System

## Overview
The Cargo Bay Management System provides comprehensive inventory tracking and control for the USS Voyager. It manages storage areas for different departments with proper access controls and logging.

## Setup Instructions

### 1. Database Setup
Run the setup script to create the necessary database tables:
```
http://yourdomain.com/setup_cargo_bay.php
```

This creates:
- `cargo_areas` - Storage area definitions
- `cargo_inventory` - Item inventory tracking  
- `cargo_logs` - All activity logging

### 2. Storage Areas

#### Default Storage Areas Created:
1. **MED/SCI Shelf Unit** - Large shelf for medical and scientific supplies
2. **ENG/OPS Shelf Unit 1, 2, 3** - Three shelf units for engineering/operations
3. **SEC/TAC Upper Level** - Security/tactical storage on upper cargo bay level
4. **Miscellaneous Items** - General storage for all departments

#### Access Permissions:
- **MED/SCI Shelf**: MED/SCI, ENG/OPS, COMMAND can modify
- **ENG/OPS Shelves**: ENG/OPS, COMMAND can modify  
- **SEC/TAC Area**: SEC/TAC, ENG/OPS, COMMAND can modify
- **Miscellaneous Area**: All departments can modify
- **All Areas**: Everyone can view inventory

## Features

### 🔐 Access Control System
- **View Access**: All logged-in users can see all inventory
- **Modify Access**: Department-specific permissions
- **ENG/OPS Override**: Can access all storage areas (cargo bay operations)
- **COMMAND Override**: Full access to all areas

### 📦 Inventory Management
- **Add Items**: Department members can add items to their areas
- **Remove Items**: Controlled removal with optional reason logging
- **Item Descriptions**: Detailed item information
- **Quantity Tracking**: Real-time inventory levels

### 🚚 Bulk Delivery System
- **ENG/OPS Exclusive**: Only Engineering/Operations can perform bulk deliveries
- **Mass Addition**: Add large quantities of items efficiently
- **Any Storage Area**: Can deliver to any area as cargo bay operators

### ⚠️ Low Stock Warnings
- **Automatic Alerts**: Items below 5 units trigger warnings
- **Department-Specific**: Warnings appear on relevant department pages
- **Real-time Monitoring**: Instant alerts when items run low

### 📋 Activity Logging
- **Complete Audit Trail**: Every add/remove action is logged
- **User Tracking**: Records who performed each action
- **Department Attribution**: Logs which department made changes
- **Reason Logging**: Optional explanations for item removal
- **Timestamp Tracking**: Precise time records for all activities

## User Interface

### 🎨 LCARS Design
- **Authentic Styling**: Consistent with USS Voyager interface theme
- **Department Colors**: Color-coded storage areas
- **Responsive Design**: Works on all device sizes
- **Audio Integration**: LCARS sound effects

### 📱 Interactive Elements
- **Modal Dialogs**: Professional removal confirmation system
- **Real-time Updates**: Immediate inventory updates
- **Form Validation**: Prevents invalid operations
- **Low Stock Highlighting**: Visual warnings for depleted items

## Department Workflows

### Medical/Science Department
1. Access MED/SCI storage area
2. Add medical supplies and scientific equipment
3. Remove items with optional reason logging
4. Receive alerts when medical supplies run low

### Engineering/Operations Department  
1. Full cargo bay access (all areas)
2. Perform bulk deliveries to any storage area
3. Manage ship operations supplies
4. Monitor all inventory levels across departments

### Security/Tactical Department
1. Access SEC/TAC upper level storage
2. Manage security equipment and tactical supplies
3. Remove items with security justification logging
4. Receive alerts for security equipment shortages

### Command Staff
1. Full oversight of all storage areas
2. Add/remove items from any location
3. Access complete activity logs
4. Monitor department inventory compliance

## Security Features

### 🛡️ Permission System
- **Steam Authentication**: Integrates with existing user system
- **Department Validation**: Automatic department detection
- **Action Authorization**: Real-time permission checking
- **Access Logging**: All attempts logged for security audit

### 📊 Audit Capabilities
- **Complete History**: Full record of all inventory changes
- **User Attribution**: Links all actions to specific users
- **Reason Tracking**: Optional explanations for accountability
- **Timestamp Precision**: Exact time tracking for investigations

## Technical Implementation

### Database Schema
```sql
cargo_areas:
- id (Primary Key)
- area_name (Display name)
- area_code (Unique identifier)
- description (Area details)
- department_access (Permission list)

cargo_inventory:
- id (Primary Key)
- area_id (Foreign Key to cargo_areas)
- item_name (Item identifier)
- item_description (Item details)
- quantity (Current stock level)
- min_quantity (Low stock threshold)
- added_by (User who created entry)
- added_department (Department attribution)

cargo_logs:
- id (Primary Key)
- inventory_id (Foreign Key to cargo_inventory)
- action (ADD/REMOVE/BULK_DELIVERY)
- quantity_change (Amount modified)
- previous_quantity (Stock before change)
- new_quantity (Stock after change)
- performed_by (User performing action)
- performer_department (Department of user)
- reason (Optional explanation)
- timestamp (Precise action time)
```

### File Structure
```
setup_cargo_bay.php          # Database setup script
pages/cargo_bay.php           # Main cargo bay interface
includes/config.php           # Database configuration
assets/classic.css            # LCARS styling
```

## Usage Examples

### Adding Medical Supplies
1. MED/SCI user accesses cargo bay
2. Navigates to MED/SCI Shelf Unit
3. Fills out add item form:
   - Item: "Tricorder Medical Scanner"
   - Description: "Standard medical tricorder for patient diagnosis"
   - Quantity: 10
4. System logs addition with user and department

### Bulk Engineering Delivery
1. ENG/OPS user performs bulk delivery
2. Selects target storage area
3. Enters bulk item details:
   - Item: "Isolinear Chips"
   - Description: "Data storage chips for computer systems"  
   - Quantity: 500
4. System logs as bulk delivery operation

### Security Equipment Removal
1. SEC/TAC user removes tactical equipment
2. Selects item and quantity to remove
3. Provides reason: "Issued for away team mission"
4. System logs removal with reason and user

## Maintenance

### Regular Tasks
- Monitor low stock warnings
- Review activity logs for unusual patterns
- Verify department access permissions
- Backup inventory data regularly

### Troubleshooting
- Check Steam authentication if access denied
- Verify department assignments in user profiles
- Review cargo_logs table for action history
- Confirm database permissions for write operations

## Integration Points

### Existing Systems
- **Steam Authentication**: Uses current login system
- **Department Structure**: Integrates with roster departments
- **LCARS Interface**: Matches site design theme
- **Audio System**: Uses existing sound effects

### Future Enhancements
- Integration with mission reports
- Automated supply requisition system
- Department budget tracking
- Supply chain management features

Your USS Voyager now has a comprehensive cargo bay management system that provides realistic inventory control while maintaining the authentic Star Trek LCARS experience!
