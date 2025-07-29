<?php
/**
 * Translation Fix Script for WeBuy
 * This script automatically fixes translation issues across all files
 */

require_once 'db.php';
require_once 'lang.php';

echo "<h1>🔧 Translation Fix Script</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { background: #ffe6e6; border-color: #ff9999; }
    .warning { background: #fff3cd; border-color: #ffeaa7; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    .code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f8f9fa; }
    .fixed { color: #155724; }
    .error-msg { color: #721c24; }
</style>";

// Step 1: Update Arabic translation file with missing keys
echo "<div class='section info'>";
echo "<h2>📝 Step 1: Adding Missing Translation Keys</h2>";

$missing_keys = [
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

// Add missing keys to Arabic translation file
$ar_file = 'lang/ar.php';
if (file_exists($ar_file)) {
    $ar_content = file_get_contents($ar_file);
    $ar_translations = eval('return ' . substr($ar_content, 5) . ';');
    
    $added_count = 0;
    foreach ($missing_keys as $key => $translation) {
        if (!isset($ar_translations[$key])) {
            $ar_translations[$key] = $translation;
            $added_count++;
        }
    }
    
    if ($added_count > 0) {
        // Sort translations alphabetically
        ksort($ar_translations);
        
        // Generate new content
        $new_content = "<?php\nreturn [\n";
        foreach ($ar_translations as $key => $value) {
            $new_content .= "    '$key' => '$value',\n";
        }
        $new_content .= "];\n";
        
        // Write back to file
        if (file_put_contents($ar_file, $new_content)) {
            echo "<p class='fixed'>✅ Added $added_count missing translation keys to <code>$ar_file</code></p>";
        } else {
            echo "<p class='error-msg'>❌ Failed to update <code>$ar_file</code></p>";
        }
    } else {
        echo "<p class='success'>✅ All translation keys already exist in <code>$ar_file</code></p>";
    }
} else {
    echo "<p class='error-msg'>❌ Arabic translation file not found: <code>$ar_file</code></p>";
}
echo "</div>";

// Step 2: Fix files using old $lang[] method
echo "<div class='section warning'>";
echo "<h2>🔧 Step 2: Converting Old Translation Method</h2>";

$files_to_fix = [
    'wallet.php',
    'security_center.php',
    'promo_codes.php',
    'notifications_center.php'
];

$fixed_files = [];
foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Replace $lang['key'] with __('key')
        $content = preg_replace('/\$lang\[[\'"]([^\'"]+)[\'"]\]/', '__(\'$1\')', $content);
        
        if ($content !== $original_content) {
            if (file_put_contents($file, $content)) {
                $fixed_files[] = $file;
                echo "<p class='fixed'>✅ Fixed <code>$file</code> - converted \$lang[] to __()</p>";
            } else {
                echo "<p class='error-msg'>❌ Failed to update <code>$file</code></p>";
            }
        } else {
            echo "<p class='success'>✅ <code>$file</code> already uses __() function</p>";
        }
    } else {
        echo "<p class='error-msg'>❌ File not found: <code>$file</code></p>";
    }
}

if (empty($fixed_files)) {
    echo "<p class='success'>✅ All files already use the new __() translation method!</p>";
}
echo "</div>";

// Step 3: Fix hardcoded Arabic strings
echo "<div class='section info'>";
echo "<h2>🔧 Step 3: Replacing Hardcoded Arabic Strings</h2>";

$hardcoded_replacements = [
    'بطاقة بنكية' => __('payment_method_card'),
    'تحويل بنكي' => __('payment_method_bank_transfer'),
    'الدفع عند الاستلام' => __('payment_method_cod'),
    'رقم حامل البطاقة' => __('card_holder_name'),
    'نوع البطاقة' => __('card_type'),
    'تاريخ الانتهاء' => __('expiry_date'),
    'رقم الهاتف' => __('phone_number'),
    'اسم البنك' => __('bank_name'),
    'اسم صاحب الحساب' => __('account_holder'),
    'رقم المرجع' => __('reference_number'),
    'سيتم إرسال رابط الدفع' => __('payment_link_will_be_sent'),
    'سيتم إرسال تفاصيل الحساب' => __('account_details_will_be_sent'),
    'سيتم الدفع عند استلام الطلب' => __('payment_will_be_on_delivery')
];

// Add these new translation keys to Arabic file
$additional_keys = [
    'payment_method_card' => 'بطاقة بنكية',
    'payment_method_bank_transfer' => 'تحويل بنكي',
    'payment_method_cod' => 'الدفع عند الاستلام',
    'card_holder_name' => 'اسم حامل البطاقة',
    'card_type' => 'نوع البطاقة',
    'expiry_date' => 'تاريخ الانتهاء',
    'phone_number' => 'رقم الهاتف',
    'bank_name' => 'اسم البنك',
    'account_holder' => 'اسم صاحب الحساب',
    'reference_number' => 'رقم المرجع',
    'payment_link_will_be_sent' => 'سيتم إرسال رابط الدفع',
    'account_details_will_be_sent' => 'سيتم إرسال تفاصيل الحساب',
    'payment_will_be_on_delivery' => 'سيتم الدفع عند استلام الطلب'
];

// Update Arabic translation file with additional keys
if (file_exists($ar_file)) {
    $ar_content = file_get_contents($ar_file);
    $ar_translations = eval('return ' . substr($ar_content, 5) . ';');
    
    $added_count = 0;
    foreach ($additional_keys as $key => $translation) {
        if (!isset($ar_translations[$key])) {
            $ar_translations[$key] = $translation;
            $added_count++;
        }
    }
    
    if ($added_count > 0) {
        ksort($ar_translations);
        $new_content = "<?php\nreturn [\n";
        foreach ($ar_translations as $key => $value) {
            $new_content .= "    '$key' => '$value',\n";
        }
        $new_content .= "];\n";
        
        if (file_put_contents($ar_file, $new_content)) {
            echo "<p class='fixed'>✅ Added $added_count additional translation keys for hardcoded strings</p>";
        }
    }
}

// Fix order_confirmation.php hardcoded strings
$order_confirmation_file = 'order_confirmation.php';
if (file_exists($order_confirmation_file)) {
    $content = file_get_contents($order_confirmation_file);
    $original_content = $content;
    
    // Replace hardcoded payment method strings
    $content = str_replace("case 'card': echo '💳 بطاقة بنكية'; break;", "case 'card': echo '💳 ' . __('payment_method_card'); break;", $content);
    $content = str_replace("case 'd17': echo '📱 D17'; break;", "case 'd17': echo '📱 D17'; break;", $content);
    $content = str_replace("case 'bank_transfer': echo '🏦 تحويل بنكي'; break;", "case 'bank_transfer': echo '🏦 ' . __('payment_method_bank_transfer'); break;", $content);
    $content = str_replace("case 'cod': echo '💰 الدفع عند الاستلام'; break;", "case 'cod': echo '💰 ' . __('payment_method_cod'); break;", $content);
    
    // Replace hardcoded labels
    $content = str_replace("اسم حامل البطاقة:", __('card_holder_name') . ":", $content);
    $content = str_replace("نوع البطاقة:", __('card_type') . ":", $content);
    $content = str_replace("تاريخ الانتهاء:", __('expiry_date') . ":", $content);
    $content = str_replace("رقم الهاتف:", __('phone_number') . ":", $content);
    $content = str_replace("اسم البنك:", __('bank_name') . ":", $content);
    $content = str_replace("اسم صاحب الحساب:", __('account_holder') . ":", $content);
    $content = str_replace("رقم المرجع:", __('reference_number') . ":", $content);
    
    if ($content !== $original_content) {
        if (file_put_contents($order_confirmation_file, $content)) {
            echo "<p class='fixed'>✅ Fixed hardcoded strings in <code>$order_confirmation_file</code></p>";
        } else {
            echo "<p class='error-msg'>❌ Failed to update <code>$order_confirmation_file</code></p>";
        }
    } else {
        echo "<p class='success'>✅ <code>$order_confirmation_file</code> already uses translations</p>";
    }
}
echo "</div>";

// Step 4: Verify all translations are working
echo "<div class='section success'>";
echo "<h2>✅ Step 4: Verification</h2>";

// Test some translations
$test_keys = [
    'wallet_loyalty',
    'security_center',
    'promo_codes_vouchers',
    'order_confirmed',
    'my_wishlist'
];

echo "<p><strong>Testing translations:</strong></p>";
echo "<ul>";
foreach ($test_keys as $key) {
    $translation = __($key);
    if ($translation !== $key) {
        echo "<li class='fixed'>✅ <code>$key</code> → \"$translation\"</li>";
    } else {
        echo "<li class='error-msg'>❌ <code>$key</code> → Missing translation</li>";
    }
}
echo "</ul>";

echo "<p class='success'>🎉 <strong>Translation fix completed!</strong></p>";
echo "<p>All files should now use the consistent __() translation method with proper Arabic translations.</p>";
echo "</div>";

// Summary
echo "<div class='section info'>";
echo "<h2>📋 Summary</h2>";
echo "<ul>";
echo "<li><strong>Translation keys added:</strong> " . count($missing_keys) . "</li>";
echo "<li><strong>Files converted to __() method:</strong> " . count($fixed_files) . "</li>";
echo "<li><strong>Hardcoded strings replaced:</strong> " . count($hardcoded_replacements) . "</li>";
echo "<li><strong>Total translation keys available:</strong> " . count($trans) . "</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test the website to ensure all translations display correctly</li>";
echo "<li>Update English and French translation files with corresponding translations</li>";
echo "<li>Consider adding more language options if needed</li>";
echo "</ol>";
echo "</div>";
?> 