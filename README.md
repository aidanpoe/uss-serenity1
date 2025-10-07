# USS-VOYAGER NCC-74656 - LCARS Website

A Star Trek LCARS-themed website for managing ship operations, crew roster, and departmental functions.

## 🚀 Quick Start

**Ready-to-use database included!** Simply import `database/ussv_voy## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Web server (Apache/Nginx) with mod_rewrite
- Modern web browser with JavaScript enabled
- SSL certificate (recommended for production)

## Customizationur MySQL server and you're ready to go.

See [Database Setup](#database-setup) below for detailed instructions.

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

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

### Database Setup

**Option 1: Import Ready-Made Database (Recommended)**

A complete database file is provided in the `database/` directory:

1. **Create a new database:**
   ```sql
   CREATE DATABASE ussv_voyager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'voyager'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON ussv_voyager.* TO 'voyager'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Import the database file:**
   
   **Via phpMyAdmin:**
   - Select your database
   - Click "Import"
   - Choose `database/ussv_voyager.sql`
   - Click "Go"
   
   **Via command line:**
   ```bash
   mysql -u your_username -p ussv_voyager < database/ussv_voyager.sql
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

📖 **For detailed database setup instructions, see [database/README.md](database/README.md)**

**Option 2: Manual Database Setup**

If you prefer to create the database from scratch:

First, ensure you have MySQL installed and running. Create the database and user:

```sql
CREATE DATABASE voyager;
CREATE USER 'voyager'@'localhost' IDENTIFIED BY 'Os~886go4';
GRANT ALL PRIVILEGES ON voyager.* TO 'voyager'@'localhost';
FLUSH PRIVILEGES;
```

### Initialize Application

Run the database setup script by visiting:
```
http://your-domain/setup_database.php
```

This will create all necessary tables and insert default users.

### Configure Environment Variables

1. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your database credentials and settings

3. Set proper permissions (Linux/Mac):
   ```bash
   chmod 600 .env
   ```

### Default Login Credentials

- **Captain**: Username: `Poe` | Password: `Class390`
- **Engineering**: Username: `torres` | Password: `engineering123`
- **Medical**: Username: `mccoy` | Password: `medical456`
- **Security**: Username: `worf` | Password: `security789`
- **Command**: Username: `riker` | Password: `command101`

⚠️ **IMPORTANT:** Change all default passwords immediately after first login!

### File Structure

```
USS-VOYAGER/
├── index.php                 # Home page
├── database/                 # Database files
│   ├── ussv_voyager.sql      # Ready-to-import database
│   └── README.md             # Database setup guide
├── setup_database.php        # Database initialization
├── includes/
│   ├── config.php            # Database configuration
│   ├── secure_config.php     # Security configuration
│   └── security_headers.php  # HTTP security headers
├── pages/
│   ├── login.php             # Authentication
│   ├── logout.php            # Session termination
│   ├── roster.php            # Crew roster management
│   ├── med_sci.php           # Medical/Science department
│   ├── eng_ops.php           # Engineering/Operations
│   ├── sec_tac.php           # Security/Tactical
│   ├── command.php           # Command center
│   ├── training.php          # Training documents
│   ├── cargo_bay.php         # Cargo management
│   ├── rewards.php           # Awards & commendations
│   └── reports.php           # Reports overview
├── api/                      # API endpoints
├── steamauth/                # Steam authentication
├── assets/                   # LCARS template assets
│   ├── classic.css
│   ├── lcars.js
│   └── [audio files]
├── .env.example              # Environment variables template
├── .htaccess                 # Apache security configuration
├── robots.txt                # SEO and security
└── SECURITY_CHECKLIST.md     # Security documentation
```

### 4. File Structure

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
- `cargo_items`: Inventory management
- `awards`: Commendations and honors
- `audit_log`: Activity tracking for GDPR compliance

## Security Features

✅ **Comprehensive security implementation** - see [SECURITY_CHECKLIST.md](SECURITY_CHECKLIST.md) for full details:

- SQL injection protection (prepared statements)
- XSS protection (output escaping)
- CSRF protection (token validation)
- Secure session management
- Role-based access control
- File upload security
- HTTP security headers
- GDPR compliance
- Audit trails with timestamps

## Installation & Production Deployment

### Quick Start (Development)
1. Import `database/ussv_voyager.sql` to your MySQL server
2. Copy `.env.example` to `.env` and configure
3. Upload files to your web server
4. Access via browser and log in with default credentials
5. **Change all default passwords immediately!**

### Production Deployment
Before going live, review the [SECURITY_CHECKLIST.md](SECURITY_CHECKLIST.md):
- [ ] Change all default passwords
- [ ] Configure SSL/HTTPS
- [ ] Update Steam API keys
- [ ] Set proper file permissions
- [ ] Enable error logging
- [ ] Configure backups
- [ ] Review security headers

## Technical Requirements

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Web server (Apache/Nginx) with mod_rewrite
- Modern web browser with JavaScript enabled
- SSL certificate (recommended for production)

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

## Credits

- LCARS Template by [TheLCARS.com](https://www.thelcars.com)
- Star Trek and LCARS are trademarks of CBS Studios Inc.
- This is a fan-made project for educational purposes

## Support & Documentation

### Getting Help
1. Check [database/README.md](database/README.md) for database setup help
2. Review [SECURITY_CHECKLIST.md](SECURITY_CHECKLIST.md) for security configuration
3. Check the browser console for JavaScript errors
4. Verify database connection in `.env` file
5. Ensure all file permissions are correctly set
6. Check PHP error logs for server-side issues

### Documentation Files
- `README.md` - Main documentation (this file)
- `database/README.md` - Database setup guide
- `SECURITY_CHECKLIST.md` - Security implementation and best practices
- `SECURITY_ASSESSMENT_2025.md` - Vulnerability assessment
- `GDPR_IMPLEMENTATION_COMPLETE.md` - GDPR compliance details
- `STEAM_SETUP_GUIDE.md` - Steam authentication setup

### Contact
- **Email:** computer@uss-voyager.org
- **Security Issues:** See `.well-known/security.txt`

---

🖖 **Live Long and Prosper!**
