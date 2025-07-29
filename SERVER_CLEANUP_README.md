# 🧹 WeBuy Server Cleanup Instructions

## 📋 Overview
This cleanup process moves temporary and test files to the `/old` directory while preserving all essential functionality files.

## 🚀 How to Run the Cleanup

### Option 1: Using the Batch File (Windows)
1. Upload `run_server_cleanup.bat` to your server
2. Double-click the batch file to run it
3. The script will automatically execute and show results

### Option 2: Using the PHP Script Directly
1. Upload `server_cleanup.php` to your server
2. Open the script in your web browser: `https://yoursite.com/server_cleanup.php`
3. The script will show a detailed report of the cleanup process

### Option 3: Command Line
1. Upload `server_cleanup.php` to your server
2. Run via SSH: `php server_cleanup.php`

## 📁 What Gets Cleaned Up

### ✅ Files Moved to `/old` Directory:
- `cleanup_runner.php` - Temporary cleanup script
- `cleanup_log_2025-07-28_20-28-17.txt` - Cleanup log file
- `security_testing_framework.php` - Security testing tool
- `translation_audit.php` - Translation audit tool
- `fix_translations.php` - Translation fix tool

### ✅ Files Preserved (Essential):

#### Root Directory:
- All security files (WAF, rate limiting, headers, integration)
- All payment processing files (PCI compliant handlers)
- All core functionality files (index.php, header.php, etc.)
- All configuration files (.htaccess, robots.txt, etc.)
- All documentation files (database.txt, LICENSE, etc.)
- All image and asset files (logos, icons, etc.)

#### Client Directory (User Functionality):
- `client/login.php` - User login
- `client/register.php` - User registration
- `client/account.php` - User account management
- `client/logout.php` - User logout
- `client/verify.php` - Email verification
- `client/forgot_password.php` - Password reset
- `client/change_password.php` - Password change
- `client/update_credentials.php` - Credential updates
- `client/manage_addresses.php` - Address management
- `client/manage_payment_methods.php` - Payment method management
- `client/orders.php` - User orders
- `client/notifications.php` - User notifications
- `client/user_notifications.php` - User notification system
- `client/seller_dashboard.php` - Seller dashboard
- `client/add_product.php` - Add products
- `client/bulk_upload.php` - Bulk upload
- `client/download_template.php` - Download templates
- `client/export_orders.php` - Export orders
- `client/mailer.php` - Email functionality
- `client/make_thumbnail.php` - Image processing
- `client/remove_from_wishlist.php` - Wishlist management
- `client/request_return.php` - Return requests
- `client/seller_help.php` - Seller help
- `client/update_address.php` - Address updates
- `client/google_login.php` - Google login
- `client/google_callback.php` - Google callback

#### Admin Directory:
- All admin functionality files (dashboard, management, etc.)

### ✅ Directories Preserved:
- `admin/` - All admin functionality
- `client/` - All user functionality
- `data/` - Data files
- `webhooks/` - Payment webhooks
- `lang/` - Language files
- `uploads/` - User uploads
- `archive/` - Archive files

## 🔒 Safety Features

### ✅ Built-in Safety Checks:
- **File Verification**: Checks if files exist before moving
- **Directory Creation**: Creates `/old` directory if it doesn't exist
- **Error Handling**: Reports any errors during the process
- **Logging**: Creates a detailed log file of all operations
- **Backup**: Files are moved, not deleted (can be recovered)

### ✅ Essential Files Protection:
- Comprehensive list of essential files that will never be moved
- Verification that all critical functionality is preserved
- Directory structure maintenance

## 📊 Expected Results

After running the cleanup, you should see:
- ✅ **5 temporary files** moved to `/old` directory
- ✅ **All essential files** preserved in their original locations
- ✅ **All directories** maintained
- ✅ **No functionality lost**
- ✅ **Clean, organized workspace**

## 🛠️ Troubleshooting

### If the script fails:
1. Check file permissions (need write access)
2. Ensure PHP is installed and accessible
3. Check available disk space
4. Verify the script is in the correct directory

### If files are missing after cleanup:
1. Check the `/old` directory - files may have been moved there
2. Review the cleanup log file for details
3. Restore files from `/old` directory if needed

## 📝 Log Files

The cleanup process creates:
- **Cleanup log**: `cleanup_log_YYYY-MM-DD_HH-MM-SS.txt`
- **Updated cleanup list**: `cleanup_list.txt`

## 🎯 Post-Cleanup Verification

After running the cleanup, verify:
1. ✅ Website still loads properly
2. ✅ Admin panel works correctly
3. ✅ All security features are functional
4. ✅ Payment processing works
5. ✅ User functionality is intact

## 📞 Support

If you encounter any issues:
1. Check the cleanup log file for error details
2. Verify all essential files are present
3. Restore any accidentally moved files from `/old` directory
4. Contact support if needed

---

**Note**: This cleanup is designed to be safe and reversible. All files are moved to `/old` directory rather than deleted, so they can be recovered if needed. 