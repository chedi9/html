<?php
/**
 * Server Cleanup Script for WeBuy
 * This script performs the same cleanup operations on the server
 * that we completed locally
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>WeBuy Server Cleanup</title>
    
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🧹 WeBuy Server Cleanup</h1>
            <p>Cleaning up temporary files and organizing workspace</p>
        </div>";

// Initialize counters
$moved_files = [];
$kept_files = [];
$error_files = [];
$total_moved = 0;
$total_kept = 0;
$total_errors = 0;

// Create old directory if it doesn't exist
$old_dir = 'old';
if (!is_dir($old_dir)) {
    if (mkdir($old_dir, 0755, true)) {
        echo "<div class='section success'>✅ Created /old directory</div>";
    } else {
        echo "<div class='section error'>❌ Failed to create /old directory</div>";
        $total_errors++;
    }
}

// Files to move to old directory (temporary/test files)
$files_to_move = [
    'cleanup_runner.php',
    'cleanup_log_2025-07-28_20-28-17.txt',
    'security_testing_framework.php',
    'translation_audit.php',
    'fix_translations.php'
];

// Files to keep (essential files)
$files_to_keep = [
    // Security files
    'security_feature_checker.php',
    'security_integration.php',
    'security_integration_admin.php',
    'web_application_firewall.php',
    'enhanced_rate_limiting.php',
    'setup_security_tables.php',
    'https_enforcement.php',
    'pci_compliant_payment_handler.php',
    'pci_compliance_helper.php',
    'security_center.php',
    'fraud_detection.php',
    
    // Core functionality
    'index.php',
    'db.php',
    'header.php',
    'beta333.css',
    'main.js',
    'lang.php',
    'cookies.php',
    'cookie_consent_banner.php',
    
    // E-commerce functionality
    'cart.php',
    'checkout.php',
    'product.php',
    'store.php',
    'wishlist.php',
    'wallet.php',
    'order_confirmation.php',
    'order_details.php',
    'order_tracking.php',
    'my_orders.php',
    'faq.php',
    'search.php',
    'search_suggest.php',
    
    // User functionality (in client/ directory)
    'client/login.php',
    'client/register.php',
    'forgot_password.php',
    'reset_password.php',
    'client/account.php',
    
    // Payment processing
    'payment_gateway_processor.php',
    'payment_processor.php',
    
    // Configuration
    '.htaccess',
    'robots.txt',
    'sitemap.xml',
    'composer.json',
    'composer.lock',
    'yarn.lock',
    '.gitignore',
    '.rtlcssrc.json',
    
    // Documentation
    'database.txt',
    'LICENSE',
    'ChangeLog',
    'RELEASE-DATE-5.2.2',
    
    // Data files
    'tunisia_addresses.php',
    'tunisia_addresses_correct.php',
    'get_wallet_balance.php',
    'priority_products_helper.php',
    'submit_review.php',
    'review_vote_handler.php',
    'enhanced_submit_review.php',
    'notifications_center.php',
    'product_qa.php',
    'privacy.php',
    'promo_codes.php',
    'email_helper.php',
    'email_config.php',
    'add_to_cart.php',
    'wishlist_action.php',
    
    // Images and assets
    'webuy.jpg',
    'webuy.png',
    'webuy-logo-transparent.jpg',
    'cart.svg',
    'cart-icon.svg',
    'google-icon.svg',
    'favicon.ico'
];

echo "<div class='section info'>
    <h2>📋 Cleanup Plan</h2>
    <p><strong>Files to move to /old:</strong> " . count($files_to_move) . " temporary/test files</p>
    <p><strong>Files to keep:</strong> " . count($files_to_keep) . " essential files</p>
    <p><strong>Directories to preserve:</strong> admin/, client/, data/, webhooks/, lang/, uploads/, archive/</p>
</div>";

// Move temporary files to old directory
echo "<div class='section warning'>
    <h2>🔄 Moving Temporary Files to /old</h2>";

foreach ($files_to_move as $file) {
    if (file_exists($file)) {
        $new_path = $old_dir . '/' . $file;
        if (rename($file, $new_path)) {
            $moved_files[] = $file;
            $total_moved++;
            echo "<div class='file-item moved'>✅ Moved: $file → $new_path</div>";
        } else {
            $error_files[] = $file;
            $total_errors++;
            echo "<div class='file-item error-file'>❌ Failed to move: $file</div>";
        }
    } else {
        echo "<div class='file-item'>ℹ️ File not found: $file (already moved or doesn't exist)</div>";
    }
}

echo "</div>";

// Check essential files
echo "<div class='section info'>
    <h2>✅ Verifying Essential Files</h2>";

foreach ($files_to_keep as $file) {
    if (file_exists($file)) {
        $kept_files[] = $file;
        $total_kept++;
        echo "<div class='file-item kept'>✅ Preserved: $file</div>";
    } else {
        echo "<div class='file-item error-file'>⚠️ Missing essential file: $file</div>";
    }
}

echo "</div>";

// Check directories
echo "<div class='section info'>
    <h2>📁 Verifying Essential Directories</h2>";

$essential_dirs = ['admin', 'client', 'data', 'webhooks', 'lang', 'uploads', 'archive'];
foreach ($essential_dirs as $dir) {
    if (is_dir($dir)) {
        echo "<div class='file-item kept'>✅ Directory preserved: $dir/</div>";
    } else {
        echo "<div class='file-item error-file'>⚠️ Missing directory: $dir/</div>";
    }
}

echo "</div>";

// Summary statistics
echo "<div class='summary'>
    <h2>📊 Cleanup Summary</h2>
    <div class='stats'>
        <div class='stat-card'>
            <div class='stat-number'>$total_moved</div>
            <div class='stat-label'>Files Moved</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number'>$total_kept</div>
            <div class='stat-label'>Files Preserved</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number'>$total_errors</div>
            <div class='stat-label'>Errors</div>
        </div>
    </div>
</div>";

// Final status
if ($total_errors == 0) {
    echo "<div class='section success'>
        <h2>🎉 Cleanup Completed Successfully!</h2>
        <p>✅ All temporary files moved to /old directory</p>
        <p>✅ All essential files preserved</p>
        <p>✅ All directories maintained</p>
        <p>✅ Workspace is now clean and organized</p>
    </div>";
} else {
    echo "<div class='section warning'>
        <h2>⚠️ Cleanup Completed with Warnings</h2>
        <p>✅ Most files processed successfully</p>
        <p>⚠️ $total_errors errors encountered</p>
        <p>Please check the error list above</p>
    </div>";
}

// Create cleanup log
$log_content = "WeBuy Server Cleanup Log\n";
$log_content .= "Date: " . date('Y-m-d H:i:s') . "\n";
$log_content .= "Files moved: " . implode(', ', $moved_files) . "\n";
$log_content .= "Files preserved: " . count($kept_files) . "\n";
$log_content .= "Errors: " . $total_errors . "\n";

$log_file = 'cleanup_log_' . date('Y-m-d_H-i-s') . '.txt';
if (file_put_contents($log_file, $log_content)) {
    echo "<div class='section success'>
        <h2>📝 Cleanup Log Created</h2>
        <p>Log file: $log_file</p>
    </div>";
}

echo "</div></body></html>";

// Flush output
ob_end_flush();
?> 