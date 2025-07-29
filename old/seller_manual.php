<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require 'lang.php';
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دليل البائع الشامل - WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <style>
        .manual-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            line-height: 1.6;
        }
        .manual-header {
            text-align: center;
            background: linear-gradient(135deg, #1A237E, #00BFAE);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .manual-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .manual-header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        .toc {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #00BFAE;
        }
        .toc h2 {
            color: #1A237E;
            margin-bottom: 15px;
        }
        .toc ul {
            list-style: none;
            padding: 0;
        }
        .toc li {
            margin: 8px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .toc a {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
        }
        .toc a:hover {
            color: #00BFAE;
        }
        .section {
            margin-bottom: 40px;
            padding: 25px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
        }
        .section h2 {
            color: #1A237E;
            border-bottom: 2px solid #00BFAE;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .section h3 {
            color: #2c3e50;
            margin: 20px 0 10px 0;
        }
        .step-box {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #00BFAE;
        }
        .step-number {
            background: #00BFAE;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-weight: bold;
        }
        .tip-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .warning-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .image-placeholder {
            background: #e9ecef;
            border: 2px dashed #adb5bd;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            margin: 15px 0;
            color: #6c757d;
        }
        .download-btn {
            background: #00BFAE;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin: 10px 5px;
            font-weight: bold;
        }
        .download-btn:hover {
            background: #009688;
        }
        .contact-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .contact-info h3 {
            color: #1565c0;
            margin-bottom: 15px;
        }
        .contact-info ul {
            list-style: none;
            padding: 0;
        }
        .contact-info li {
            margin: 8px 0;
            padding: 5px 0;
        }
        @media (max-width: 768px) {
            .manual-container {
                padding: 10px;
            }
            .manual-header h1 {
                font-size: 2em;
            }
            .section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
  <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px;max-width:1200px;margin-left:auto;margin-right:auto;gap:18px;">
    <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode" style="background:#00BFAE;color:#fff;border:none;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-left:16px;cursor:pointer;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:background 0.2s, color 0.2s;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
      </svg>
    </button>
  </div>
    <div class="manual-container">
        <div class="manual-header">
            <h1>📚 دليل البائع الشامل</h1>
            <p>مرحبًا بك في عائلة WeBuy! هذا الدليل سيساعدك على بدء رحلتك كبائع ناجح</p>
        </div>

        <div class="toc">
            <h2>📋 محتويات الدليل</h2>
            <ul>
                <li><a href="#getting-started">🚀 بدء العمل كبائع</a></li>
                <li><a href="#dashboard">📊 لوحة تحكم البائع</a></li>
                <li><a href="#products">📦 إدارة المنتجات</a></li>
                <li><a href="#pricing">💰 التسعير والعمولات</a></li>
                <li><a href="#shipping">🚚 الشحن والتوصيل</a></li>
                <li><a href="#orders">📋 إدارة الطلبات</a></li>
                <li><a href="#marketing">📢 التسويق والترويج</a></li>
                <li><a href="#policies">📋 السياسات والقواعد</a></li>
                <li><a href="#support">📞 الدعم والمساعدة</a></li>
            </ul>
        </div>

        <div id="getting-started" class="section">
            <h2>🚀 بدء العمل كبائع</h2>
            
            <div class="step-box">
                <h3><span class="step-number">1</span> إنشاء حساب البائع</h3>
                <p>تم إنشاء حسابك بنجاح! يمكنك الآن الوصول إلى لوحة تحكم البائع من خلال:</p>
                <ul>
                    <li>تسجيل الدخول إلى حسابك</li>
                    <li>الانتقال إلى "لوحة تحكم البائع"</li>
                    <li>إكمال معلومات المتجر الأساسية</li>
                </ul>
            </div>

            <div class="step-box">
                <h3><span class="step-number">2</span> إعداد المتجر</h3>
                <p>قبل بدء إضافة المنتجات، تأكد من إكمال المعلومات التالية:</p>
                <ul>
                    <li><strong>اسم المتجر:</strong> اختر اسمًا واضحًا ومميزًا</li>
                    <li><strong>وصف المتجر:</strong> اكتب وصفًا شاملًا عن متجرك</li>
                    <li><strong>معلومات الاتصال:</strong> رقم الهاتف والبريد الإلكتروني</li>
                    <li><strong>العنوان:</strong> عنوان المتجر أو المستودع</li>
                </ul>
            </div>

            <div class="tip-box">
                <h4>💡 نصيحة مهمة</h4>
                <p>احرص على اختيار اسم متجر واضح ومميز يعكس هوية علامتك التجارية. هذا سيساعد العملاء في تذكر متجرك والعودة إليه.</p>
            </div>
        </div>

        <div id="dashboard" class="section">
            <h2>📊 لوحة تحكم البائع</h2>
            
            <h3>الميزات الرئيسية للوحة التحكم:</h3>
            <ul>
                <li><strong>نظرة عامة:</strong> إحصائيات المبيعات والإيرادات</li>
                <li><strong>إدارة المنتجات:</strong> إضافة وتعديل وحذف المنتجات</li>
                <li><strong>الطلبات:</strong> عرض وإدارة جميع الطلبات</li>
                <li><strong>التقارير:</strong> تقارير مفصلة عن الأداء</li>
                <li><strong>الإعدادات:</strong> إعدادات المتجر والحساب</li>
            </ul>

            <div class="image-placeholder">
                [صورة توضيحية للوحة تحكم البائع]
            </div>
        </div>

        <div id="products" class="section">
            <h2>📦 إدارة المنتجات</h2>
            
            <h3>كيفية إضافة منتج جديد:</h3>
            <div class="step-box">
                <h4><span class="step-number">1</span> الوصول إلى صفحة إضافة المنتج</h4>
                <p>من لوحة التحكم، انقر على "إضافة منتج جديد"</p>
            </div>

            <div class="step-box">
                <h4><span class="step-number">2</span> ملء المعلومات الأساسية</h4>
                <ul>
                    <li><strong>اسم المنتج:</strong> اسم واضح ووصفي</li>
                    <li><strong>الفئة:</strong> اختر الفئة المناسبة</li>
                    <li><strong>الوصف:</strong> وصف تفصيلي للمنتج</li>
                    <li><strong>السعر:</strong> السعر بالدينار التونسي</li>
                    <li><strong>الكمية المتوفرة:</strong> عدد القطع المتوفرة</li>
                </ul>
            </div>

            <div class="step-box">
                <h4><span class="step-number">3</span> إضافة الصور</h4>
                <ul>
                    <li>صورة رئيسية عالية الجودة</li>
                    <li>صور إضافية من زوايا مختلفة</li>
                    <li>صور توضيحية للميزات</li>
                </ul>
            </div>

            <div class="tip-box">
                <h4>📸 نصائح للتصوير</h4>
                <ul>
                    <li>استخدم إضاءة جيدة وطبيعية</li>
                    <li>التقط صورًا من زوايا متعددة</li>
                    <li>أظهر المنتج في سياق الاستخدام</li>
                    <li>تأكد من وضوح التفاصيل</li>
                </ul>
            </div>

            <div class="warning-box">
                <h4>⚠️ تحذيرات مهمة</h4>
                <ul>
                    <li>لا ترفع صورًا محمية بحقوق النشر</li>
                    <li>تأكد من دقة المعلومات المقدمة</li>
                    <li>لا تستخدم أوصافًا مضللة</li>
                </ul>
            </div>
        </div>

        <div id="pricing" class="section">
            <h2>💰 التسعير والعمولات</h2>
            
            <h3>هيكل العمولات:</h3>
            <ul>
                <li><strong>العمولة الأساسية:</strong> 10% من قيمة المبيعات</li>
                <li><strong>عرض ترحيبي:</strong> 5% لمدة 3 أشهر</li>
                <li><strong>خصم الحجم:</strong> عمولة مخفضة للمبيعات العالية</li>
            </ul>

            <div class="success-box">
                <h4>🎁 العروض الحالية</h4>
                <p>احصل على عمولة مخفضة بنسبة 5% لمدة 3 أشهر من تاريخ التسجيل!</p>
            </div>

            <h3>نصائح التسعير:</h3>
            <ul>
                <li>ابحث عن أسعار المنافسين</li>
                <li>احسب تكاليف الإنتاج والشحن</li>
                <li>اترك هامش ربح مناسب</li>
                <li>قدم عروضًا خاصة للعملاء الجدد</li>
            </ul>
        </div>

        <div id="shipping" class="section">
            <h2>🚚 الشحن والتوصيل</h2>
            
            <h3>خيارات الشحن المتاحة:</h3>
            <ul>
                <li><strong>الشحن السريع:</strong> 1-2 أيام عمل</li>
                <li><strong>الشحن العادي:</strong> 3-5 أيام عمل</li>
                <li><strong>الشحن الاقتصادي:</strong> 5-7 أيام عمل</li>
            </ul>

            <h3>تكاليف الشحن:</h3>
            <div class="code-block">
                تونس العاصمة والمدن الكبرى: 5-8 دينار
                المدن المتوسطة: 8-12 دينار
                المناطق النائية: 12-18 دينار
            </div>

            <div class="tip-box">
                <h4>📦 نصائح للتغليف</h4>
                <ul>
                    <li>استخدم مواد تغليف قوية</li>
                    <li>أضف حشوات واقية</li>
                    <li>أغلق الطرد بإحكام</li>
                    <li>أضف ملصق "هش" للمنتجات الحساسة</li>
                </ul>
            </div>
        </div>

        <div id="orders" class="section">
            <h2>📋 إدارة الطلبات</h2>
            
            <h3>حالات الطلب:</h3>
            <ul>
                <li><strong>جديد:</strong> طلب جديد يتطلب التأكيد</li>
                <li><strong>مؤكد:</strong> تم تأكيد الطلب</li>
                <li><strong>قيد التحضير:</strong> يتم تحضير الطلب</li>
                <li><strong>تم الشحن:</strong> تم إرسال الطرد</li>
                <li><strong>تم التسليم:</strong> تم تسليم الطلب</li>
                <li><strong>ملغي:</strong> تم إلغاء الطلب</li>
            </ul>

            <h3>الوقت المطلوب للشحن:</h3>
            <div class="warning-box">
                <h4>⏰ مواعيد مهمة</h4>
                <ul>
                    <li>يجب تأكيد الطلب خلال 24 ساعة</li>
                    <li>يجب شحن الطلب خلال 48 ساعة من التأكيد</li>
                    <li>يجب تحديث رقم التتبع فور الشحن</li>
                </ul>
            </div>
        </div>

        <div id="marketing" class="section">
            <h2>📢 التسويق والترويج</h2>
            
            <h3>طرق زيادة المبيعات:</h3>
            <ul>
                <li><strong>العروض الخاصة:</strong> خصومات وعروض موسمية</li>
                <li><strong>المنتجات المميزة:</strong> إبراز أفضل المنتجات</li>
                <li><strong>التقييمات الإيجابية:</strong> تشجيع العملاء على التقييم</li>
                <li><strong>المحتوى التسويقي:</strong> أوصاف جذابة وصور عالية الجودة</li>
            </ul>

            <div class="tip-box">
                <h4>🎯 نصائح تسويقية</h4>
                <ul>
                    <li>استخدم كلمات مفتاحية في أوصاف المنتجات</li>
                    <li>أضف فيديوهات توضيحية للمنتجات</li>
                    <li>قدم عروضًا للعملاء المخلصين</li>
                    <li>شارك في الحملات التسويقية للموقع</li>
                </ul>
            </div>
        </div>

        <div id="policies" class="section">
            <h2>📋 السياسات والقواعد</h2>
            
            <h3>القواعد الأساسية:</h3>
            <ul>
                <li>عدم بيع منتجات مقلدة أو محظورة</li>
                <li>احترام حقوق الملكية الفكرية</li>
                <li>تقديم معلومات دقيقة عن المنتجات</li>
                <li>الالتزام بمواعيد الشحن المحددة</li>
                <li>التعامل باحترافية مع العملاء</li>
            </ul>

            <h3>سياسة الاسترجاع:</h3>
            <div class="success-box">
                <h4>🔄 ضمان رضا العملاء</h4>
                <p>نحن نضمن رضا العملاء بنسبة 100%. إذا لم يكن العميل راضيًا، يمكنه إرجاع المنتج خلال 14 يومًا من تاريخ الاستلام.</p>
            </div>

            <div class="warning-box">
                <h4>⚠️ المنتجات المحظورة</h4>
                <ul>
                    <li>المنتجات المقلدة</li>
                    <li>المنتجات الخطرة أو المحظورة قانونًا</li>
                    <li>المنتجات التي تنتهك حقوق الملكية</li>
                    <li>المنتجات غير المطابقة للمعايير</li>
                </ul>
            </div>
        </div>

        <div id="support" class="section">
            <h2>📞 الدعم والمساعدة</h2>
            
            <div class="contact-info">
                <h3>معلومات الاتصال</h3>
                <ul>
                    <li><strong>البريد الإلكتروني:</strong> sellers@webyutn.infy.uk</li>
                    <li><strong>الهاتف:</strong> +216 XX XXX XXX</li>
                    <li><strong>ساعات العمل:</strong> الأحد - الخميس 9:00 - 18:00</li>
                    <li><strong>الدردشة المباشرة:</strong> متاحة في لوحة التحكم</li>
                </ul>
            </div>

            <h3>الأسئلة الشائعة:</h3>
            <div class="step-box">
                <h4>س: كيف يمكنني تغيير معلومات متجري؟</h4>
                <p>ج: يمكنك تعديل معلومات المتجر من خلال "إعدادات المتجر" في لوحة التحكم.</p>
            </div>

            <div class="step-box">
                <h4>س: متى يتم تحويل الأرباح إلى حسابي؟</h4>
                <p>ج: يتم تحويل الأرباح شهريًا، عادة في أول يوم عمل من كل شهر.</p>
            </div>

            <div class="step-box">
                <h4>س: كيف يمكنني إلغاء طلب؟</h4>
                <p>ج: يمكنك إلغاء الطلب من خلال "إدارة الطلبات" قبل تأكيده.</p>
            </div>
        </div>

        <div class="section" style="text-align: center; background: #f8f9ff; border: 2px solid #00BFAE;">
            <h2>🎉 تهانينا!</h2>
            <p>أنت الآن جاهز لبدء رحلتك كبائع ناجح في WeBuy!</p>
            <p>نتمنى لك نجاحًا كبيرًا ونحن هنا لمساعدتك في كل خطوة.</p>
            <br>
            <a href="client/seller_dashboard.php" class="download-btn">الوصول إلى لوحة التحكم</a>
            <a href="client/seller_help.php" class="download-btn">طلب المساعدة</a>
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

        // Add active class to current section in TOC
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('.section');
            const navLinks = document.querySelectorAll('.toc a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    </script>
    <script src="main.js?v=1.2"></script>
</body>
</html> 