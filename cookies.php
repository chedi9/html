<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
?><!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>ملفات تعريف الارتباط</title>
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
    <section class="container">
        <h1>سياسة ملفات تعريف الارتباط (Cookies)</h1>
        
        <div class="cookies-content">
            <div class="section">
                <h2>🍪 ما هي ملفات تعريف الارتباط؟</h2>
                <p>ملفات تعريف الارتباط هي ملفات نصية صغيرة يتم تخزينها على جهازك عند زيارة موقعنا. تساعدنا هذه الملفات في تحسين تجربتك وتقديم خدمات أفضل وأكثر أماناً.</p>
            </div>

            <div class="section">
                <h2>🔒 أنواع ملفات تعريف الارتباط التي نستخدمها</h2>
                
                <div class="cookie-type">
                    <h3>🛡️ ملفات الأمان الأساسية (Essential Cookies)</h3>
                    <p><strong>الغرض:</strong> ضرورية لتشغيل الموقع وحماية حسابك</p>
                    <ul>
                        <li>إدارة الجلسات وتسجيل الدخول</li>
                        <li>حماية من هجمات CSRF</li>
                        <li>تتبع الأجهزة المعروفة</li>
                        <li>منع الاحتيال والأنشطة المشبوهة</li>
                        <li>تحديد المعدل لمنع الاستخدام المفرط</li>
                    </ul>
                    <p><strong>المدة:</strong> طوال الجلسة أو سنة واحدة</p>
                    <p><strong>لا يمكن إلغاؤها:</strong> هذه الملفات ضرورية لتشغيل الموقع</p>
                </div>

                <div class="cookie-type">
                    <h3>⚙️ ملفات التفضيلات (Preference Cookies)</h3>
                    <p><strong>الغرض:</strong> تذكر إعداداتك وتفضيلاتك</p>
                    <ul>
                        <li>اللغة المفضلة (العربية/الإنجليزية)</li>
                        <li>وضع الظلام/الفاتح</li>
                        <li>إعدادات البحث والتصفية</li>
                        <li>تفضيلات الشحن والدفع</li>
                        <li>إعدادات الإشعارات</li>
                    </ul>
                    <p><strong>المدة:</strong> سنة واحدة</p>
                    <p><strong>قابلة للإلغاء:</strong> يمكنك إلغاؤها من إعدادات المتصفح</p>
                </div>

                <div class="cookie-type">
                    <h3>📊 ملفات التحليل (Analytics Cookies)</h3>
                    <p><strong>الغرض:</strong> فهم كيفية استخدام الموقع لتحسين الخدمات</p>
                    <ul>
                        <li>إحصائيات الزيارات والصفحات الأكثر شعبية</li>
                        <li>تحليل سلوك المستخدمين</li>
                        <li>قياس أداء الموقع وسرعته</li>
                        <li>تحديد المشاكل التقنية</li>
                        <li>تحسين تجربة المستخدم</li>
                    </ul>
                    <p><strong>المدة:</strong> 2 سنوات</p>
                    <p><strong>قابلة للإلغاء:</strong> يمكنك إلغاؤها من إعدادات الخصوصية</p>
                </div>

                <div class="cookie-type">
                    <h3>🎯 ملفات التسويق (Marketing Cookies)</h3>
                    <p><strong>الغرض:</strong> تقديم إعلانات ذات صلة وتحسين تجربتك</p>
                    <ul>
                        <li>إظهار منتجات قد تهمك</li>
                        <li>تخصيص العروض والخصومات</li>
                        <li>تحليل فعالية الحملات الإعلانية</li>
                        <li>تحسين تجربة التسوق</li>
                    </ul>
                    <p><strong>المدة:</strong> سنة واحدة</p>
                    <p><strong>قابلة للإلغاء:</strong> يمكنك إلغاؤها من إعدادات الخصوصية</p>
                </div>

                <div class="cookie-type">
                    <h3>🔐 ملفات الأمان المتقدمة (Security Cookies)</h3>
                    <p><strong>الغرض:</strong> حماية إضافية لحسابك وبياناتك</p>
                    <ul>
                        <li>التحقق بخطوتين (2FA)</li>
                        <li>كشف الأجهزة الجديدة</li>
                        <li>مراقبة الأنشطة المشبوهة</li>
                        <li>حماية من هجمات البرمجة النصية عبر المواقع</li>
                        <li>تشفير البيانات الحساسة</li>
                    </ul>
                    <p><strong>المدة:</strong> طوال الجلسة</p>
                    <p><strong>لا يمكن إلغاؤها:</strong> ضرورية لحماية حسابك</p>
                </div>
            </div>

            <div class="section">
                <h2>🌐 ملفات تعريف الارتباط من أطراف ثالثة</h2>
                <p>نستخدم خدمات من أطراف ثالثة لتحسين خدماتنا:</p>
                
                <div class="third-party">
                    <h3>Google Analytics</h3>
                    <p>لتحليل استخدام الموقع وتحسين الخدمات</p>
                    <ul>
                        <li>إحصائيات الزيارات والصفحات</li>
                        <li>تحليل سلوك المستخدمين</li>
                        <li>قياس أداء الموقع</li>
                    </ul>
                </div>

                <div class="third-party">
                    <h3>مزودي خدمات الدفع</h3>
                    <p>PayPal, Stripe, D17, Flouci لمعالجة المدفوعات</p>
                    <ul>
                        <li>معالجة آمنة للمدفوعات</li>
                        <li>حماية بيانات البطاقات</li>
                        <li>امتثال معايير PCI DSS</li>
                    </ul>
                </div>

                <div class="third-party">
                    <h3>خدمات الأمان</h3>
                    <p>لحماية الموقع والمستخدمين من التهديدات</p>
                    <ul>
                        <li>كشف ومنع الاحتيال</li>
                        <li>حماية من الهجمات السيبرانية</li>
                        <li>مراقبة الأنشطة المشبوهة</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2>⚙️ إدارة ملفات تعريف الارتباط</h2>
                <p>يمكنك التحكم في ملفات تعريف الارتباط بعدة طرق:</p>
                
                <div class="management-option">
                    <h3>🎛️ إعدادات الموقع</h3>
                    <ul>
                        <li>إلغاء ملفات التحليل والتسويق</li>
                        <li>تحديث تفضيلات الخصوصية</li>
                        <li>إدارة إعدادات الإشعارات</li>
                        <li>التحكم في مشاركة البيانات</li>
                    </ul>
                </div>

                <div class="management-option">
                    <h3>🌐 إعدادات المتصفح</h3>
                    <ul>
                        <li>حذف جميع ملفات تعريف الارتباط</li>
                        <li>منع تخزين ملفات جديدة</li>
                        <li>إعدادات الخصوصية المتقدمة</li>
                        <li>وضع التصفح الخاص</li>
                    </ul>
                </div>

                <div class="management-option">
                    <h3>📱 إعدادات الأجهزة</h3>
                    <ul>
                        <li>إدارة الأجهزة المعروفة</li>
                        <li>إلغاء تفويض الأجهزة</li>
                        <li>إعدادات الأمان والخصوصية</li>
                        <li>التحكم في الموقع الجغرافي</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2>🔍 كيفية حذف ملفات تعريف الارتباط</h2>
                <p>يمكنك حذف ملفات تعريف الارتباط من متصفحك:</p>
                
                <div class="browser-instructions">
                    <h3>Chrome</h3>
                    <p>الإعدادات > الخصوصية والأمان > مسح بيانات التصفح</p>
                </div>

                <div class="browser-instructions">
                    <h3>Firefox</h3>
                    <p>الإعدادات > الخصوصية والأمان > ملفات تعريف الارتباط وبيانات الموقع</p>
                </div>

                <div class="browser-instructions">
                    <h3>Safari</h3>
                    <p>التفضيلات > الخصوصية > إدارة بيانات الموقع</p>
                </div>

                <div class="browser-instructions">
                    <h3>Edge</h3>
                    <p>الإعدادات > ملفات تعريف الارتباط وأذونات الموقع</p>
                </div>
            </div>

            <div class="section">
                <h2>⚠️ تأثير إلغاء ملفات تعريف الارتباط</h2>
                <p>قد يؤثر إلغاء بعض ملفات تعريف الارتباط على تجربتك:</p>
                <ul>
                    <li><strong>ملفات الأمان:</strong> قد لا يعمل الموقع بشكل صحيح</li>
                    <li><strong>ملفات التفضيلات:</strong> ستحتاج لإعادة تعيين إعداداتك</li>
                    <li><strong>ملفات التحليل:</strong> لن نتمكن من تحسين الخدمات</li>
                    <li><strong>ملفات التسويق:</strong> قد لا ترى العروض المناسبة لك</li>
                </ul>
            </div>

            <div class="section">
                <h2>📞 التواصل معنا</h2>
                <p>لأي استفسارات حول ملفات تعريف الارتباط:</p>
                <ul>
                    <li>البريد الإلكتروني: <a href="mailto:webuytn0@gmail.com">webuytn0@gmail.com</a></li>
                    <li>مركز الأمان: <a href="security_center.php">إدارة إعدادات الأمان</a></li>
                    <li>سياسة الخصوصية: <a href="privacy.php">قراءة سياسة الخصوصية الكاملة</a></li>
                </ul>
            </div>

            <div class="section">
                <h2>📅 تحديثات السياسة</h2>
                <p>قد نقوم بتحديث سياسة ملفات تعريف الارتباط من وقت لآخر. سنقوم بإشعارك بأي تغييرات مهمة عبر البريد الإلكتروني أو إشعار في الموقع.</p>
                <p><strong>آخر تحديث:</strong> <?php echo date('Y-m-d'); ?></p>
            </div>
        </div>
    </section>

    
</body>
</html> 