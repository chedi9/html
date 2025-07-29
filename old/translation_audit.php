<?php
/**
 * Translation Audit Script for WeBuy
 * This script identifies all missing translations and inconsistencies
 */

require_once 'db.php';
require_once 'lang.php';

echo "<h1>🔍 Translation Audit Report</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { background: #ffe6e6; border-color: #ff9999; }
    .warning { background: #fff3cd; border-color: #ffeaa7; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f8f9fa; }
</style>";

// Load current translations
$current_translations = $trans;

// Files to scan for translations
$files_to_scan = [
    'wallet.php',
    'security_center.php', 
    'promo_codes.php',
    'notifications_center.php',
    'order_confirmation.php',
    'product.php',
    'index.php',
    'wishlist.php',
    'my_orders.php',
    'order_details.php',
    'order_tracking.php',
    'store.php'
];

$missing_translations = [];
$inconsistent_usage = [];
$hardcoded_strings = [];

echo "<div class='section success'>";
echo "<h2>📊 Current Translation Status</h2>";
echo "<p><strong>Total translations available:</strong> " . count($current_translations) . "</p>";
echo "<p><strong>Files scanned:</strong> " . count($files_to_scan) . "</p>";
echo "</div>";

// Scan for $lang[] usage (old method)
echo "<div class='section warning'>";
echo "<h2>⚠️ Old Translation Method Usage (\$lang[])</h2>";
echo "<p>These files are still using the old \$lang[] method instead of the new __() function:</p>";

$old_method_files = [];
foreach ($files_to_scan as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match_all('/\$lang\[[\'"]([^\'"]+)[\'"]\]/', $content, $matches)) {
            $old_method_files[$file] = $matches[1];
        }
    }
}

if (empty($old_method_files)) {
    echo "<p class='success'>✅ No files using old \$lang[] method found!</p>";
} else {
    echo "<table>";
    echo "<tr><th>File</th><th>Translation Keys</th><th>Status</th></tr>";
    foreach ($old_method_files as $file => $keys) {
        echo "<tr>";
        echo "<td><code>$file</code></td>";
        echo "<td>" . implode(', ', array_unique($keys)) . "</td>";
        echo "<td>❌ Needs update to __() function</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// Scan for missing translations
echo "<div class='section error'>";
echo "<h2>❌ Missing Translations</h2>";

$all_used_keys = [];

// Collect all used translation keys
foreach ($files_to_scan as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Find __() function calls
        if (preg_match_all('/__\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            foreach ($matches[1] as $key) {
                $all_used_keys[] = $key;
            }
        }
        
        // Find $lang[] usage
        if (preg_match_all('/\$lang\[[\'"]([^\'"]+)[\'"]\]/', $content, $matches)) {
            foreach ($matches[1] as $key) {
                $all_used_keys[] = $key;
            }
        }
    }
}

$all_used_keys = array_unique($all_used_keys);
$missing_keys = array_diff($all_used_keys, array_keys($current_translations));

if (empty($missing_keys)) {
    echo "<p class='success'>✅ All translation keys are defined!</p>";
} else {
    echo "<p><strong>Missing translation keys (" . count($missing_keys) . "):</strong></p>";
    echo "<table>";
    echo "<tr><th>Translation Key</th><th>Suggested Arabic Translation</th></tr>";
    
    foreach ($missing_keys as $key) {
        echo "<tr>";
        echo "<td><code>$key</code></td>";
        echo "<td><em>Translation needed</em></td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// Scan for hardcoded strings
echo "<div class='section warning'>";
echo "<h2>⚠️ Hardcoded Arabic Strings</h2>";
echo "<p>These files contain hardcoded Arabic text that should be translated:</p>";

$hardcoded_patterns = [
    '/[\u0600-\u06FF]+/u', // Arabic Unicode range
    '/بطاقة بنكية/',
    '/تحويل بنكي/',
    '/الدفع عند الاستلام/',
    '/رقم حامل البطاقة/',
    '/نوع البطاقة/',
    '/تاريخ الانتهاء/',
    '/رقم الهاتف/',
    '/اسم البنك/',
    '/اسم صاحب الحساب/',
    '/رقم المرجع/',
    '/سيتم إرسال رابط الدفع/',
    '/سيتم إرسال تفاصيل الحساب/',
    '/سيتم الدفع عند استلام الطلب/'
];

$hardcoded_files = [];
foreach ($files_to_scan as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hardcoded_matches = [];
        
        foreach ($hardcoded_patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $hardcoded_matches = array_merge($hardcoded_matches, $matches[0]);
            }
        }
        
        if (!empty($hardcoded_matches)) {
            $hardcoded_files[$file] = array_unique($hardcoded_matches);
        }
    }
}

if (empty($hardcoded_files)) {
    echo "<p class='success'>✅ No hardcoded Arabic strings found!</p>";
} else {
    echo "<table>";
    echo "<tr><th>File</th><th>Hardcoded Strings</th></tr>";
    foreach ($hardcoded_files as $file => $strings) {
        echo "<tr>";
        echo "<td><code>$file</code></td>";
        echo "<td>" . implode('<br>', array_slice($strings, 0, 5)) . 
             (count($strings) > 5 ? '<br><em>... and ' . (count($strings) - 5) . ' more</em>' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// Generate missing translations
echo "<div class='section'>";
echo "<h2>📝 Missing Translation Keys to Add</h2>";
echo "<p>Add these keys to your <code>lang/ar.php</code> file:</p>";

if (!empty($missing_keys)) {
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    foreach ($missing_keys as $key) {
        echo "    '$key' => 'Translation needed',\n";
    }
    echo "</pre>";
}

// Generate suggested translations for common patterns
echo "<h3>🎯 Suggested Translations for Common Patterns</h3>";
$suggested_translations = [
    'wallet_loyalty' => 'المحفظة ونقاط الولاء',
    'manage_your_wallet_and_earn_rewards' => 'إدارة محفظتك وكسب المكافآت',
    'wallet_balance' => 'رصيد المحفظة',
    'available_for_purchases' => 'متاح للشراء',
    'loyalty_points' => 'نقاط الولاء',
    'earned_from_purchases' => 'مكتسب من المشتريات',
    'loyalty_tier' => 'مستوى الولاء',
    'current_membership_level' => 'مستوى العضوية الحالي',
    'progress_to' => 'التقدم نحو',
    'complete' => 'مكتمل',
    'wallet_actions' => 'إجراءات المحفظة',
    'add_funds' => 'إضافة أموال',
    'enter_amount' => 'أدخل المبلغ',
    'add_to_wallet' => 'إضافة إلى المحفظة',
    'redeem_points' => 'استبدال النقاط',
    'enter_points' => 'أدخل النقاط',
    'redeem_for_cash' => 'استبدال نقداً',
    'points_exchange_rate' => 'سعر صرف النقاط',
    'recent_transactions' => 'المعاملات الأخيرة',
    'no_transactions_yet' => 'لا توجد معاملات بعد',
    'loyalty_benefits' => 'مزايا الولاء',
    'points_earned' => 'النقاط المكتسبة',
    'earn_points_on_every_purchase' => 'اكسب نقاط على كل عملية شراء',
    'cash_back' => 'استرداد نقدي',
    'redeem_points_for_cash' => 'استبدل النقاط نقداً',
    'free_shipping' => 'شحن مجاني',
    'free_shipping_on_orders' => 'شحن مجاني على الطلبات',
    'exclusive_offers' => 'عروض حصرية',
    'access_to_exclusive_deals' => 'الوصول إلى الصفقات الحصرية',
    'priority_support' => 'دعم ذو أولوية',
    'faster_customer_support' => 'دعم عملاء أسرع',
    'early_access' => 'وصول مبكر',
    'early_access_to_new_products' => 'وصول مبكر للمنتجات الجديدة',
    'please_enter_valid_amount' => 'يرجى إدخال مبلغ صحيح',
    'minimum_points_required' => 'الحد الأدنى من النقاط المطلوبة',
    'security_center' => 'مركز الأمان',
    'manage_your_account_security' => 'إدارة أمان حسابك',
    'total_logins' => 'إجمالي تسجيلات الدخول',
    'all_time_logins' => 'جميع تسجيلات الدخول',
    'successful_logins' => 'تسجيلات الدخول الناجحة',
    'successful_attempts' => 'المحاولات الناجحة',
    'failed_logins' => 'تسجيلات الدخول الفاشلة',
    'failed_attempts' => 'المحاولات الفاشلة',
    'password_changes' => 'تغييرات كلمة المرور',
    'times_changed' => 'مرات التغيير',
    'security_tips' => 'نصائح الأمان',
    'use_strong_password' => 'استخدم كلمة مرور قوية',
    'enable_2fa' => 'تفعيل المصادقة الثنائية',
    'never_share_credentials' => 'لا تشارك بيانات الاعتماد أبداً',
    'log_out_public_devices' => 'تسجيل الخروج من الأجهزة العامة',
    'monitor_login_activity' => 'مراقبة نشاط تسجيل الدخول',
    'change_password' => 'تغيير كلمة المرور',
    'current_password' => 'كلمة المرور الحالية',
    'new_password' => 'كلمة المرور الجديدة',
    'confirm_new_password' => 'تأكيد كلمة المرور الجديدة',
    'two_factor_authentication' => 'المصادقة الثنائية',
    '2fa_enabled' => 'مفعلة',
    '2fa_disabled' => 'معطلة',
    '2fa_description' => 'المصادقة الثنائية تضيف طبقة إضافية من الأمان لحسابك',
    'disable_2fa' => 'إلغاء تفعيل المصادقة الثنائية',
    'enable_2fa' => 'تفعيل المصادقة الثنائية',
    'active_sessions' => 'الجلسات النشطة',
    'no_active_sessions' => 'لا توجد جلسات نشطة',
    'last_activity' => 'آخر نشاط',
    'revoke' => 'إلغاء',
    'login_history' => 'تاريخ تسجيل الدخول',
    'no_login_history' => 'لا يوجد تاريخ تسجيل دخول',
    'new_security_events' => 'أحداث أمان جديدة',
    'password_weak' => 'كلمة المرور ضعيفة',
    'password_medium' => 'كلمة المرور متوسطة',
    'password_strong' => 'كلمة المرور قوية',
    'password_changed_successfully' => 'تم تغيير كلمة المرور بنجاح',
    'password_too_short' => 'كلمة المرور قصيرة جداً',
    'passwords_dont_match' => 'كلمتا المرور غير متطابقتين',
    'current_password_incorrect' => 'كلمة المرور الحالية غير صحيحة',
    '2fa_enabled_successfully' => 'تم تفعيل المصادقة الثنائية بنجاح',
    '2fa_disabled_successfully' => 'تم إلغاء تفعيل المصادقة الثنائية بنجاح',
    'session_revoked_successfully' => 'تم إلغاء الجلسة بنجاح',
    'promo_codes_vouchers' => 'رموز الخصم والقسائم',
    'apply_discounts_and_save_money' => 'طبق الخصومات ووفر المال',
    'apply_promo_code' => 'تطبيق رمز الخصم',
    'off' => 'خصم',
    'remove' => 'إزالة',
    'enter_promo_code' => 'أدخل رمز الخصم',
    'apply_code' => 'تطبيق الرمز',
    'your_vouchers' => 'قسائمك',
    'no_vouchers_available' => 'لا توجد قسائم متاحة',
    'earn_vouchers_by_shopping' => 'اكسب قسائم بالتسوق',
    'expires' => 'ينتهي',
    'promo_code_history' => 'تاريخ رموز الخصم',
    'no_promo_history' => 'لا يوجد تاريخ رموز خصم',
    'start_using_promo_codes' => 'ابدأ باستخدام رموز الخصم',
    'new_vouchers_available' => 'قسائم جديدة متاحة',
    'voucher_code_copied' => 'تم نسخ رمز القسيمة',
    'promo_code_applied' => 'تم تطبيق رمز الخصم',
    'promo_code_already_used' => 'رمز الخصم مستخدم بالفعل',
    'invalid_promo_code' => 'رمز الخصم غير صحيح',
    'promo_code_removed' => 'تم إزالة رمز الخصم',
    'notifications_center' => 'مركز الإشعارات',
    'manage_your_notifications' => 'إدارة إشعاراتك',
    'total_notifications' => 'إجمالي الإشعارات',
    'unread_notifications' => 'الإشعارات غير المقروءة',
    'order_confirmed' => 'تم تأكيد الطلب',
    'thank_you_for_your_order' => 'شكراً لك على طلبك',
    'order_details' => 'تفاصيل الطلب',
    'order_number' => 'رقم الطلب',
    'customer_name' => 'اسم العميل',
    'order_date' => 'تاريخ الطلب',
    'order_status' => 'حالة الطلب',
    'payment_method' => 'طريقة الدفع',
    'shipping_address' => 'عنوان الشحن',
    'billing_address' => 'عنوان الفواتير',
    'order_items' => 'عناصر الطلب',
    'sold_by' => 'باعه',
    'quantity' => 'الكمية',
    'order_summary' => 'ملخص الطلب',
    'subtotal' => 'المجموع الفرعي',
    'shipping' => 'الشحن',
    'tax' => 'الضريبة',
    'total' => 'الإجمالي',
    'view_all_orders' => 'عرض جميع الطلبات',
    'track_order' => 'تتبع الطلب',
    'continue_shopping' => 'مواصلة التسوق',
    'my_wishlist' => 'مفضلتي',
    'remove_from_wishlist' => 'إزالة من المفضلة',
    'login_to_view_wishlist' => 'يرجى تسجيل الدخول لعرض المفضلة',
    'product_details' => 'تفاصيل المنتج',
    'back_to_home' => 'العودة للرئيسية',
    'product_image' => 'صورة المنتج',
    'add_to_favorites' => 'إضافة للمفضلة',
    'price' => 'السعر',
    'in_stock' => 'متوفر',
    'out_of_stock' => 'غير متوفر',
    'category' => 'الفئة',
    'add_to_cart' => 'إضافة للسلة',
    'selected' => 'المحدد',
    'about_seller' => 'عن البائع',
    'seller_photo' => 'صورة البائع',
    'description' => 'الوصف',
    'customer_reviews' => 'تقييمات العملاء',
    'no_reviews_yet' => 'لا توجد تقييمات بعد',
    'add_your_review' => 'أضف تقييمك',
    'your_rating' => 'تقييمك',
    'your_comment' => 'تعليقك',
    'submit_review' => 'إرسال التقييم',
    'please_login_to_add_review' => 'يرجى تسجيل الدخول لإضافة تقييم',
    'shipping_available' => 'الشحن متاح',
    'within_2_5_working_days' => 'خلال 2-5 أيام عمل',
    'related_products' => 'منتجات ذات صلة',
    'login_to_view_orders' => 'يرجى تسجيل الدخول لعرض الطلبات',
    'order_not_found' => 'الطلب غير موجود',
    'date' => 'التاريخ',
    'status' => 'الحالة',
    'payment' => 'الدفع',
    'actions' => 'الإجراءات',
    'view_details' => 'عرض التفاصيل',
    'currency' => 'د.ت',
    'search_placeholder' => 'ابحث عن منتج...',
    'arabic_language' => 'العربية',
    'welcome' => 'مرحباً',
    'services' => 'الخدمات'
];

echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
foreach ($suggested_translations as $key => $translation) {
    if (in_array($key, $missing_keys)) {
        echo "    '$key' => '$translation',\n";
    }
}
echo "</pre>";
echo "</div>";

// Summary
echo "<div class='section success'>";
echo "<h2>📋 Summary</h2>";
echo "<ul>";
echo "<li><strong>Files scanned:</strong> " . count($files_to_scan) . "</li>";
echo "<li><strong>Current translations:</strong> " . count($current_translations) . "</li>";
echo "<li><strong>Used translation keys:</strong> " . count($all_used_keys) . "</li>";
echo "<li><strong>Missing translations:</strong> " . count($missing_keys) . "</li>";
echo "<li><strong>Files using old method:</strong> " . count($old_method_files) . "</li>";
echo "<li><strong>Files with hardcoded strings:</strong> " . count($hardcoded_files) . "</li>";
echo "</ul>";

if (count($missing_keys) > 0 || count($old_method_files) > 0 || count($hardcoded_files) > 0) {
    echo "<p style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "⚠️ <strong>Action Required:</strong> Please fix the translation issues above to ensure proper internationalization.";
    echo "</p>";
} else {
    echo "<p style='color: #155724; background: #d4edda; padding: 10px; border-radius: 5px;'>";
    echo "✅ <strong>All Good!</strong> No translation issues found.";
    echo "</p>";
}
echo "</div>";
?> 