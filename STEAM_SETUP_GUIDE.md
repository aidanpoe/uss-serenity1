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
1. Run the database update script by visiting: `http://yourdomain.com/setup_steam.php`
2. This adds the necessary columns to support Steam IDs and user management

### 4. How It Works

#### For New Steam Users:
1. Click "Sign in through Steam" on the homepage
2. Steam authentication redirects to registration page
3. User creates username and **must create their crew roster profile** (name, species, department, position, etc.)
4. **Department selection determines system access permissions:**
   - **Medical/Science** → MED/SCI access group
   - **Engineering/Operations** → ENG/OPS access group  
   - **Security/Tactical** → SEC/TAC access group
5. Account and roster entry are created simultaneously and user is logged in (no password needed)

#### For Existing Users:
- All users must now use Steam authentication
- Traditional login/password has been disabled
- Contact your Captain to link existing accounts to Steam IDs

#### Admin Features:
- Captains can view user information via User Management
- Account status can be managed (active/inactive)
- View last login times and Steam account linkage

### 5. Features

✅ **Steam-Only Authentication** - Secure login exclusively via Steam  
✅ **Integrated Roster Creation** - Crew roster profile created during Steam registration  
✅ **Mandatory Character Creation** - All users must create their character during signup  
✅ **Department-Based Permissions** - Access groups automatically assigned based on department selection  
✅ **Rank Restrictions** - Command ranks (Captain, Commander) restricted to admin assignment  
✅ **User Profile Management** - View Steam account info and roster details  
✅ **Captain Admin Controls** - Account management and status control  
✅ **Enhanced Security** - Steam handles all authentication securely  
✅ **LCARS Styling** - Consistent Star Trek interface design  

### 6. User Experience

**Homepage Updates:**
- Steam login button is the only authentication option
- Clean, streamlined interface focused on Steam authentication
- Profile and admin links appear for logged-in users

**New Pages:**
- `pages/steam_register.php` - Steam user registration with mandatory roster creation
- `pages/profile.php` - User profile management (Steam-focused)
- `pages/user_management.php` - Captain admin interface

**Removed Pages:**
- Traditional login and registration forms redirect to Steam authentication
- Password change functionality removed (Steam handles authentication)

**Database Enhancements:**
- `steam_id` column in users table (required for all users)
- `department` column in users table (stores permission groups: MED/SCI, ENG/OPS, SEC/TAC)
- `active`, `last_login` columns for user management
- `user_id` column in roster table (links Steam accounts to crew profiles)
- Password column optional (Steam authentication only)

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
- All authentication is handled by Steam (no local passwords)
- Captains can manage account status (active/inactive)
- Traditional login methods have been disabled for enhanced security
- Users must have valid Steam accounts to access the system

Your USS Serenity website now has a streamlined Steam-only authentication system while maintaining the original LCARS design aesthetic!
