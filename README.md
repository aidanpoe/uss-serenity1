# USS-VOYAGER NCC-74656 - LCARS Website

A Star Trek LCARS-themed website for managing ship operations, crew roster, and departmental functions.

## Features

- **LCARS Interface**: Authentic Star Trek LCARS design with interactive elements
- **User Authentication**: Role-based access control for different departments
- **Ship's Roster**: Comprehensive crew management with command hierarchy
- **Department Modules**:
  - **Medical/Science**: Health records and research reports
  - **Engineering/Operations**: System fault reporting and maintenance
  - **Security/Tactical**: Incident reports and phaser training records
  - **Command**: Strategic suggestions and ship management
- **Training Documents**: Department-specific educational resources
- **Reports Dashboard**: Overview of all departmental activities

## Setup Instructions

### 1. Database Setup

First, ensure you have MySQL installed and running. Create the database and user:

```sql
CREATE DATABASE voyager;
CREATE USER 'voyager'@'localhost' IDENTIFIED BY 'Os~886go4';
GRANT ALL PRIVILEGES ON voyager.* TO 'voyager'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Initialize Database

Run the database setup script by visiting:
```
http://your-domain/setup_database.php
```

This will create all necessary tables and insert default users.

### 3. Default Login Credentials

- **Captain**: Username: `Poe` | Password: `Class390`
- **Engineering**: Username: `torres` | Password: `engineering123`
- **Medical**: Username: `mccoy` | Password: `medical456`
- **Security**: Username: `worf` | Password: `security789`
- **Command**: Username: `riker` | Password: `command101`

### 4. File Structure

```
USS-VOYAGER/
├── index.php                 # Home page
├── setup_database.php        # Database initialization
├── includes/
│   └── config.php            # Database configuration
├── pages/
│   ├── login.php             # Authentication
│   ├── logout.php            # Session termination
│   ├── roster.php            # Crew roster management
│   ├── med_sci.php           # Medical/Science department
│   ├── eng_ops.php           # Engineering/Operations
│   ├── sec_tac.php           # Security/Tactical
│   ├── command.php           # Command center
│   ├── training.php          # Training documents
│   └── reports.php           # Reports overview
├── assets/                   # LCARS template assets
│   ├── classic.css
│   ├── lcars.js
│   └── [audio files]
└── TEMPLATE/                 # Original LCARS templates
```

## User Permissions

### Captain
- Full access to all areas
- Can add/modify roster
- Can assign command positions
- Access to all reports and management functions

### Command
- Access to all department areas
- Can manage suggestions
- Cannot modify roster (Captain only)

### Department Staff
- **MED/SCI**: Medical records, science reports
- **ENG/OPS**: Fault reports, engineering systems
- **SEC/TAC**: Security incidents, phaser training records

### Public Access
- Ship roster viewing (with phaser training display)
- Report submission forms
- Training document viewing

## Database Tables

- `users`: Authentication and user management
- `roster`: Ship personnel records
- `medical_records`: Health and medical reports
- `science_reports`: Scientific research inquiries
- `fault_reports`: Engineering system faults
- `security_reports`: Security incidents and concerns
- `command_suggestions`: Crew suggestions to command
- `training_documents`: Educational resources

## Security Features

- Session-based authentication
- Role-based access control
- SQL injection protection (prepared statements)
- XSS protection (input sanitization)
- Audit trails with timestamps

## Customization

### Adding New Users
1. Use the Medical/Science form to add them to the roster first
2. Captain can then create login credentials
3. Assign appropriate department permissions

### Modifying Departments
Update the database enums in `setup_database.php` and corresponding form options in the PHP files.

### LCARS Theming
The website uses the classic LCARS color scheme:
- Command: Red (`--red`)
- Medical/Science: Blue (`--blue`)
- Engineering/Operations: Orange (`--orange`)
- Security/Tactical: Gold (`--gold`)

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Security Notes

- Change default passwords immediately after setup
- Use HTTPS in production
- Regularly backup the database
- Monitor user activity through the audit trails

## Credits

- LCARS Template by [TheLCARS.com](https://www.thelcars.com)
- Star Trek and LCARS are trademarks of CBS Studios Inc.
- This is a fan-made project for educational purposes

## Support

For issues or questions:
1. Check the browser console for JavaScript errors
2. Verify database connection in `includes/config.php`
3. Ensure all file permissions are correctly set
4. Check PHP error logs for server-side issues
