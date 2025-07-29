# Workspace Cleanup Script for Windows (PowerShell)
# Deletes temporary/test files and archives SQL files

Write-Host "=== WORKSPACE CLEANUP ===" -ForegroundColor Green
Write-Host ""

# Files to delete (temporary/test files)
$filesToDelete = @(
    # Root directory test files
    "test_security_login.sql",
    "check_tables.sql",
    "consolidate_admin_tables.sql",
    "add_security_role.sql",
    "check_role_structure.sql",
    "fix_security_user_complete.sql",
    "fix_security_password.sql",
    "generate_password_hash.php",
    "add_security_personnel.sql",
    "debug_session.php",
    "test_login_fix.php",
    "revert_security_personnel.sql",
    "add_security_personnel.php",
    "test_security_roles.php",
    "setup_security_roles.php",
    "admin_users_migration.sql",
    "security_dashboard.php",
    "verify_security_system.php",
    "fix_security_tables.php",
    "security_tables_migration.sql",
    "test_session_fix.php",
    "block_ip.php",
    "test_waf.php",
    "test_security.php",
    "verify_security_installation.php",
    "run_waf_migration.php",
    "setup_security_system.php",
    "waf_migration.sql",
    "waf_database_tables.sql",
    "run_rate_limits_migration.php",
    "test_complete_security.php",
    "test_pci_compliance.php",
    "test_payment_gateway.php",
    "test_email_notifications.php",
    "generate_existing_thumbnails.php",
    
    # Admin directory test files
    "admin\test_login_fixed.php",
    "admin\security_diagnostic.php",
    "admin\login_simple_test.php",
    "admin\login_simple.php",
    "admin\test_beta333_compatibility.php",
    "admin\debug_toggle_issue.php",
    "admin\test_toggle_click.php",
    "admin\test_toggle_functionality.php",
    "admin\test_toggle_visibility.php",
    "admin\test_toggle_fix.php",
    "admin\test_layout.php",
    "admin\test_header_fix.php",
    "admin\diagnose_payment_settings.php",
    "admin\test_payment_settings.php",
    "admin\create_payment_settings_table.php",
    "admin\test_pci_helper.php",
    "admin\test_dashboard_buttons.php",
    "admin\download_csv_template.php"
)

# SQL files to archive
$sqlFilesToArchive = @(
    "rate_limits_migration.sql",
    "pci_compliance_migration.sql",
    "security_tables_migration.sql",
    "admin_users_migration.sql",
    "waf_migration.sql",
    "waf_database_tables.sql"
)

# Create archive directory with timestamp
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$archiveDir = "archive\sql_migrations_$timestamp"

if (!(Test-Path $archiveDir)) {
    New-Item -ItemType Directory -Path $archiveDir -Force | Out-Null
    Write-Host "SUCCESS: Created archive directory: $archiveDir" -ForegroundColor Green
}

# Delete temporary files
Write-Host "DELETING TEMPORARY FILES..." -ForegroundColor Yellow
$deletedCount = 0
foreach ($file in $filesToDelete) {
    if (Test-Path $file) {
        try {
            Remove-Item $file -Force
            Write-Host "DELETED: $file" -ForegroundColor Green
            $deletedCount++
        }
        catch {
            Write-Host "FAILED TO DELETE: $file" -ForegroundColor Red
        }
    }
    else {
        Write-Host "NOT FOUND: $file" -ForegroundColor Yellow
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
Write-Host "• Deleted $deletedCount temporary files" -ForegroundColor White
Write-Host "• Archived $archivedCount SQL files" -ForegroundColor White
Write-Host "• Archive directory: $archiveDir" -ForegroundColor White

# List remaining important files
Write-Host "IMPORTANT FILES REMAINING:" -ForegroundColor Cyan
$importantFiles = @(
    "security_integration.php",
    "security_integration_admin.php",
    "security_headers.php",
    "web_application_firewall.php",
    "enhanced_rate_limiting.php",
    "admin\login.php",
    "admin\unified_dashboard.php",
    "admin\dashboard.php",
    "admin\security_dashboard.php",
    "admin\security_personnel.php",
    "admin\admins.php",
    "admin\add_admin.php"
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
Write-Host "2. Test login: admin\login.php" -ForegroundColor White
Write-Host "3. Test dashboard: admin\unified_dashboard.php" -ForegroundColor White
Write-Host "4. Delete this script after verification" -ForegroundColor White

Write-Host ""
Write-Host "WORKSPACE CLEANUP COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 