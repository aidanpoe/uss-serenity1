#!/bin/bash
# USS Serenity GDPR Cleanup Cron Job Setup Script
# Path: /var/www/vhosts/uss-serenity.org

echo "Setting up GDPR cleanup cron job for USS Serenity..."

WEBSITE_DIR="/var/www/vhosts/uss-serenity.org/httpdocs"
LOG_DIR="/var/www/vhosts/uss-serenity.org/logs"
PHP_PATH="/usr/bin/php"

# Create logs directory if it doesn't exist
mkdir -p $LOG_DIR
chmod 755 $LOG_DIR

# Test the PHP path
echo "Testing PHP installation..."
$PHP_PATH --version

if [ $? -eq 0 ]; then
    echo "✅ PHP found at $PHP_PATH"
else
    echo "❌ PHP not found at $PHP_PATH"
    echo "Common PHP paths to try:"
    echo "  /usr/bin/php"
    echo "  /usr/local/bin/php" 
    echo "  /opt/plesk/php/8.1/bin/php"
    echo "  /opt/plesk/php/8.2/bin/php"
    exit 1
fi

# Test the script exists
if [ -f "$WEBSITE_DIR/gdpr_cleanup.php" ]; then
    echo "✅ GDPR cleanup script found"
else
    echo "❌ GDPR cleanup script not found at $WEBSITE_DIR/gdpr_cleanup.php"
    echo "Please ensure the script is uploaded to your website directory"
    exit 1
fi

# Test database connection by running the script
echo "Testing GDPR cleanup script..."
$PHP_PATH $WEBSITE_DIR/gdpr_cleanup.php

if [ $? -eq 0 ]; then
    echo "✅ Script test successful"
    
    # Check if cron job already exists
    if crontab -l 2>/dev/null | grep -q "gdpr_cleanup.php"; then
        echo "⚠️  GDPR cleanup cron job already exists"
        echo "Current crontab:"
        crontab -l | grep gdpr_cleanup.php
    else
        # Add cron job
        echo "Adding cron job..."
        (crontab -l 2>/dev/null; echo "0 2 * * * $PHP_PATH $WEBSITE_DIR/gdpr_cleanup.php >> $LOG_DIR/gdpr_cleanup.log 2>&1") | crontab -
        
        echo "✅ Cron job added successfully!"
    fi
    
    echo ""
    echo "=== CRON JOB DETAILS ==="
    echo "Schedule: Daily at 2:00 AM"
    echo "Script: $WEBSITE_DIR/gdpr_cleanup.php"
    echo "Logs: $LOG_DIR/gdpr_cleanup.log"
    echo ""
    echo "To view current crontab:"
    echo "  crontab -l"
    echo ""
    echo "To monitor logs:"
    echo "  tail -f $LOG_DIR/gdpr_cleanup.log"
    echo ""
    echo "To test manually:"
    echo "  $PHP_PATH $WEBSITE_DIR/gdpr_cleanup.php"
    
else
    echo "❌ Script test failed"
    echo "Please check:"
    echo "1. Database connection in includes/config.php"
    echo "2. File permissions"
    echo "3. PHP error logs"
fi
