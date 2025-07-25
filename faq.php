<?php
// FAQ & Help Center Page
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>الأسئلة الشائعة & مركز المساعدة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
    <style>
        .faq-container { max-width: 800px; margin: 40px auto; background: #fff; padding: 36px; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .faq-container h2 { text-align: center; margin-bottom: 30px; color: var(--primary-color); }
        .faq-list { margin-bottom: 40px; }
        .faq-q { font-weight: bold; color: var(--primary-color); margin-top: 18px; margin-bottom: 6px; }
        .faq-a { color: #222; margin-bottom: 12px; }
        .support-form { background: #f4f6fb; border-radius: 10px; padding: 24px; box-shadow: 0 1px 4px #0001; }
        .support-form h3 { margin-bottom: 18px; color: var(--primary-color); }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; }
        input, textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; }
        .send-btn { background: var(--primary-color); color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>
    <div class="faq-container">
        <h2>الأسئلة الشائعة & مركز المساعدة</h2>
        <div class="faq-list">
            <div class="faq-q">كيف يمكنني إنشاء حساب جديد؟</div>
            <div class="faq-a">اضغط على "تسجيل" في أعلى الصفحة واملأ البيانات المطلوبة.</div>
            <div class="faq-q">كيف أضيف منتجًا إلى السلة؟</div>
            <div class="faq-a">اضغط على زر "أضف إلى السلة" بجانب المنتج الذي ترغب في شرائه.</div>
            <div class="faq-q">ما هي طرق الدفع المتاحة؟</div>
            <div class="faq-a">يمكنك الدفع عبر البطاقة البنكية، D17، أو الدفع عند الاستلام.</div>
            <div class="faq-q">كيف أتتبع طلبي؟</div>
            <div class="faq-a">استخدم صفحة "تتبع الطلب" وأدخل رقم الطلب والبريد الإلكتروني.</div>
            <div class="faq-q">كيف أتواصل مع الدعم؟</div>
            <div class="faq-a">يمكنك استخدام النموذج أدناه أو مراسلتنا على البريد الإلكتروني: <a href="mailto:webuytn0@gmail.com">webuytn0@gmail.com</a></div>
        </div>
        <div class="support-form">
            <h3>بحاجة إلى مساعدة إضافية؟</h3>
            <form method="post" action="support_submit.php">
                <div class="form-group">
                    <label for="name">الاسم:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">البريد الإلكتروني:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="message">سؤالك أو مشكلتك:</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="send-btn">إرسال</button>
            </form>
        </div>
    </div>
</body>
</html> 