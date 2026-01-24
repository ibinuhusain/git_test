# Troubleshooting Guide for Hostinger Deployment

## Common Issues and Solutions

### 1. Pages Not Loading ("This site can't be reached")

**Possible Causes:**
- Incorrect file upload structure
- Missing .htaccess file
- Server configuration issues
- PHP errors preventing page rendering

**Solutions:**
1. Verify all files are uploaded to the correct directory (public_html or subdirectory)
2. Ensure the `.htaccess` file is present in the root directory
3. Check if your hosting plan supports PHP and MySQL
4. Enable error reporting temporarily by adding this to the top of problematic files:
   ```php
   <?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ?>
   ```

### 2. Database Connection Issues

**Symptoms:**
- Blank pages
- "Connection failed" messages
- "Database does not exist" errors

**Solutions:**
1. Verify database credentials in `config.php`
2. Make sure the database has been created in your Hostinger control panel
3. Check if the database host is different from 'localhost' (often includes port number like 'localhost:3306')
4. Contact Hostinger support to confirm MySQL service is active

### 3. Session Problems

**Symptoms:**
- Unable to stay logged in
- Random logouts
- Login not working

**Solutions:**
1. Check if the session directory is writable
2. Verify that cookies are allowed in your browser
3. Confirm that session.save_path is properly configured (contact your host if needed)

### 4. Form Submission Failures

**Symptoms:**
- Forms not submitting
- Redirects not working
- Import/export functions failing

**Solutions:**
1. Verify POST size limits in hosting configuration
2. Check file upload permissions
3. Confirm that the required PHP extensions are enabled

## Debugging Steps

### Step 1: Check Server Information
Access `info.php` to see server configuration and error messages:
```
http://yourdomain.com/info.php
```

### Step 2: Test Database Directly
Run `test_db.php` to check database connectivity:
```
http://yourdomain.com/test_db.php
```

### Step 3: Check File Permissions
Ensure the following permissions:
- PHP files: 644
- Directories: 755
- Upload directory: 755 or 777 (temporarily for testing)

### Step 4: Enable Error Logging
Add this to the beginning of problematic files:
```php
<?php
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/php_error.log');
error_reporting(E_ALL);
?>
```

Then check the generated `php_error.log` file for specific error messages.

## Web Hosting Specific Issues

### URL Rewriting
Make sure your hosting provider has Apache mod_rewrite enabled. The .htaccess file should handle URL routing.

### PHP Version Compatibility
Check that your hosting uses PHP 7.0 or higher. Add this to a test file to check:
```php
<?php echo 'PHP Version: ' . phpversion(); ?>
```

### Memory and Time Limits
Web hosts often have strict limits. The .htaccess file includes settings to increase these limits:
```
php_value memory_limit 256M
php_value max_execution_time 300
```

## Quick Fixes for Common Scenarios

### Scenario 1: All pages show "This site can't be reached"
1. Verify the files are in the correct directory (public_html)
2. Check if .htaccess file was uploaded (it might be hidden)
3. Try accessing index.php directly: `http://yourdomain.com/index.php`

### Scenario 2: Login page loads but login fails
1. Verify the database has been initialized
2. Check if default users were created
3. Run test_db.php to verify database functionality

### Scenario 3: Admin pages redirect to index.php
1. This is likely a session/authorization issue
2. Check if you're logged in as an admin user
3. Verify session functionality with the debug steps above

## Contacting Hostinger Support

If issues persist, contact Hostinger support with:
1. Specific error messages (from error logs)
2. The URL where the issue occurs
3. Steps you've taken to troubleshoot
4. The fact that you're running a PHP/MySQL application

## Security Notes

After successful deployment and testing:
1. Remove or rename `info.php` for security
2. Secure the uploads directory if possible
3. Consider changing default passwords
4. Regularly update your application and hosting environment