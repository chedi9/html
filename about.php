<?php
$page_title = 'About Us - WeBuy';
require_once 'header.php';
?>

<div class="container">
    <div class="section">
        <div class="section__header">
            <h1 class="section__title">
                <?php echo ($lang ?? 'en') === 'ar' ? 'من نحن' : 'About WeBuy'; ?>
            </h1>
            <p class="section__subtitle">
                <?php echo ($lang ?? 'en') === 'ar' ? 'منصة التسوق الإلكتروني الرائدة في تونس' : 'Tunisia\'s leading online shopping platform'; ?>
            </p>
        </div>

        <div class="grid grid--2-cols">
            <div class="about__content">
                <h2><?php echo ($lang ?? 'en') === 'ar' ? 'مهمتنا' : 'Our Mission'; ?></h2>
                <p>
                    <?php if (($lang ?? 'en') === 'ar'): ?>
                        نحن في WeBuy نسعى لتوفير تجربة تسوق إلكتروني متميزة ومريحة لجميع العملاء في تونس. 
                        نهدف إلى ربط البائعين بالمشترين من خلال منصة آمنة وسهلة الاستخدام.
                    <?php else: ?>
                        At WeBuy, we strive to provide an exceptional and convenient online shopping experience 
                        for all customers in Tunisia. We aim to connect sellers with buyers through a secure 
                        and user-friendly platform.
                    <?php endif; ?>
                </p>

                <h2><?php echo ($lang ?? 'en') === 'ar' ? 'قيمنا' : 'Our Values'; ?></h2>
                <ul class="about__values">
                    <li><strong><?php echo ($lang ?? 'en') === 'ar' ? 'الجودة:' : 'Quality:'; ?></strong> 
                        <?php echo ($lang ?? 'en') === 'ar' ? 'نحرص على توفير منتجات عالية الجودة من بائعين موثوقين' : 'We ensure high-quality products from trusted sellers'; ?></li>
                    <li><strong><?php echo ($lang ?? 'en') === 'ar' ? 'الأمان:' : 'Security:'; ?></strong> 
                        <?php echo ($lang ?? 'en') === 'ar' ? 'نحمي بياناتك الشخصية ومعاملاتك المالية' : 'We protect your personal data and financial transactions'; ?></li>
                    <li><strong><?php echo ($lang ?? 'en') === 'ar' ? 'الخدمة:' : 'Service:'; ?></strong> 
                        <?php echo ($lang ?? 'en') === 'ar' ? 'فريق دعم العملاء متاح لمساعدتك دائماً' : 'Our customer support team is always available to help'; ?></li>
                </ul>
            </div>

            <div class="about__image">
                <img src="webuy-logo-transparent.jpg" alt="WeBuy Logo" class="about__logo">
            </div>
        </div>

        <div class="about__stats">
            <div class="stats">
                <div class="stat">
                    <h3 class="stat__number">1000+</h3>
                    <p class="stat__label"><?php echo ($lang ?? 'en') === 'ar' ? 'منتج' : 'Products'; ?></p>
                </div>
                <div class="stat">
                    <h3 class="stat__number">500+</h3>
                    <p class="stat__label"><?php echo ($lang ?? 'en') === 'ar' ? 'عميل راضي' : 'Happy Customers'; ?></p>
                </div>
                <div class="stat">
                    <h3 class="stat__number">50+</h3>
                    <p class="stat__label"><?php echo ($lang ?? 'en') === 'ar' ? 'بائع' : 'Sellers'; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.about__content h2 {
    color: var(--color-primary-600);
    margin-top: var(--space-6);
    margin-bottom: var(--space-3);
}

.about__values {
    list-style: none;
    padding: 0;
}

.about__values li {
    margin-bottom: var(--space-3);
    padding: var(--space-3);
    background: var(--color-gray-50);
    border-radius: var(--border-radius-md);
    border-left: 4px solid var(--color-primary-600);
}

.about__logo {
    max-width: 100%;
    height: auto;
    border-radius: var(--border-radius-lg);
}

.about__stats {
    margin-top: var(--space-8);
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-4);
}

.stat {
    text-align: center;
    padding: var(--space-4);
    background: var(--color-primary-50);
    border-radius: var(--border-radius-lg);
}

.stat__number {
    font-size: 2.5rem;
    font-weight: var(--font-weight-bold);
    color: var(--color-primary-600);
    margin: 0;
}

.stat__label {
    color: var(--color-text-secondary);
    margin: var(--space-2) 0 0 0;
}

html[data-theme="dark"] .about__values li {
    background: var(--color-gray-800);
}

html[data-theme="dark"] .stat {
    background: var(--color-gray-800);
}
</style>

<?php require_once 'footer.php'; ?>