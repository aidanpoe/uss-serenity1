# GDPR Cleanup Cron Job Setup Guide

## Overview
The `gdpr_cleanup.php` script automatically handles data retention and cleanup to maintain GDPR compliance. It should run daily to:
- Delete old log files beyond retention period
- Clean up expired session data
- Remove inactive user accounts (after appropriate warnings)
- Generate compliance reports

## Server Setup Instructions

### 1. Linux/Unix Server (cPanel, Plesk, VPS)

#### Option A: cPanel Cron Jobs
1. Log into cPanel
2. Go to "Cron Jobs" under "Advanced" section
3. Add new cron job with these settings:
   ```
   Minute: 0
   Hour: 2
   Day: *
   Month: *
   Weekday: *
   Command: /usr/bin/php /path/to/your/website/gdpr_cleanup.php
   ```

#### Option B: Command Line (SSH Access)
1. SSH into your server
2. Edit crontab: `crontab -e`
3. Add this line:
   ```bash
   0 2 * * * /usr/bin/php /home/username/public_html/gdpr_cleanup.php >> /home/username/gdpr_cleanup.log 2>&1
   ```

#### Option C: Plesk Panel
1. Log into Plesk
2. Go to "Scheduled Tasks" under your domain
3. Add new task:
   - Command: `/usr/bin/php`
   - Arguments: `/var/www/vhosts/yourdomain.com/httpdocs/gdpr_cleanup.php`
   - Schedule: Daily at 2:00 AM

### 2. Windows Server

#### Option A: Task Scheduler
1. Open Task Scheduler
2. Create Basic Task
3. Name: "GDPR Cleanup"
4. Trigger: Daily at 2:00 AM
5. Action: Start a program
   - Program: `C:\php\php.exe`
   - Arguments: `C:\inetpub\wwwroot\gdpr_cleanup.php`

#### Option B: PowerShell Script
Create `gdpr_cleanup_task.ps1`:
```powershell
# GDPR Cleanup PowerShell Script
$phpPath = "C:\php\php.exe"
$scriptPath = "C:\inetpub\wwwroot\gdpr_cleanup.php"
$logPath = "C:\logs\gdpr_cleanup.log"

# Run the cleanup script
& $phpPath $scriptPath | Out-File -Append $logPath

# Log the execution
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
"[$timestamp] GDPR cleanup completed" | Out-File -Append $logPath
```

Then schedule this PowerShell script in Task Scheduler.

### 3. Shared Hosting (Limited Access)

#### Option A: Hosting Control Panel
Most shared hosts provide cron job interfaces:
1. Look for "Cron Jobs", "Scheduled Tasks", or "Task Scheduler"
2. Set command: `/usr/bin/php /home/username/public_html/gdpr_cleanup.php`
3. Schedule: `0 2 * * *` (daily at 2 AM)

#### Option B: Wget/Curl Method (if direct PHP execution not available)
Create a web-accessible version and call it via HTTP:
```bash
0 2 * * * wget -q -O - http://yourdomain.com/gdpr_cleanup_web.php
```

## Implementation Steps

### Step 1: Test the Script Manually
Before setting up automation, test the script:

```bash
# Navigate to your website directory
cd /path/to/your/website

# Run the script manually
php gdpr_cleanup.php

# Check for any errors
echo $?
```

### Step 2: Create Log Directory
```bash
mkdir -p /path/to/logs
chmod 755 /path/to/logs
```

### Step 3: Set Up Cron Job with Logging
```bash
# Example cron entry with full logging
0 2 * * * /usr/bin/php /path/to/website/gdpr_cleanup.php >> /path/to/logs/gdpr_cleanup.log 2>&1
```

### Step 4: Monitor and Verify
```bash
# Check if cron job is running
tail -f /path/to/logs/gdpr_cleanup.log

# Verify cron is scheduled
crontab -l
```

## Configuration Options

### Environment Variables
Add these to your server environment or script:
```bash
export GDPR_CLEANUP_LOG_LEVEL="INFO"
export GDPR_CLEANUP_DRY_RUN="false"
export GDPR_CLEANUP_EMAIL_ALERTS="computer@uss-serenity.org"
```

### PHP Configuration in gdpr_cleanup.php
```php
// Add at the beginning of gdpr_cleanup.php
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/gdpr_cleanup_errors.log');
```

## Monitoring and Alerts

### Email Notifications
Add to gdpr_cleanup.php:
```php
// Send completion email
$to = 'computer@uss-serenity.org';
$subject = 'GDPR Cleanup Completed - ' . date('Y-m-d');
$message = "GDPR cleanup completed successfully at " . date('Y-m-d H:i:s') . "\n\n";
$message .= "Records processed: $recordsProcessed\n";
$message .= "Files deleted: $filesDeleted\n";
$message .= "Errors: $errorCount\n";
mail($to, $subject, $message);
```

### Log Rotation
Create log rotation to prevent log files from growing too large:
```bash
# Add to /etc/logrotate.d/gdpr-cleanup
/path/to/logs/gdpr_cleanup.log {
    daily
    rotate 30
    compress
    missingok
    notifempty
    create 644 www-data www-data
}
```

## Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   chmod +x gdpr_cleanup.php
   chown www-data:www-data gdpr_cleanup.php
   ```

2. **PHP Path Issues**
   Find correct PHP path:
   ```bash
   which php
   # or
   whereis php
   ```

3. **Memory/Timeout Issues**
   Increase PHP limits in the script:
   ```php
   ini_set('max_execution_time', 600);
   ini_set('memory_limit', '512M');
   ```

4. **Database Connection Issues**
   Ensure the script has database credentials:
   ```php
   // Test database connection in script
   try {
       $pdo = new PDO($dsn, $username, $password);
       echo "Database connection successful\n";
   } catch (PDOException $e) {
       error_log("Database connection failed: " . $e->getMessage());
   }
   ```

### Testing the Cron Job
```bash
# Test cron environment
* * * * * /usr/bin/env > /tmp/cron-env.txt

# Test PHP execution
* * * * * /usr/bin/php -v > /tmp/php-test.txt 2>&1

# Test script path
* * * * * ls -la /path/to/gdpr_cleanup.php > /tmp/file-test.txt 2>&1
```

## Security Considerations

1. **File Permissions**: Ensure only necessary users can read the script
2. **Log Security**: Protect log files from public access
3. **Database Security**: Use dedicated database user with minimal permissions
4. **Web Access**: If using web-triggered version, protect with authentication

## Compliance Documentation

Keep records of:
- When the cron job was set up
- Execution logs and any errors
- Data deletion confirmations
- Any manual interventions

This documentation helps demonstrate GDPR compliance during audits.

## Example Complete Cron Setup Script

```bash
#!/bin/bash
# setup_gdpr_cron.sh

WEBSITE_DIR="/path/to/your/website"
LOG_DIR="/path/to/logs"
PHP_PATH="/usr/bin/php"

# Create log directory
mkdir -p $LOG_DIR
chmod 755 $LOG_DIR

# Test the script
echo "Testing GDPR cleanup script..."
$PHP_PATH $WEBSITE_DIR/gdpr_cleanup.php

if [ $? -eq 0 ]; then
    echo "Script test successful. Setting up cron job..."
    
    # Add cron job
    (crontab -l 2>/dev/null; echo "0 2 * * * $PHP_PATH $WEBSITE_DIR/gdpr_cleanup.php >> $LOG_DIR/gdpr_cleanup.log 2>&1") | crontab -
    
    echo "Cron job setup complete!"
    echo "The GDPR cleanup will run daily at 2:00 AM"
    echo "Logs will be saved to: $LOG_DIR/gdpr_cleanup.log"
else
    echo "Script test failed. Please check the script before setting up cron job."
fi
```

Run this setup script:
```bash
chmod +x setup_gdpr_cron.sh
./setup_gdpr_cron.sh
```
