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
    <title>سياسة الكوكيز | WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
</head>
<body>
    <section class="container" style="max-width:700px;margin:40px auto;">
        <h2>سياسة الكوكيز</h2>
        <p>
        يستخدم موقع WeBuy الكوكيز لإدارة الجلسات (sessions) وتسجيل الدخول وتحسين تجربتك على الموقع. كما نستخدم أيضاً كوكيز Google Analytics لتحليل استخدام الموقع وتحسين خدماتنا. قد يتم تخزين بعض الكوكيز من قبل أطراف ثالثة (مثل Google) لأغراض التحليل الإحصائي. يمكنك رفض أو قبول الكوكيز التحليلية من خلال شريط الكوكيز في أسفل الصفحة.
        باستخدامك لموقعنا، فإنك توافق على استخدامنا للكوكيز وفقًا لهذه السياسة.
        </p>
    </section>
</body>
</html> 