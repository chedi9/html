    </main>
    
    <!-- Footer with Bootstrap 5 -->
    <footer class="bg-dark text-white py-5 mt-5" role="contentinfo">
        <div class="container">
            <div class="row g-4">
                <!-- Company Info -->
                <div class="col-md-6 col-lg-3">
                    <div class="mb-3">
                        <img src="webuy-logo-transparent.jpg" alt="WeBuy Logo" class="mb-2" style="height: 40px; width: auto;">
                        <h5 class="fw-bold">WeBuy</h5>
                    </div>
                    <p class="text-white-50">
                        <?php echo $lang === 'ar' ? 'منصة التسوق الإلكتروني الرائدة في تونس' : 'Leading e-commerce platform in Tunisia'; ?>
                    </p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle" aria-label="Facebook" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle" aria-label="Twitter" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle" aria-label="Instagram" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-md-6 col-lg-3">
                    <h6 class="fw-bold mb-3"><?php echo $lang === 'ar' ? 'روابط سريعة' : 'Quick Links'; ?></h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'الرئيسية' : 'Home'; ?></a></li>
                        <li class="mb-2"><a href="store.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'المتجر' : 'Store'; ?></a></li>
                        <li class="mb-2"><a href="faq.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?></a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div class="col-md-6 col-lg-3">
                    <h6 class="fw-bold mb-3"><?php echo $lang === 'ar' ? 'خدمة العملاء' : 'Customer Service'; ?></h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="faq.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?></a></li>
                        <li class="mb-2"><a href="terms.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'الشروط والأحكام' : 'Terms & Conditions'; ?></a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy'; ?></a></li>
                    </ul>
                </div>
                
                <!-- Account -->
                <div class="col-md-6 col-lg-3">
                    <h6 class="fw-bold mb-3"><?php echo $lang === 'ar' ? 'حسابي' : 'My Account'; ?></h6>
                    <ul class="list-unstyled">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="mb-2"><a href="client/account.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'حسابي' : 'My Account'; ?></a></li>
                            <li class="mb-2"><a href="client/orders.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'طلباتي' : 'My Orders'; ?></a></li>
                            <li class="mb-2"><a href="wishlist.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'المفضلة' : 'Wishlist'; ?></a></li>
                            <li class="mb-2"><a href="client/seller_dashboard.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'لوحة البائع' : 'Seller Dashboard'; ?></a></li>
                        <?php else: ?>
                            <li class="mb-2"><a href="login.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'تسجيل الدخول' : 'Login'; ?></a></li>
                            <li class="mb-2"><a href="client/register.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'التسجيل' : 'Register'; ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <hr class="my-4 bg-white opacity-25">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="text-white-50 mb-0">
                        © <?php echo date('Y'); ?> WeBuy. <?php echo $lang === 'ar' ? 'جميع الحقوق محفوظة' : 'All rights reserved'; ?>.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="terms.php" class="text-white-50 text-decoration-none me-3"><?php echo $lang === 'ar' ? 'الشروط' : 'Terms'; ?></a>
                    <a href="privacy.php" class="text-white-50 text-decoration-none me-3"><?php echo $lang === 'ar' ? 'الخصوصية' : 'Privacy'; ?></a>
                    <a href="cookies.php" class="text-white-50 text-decoration-none"><?php echo $lang === 'ar' ? 'ملفات تعريف الارتباط' : 'Cookies'; ?></a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5.3+ JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html>