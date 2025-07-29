<?php
/**
 * Workspace Cleanup Script
 * Deletes temporary/test files and archives SQL files
 */

echo "<h1>🧹 تنظيف مساحة العمل</h1>";

// Files to delete (temporary/test files)
$files_to_delete = [
    // Root directory test files
    'test_security_login.sql',
    'check_tables.sql',
    'consolidate_admin_tables.sql',
    'add_security_role.sql',
    'check_role_structure.sql',
    'fix_security_user_complete.sql',
    'fix_security_password.sql',
    'generate_password_hash.php',
    'add_security_personnel.sql',
    'debug_session.php',
    'test_login_fix.php',
    'revert_security_personnel.sql',
    'add_security_personnel.php',
    'test_security_roles.php',
    'setup_security_roles.php',
    'admin_users_migration.sql',
    'security_dashboard.php',
    'verify_security_system.php',
    'fix_security_tables.php',
    'security_tables_migration.sql',
    'test_session_fix.php',
    'block_ip.php',
    'test_waf.php',
    'test_security.php',
    'verify_security_installation.php',
    'run_waf_migration.php',
    'setup_security_system.php',
    'waf_migration.sql',
    'waf_database_tables.sql',
    'run_rate_limits_migration.php',
    'test_complete_security.php',
    'test_pci_compliance.php',
    'test_payment_gateway.php',
    'test_email_notifications.php',
    'generate_existing_thumbnails.php',
    
    // Admin directory test files
    'admin/test_login_fixed.php',
    'admin/security_diagnostic.php',
    'admin/login_simple_test.php',
    'admin/login_simple.php',
    'admin/test_beta333_compatibility.php',
    'admin/debug_toggle_issue.php',
    'admin/test_toggle_click.php',
    'admin/test_toggle_functionality.php',
    'admin/test_toggle_visibility.php',
    'admin/test_toggle_fix.php',
    'admin/test_layout.php',
    'admin/test_header_fix.php',
    'admin/diagnose_payment_settings.php',
    'admin/test_payment_settings.php',
    'admin/create_payment_settings_table.php',
    'admin/test_pci_helper.php',
    'admin/test_dashboard_buttons.php',
    'admin/download_csv_template.php'
];

// SQL files to archive
$sql_files_to_archive = [
    'rate_limits_migration.sql',
    'pci_compliance_migration.sql',
    'security_tables_migration.sql',
    'admin_users_migration.sql',
    'waf_migration.sql',
    'waf_database_tables.sql'
];

// Create archive directory if it doesn't exist
$archive_dir = 'archive/sql_migrations_' . date('Y-m-d_H-i-s');
if (!is_dir($archive_dir)) {
    mkdir($archive_dir, 0755, true);
    echo "<p>✅ تم إنشاء مجلد الأرشيف: $archive_dir</p>";
}

// Delete temporary files
echo "<h2>🗑️ حذف الملفات المؤقتة</h2>";
$deleted_count = 0;
foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p>✅ تم حذف: $file</p>";
            $deleted_count++;
        } else {
            echo "<p>❌ فشل في حذف: $file</p>";
        }
    } else {
        echo "<p>⚠️ الملف غير موجود: $file</p>";
    }
}

// Archive SQL files
echo "<h2>📦 أرشفة ملفات SQL</h2>";
$archived_count = 0;
foreach ($sql_files_to_archive as $file) {
    if (file_exists($file)) {
        $new_path = $archive_dir . '/' . basename($file);
        if (copy($file, $new_path)) {
            echo "<p>✅ تم أرشفة: $file → $new_path</p>";
            $archived_count++;
        } else {
            echo "<p>❌ فشل في أرشفة: $file</p>";
        }
    } else {
        echo "<p>⚠️ الملف غير موجود: $file</p>";
    }
}

// Create summary
echo "<h2>📊 ملخص التنظيف</h2>";
echo "<ul>";
echo "<li>تم حذف $deleted_count ملف مؤقت</li>";
echo "<li>تم أرشفة $archived_count ملف SQL</li>";
echo "<li>تم إنشاء مجلد الأرشيف: $archive_dir</li>";
echo "</ul>";

// List remaining important files
echo "<h2>📁 الملفات المتبقية المهمة</h2>";
$important_files = [
    'security_integration.php',
    'security_integration_admin.php',
    'security_headers.php',
    'web_application_firewall.php',
    'enhanced_rate_limiting.php',
    'admin/login.php',
    'admin/unified_dashboard.php',
    'admin/dashboard.php',
    'admin/security_dashboard.php',
    'admin/security_personnel.php',
    'admin/admins.php',
    'admin/add_admin.php'
];

echo "<ul>";
foreach ($important_files as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file</li>";
    } else {
        echo "<li>❌ $file (غير موجود)</li>";
    }
}
echo "</ul>";

echo "<h2>🎯 الخطوات التالية</h2>";
echo "<ul>";
echo "<li>1. تحقق من أن جميع الملفات المهمة موجودة</li>";
echo "<li>2. اختبر تسجيل الدخول: admin/login.php</li>";
echo "<li>3. اختبر لوحة التحكم الموحدة: admin/unified_dashboard.php</li>";
echo "<li>4. احذف هذا الملف بعد التأكد من كل شيء</li>";
echo "</ul>";

echo "<p><strong>تم تنظيف مساحة العمل بنجاح! 🎉</strong></p>";
?> 