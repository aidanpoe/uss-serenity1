# Database Files

This directory contains the database schema and sample data for the USS-Voyager website.

## 📊 Database File

**`ussv_voyager.sql`** - Complete database schema and structure

### Installation Instructions

#### Method 1: Using phpMyAdmin
1. Log in to your phpMyAdmin interface
2. Create a new database (e.g., `ussv_voyager` or `usss_voyager`)
3. Select the newly created database
4. Click on the "Import" tab
5. Click "Choose File" and select `ussv_voyager.sql`
6. Click "Go" to import the database
7. Update your `.env` file with the database credentials

#### Method 2: Using MySQL Command Line
```bash
# Create the database
mysql -u your_username -p -e "CREATE DATABASE ussv_voyager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import the SQL file
mysql -u your_username -p ussv_voyager < database/ussv_voyager.sql
```

#### Method 3: Using cPanel
1. Log in to cPanel
2. Navigate to "MySQL Databases"
3. Create a new database
4. Go to phpMyAdmin from cPanel
5. Select your database
6. Click "Import" and upload `ussv_voyager.sql`

### Database Configuration

After importing the database, update your configuration:

1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` with your database credentials:**
   ```
   DB_HOST=localhost
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_secure_password
   DB_NAME=ussv_voyager
   DB_PORT=3306
   ```

3. **Set proper permissions:**
   ```bash
   chmod 600 .env  # Make .env readable only by owner
   ```

### Database Structure

The database includes tables for:
- User accounts and authentication
- Character roster management
- Department systems (Medical, Engineering, Security, etc.)
- Training and competency tracking
- Cargo bay inventory
- Awards and commendations
- Messaging system
- Audit logging
- GDPR compliance data

### Security Notes

⚠️ **Important:**
- Change the default database password immediately
- Use a dedicated database user with minimal privileges
- Never commit `.env` files to version control
- Ensure database backups are configured
- Keep the database server updated

### Troubleshooting

**Import Error - "Table already exists":**
- Drop the existing database and create a fresh one
- Or use the `--force` option if using command line

**Character encoding issues:**
- Ensure your database uses UTF8MB4 character set
- Check that your MySQL server supports UTF8MB4

**Large import fails:**
- Increase `upload_max_filesize` in php.ini
- Increase `post_max_size` in php.ini
- Increase `max_execution_time` in php.ini
- Try importing via command line instead of phpMyAdmin

### Need Help?

For setup assistance or issues:
- Check the main [README.md](../README.md) for installation guide
- Review [SECURITY_CHECKLIST.md](../SECURITY_CHECKLIST.md) for security setup
- Contact: computer@uss-voyager.org

---

🖖 **Live Long and Prosper!**
