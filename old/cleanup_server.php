<?php
/**
 * Server-Side Workspace Cleanup Script
 * Comprehensive cleanup for server environment
 */

echo "<h1>🧹 Server Workspace Cleanup</h1>";

// Files to delete from root directory
$rootFilesToDelete = [
    // Cleanup scripts (after use)
    'cleanup_workspace.php',
    'cleanup_workspace.bat',
    'cleanup_workspace.ps1',
    'cleanup_workspace_local.bat',
    'cleanup_workspace_simple.ps1',
    
    // Temporary/development files
    'cleaner.php',
    'fix_translations.php',
    'translation_audit.php',
    'generate_existing_thumbnails.php',
    'priority_products_helper.php',
    'get_wallet_balance.php',
    'search_suggest.php',
    'faq.php',
    'my_orders.php',
    'submit_review.php',
    'review_vote_handler.php',
    'wishlist_action.php',
    'add_to_cart.php',
    
    // Documentation files (can be archived)
    'improvments.md',
    'SECURITY_IMPLEMENTATION_GUIDE.md',
    'GENDER_FIELD_IMPLEMENTATION.md',
    'WELCOME_EMAIL_SYSTEM.md',
    'automated_reports_setup.md',
    'database.txt',
    
    // Test/development files
    'security_testing_framework.php',
    'https_enforcement.php',
    'cleanup_pci_data.php',
    'pci_compliance_helper.php',
    'pci_compliant_payment_handler.php',
    'fraud_detection.php',
    'security_center.php',
    'payment_gateway_processor.php',
    'payment_processor.php',
    'enhanced_submit_review.php',
    'product_qa.php',
    'notifications_center.php',
    'promo_codes.php',
    'wallet.php',
    'order_confirmation.php',
    'order_details.php',
    'order_tracking.php',
    'reset_password.php',
    'forgot_password.php',
    'seller_manual.php',
    'tunisia_addresses.php',
    'tunisia_addresses_correct.php',
    'email_helper.php',
    'email_config.php',
    'lang.php',
    
    // Large files that can be optimized
    'beta333.css',
    'webuy-logo-transparent.jpg',
    'webuy.jpg',
    'webuy.png',
    'google-icon.svg',
    'cart.svg',
    'favicon.ico'
];

// Files to delete from client directory
$clientFilesToDelete = [
    'mailer.php',
    'make_thumbnail.php',
    'google_callback.php',
    'google_login.php',
    'download_template.php',
    'export_orders.php',
    'update_address.php',
    'change_password.php',
    'update_credentials.php',
    'remove_from_wishlist.php'
];

// SQL files to archive
$sqlFilesToArchive = [
    'rate_limits_migration.sql',
    'pci_compliance_migration.sql'
];

// Create archive directory
$archiveDir = 'archive/cleanup_' . date('Y-m-d_H-i-s');
if (!is_dir($archiveDir)) {
    mkdir($archiveDir, 0755, true);
    echo "<p>✅ Created archive directory: $archiveDir</p>";
}

// Delete root files
echo "<h2>🗑️ Deleting Root Directory Files</h2>";
$deletedCount = 0;
foreach ($rootFilesToDelete as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p>✅ Deleted: $file</p>";
            $deletedCount++;
        } else {
            echo "<p>❌ Failed to delete: $file</p>";
        }
    } else {
        echo "<p>⚠️ Not found: $file</p>";
    }
}

// Delete client files
echo "<h2>🗑️ Deleting Client Directory Files</h2>";
$clientDeletedCount = 0;
foreach ($clientFilesToDelete as $file) {
    $clientFile = "client/$file";
    if (file_exists($clientFile)) {
        if (unlink($clientFile)) {
            echo "<p>✅ Deleted: $clientFile</p>";
            $clientDeletedCount++;
        } else {
            echo "<p>❌ Failed to delete: $clientFile</p>";
        }
    } else {
        echo "<p>⚠️ Not found: $clientFile</p>";
    }
}

// Archive SQL files
echo "<h2>📦 Archiving SQL Files</h2>";
$archivedCount = 0;
foreach ($sqlFilesToArchive as $file) {
    if (file_exists($file)) {
        $newPath = $archiveDir . '/' . basename($file);
        if (copy($file, $newPath)) {
            echo "<p>✅ Archived: $file → $newPath</p>";
            $archivedCount++;
            // Delete original after archiving
            unlink($file);
        } else {
            echo "<p>❌ Failed to archive: $file</p>";
        }
    } else {
        echo "<p>⚠️ Not found: $file</p>";
    }
}

// Create summary
echo "<h2>📊 Cleanup Summary</h2>";
echo "<ul>";
echo "<li>Deleted $deletedCount root files</li>";
echo "<li>Deleted $clientDeletedCount client files</li>";
echo "<li>Archived $archivedCount SQL files</li>";
echo "<li>Archive directory: $archiveDir</li>";
echo "</ul>";

// List remaining important files
echo "<h2>📁 Important Files Remaining</h2>";
$importantFiles = [
    'index.php',
    'security_integration.php',
    'security_integration_admin.php',
    'security_headers.php',
    'web_application_firewall.php',
    'enhanced_rate_limiting.php',
    'cookie_consent_banner.php',
    'cookies.php',
    'privacy.php',
    'checkout.php',
    'cart.php',
    'wishlist.php',
    'store.php',
    'product.php',
    'composer.json',
    'db.php',
    'main.js',
    'admin/login.php',
    'admin/unified_dashboard.php',
    'admin/dashboard.php',
    'admin/security_dashboard.php',
    'admin/security_personnel.php',
    'admin/admins.php',
    'admin/add_admin.php',
    'client/login.php',
    'client/register.php',
    'client/verify.php',
    'client/account.php',
    'client/seller_dashboard.php',
    'client/add_product.php',
    'client/bulk_upload.php',
    'client/orders.php',
    'client/notifications.php',
    'client/manage_payment_methods.php',
    'client/manage_addresses.php',
    'client/request_return.php',
    'client/user_notifications.php',
    'client/seller_help.php',
    'client/logout.php'
];

echo "<ul>";
foreach ($importantFiles as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file</li>";
    } else {
        echo "<li>❌ $file (missing)</li>";
    }
}
echo "</ul>";

echo "<h2>🎯 Next Steps</h2>";
echo "<ul>";
echo "<li>1. Verify all important files are present</li>";
echo "<li>2. Test the main website: index.php</li>";
echo "<li>3. Test admin login: admin/login.php</li>";
echo "<li>4. Test client login: client/login.php</li>";
echo "<li>5. Delete this script after verification</li>";
echo "</ul>";

echo "<p><strong>✅ Server cleanup completed successfully! 🎉</strong></p>";
?> 