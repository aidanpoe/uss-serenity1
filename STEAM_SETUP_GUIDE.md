# USS Serenity Steam Authentication Integration

Your USS Serenity website now supports Steam authentication! Users can log in with their Steam accounts and link them to roster entries.

## Setup Instructions

### 1. Get a Steam API Key
1. Visit [https://steamcommunity.com/dev/apikey](https://steamcommunity.com/dev/apikey)
2. Log in with your Steam account
3. Enter your domain name (e.g., `yourdomain.com` or `subdomain.yourdomain.com`)
4. Copy the generated API key

### 2. Configure Steam Authentication
1. Edit `steamauth/SteamConfig.php`
2. Add your Steam API key: `$steamauth['apikey'] = "YOUR_API_KEY_HERE";`
3. Add your domain name: `$steamauth['domainname'] = "yourdomain.com";`

### 3. Update Database
1. Run the database update script by visiting: `http://yourdomain.com/update_steam_integration.php`
2. This adds the necessary columns to support Steam IDs and enhanced user management

### 4. How It Works

#### For New Steam Users:
1. Click "Sign in through Steam" on the homepage
2. Steam authentication redirects to registration page
3. User creates username/password and optionally links to roster entry
4. Account is created and user is logged in

#### For Existing Users:
1. Users can link their Steam account in their profile settings
2. Once linked, they can use either Steam login or traditional login

#### Admin Features:
- Captains can reset passwords to username via User Management
- Force password changes on next login
- View last login times and account status

### 5. Features

✅ **Steam OpenID Authentication** - Secure login via Steam  
✅ **Roster Integration** - Link Steam accounts to crew roster entries  
✅ **Dual Login Support** - Traditional and Steam login work simultaneously  
✅ **User Self-Management** - Profile editing, password changes, username changes  
✅ **Captain Admin Controls** - Password resets, account management  
✅ **Enhanced Security** - Force password changes, account status control  
✅ **LCARS Styling** - Consistent Star Trek interface design  

### 6. User Experience

**Homepage Updates:**
- Steam login button appears alongside traditional login options
- Proper logout handling for Steam vs traditional sessions
- Profile and admin links appear for logged-in users

**New Pages:**
- `pages/steam_register.php` - Steam user registration
- `pages/profile.php` - User profile management
- `pages/user_management.php` - Captain admin interface

**Database Enhancements:**
- `steam_id` column in users table
- `force_password_change`, `active`, `last_login` columns for user management

### 7. File Structure

```
steamauth/
├── SteamConfig.php      # Configuration (API key, domain)
├── steamauth.php        # Main authentication handler
├── userInfo.php         # Steam profile data
└── openid.php          # OpenID library

pages/
├── steam_register.php   # Steam user registration
├── profile.php         # User profile management
└── user_management.php  # Captain admin interface

Database Scripts:
├── update_steam_integration.php    # Database update script
└── update_user_management.php      # User management setup
```

### 8. Security Notes

- Steam API keys should be kept secure
- Users maintain separate passwords for non-Steam access
- Captains can reset passwords and force password changes
- Account status can be managed (active/inactive)

Your USS Serenity website now has a complete user management system with Steam integration while maintaining the original LCARS design aesthetic!
