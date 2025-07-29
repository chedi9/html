    </main>
    
    <!-- Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer__content">
                <!-- Company Info -->
                <div class="footer__section">
                    <div class="footer__brand">
                        <img src="../webuy-logo-transparent.jpg" alt="WeBuy Logo" style="height: 40px; width: auto;">
                        <h3 class="footer__title">WeBuy</h3>
                    </div>
                    <p class="footer__description">
                        <?php echo $lang === 'ar' ? 'منصة التسوق الإلكتروني الرائدة في تونس' : 'Leading e-commerce platform in Tunisia'; ?>
                    </p>
                    <div class="footer__social">
                        <a href="#" class="footer__social-link" aria-label="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="footer__social-link" aria-label="Twitter">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="footer__social-link" aria-label="Instagram">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323c-.875.807-2.026 1.297-3.323 1.297zm7.718-1.297c-.875.807-2.026 1.297-3.323 1.297s-2.448-.49-3.323-1.297c-.807-.875-1.297-2.026-1.297-3.323s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer__section">
                    <h4 class="footer__section-title"><?php echo $lang === 'ar' ? 'روابط سريعة' : 'Quick Links'; ?></h4>
                    <ul class="footer__links">
                        <li><a href="index.php" class="footer__link"><?php echo $lang === 'ar' ? 'الرئيسية' : 'Home'; ?></a></li>
                        <li><a href="store.php" class="footer__link"><?php echo $lang === 'ar' ? 'المتجر' : 'Store'; ?></a></li>
                        <li><a href="faq.php" class="footer__link"><?php echo $lang === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?></a></li>
                        <li><a href="about.php" class="footer__link"><?php echo $lang === 'ar' ? 'من نحن' : 'About Us'; ?></a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div class="footer__section">
                    <h4 class="footer__section-title"><?php echo $lang === 'ar' ? 'خدمة العملاء' : 'Customer Service'; ?></h4>
                    <ul class="footer__links">
                        <li><a href="contact.php" class="footer__link"><?php echo $lang === 'ar' ? 'اتصل بنا' : 'Contact Us'; ?></a></li>
                        <li><a href="shipping.php" class="footer__link"><?php echo $lang === 'ar' ? 'الشحن والتوصيل' : 'Shipping & Delivery'; ?></a></li>
                        <li><a href="returns.php" class="footer__link"><?php echo $lang === 'ar' ? 'الإرجاع والاستبدال' : 'Returns & Exchanges'; ?></a></li>
                        <li><a href="privacy.php" class="footer__link"><?php echo $lang === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy'; ?></a></li>
                    </ul>
                </div>
                
                <!-- Account -->
                <div class="footer__section">
                    <h4 class="footer__section-title"><?php echo $lang === 'ar' ? 'حسابي' : 'My Account'; ?></h4>
                    <ul class="footer__links">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="client/account.php" class="footer__link"><?php echo $lang === 'ar' ? 'حسابي' : 'My Account'; ?></a></li>
                            <li><a href="client/orders.php" class="footer__link"><?php echo $lang === 'ar' ? 'طلباتي' : 'My Orders'; ?></a></li>
                            <li><a href="wishlist.php" class="footer__link"><?php echo $lang === 'ar' ? 'المفضلة' : 'Wishlist'; ?></a></li>
                            <li><a href="client/seller_dashboard.php" class="footer__link"><?php echo $lang === 'ar' ? 'لوحة البائع' : 'Seller Dashboard'; ?></a></li>
                        <?php else: ?>
                            <li><a href="client/login.php" class="footer__link"><?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Login'; ?></a></li>
                            <li><a href="client/register.php" class="footer__link"><?php echo $lang === 'ar' ? 'التسجيل' : 'Register'; ?></a></li>
                            <li><a href="client/forgot_password.php" class="footer__link"><?php echo $lang === 'ar' ? 'نسيت كلمة المرور' : 'Forgot Password'; ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer__bottom">
                <div class="footer__bottom-content">
                    <p class="footer__copyright">
                        © <?php echo date('Y'); ?> WeBuy. <?php echo $lang === 'ar' ? 'جميع الحقوق محفوظة' : 'All rights reserved'; ?>.
                    </p>
                    <div class="footer__bottom-links">
                        <a href="terms.php" class="footer__bottom-link"><?php echo $lang === 'ar' ? 'الشروط والأحكام' : 'Terms & Conditions'; ?></a>
                        <a href="privacy.php" class="footer__bottom-link"><?php echo $lang === 'ar' ? 'الخصوصية' : 'Privacy'; ?></a>
                        <a href="cookies.php" class="footer__bottom-link"><?php echo $lang === 'ar' ? 'ملفات تعريف الارتباط' : 'Cookies'; ?></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Optimized JavaScript -->
    <script src="../js/optimized/main.min.js" defer></script>
    
    <!-- Performance monitoring -->
    <script>
        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('Page load time:', loadTime.toFixed(2), 'ms');
        });
    </script>
</body>
</html>