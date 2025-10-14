<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require '../lang.php';
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';
$stmt = $pdo->prepare('SELECT is_seller FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['is_seller']) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مساعدة البائعين - WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    <div class="help-container">
        <div class="help-header">
            <h1>📞 مركز مساعدة البائعين</h1>
            <p>نحن هنا لمساعدتك في كل خطوة من رحلتك مع WeBuy</p>
        </div>

        <div class="help-grid">
            <div class="help-card">
                <h3>📚 الدليل الشامل</h3>
                <p>احصل على دليل مفصل يغطي جميع جوانب العمل كبائع، من إضافة المنتجات إلى إدارة الطلبات.</p>
                <a href="../seller_manual.php" class="help-btn">قراءة الدليل</a>
            </div>

            <div class="help-card">
                <h3>📧 البريد الإلكتروني</h3>
                <p>راسلنا عبر البريد الإلكتروني للحصول على مساعدة فورية في أي مشكلة تواجهها.</p>
                <a href="mailto:sellers@webyutn.infy.uk" class="help-btn">إرسال بريد</a>
            </div>

            <div class="help-card">
                <h3>📱 الدردشة المباشرة</h3>
                <p>تواصل مع فريق الدعم مباشرة من خلال الدردشة المباشرة المتاحة في لوحة التحكم.</p>
                <a href="seller_dashboard.php" class="help-btn">الذهاب للوحة التحكم</a>
            </div>

            <div class="help-card">
                <h3>📋 الأسئلة الشائعة</h3>
                <p>اطلع على الأسئلة الشائعة وإجاباتها لمعرفة حلول سريعة للمشاكل المعتادة.</p>
                <a href="#faq" class="help-btn">عرض الأسئلة</a>
            </div>

            <div class="help-card">
                <h3>📞 الهاتف</h3>
                <p>اتصل بنا مباشرة للحصول على مساعدة فورية من فريق الدعم المتخصص.</p>
                <a href="tel:+216XXXXXXX" class="help-btn">اتصال فوري</a>
            </div>

            <div class="help-card">
                <h3>📊 التقارير والدعم</h3>
                <p>احصل على تقارير مفصلة عن أداء متجرك ونصائح لتحسين المبيعات.</p>
                <a href="seller_dashboard.php" class="help-btn">عرض التقارير</a>
            </div>
        </div>

        <div class="contact-section">
            <h2>📞 معلومات الاتصال</h2>
            <div class="contact-info">
                <div class="contact-item">
                    <h4>📧 البريد الإلكتروني</h4>
                    <p>sellers@webyutn.infy.uk</p>
                    <p>للأسئلة العامة والدعم الفني</p>
                </div>
                <div class="contact-item">
                    <h4>📱 الهاتف</h4>
                    <p>+216 XX XXX XXX</p>
                    <p>ساعات العمل: 9:00 - 18:00</p>
                </div>
                <div class="contact-item">
                    <h4>💬 الدردشة المباشرة</h4>
                    <p>متاحة في لوحة التحكم</p>
                    <p>24/7 للأسئلة العاجلة</p>
                </div>
                <div class="contact-item">
                    <h4>📅 ساعات العمل</h4>
                    <p>الأحد - الخميس</p>
                    <p>9:00 صباحًا - 6:00 مساءً</p>
                </div>
            </div>
        </div>

        <div id="faq" class="faq-section">
            <h2>❓ الأسئلة الشائعة</h2>
            
            <div class="faq-item">
                <h4>كيف يمكنني إضافة منتج جديد؟</h4>
                <p>اذهب إلى لوحة التحكم > إدارة المنتجات > إضافة منتج جديد، ثم املأ جميع المعلومات المطلوبة وأضف الصور.</p>
            </div>

            <div class="faq-item">
                <h4>متى يتم تحويل الأرباح إلى حسابي؟</h4>
                <p>يتم تحويل الأرباح شهريًا، عادة في أول يوم عمل من كل شهر. يمكنك تتبع الأرباح من لوحة التحكم.</p>
            </div>

            <div class="faq-item">
                <h4>كيف يمكنني إلغاء طلب؟</h4>
                <p>يمكنك إلغاء الطلب من خلال "إدارة الطلبات" قبل تأكيده. بعد التأكيد، يجب التواصل مع فريق الدعم.</p>
            </div>

            <div class="faq-item">
                <h4>ما هي سياسة الاسترجاع؟</h4>
                <p>يمكن للعملاء إرجاع المنتجات خلال 14 يومًا من تاريخ الاستلام إذا لم يكونوا راضين عن المنتج.</p>
            </div>

            <div class="faq-item">
                <h4>كيف يمكنني تحسين مبيعاتي؟</h4>
                <p>أضف صور عالية الجودة، اكتب أوصافًا تفصيلية، قدم عروضًا خاصة، ورد بسرعة على استفسارات العملاء.</p>
            </div>

            <div class="faq-item">
                <h4>ما هي المنتجات المحظورة؟</h4>
                <p>المنتجات المقلدة، الخطرة، المحظورة قانونًا، أو التي تنتهك حقوق الملكية الفكرية.</p>
            </div>
        </div>

        <div>
            <a href="seller_dashboard.php" class="back-btn">العودة إلى لوحة التحكم</a>
        </div>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html> 