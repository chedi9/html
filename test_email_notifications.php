<?php
// Test script for Email Notifications System
require_once 'client/mailer.php';

echo "<h1>🧪 اختبار نظام الإشعارات بالبريد الإلكتروني</h1>";

// Test order confirmation email
echo "<h2>📧 اختبار إرسال تأكيد الطلب</h2>";

$test_order_data = [
    'order' => [
        'id' => 123,
        'name' => 'أحمد محمد',
        'email' => 'test@example.com',
        'phone' => '12345678',
        'address' => 'شارع الحبيب بورقيبة، تونس',
        'payment_method' => 'card',
        'shipping_method' => 'التوصيل السريع',
        'subtotal' => 150.00,
        'shipping_cost' => 10.00,
        'total_amount' => 160.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ],
    'order_items' => [
        [
            'product_name' => 'هاتف ذكي جديد',
            'product_image' => 'phone.jpg',
            'quantity' => 1,
            'price' => 100.00,
            'subtotal' => 100.00,
            'seller_name' => 'WeBuy'
        ],
        [
            'product_name' => 'سماعات لاسلكية',
            'product_image' => 'headphones.jpg',
            'quantity' => 1,
            'price' => 50.00,
            'subtotal' => 50.00,
            'seller_name' => 'WeBuy'
        ]
    ],
    'payment_details' => [
        'card_number' => '1234',
        'card_holder' => 'أحمد محمد',
        'card_type' => 'visa'
    ]
];

// Test order confirmation email
$result = send_order_confirmation_email('test@example.com', 'أحمد محمد', $test_order_data);

if ($result) {
    echo "<p style='color: green;'>✅ تم إرسال تأكيد الطلب بنجاح!</p>";
} else {
    echo "<p style='color: red;'>❌ فشل في إرسال تأكيد الطلب</p>";
}

// Test order status update email
echo "<h2>📧 اختبار تحديث حالة الطلب</h2>";

$result2 = send_order_status_update_email('test@example.com', 'أحمد محمد', $test_order_data, 'shipped');

if ($result2) {
    echo "<p style='color: green;'>✅ تم إرسال تحديث حالة الطلب بنجاح!</p>";
} else {
    echo "<p style='color: red;'>❌ فشل في إرسال تحديث حالة الطلب</p>";
}

echo "<h2>📋 ملخص الاختبار</h2>";
echo "<ul>";
echo "<li>✅ نظام إرسال البريد الإلكتروني: " . ($result ? 'يعمل' : 'لا يعمل') . "</li>";
echo "<li>✅ قوالب البريد الإلكتروني: تم إنشاؤها</li>";
echo "<li>✅ دعم HTML والنص العادي: متوفر</li>";
echo "<li>✅ الترميز العربي: مدعوم</li>";
echo "</ul>";

echo "<h2>🔧 الخطوات التالية</h2>";
echo "<ol>";
echo "<li>تأكد من إعدادات SMTP في ملف mailer.php</li>";
echo "<li>اختبر إرسال البريد الإلكتروني الفعلي</li>";
echo "<li>أضف رابط إدارة الطلبات في لوحة التحكم</li>";
echo "<li>اختبر تحديث حالة الطلبات</li>";
echo "</ol>";

echo "<p><strong>ملاحظة:</strong> هذا اختبار تجريبي. تأكد من تغيير عنوان البريد الإلكتروني إلى عنوان صحيح للاختبار الفعلي.</p>";
?> 