# Garry's Mod Server Integration

This document describes the Garry's Mod server integration for the USS Serenity LCARS interface.

## Overview

The system displays real-time "Who's on Shift?" information by querying a Garry's Mod server. Due to common security restrictions on game servers, the system includes multiple fallback methods to ensure reliable status display.

## Features

- **Real-time player count and names** (when server allows queries)
- **Automatic status detection** with intelligent fallback handling
- **Manual admin override system** for when queries are disabled
- **Caching system** to reduce server load and improve performance
- **Multiple query methods** with graceful degradation

## Server Status Types

The system handles these status types:

1. **online_full_data** - Server online with full player information
2. **online_manual_update** - Server status manually updated by admin
3. **online_count_only** - Server online but only player count available
4. **online_queries_disabled** - Server online but queries blocked (common)
5. **offline** - Server confirmed offline
6. **unreachable** - Cannot determine server status

## Configuration

### Server Settings
Edit `includes/config.php` to configure your server:

```php
// Garry's Mod server configuration
define('GMOD_SERVER_IP', '46.4.12.78');
define('GMOD_SERVER_PORT', 27015);
```

### Cache Settings
```php
// Cache settings (in seconds)
define('GMOD_CACHE_DURATION', 60); // 1 minute
```

## Query Methods

The system attempts multiple query methods in order:

1. **Source Engine Query Protocol** - Direct UDP queries to server
2. **Basic Connection Test** - Checks if server port is reachable
3. **Manual Override** - Admin-configured status when queries fail

## Admin Panel

Command-level personnel can access the Server Admin Panel at `/server_admin.php` to:

- Manually update server status when queries are disabled
- Set player count and names manually
- Clear cache to force fresh queries
- View current cache status

## Common Issues & Solutions

### Server Queries Disabled
**Problem**: Server shows as "online but queries disabled"
**Cause**: Many Garry's Mod servers disable queries for security
**Solution**: Use the admin panel to manually update status

### Connection Timeouts
**Problem**: Server appears unreachable
**Cause**: Network issues or server actually offline
**Solution**: Check server status manually and update via admin panel

### Incorrect Player Count
**Problem**: Player count doesn't match actual server
**Cause**: Cached data or query restrictions
**Solution**: Use "Refresh Status" button or admin panel to update

## Files

### Core Files
- `includes/config.php` - Main server query functions
- `api/gmod_status.php` - JSON API endpoint for AJAX requests
- `server_admin.php` - Admin panel for manual updates
- `test_gmod.php` - Diagnostic and testing tools

### Functions

#### `getGmodPlayersOnline()`
Main function that returns server status with fallback handling.

#### `queryGmodServer($ip, $port)`
Direct Source Engine protocol query.

#### `testBasicConnection($ip, $port)`
Basic UDP connectivity test.

#### `updateManualServerStatus($status, $count, $players)`
Admin function to manually set server status.

## Security Notes

- Only Command-level users can access admin functions
- Server queries are cached to prevent abuse
- UDP queries are handled safely with timeouts
- Manual updates are logged with timestamps

## Integration Notes

The homepage automatically displays server status and refreshes every 30 seconds. The display adapts based on the available information:

- Full player list when available
- Player count only when names are restricted
- Appropriate messages for different status types
- Admin controls for authorized users

## Troubleshooting

1. **Check test page**: Visit `/test_gmod.php` for diagnostic information
2. **Review cache**: Admin panel shows current cache status
3. **Manual override**: Use admin panel when automatic queries fail
4. **Check permissions**: Ensure proper user access levels
