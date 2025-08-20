@echo off
REM USS Serenity Message Cleanup Script for Windows
REM Schedule this in Windows Task Scheduler to run daily

cd /d "%~dp0"
php cleanup_messages.php

REM Optional: Log the result
echo %date% %time%: Message cleanup completed >> logs\cleanup.log
