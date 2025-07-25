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
    <title>سياسة الخصوصية | WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
</head>
<body>
    <section class="container" style="max-width:700px;margin:40px auto;">
        <h2>سياسة الخصوصية</h2>
        <p>
        نحن في WeBuy نحترم خصوصيتك. نقوم بجمع اسمك، بريدك الإلكتروني، ومعلومات الطلب فقط لتقديم خدماتنا بشكل أفضل. لا نشارك بياناتك مع أي طرف ثالث إلا إذا كان ذلك مطلوبًا بموجب القانون.
        نستخدم Google Analytics لتحليل استخدام الموقع وتحسين خدماتنا. قد تجمع Google Analytics بيانات مثل عنوان IP، نوع الجهاز، ونمط الاستخدام. هذه البيانات تُستخدم فقط لأغراض التحليل الإحصائي ولا يتم مشاركتها مع أي طرف ثالث آخر إلا إذا كان ذلك مطلوبًا قانونيًا.
        يمكنك رفض أو قبول الكوكيز التحليلية من خلال شريط الكوكيز في أسفل الصفحة.
        لمزيد من المعلومات أو الاستفسارات، يرجى التواصل معنا عبر البريد الإلكتروني: <a href="mailto:webuytn0@gmail.com" style="color:var(--accent-color);">webuytn0@gmail.com</a>
        </p>
    </section>
</body>
</html> 