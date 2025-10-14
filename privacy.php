<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
?><!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سياسة الخصوصية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS Files - Load in correct order -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="main.js?v=1.2" defer></script>
    
</head>
<body>
  <div>
    <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
      </svg>
    </button>
  </div>
    <section class="container">
        <h1>سياسة الخصوصية</h1>
        
        <div class="privacy-content">
            <div class="section">
                <h2>🔒 مقدمة</h2>
                <p>نحن في WeBuy نلتزم بحماية خصوصيتك وبياناتك الشخصية. تم تحديث سياسة الخصوصية هذه لتعكس أحدث معايير الأمان والخصوصية المطبقة في منصتنا.</p>
            </div>

            <div class="section">
                <h2>📋 المعلومات التي نجمعها</h2>
                <h3>المعلومات الشخصية:</h3>
                <ul>
                    <li>الاسم الكامل وعنوان البريد الإلكتروني</li>
                    <li>رقم الهاتف وعنوان الشحن</li>
                    <li>معلومات الدفع (مشفرة ومؤمنة)</li>
                    <li>تاريخ الميلاد والجنس (اختياري)</li>
                </ul>
                
                <h3>معلومات الاستخدام:</h3>
                <ul>
                    <li>عنوان IP وموقع جغرافي</li>
                    <li>نوع الجهاز ومتصفح الويب</li>
                    <li>صفحات الموقع التي تزورها</li>
                    <li>وقت وتاريخ الزيارات</li>
                </ul>
            </div>

            <div class="section">
                <h2>🛡️ حماية البيانات والأمان</h2>
                <p>نطبق أعلى معايير الأمان لحماية بياناتك:</p>
                <ul>
                    <li><strong>التشفير:</strong> جميع البيانات محمية بتشفير AES-256</li>
                    <li><strong>HTTPS:</strong> جميع الاتصالات مشفرة ومؤمنة</li>
                    <li><strong>امتثال PCI DSS:</strong> حماية كاملة لبيانات الدفع</li>
                    <li><strong>التحقق بخطوتين:</strong> حماية إضافية لحسابك</li>
                    <li><strong>كشف الاحتيال:</strong> مراقبة مستمرة للأنشطة المشبوهة</li>
                    <li><strong>تحديد المعدل:</strong> منع الاستخدام المفرط والهجمات</li>
                </ul>
            </div>

            <div class="section">
                <h2>💳 معالجة المدفوعات</h2>
                <p>نتبع أعلى معايير أمان المدفوعات:</p>
                <ul>
                    <li>لا نخزن أرقام البطاقات الكاملة</li>
                    <li>نستخدم التشفير والتوكنيز للبيانات الحساسة</li>
                    <li>جميع المعاملات محمية بتشفير SSL/TLS</li>
                    <li>مراقبة مستمرة للاحتيال والأنشطة المشبوهة</li>
                </ul>
            </div>

            <div class="section">
                <h2>🎯 استخدام المعلومات</h2>
                <p>نستخدم معلوماتك للأغراض التالية:</p>
                <ul>
                    <li>معالجة الطلبات وتقديم الخدمات</li>
                    <li>تحسين تجربة المستخدم</li>
                    <li>إرسال إشعارات مهمة</li>
                    <li>منع الاحتيال وحماية الأمان</li>
                    <li>تحليل الاستخدام لتحسين الخدمات</li>
                </ul>
            </div>

            <div class="section">
                <h2>🤝 مشاركة البيانات</h2>
                <p>لا نشارك بياناتك الشخصية مع أطراف ثالثة إلا في الحالات التالية:</p>
                <ul>
                    <li>مزودي خدمات الدفع (PayPal, Stripe, D17, Flouci)</li>
                    <li>خدمات الشحن والتوصيل</li>
                    <li>مزودي خدمات الأمان والتحليل</li>
                    <li>الامتثال للقوانين والأنظمة</li>
                </ul>
            </div>

            <div class="section">
                <h2>🍪 ملفات تعريف الارتباط (Cookies)</h2>
                <p>نستخدم ملفات تعريف الارتباط لتحسين تجربتك:</p>
                <ul>
                    <li><strong>الكوكيز الأساسية:</strong> لإدارة الجلسات وتسجيل الدخول</li>
                    <li><strong>كوكيز الأمان:</strong> لحماية حسابك ومنع الاحتيال</li>
                    <li><strong>كوكيز التحليل:</strong> لفهم كيفية استخدام الموقع</li>
                    <li><strong>كوكيز التفضيلات:</strong> لتذكر إعداداتك</li>
                </ul>
            </div>

            <div class="section">
                <h2>📱 تتبع الأجهزة والجلسات</h2>
                <p>نستخدم تقنيات متقدمة لحماية حسابك:</p>
                <ul>
                    <li>تتبع الأجهزة المعروفة والجلسات النشطة</li>
                    <li>كشف الأجهزة الجديدة والأنشطة المشبوهة</li>
                    <li>إشعارات فورية للأنشطة غير المعتادة</li>
                    <li>إمكانية إلغاء تفويض الأجهزة في أي وقت</li>
                </ul>
            </div>

            <div class="section">
                <h2>⏰ الاحتفاظ بالبيانات</h2>
                <p>نحتفظ ببياناتك لفترات محددة:</p>
                <ul>
                    <li>بيانات الحساب: طالما الحساب نشط</li>
                    <li>بيانات الطلبات: 7 سنوات (متطلبات ضريبية)</li>
                    <li>سجلات الأمان: 2 سنوات</li>
                    <li>بيانات الدفع المشفرة: حسب متطلبات PCI DSS</li>
                </ul>
            </div>

            <div class="section">
                <h2>🔐 حقوقك</h2>
                <p>لديك الحق في:</p>
                <ul>
                    <li>الوصول إلى بياناتك الشخصية</li>
                    <li>تصحيح البيانات غير الدقيقة</li>
                    <li>حذف حسابك وبياناتك</li>
                    <li>تصدير بياناتك</li>
                    <li>إلغاء الاشتراك في الرسائل التسويقية</li>
                    <li>إدارة إعدادات الخصوصية والأمان</li>
                </ul>
            </div>

            <div class="section">
                <h2>🌍 النقل الدولي للبيانات</h2>
                <p>قد يتم نقل بياناتك إلى دول أخرى لمعالجة الطلبات وتقديم الخدمات. نضمن حماية بياناتك وفقاً لأعلى المعايير الدولية.</p>
            </div>

            <div class="section">
                <h2>📞 التواصل معنا</h2>
                <p>لأي استفسارات حول سياسة الخصوصية أو لحماية بياناتك:</p>
                <ul>
                    <li>البريد الإلكتروني: <a href="mailto:webuytn0@gmail.com">webuytn0@gmail.com</a></li>
                    <li>مركز الأمان: <a href="security_center.php">إدارة إعدادات الأمان</a></li>
                    <li>إعدادات الحساب: <a href="client/account.php">تحديث المعلومات الشخصية</a></li>
                </ul>
            </div>

            <div class="section">
                <h2>📅 تحديثات السياسة</h2>
                <p>قد نقوم بتحديث سياسة الخصوصية من وقت لآخر. سنقوم بإشعارك بأي تغييرات مهمة عبر البريد الإلكتروني أو إشعار في الموقع.</p>
                <p><strong>آخر تحديث:</strong> <?php echo date('Y-m-d'); ?></p>
            </div>
        </div>
    </section>

    

    <script src="main.js?v=1.2"></script>
</body>
</html> 