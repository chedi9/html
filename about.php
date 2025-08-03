<?php
$page_title = 'About Us - WeBuy';
require_once 'header.php';
?>

<div class="container">
    <div class="content-section" style="margin: 2rem 0;">
        <h1><?php echo ($lang ?? 'en') === 'ar' ? 'من نحن' : 'About Us'; ?></h1>
        
        <div class="about-content" style="max-width: 800px; margin: 0 auto;">
            <?php if (($lang ?? 'en') === 'ar'): ?>
                <h2>مرحباً بكم في WeBuy</h2>
                <p>نحن منصة تسوق إلكترونية تهدف إلى تقديم أفضل تجربة تسوق عبر الإنترنت للعملاء في تونس والمنطقة.</p>
                
                <h3>رؤيتنا</h3>
                <p>أن نكون المنصة الرائدة للتجارة الإلكترونية في المنطقة، نوفر منتجات عالية الجودة بأسعار تنافسية.</p>
                
                <h3>مهمتنا</h3>
                <p>تسهيل عملية التسوق الإلكتروني وتوفير تجربة آمنة وموثوقة لجميع عملائنا.</p>
                
                <h3>قيمنا</h3>
                <ul>
                    <li>الجودة العالية في المنتجات والخدمات</li>
                    <li>الشفافية في التعامل</li>
                    <li>خدمة العملاء المتميزة</li>
                    <li>الأمان والموثوقية</li>
                </ul>
            <?php else: ?>
                <h2>Welcome to WeBuy</h2>
                <p>We are an e-commerce platform dedicated to providing the best online shopping experience for customers in Tunisia and the region.</p>
                
                <h3>Our Vision</h3>
                <p>To be the leading e-commerce platform in the region, offering high-quality products at competitive prices.</p>
                
                <h3>Our Mission</h3>
                <p>To simplify online shopping and provide a safe and reliable experience for all our customers.</p>
                
                <h3>Our Values</h3>
                <ul>
                    <li>High quality products and services</li>
                    <li>Transparency in dealings</li>
                    <li>Excellent customer service</li>
                    <li>Security and reliability</li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>