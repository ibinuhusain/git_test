# Setting Up Your Apparels Collection System on Hostinger

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

## Testing After Setup

1. Visit your domain to check if the login page loads
2. Try logging in with default credentials:
   - Super Admin: `apprelsadmin` / `x9n6X8o1u41TSRU95`
   - Admin: `admin` / `admin123`
   - Agent: `agent1` / `agent123`
3. Test the logout functionality
4. Verify the bank approval page loads
5. Test import/export features with small CSV files first

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