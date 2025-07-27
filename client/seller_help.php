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
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .help-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }
        .help-header {
            text-align: center;
            background: linear-gradient(135deg, #1A237E, #00BFAE);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .help-header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .help-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .help-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .help-card h3 {
            color: #1A237E;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .help-card p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .help-btn {
            background: #00BFAE;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            font-weight: bold;
            transition: background 0.2s;
        }
        .help-btn:hover {
            background: #009688;
        }
        .contact-section {
            background: #e3f2fd;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .contact-section h2 {
            color: #1565c0;
            margin-bottom: 20px;
        }
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .contact-item {
            background: white;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .contact-item h4 {
            color: #1A237E;
            margin-bottom: 10px;
        }
        .contact-item p {
            color: #666;
            margin: 5px 0;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin-top: 20px;
        }
        .back-btn:hover {
            background: #5a6268;
        }
        .faq-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .faq-item {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #00BFAE;
        }
        .faq-item h4 {
            color: #1A237E;
            margin-bottom: 10px;
        }
        .faq-item p {
            color: #666;
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            .help-container {
                padding: 10px;
            }
            .help-header h1 {
                font-size: 1.8em;
            }
            .help-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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

        <div style="text-align: center;">
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