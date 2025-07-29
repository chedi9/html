# Workspace Cleanup Script for Windows (PowerShell)
# Deletes temporary/test files and archives SQL files

Write-Host "🧹 تنظيف مساحة العمل" -ForegroundColor Green
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
    Write-Host "✅ تم إنشاء مجلد الأرشيف: $archiveDir" -ForegroundColor Green
}

# Delete temporary files
Write-Host "🗑️ حذف الملفات المؤقتة" -ForegroundColor Yellow
$deletedCount = 0
foreach ($file in $filesToDelete) {
    if (Test-Path $file) {
        try {
            Remove-Item $file -Force
            Write-Host "✅ تم حذف: $file" -ForegroundColor Green
            $deletedCount++
        }
        catch {
            Write-Host "❌ فشل في حذف: $file" -ForegroundColor Red
        }
    }
    else {
        Write-Host "⚠️ الملف غير موجود: $file" -ForegroundColor Yellow
    }
}

# Archive SQL files
Write-Host "📦 أرشفة ملفات SQL" -ForegroundColor Yellow
$archivedCount = 0
foreach ($file in $sqlFilesToArchive) {
    if (Test-Path $file) {
        try {
            $newPath = Join-Path $archiveDir (Split-Path $file -Leaf)
            Copy-Item $file $newPath
            Write-Host "✅ تم أرشفة: $file → $newPath" -ForegroundColor Green
            $archivedCount++
        }
        catch {
            Write-Host "❌ فشل في أرشفة: $file" -ForegroundColor Red
        }
    }
    else {
        Write-Host "⚠️ الملف غير موجود: $file" -ForegroundColor Yellow
    }
}

# Create summary
Write-Host "📊 ملخص التنظيف" -ForegroundColor Cyan
Write-Host "• تم حذف $deletedCount ملف مؤقت" -ForegroundColor White
Write-Host "• تم أرشفة $archivedCount ملف SQL" -ForegroundColor White
Write-Host "• تم إنشاء مجلد الأرشيف: $archiveDir" -ForegroundColor White

# List remaining important files
Write-Host "📁 الملفات المتبقية المهمة" -ForegroundColor Cyan
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
        Write-Host "✅ $file" -ForegroundColor Green
    }
    else {
        Write-Host "❌ $file (غير موجود)" -ForegroundColor Red
    }
}

Write-Host "🎯 الخطوات التالية" -ForegroundColor Cyan
Write-Host "1. تحقق من أن جميع الملفات المهمة موجودة" -ForegroundColor White
Write-Host "2. اختبر تسجيل الدخول: admin\login.php" -ForegroundColor White
Write-Host "3. اختبر لوحة التحكم الموحدة: admin\unified_dashboard.php" -ForegroundColor White
Write-Host "4. احذف هذا الملف بعد التأكد من كل شيء" -ForegroundColor White

Write-Host ""
Write-Host "✅ تم تنظيف مساحة العمل بنجاح! 🎉" -ForegroundColor Green
Write-Host ""
Write-Host "اضغط أي مفتاح للخروج..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 