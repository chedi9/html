# Comprehensive Local Workspace Cleanup Script for Windows (PowerShell)
# Deletes unnecessary files from root and client directories

Write-Host "=== COMPREHENSIVE WORKSPACE CLEANUP ===" -ForegroundColor Green
Write-Host ""

# Files to delete from root directory
$rootFilesToDelete = @(
    # Cleanup scripts (after use)
    "cleanup_workspace.php",
    "cleanup_workspace.bat",
    "cleanup_workspace.ps1",
    "cleanup_workspace_local.bat",
    "cleanup_workspace_simple.ps1",
    "cleanup_server.php",
    
    # Temporary/development files
    "cleaner.php",
    "fix_translations.php",
    "translation_audit.php",
    "generate_existing_thumbnails.php",
    "priority_products_helper.php",
    "get_wallet_balance.php",
    "search_suggest.php",
    "faq.php",
    "my_orders.php",
    "submit_review.php",
    "review_vote_handler.php",
    "wishlist_action.php",
    "add_to_cart.php",
    
    # Documentation files (can be archived)
    "improvments.md",
    "SECURITY_IMPLEMENTATION_GUIDE.md",
    "GENDER_FIELD_IMPLEMENTATION.md",
    "WELCOME_EMAIL_SYSTEM.md",
    "automated_reports_setup.md",
    "database.txt",
    
    # Test/development files
    "security_testing_framework.php",
    "https_enforcement.php",
    "cleanup_pci_data.php",
    "pci_compliance_helper.php",
    "pci_compliant_payment_handler.php",
    "fraud_detection.php",
    "security_center.php",
    "payment_gateway_processor.php",
    "payment_processor.php",
    "enhanced_submit_review.php",
    "product_qa.php",
    "notifications_center.php",
    "promo_codes.php",
    "wallet.php",
    "order_confirmation.php",
    "order_details.php",
    "order_tracking.php",
    "reset_password.php",
    "forgot_password.php",
    "seller_manual.php",
    "tunisia_addresses.php",
    "tunisia_addresses_correct.php",
    "email_helper.php",
    "email_config.php",
    "lang.php",
    
    # Large files that can be optimized
    "beta333.css",
    "webuy-logo-transparent.jpg",
    "webuy.jpg",
    "webuy.png",
    "google-icon.svg",
    "cart.svg",
    "favicon.ico"
)

# Files to delete from client directory
$clientFilesToDelete = @(
    "mailer.php",
    "make_thumbnail.php",
    "google_callback.php",
    "google_login.php",
    "download_template.php",
    "export_orders.php",
    "update_address.php",
    "change_password.php",
    "update_credentials.php",
    "remove_from_wishlist.php"
)

# SQL files to archive
$sqlFilesToArchive = @(
    "rate_limits_migration.sql",
    "pci_compliance_migration.sql"
)

# Create archive directory with timestamp
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$archiveDir = "archive\comprehensive_cleanup_$timestamp"

if (!(Test-Path $archiveDir)) {
    New-Item -ItemType Directory -Path $archiveDir -Force | Out-Null
    Write-Host "SUCCESS: Created archive directory: $archiveDir" -ForegroundColor Green
}

# Delete root files
Write-Host "DELETING ROOT DIRECTORY FILES..." -ForegroundColor Yellow
$rootDeletedCount = 0
foreach ($file in $rootFilesToDelete) {
    if (Test-Path $file) {
        try {
            Remove-Item $file -Force
            Write-Host "DELETED: $file" -ForegroundColor Green
            $rootDeletedCount++
        }
        catch {
            Write-Host "FAILED TO DELETE: $file" -ForegroundColor Red
        }
    }
    else {
        Write-Host "NOT FOUND: $file" -ForegroundColor Yellow
    }
}

# Delete client files
Write-Host "DELETING CLIENT DIRECTORY FILES..." -ForegroundColor Yellow
$clientDeletedCount = 0
foreach ($file in $clientFilesToDelete) {
    $clientFile = "client\$file"
    if (Test-Path $clientFile) {
        try {
            Remove-Item $clientFile -Force
            Write-Host "DELETED: $clientFile" -ForegroundColor Green
            $clientDeletedCount++
        }
        catch {
            Write-Host "FAILED TO DELETE: $clientFile" -ForegroundColor Red
        }
    }
    else {
        Write-Host "NOT FOUND: $clientFile" -ForegroundColor Yellow
    }
}

# Archive SQL files
Write-Host "ARCHIVING SQL FILES..." -ForegroundColor Yellow
$archivedCount = 0
foreach ($file in $sqlFilesToArchive) {
    if (Test-Path $file) {
        try {
            $newPath = Join-Path $archiveDir (Split-Path $file -Leaf)
            Copy-Item $file $newPath
            Write-Host "ARCHIVED: $file -> $newPath" -ForegroundColor Green
            $archivedCount++
            # Delete original after archiving
            Remove-Item $file -Force
        }
        catch {
            Write-Host "FAILED TO ARCHIVE: $file" -ForegroundColor Red
        }
    }
    else {
        Write-Host "NOT FOUND: $file" -ForegroundColor Yellow
    }
}

# Create summary
Write-Host "CLEANUP SUMMARY:" -ForegroundColor Cyan
Write-Host "• Deleted $rootDeletedCount root files" -ForegroundColor White
Write-Host "• Deleted $clientDeletedCount client files" -ForegroundColor White
Write-Host "• Archived $archivedCount SQL files" -ForegroundColor White
Write-Host "• Archive directory: $archiveDir" -ForegroundColor White

# List remaining important files
Write-Host "IMPORTANT FILES REMAINING:" -ForegroundColor Cyan
$importantFiles = @(
    "index.php",
    "security_integration.php",
    "security_integration_admin.php",
    "security_headers.php",
    "web_application_firewall.php",
    "enhanced_rate_limiting.php",
    "cookie_consent_banner.php",
    "cookies.php",
    "privacy.php",
    "checkout.php",
    "cart.php",
    "wishlist.php",
    "store.php",
    "product.php",
    "composer.json",
    "db.php",
    "main.js",
    "admin\login.php",
    "admin\unified_dashboard.php",
    "admin\dashboard.php",
    "admin\security_dashboard.php",
    "admin\security_personnel.php",
    "admin\admins.php",
    "admin\add_admin.php",
    "client\login.php",
    "client\register.php",
    "client\verify.php",
    "client\account.php",
    "client\seller_dashboard.php",
    "client\add_product.php",
    "client\bulk_upload.php",
    "client\orders.php",
    "client\notifications.php",
    "client\manage_payment_methods.php",
    "client\manage_addresses.php",
    "client\request_return.php",
    "client\user_notifications.php",
    "client\seller_help.php",
    "client\logout.php"
)

foreach ($file in $importantFiles) {
    if (Test-Path $file) {
        Write-Host "EXISTS: $file" -ForegroundColor Green
    }
    else {
        Write-Host "MISSING: $file" -ForegroundColor Red
    }
}

Write-Host "NEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Verify all important files are present" -ForegroundColor White
Write-Host "2. Test the main website: index.php" -ForegroundColor White
Write-Host "3. Test admin login: admin\login.php" -ForegroundColor White
Write-Host "4. Test client login: client\login.php" -ForegroundColor White
Write-Host "5. Delete this script after verification" -ForegroundColor White

Write-Host ""
Write-Host "COMPREHENSIVE WORKSPACE CLEANUP COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 