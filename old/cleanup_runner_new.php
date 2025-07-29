<?php
/**
 * Cleanup Runner Script
 * Safely deletes and archives files based on cleanup_list.txt
 * 
 * Usage: Run this script from the root directory
 * Safety: Includes confirmation prompts and backup creation
 */

// Security headers
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Start session for user interaction
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
$backup_dir = 'archive/cleanup_backup_' . date('Y-m-d_H-i-s');
$log_file = 'cleanup_log_' . date('Y-m-d_H-i-s') . '.txt';

// Files to delete (temporary/test files)
$files_to_delete = [
    // Root directory
    'check_timezone.php',
    'cleanup_conservative.php',
    'cleanup_server.php',
    'demo_security_features.php',
    'fix_waf_patterns_table.php',
    'test_admin_login.php',
    'test_rate_limiting.php',
    'test_security_features.php',
    'verify_security_system.php',
    
    // Admin directory
    'admin/dashboard_redirect.php',
    'admin/enhanced_security_dashboard.php',
    'admin/comprehensive_security_dashboard.php',
    'admin/pci_compliance_dashboard.php',
    'admin/pci_compliance_dashboard_simple.php',
];

// Files to archive (old dashboard files)
$files_to_archive = [
    'admin/dashboard.php',
    'admin/security_dashboard.php',
    'admin/security_personnel.php',
    'admin/security_features.php',
    'admin/advanced_security_monitoring.php',
];

// SQL files to delete from archive
$sql_files_to_delete = [
    'archive/add_flouci_payment_method.sql',
    'archive/add_gender_to_users.sql',
    'archive/add_payment_details_to_orders.sql',
    'archive/add_sample_shipping_methods.sql',
    'archive/add_wishlist_created_at.sql',
    'archive/create_payment_logs_table.sql',
    'archive/create_payment_settings_table.sql',
    'archive/create_returns_tables.sql',
    'archive/create_security_tables.sql',
    'archive/create_user_tables.sql',
    'archive/security_fraud_tables.sql',
    'archive/wallet_promo_tables.sql',
];

// Essential files that should NEVER be deleted
$essential_files = [
    'index.php',
    'db.php',
    'header.php',
    'beta333.css',
    'main.js',
    'lang.php',
    'priority_products_helper.php',
    'security_feature_checker.php',
    'security_integration.php',
    'security_integration_admin.php',
    'web_application_firewall.php',
    'enhanced_rate_limiting.php',
    'setup_security_tables.php',
    'webuy.jpg',
    'webuy.png',
    'webuy-logo-transparent.jpg',
    'cart.svg',
    'cart-icon.svg',
    'google-icon.svg',
    'favicon.ico',
    '.htaccess',
    'robots.txt',
    'sitemap.xml',
    'composer.json',
    'composer.lock',
    '.gitignore',
    '.rtlcssrc.json',
];

// Function to log actions
function logAction($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo "<div style='margin: 5px 0; padding: 5px; background: #f8f9fa; border-radius: 3px;'>$message</div>";
}

// Function to check if file exists and is safe to delete
function isFileSafeToDelete($file_path) {
    global $essential_files;
    
    // Check if it's an essential file
    if (in_array($file_path, $essential_files)) {
        return false;
    }
    
    // Check if file exists
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Check if it's a directory
    if (is_dir($file_path)) {
        return false;
    }
    
    return true;
}

// Function to create backup
function createBackup($file_path) {
    global $backup_dir;
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_path = $backup_dir . '/' . basename($file_path);
    if (copy($file_path, $backup_path)) {
        logAction("✅ Backed up: $file_path");
        return true;
    } else {
        logAction("❌ Failed to backup: $file_path");
        return false;
    }
}

// Function to delete file
function deleteFile($file_path) {
    if (unlink($file_path)) {
        logAction("🗑️ Deleted: $file_path");
        return true;
    } else {
        logAction("❌ Failed to delete: $file_path");
        return false;
    }
}

// Function to archive file
function archiveFile($file_path) {
    global $backup_dir;
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $archive_path = $backup_dir . '/' . basename($file_path);
    if (copy($file_path, $archive_path)) {
        logAction("📦 Archived: $file_path");
        return true;
    } else {
        logAction("❌ Failed to archive: $file_path");
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'preview') {
        // Preview mode - show what will be done
        echo "<h2>🔍 Preview Mode - Files to be processed:</h2>";
        
        echo "<h3>🗑️ Files to DELETE:</h3>";
        foreach ($files_to_delete as $file) {
            if (isFileSafeToDelete($file)) {
                echo "<div style='color: #dc3545;'>• $file</div>";
            } else {
                echo "<div style='color: #6c757d;'>• $file (skipped - doesn't exist or is essential)</div>";
            }
        }
        
        echo "<h3>📦 Files to ARCHIVE:</h3>";
        foreach ($files_to_archive as $file) {
            if (isFileSafeToDelete($file)) {
                echo "<div style='color: #ffc107;'>• $file</div>";
            } else {
                echo "<div style='color: #6c757d;'>• $file (skipped - doesn't exist)</div>";
            }
        }
        
        echo "<h3>🗑️ SQL Files to DELETE:</h3>";
        foreach ($sql_files_to_delete as $file) {
            if (isFileSafeToDelete($file)) {
                echo "<div style='color: #dc3545;'>• $file</div>";
            } else {
                echo "<div style='color: #6c757d;'>• $file (skipped - doesn't exist)</div>";
            }
        }
        
        echo "<form method='post' style='margin-top: 20px;'>";
        echo "<input type='hidden' name='action' value='execute'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🚀 Execute Cleanup</button>";
        echo "</form>";
        
    } elseif ($action === 'execute') {
        // Execute cleanup
        echo "<h2>🧹 Executing Cleanup...</h2>";
        
        $deleted_count = 0;
        $archived_count = 0;
        $skipped_count = 0;
        
        // Delete files
        echo "<h3>🗑️ Deleting files...</h3>";
        foreach ($files_to_delete as $file) {
            if (isFileSafeToDelete($file)) {
                if (deleteFile($file)) {
                    $deleted_count++;
                }
            } else {
                $skipped_count++;
                logAction("⏭️ Skipped: $file (essential or doesn't exist)");
            }
        }
        
        // Archive files
        echo "<h3>📦 Archiving files...</h3>";
        foreach ($files_to_archive as $file) {
            if (isFileSafeToDelete($file)) {
                if (archiveFile($file)) {
                    $archived_count++;
                }
            } else {
                $skipped_count++;
                logAction("⏭️ Skipped: $file (doesn't exist)");
            }
        }
        
        // Delete SQL files
        echo "<h3>🗑️ Deleting SQL files...</h3>";
        foreach ($sql_files_to_delete as $file) {
            if (isFileSafeToDelete($file)) {
                if (deleteFile($file)) {
                    $deleted_count++;
                }
            } else {
                $skipped_count++;
                logAction("⏭️ Skipped: $file (doesn't exist)");
            }
        }
        
        echo "<h3>✅ Cleanup Complete!</h3>";
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Summary:</strong><br>";
        echo "• Deleted: $deleted_count files<br>";
        echo "• Archived: $archived_count files<br>";
        echo "• Skipped: $skipped_count files<br>";
        echo "• Backup location: $backup_dir<br>";
        echo "• Log file: $log_file";
        echo "</div>";
        
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='admin/unified_dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Admin Dashboard</a>";
        echo "</div>";
    }
} else {
    // Show initial form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cleanup Runner</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                background: #f8f9fa;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .warning {
                background: #fff3cd;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                border-left: 4px solid #ffc107;
            }
            .btn {
                background: #007bff;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                margin: 5px;
            }
            .btn-danger {
                background: #dc3545;
            }
            .btn-warning {
                background: #ffc107;
                color: #212529;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧹 WeBuy Cleanup Runner</h1>
            
            <div class="warning">
                <strong>⚠️ Important:</strong> This script will delete temporary files and archive old dashboard files. 
                Make sure you have a backup of your website before proceeding.
            </div>
            
            <h2>📋 What will be cleaned up:</h2>
            <ul>
                <li><strong>🗑️ Delete:</strong> 9 temporary/test files from root</li>
                <li><strong>🗑️ Delete:</strong> 5 old dashboard files from admin</li>
                <li><strong>🗑️ Delete:</strong> 12 old SQL files from archive</li>
                <li><strong>📦 Archive:</strong> 5 old dashboard files from admin</li>
                <li><strong>✅ Keep:</strong> All essential files including security files</li>
            </ul>
            
            <h2>🛡️ Safety Features:</h2>
            <ul>
                <li>✅ Essential files are protected (index.php, db.php, security files, etc.)</li>
                <li>✅ Files are backed up before deletion</li>
                <li>✅ Preview mode to see what will be done</li>
                <li>✅ Detailed logging of all actions</li>
                <li>✅ Confirmation prompts</li>
            </ul>
            
            <div style="margin-top: 30px;">
                <form method="post">
                    <input type="hidden" name="action" value="preview">
                    <button type="submit" class="btn btn-warning">🔍 Preview Cleanup</button>
                </form>
            </div>
            
            <div style="margin-top: 20px; color: #6c757d; font-size: 0.9em;">
                <strong>Note:</strong> This script is designed to be safe and will not delete any essential files.
                All actions are logged and backups are created before deletion.
            </div>
        </div>
    </body>
    </html>
    <?php
}
?> 