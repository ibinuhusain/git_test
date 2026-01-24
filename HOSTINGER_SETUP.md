# Setting Up Your Apparels Collection System on Hostinger

## Prerequisites

- A Hostinger account with hosting plan
- FTP client (like FileZilla) or Hostinger's file manager
- Database access through Hostinger's control panel (hPanel)

## Database Configuration

On Hostinger, you need to update your database settings. The application now supports environment variables for database configuration:

1. **Using Environment Variables (Recommended)**:
   - Set these environment variables in your hosting control panel:
     ```
     DB_HOST=your_hostinger_mysql_host
     DB_USER=your_database_username  
     DB_PASS=your_database_password
     DB_NAME=your_database_name
     ```

2. **Direct Configuration**:
   - Edit `config.php` to use your Hostinger database credentials:
     ```php
     define('DB_HOST', 'your_mysql_host');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'your_database_name');
     ```

## Upload Instructions

1. Upload all files to your Hostinger public_html folder
2. Make sure the following directories have write permissions:
   - `/uploads/` (for file uploads)
   - Root directory (for session files)
3. Ensure the `.htaccess` file is included in your upload (it handles URL rewriting)

## Troubleshooting Page Loading Issues

If pages are not loading (showing "This site can't be reached" errors):

1. **Check .htaccess file**: We've included a .htaccess file to handle URL rewriting on Apache servers
2. **Verify file permissions**: Make sure PHP files have 644 permissions and directories have 755
3. **Test database connection**: Run `info.php` to check if database connection works
4. **Enable error reporting**: Temporarily enable error display to see specific issues

## Import/Export Functionality

The import/export functionality has been optimized for web hosting environments:

- Removed dependency on PhpOffice/PhpSpreadsheet to avoid compatibility issues
- Uses standard CSV processing which works reliably on all hosting platforms
- Both agent import and assignment import now work with basic CSV files

## Logout Functionality

Fixed the logout button issue:
- Proper session handling through the auth system
- Correct redirect to login page
- Complete session cleanup

## Bank Approval Page

Fixed loading issues:
- Proper error handling
- Correct authentication checks
- Optimized database queries for web hosting environments

## Common Issues on Web Hosting

1. **File Permissions**: Make sure PHP files have proper permissions (usually 644)
2. **Session Path**: If sessions aren't working, contact your host to verify session directory permissions
3. **Memory Limits**: Some imports might fail due to memory limits; keep CSV files reasonably sized
4. **URL Rewriting**: Apache mod_rewrite needs to be enabled (standard on most hosts)
5. **Database Connection**: Web hosting databases often require specific hostnames (like localhost:3306 or a custom hostname)

## Testing After Setup

1. Visit your domain to check if the login page loads
2. Try logging in with default credentials:
   - Super Admin: `apprelsadmin` / `x9n6X8o1u41TSRU95`
   - Admin: `admin` / `admin123`
   - Agent: `agent1` / `agent123`
3. Test the logout functionality
4. Verify the bank approval page loads
5. Test import/export features with small CSV files first
6. Run `info.php` to check server configuration

## Debugging with info.php

We've included an `info.php` file to help troubleshoot server issues:

1. Access `http://yourdomain.com/info.php` to check:
   - PHP version and configuration
   - Database connectivity
   - File permissions
   - Server environment
2. Remove this file after setup for security

## Sample CSV Format

For importing agents, use this format:
```
Agent_Name,Username,Userid,Phone,Password
John Doe,jdoe,1,1234567890,password123
```

For importing assignments, use this format:
```
Agent_Name,Region,Mall,Entity,Brand
John Doe,North,Mall A,Entity A,Brand A
```