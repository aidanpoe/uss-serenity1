# USS Voyager GDPR Cleanup Cron Job Configuration
# Server: USS-VOYAGER.org
# Path: /var/www/vhosts/USS-VOYAGER.org

## MANUAL CRON JOB SETUP

### 1. SSH into your server and edit crontab:
```bash
crontab -e
```

### 2. Add this line to run cleanup daily at 2:00 AM:
```bash
0 2 * * * /usr/bin/php /var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php >> /var/www/vhosts/USS-VOYAGER.org/logs/gdpr_cleanup.log 2>&1
```

### 3. Alternative PHP paths (if /usr/bin/php doesn't work):
```bash
# For Plesk with PHP 8.1:
0 2 * * * /opt/plesk/php/8.1/bin/php /var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php >> /var/www/vhosts/USS-VOYAGER.org/logs/gdpr_cleanup.log 2>&1

# For Plesk with PHP 8.2:
0 2 * * * /opt/plesk/php/8.2/bin/php /var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php >> /var/www/vhosts/USS-VOYAGER.org/logs/gdpr_cleanup.log 2>&1

# For cPanel:
0 2 * * * /usr/local/bin/php /var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php >> /var/www/vhosts/USS-VOYAGER.org/logs/gdpr_cleanup.log 2>&1
```

## PLESK CONTROL PANEL SETUP

### If you're using Plesk:

1. **Log into Plesk Control Panel**
2. **Go to your domain (USS-VOYAGER.org)**
3. **Click "Scheduled Tasks"**
4. **Add New Task:**
   - **Command:** `/usr/bin/php` (or `/opt/plesk/php/8.1/bin/php`)
   - **Arguments:** `/var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php`
   - **Schedule:** Daily
   - **Time:** 02:00 AM
   - **Notify:** Your email address (optional)

## CPANEL SETUP

### If you're using cPanel:

1. **Log into cPanel**
2. **Go to "Cron Jobs" under Advanced**
3. **Add New Cron Job:**
   - **Minute:** 0
   - **Hour:** 2  
   - **Day:** *
   - **Month:** *
   - **Weekday:** *
   - **Command:** `/usr/local/bin/php /var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php`

## TESTING

### Test the script manually first:
```bash
# SSH into your server
cd /var/www/vhosts/USS-VOYAGER.org/httpdocs

# Test PHP path
which php
/usr/bin/php --version

# Test the script
/usr/bin/php gdpr_cleanup.php

# Check for errors
echo $?
```

### Create log directory:
```bash
mkdir -p /var/www/vhosts/USS-VOYAGER.org/logs
chmod 755 /var/www/vhosts/USS-VOYAGER.org/logs
```

### Monitor the cron job:
```bash
# View cron job logs
tail -f /var/www/vhosts/USS-VOYAGER.org/logs/gdpr_cleanup.log

# Check if cron job is scheduled
crontab -l

# Check system cron logs
tail -f /var/log/cron
```

## WHAT THE CLEANUP DOES (Even Without IP Logging)

✅ **Login Logs:** Deletes entries older than 12 months
✅ **Training Audit:** Deletes entries older than 24 months  
✅ **Training Access:** Deletes entries older than 24 months
✅ **Deleted Files:** Permanently removes files after 90 days in recycle bin
✅ **Expired Messages:** Removes expired crew messages
✅ **Inactive Accounts:** Identifies accounts inactive for 24+ months
✅ **Compliance Reports:** Generates daily compliance reports

## TROUBLESHOOTING

### Common Issues:

1. **Permission denied:**
   ```bash
   chmod +x /var/www/vhosts/USS-VOYAGER.org/httpdocs/gdpr_cleanup.php
   ```

2. **Database connection failed:**
   - Check `includes/config.php` has correct database credentials
   - Ensure database user has DELETE permissions

3. **PHP not found:**
   ```bash
   # Find PHP path
   which php
   whereis php
   find /usr -name php
   ```

4. **Cron not running:**
   ```bash
   # Check cron service
   systemctl status cron
   service cron status
   ```

### Verification:
After 24 hours, check:
```bash
cat /var/www/vhosts/USS-VOYAGER.org/logs/gdpr_cleanup.log
```

You should see entries like:
```
[2025-08-23 02:00:01] GDPR cleanup started
[2025-08-23 02:00:01] Deleted 0 old login entries
[2025-08-23 02:00:01] Deleted 0 old training audit entries
[2025-08-23 02:00:01] GDPR cleanup completed successfully
```
