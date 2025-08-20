#!/bin/bash
# USS Serenity Message Cleanup Cron Job
# Add this to your crontab to run daily at 2 AM:
# 0 2 * * * /path/to/your/uss-serenity1/message_cleanup.sh

# Change to the script directory
cd "$(dirname "$0")"

# Run the cleanup script
php cleanup_messages.php

# Optional: Log the result
echo "$(date): Message cleanup completed" >> logs/cleanup.log
