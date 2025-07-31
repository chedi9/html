<?php
require_once 'db.php';
require_once 'lang.php';

// Set language
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('faq'); ?> - WeBuy</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.5" defer></script>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
    <!-- Main Content -->
    <main id="main-content" role="main">
        <section class="page-header">
            <div class="container">
                <div class="page-header__content">
                    <h1 class="page-header__title"><?php echo __('faq'); ?></h1>
                    <p class="page-header__subtitle"><?php echo __('faq_subtitle'); ?></p>
                </div>
            </div>
        </section>
        
        <section class="faq-section">
            <div class="container">
                <div class="faq-content">
                    
                    <!-- Account & Registration -->
                    <div class="faq-category">
                        <h2 class="faq-category-title"><?php echo __('account_registration'); ?></h2>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('how_to_register'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('register_instruction'); ?></p>
                                <ol>
                                    <li><?php echo __('register_step_1'); ?></li>
                                    <li><?php echo __('register_step_2'); ?></li>
                                    <li><?php echo __('register_step_3'); ?></li>
                                    <li><?php echo __('register_step_4'); ?></li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('how_to_login'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('login_instruction'); ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('forgot_password_help'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('forgot_password_instruction'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shopping & Orders -->
                    <div class="faq-category">
                        <h2 class="faq-category-title"><?php echo __('shopping_orders'); ?></h2>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('how_to_add_to_cart'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('add_to_cart_instruction'); ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('payment_methods'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('payment_methods_instruction'); ?></p>
                                <ul>
                                    <li><?php echo __('payment_card'); ?></li>
                                    <li><?php echo __('payment_d17'); ?></li>
                                    <li><?php echo __('payment_cod'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('how_to_track_order'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('track_order_instruction'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping & Delivery -->
                    <div class="faq-category">
                        <h2 class="faq-category-title"><?php echo __('shipping_delivery'); ?></h2>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('delivery_times'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('delivery_times_instruction'); ?></p>
                                <ul>
                                    <li><?php echo __('standard_delivery'); ?>: 2-5 <?php echo __('business_days'); ?></li>
                                    <li><?php echo __('express_delivery'); ?>: 1-2 <?php echo __('business_days'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('delivery_costs'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('delivery_costs_instruction'); ?></p>
                                <ul>
                                    <li><?php echo __('standard_cost'); ?>: 7 TND</li>
                                    <li><?php echo __('express_cost'); ?>: 12 TND</li>
                                    <li><?php echo __('free_shipping'); ?>: <?php echo __('orders_above'); ?> 105 TND</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('delivery_areas'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('delivery_areas_instruction'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Returns & Refunds -->
                    <div class="faq-category">
                        <h2 class="faq-category-title"><?php echo __('returns_refunds'); ?></h2>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('return_policy'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('return_policy_instruction'); ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('refund_process'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('refund_process_instruction'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seller Information -->
                    <div class="faq-category">
                        <h2 class="faq-category-title"><?php echo __('seller_information'); ?></h2>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('how_to_become_seller'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('become_seller_instruction'); ?></p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><?php echo __('seller_dashboard_access'); ?></span>
                                <svg class="faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo __('seller_dashboard_instruction'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Contact Support -->
                <div class="support-section">
                    <div class="support-card">
                        <h3 class="support-title"><?php echo __('need_help'); ?></h3>
                        <p class="support-description"><?php echo __('contact_support_description'); ?></p>
                        
                        <div class="support-options">
                            <div class="support-option">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <div>
                                    <h4><?php echo __('email_support'); ?></h4>
                                    <p><a href="mailto:support@webuy.tn">support@webuy.tn</a></p>
                                </div>
                            </div>
                            
                            <div class="support-option">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <div>
                                    <h4><?php echo __('phone_support'); ?></h4>
                                    <p>+216 XX XXX XXX</p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" action="support_submit.php" class="support-form">
                            <h4><?php echo __('send_message'); ?></h4>
                            <div class="form-group">
                                <label for="name"><?php echo __('name'); ?></label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email"><?php echo __('email'); ?></label>
                                <input type="email" id="email" name="email" required autocomplete="email">
                            </div>
                            <div class="form-group">
                                <label for="subject"><?php echo __('subject'); ?></label>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="message"><?php echo __('message'); ?></label>
                                <textarea id="message" name="message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn--primary"><?php echo __('send_message'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
    
    <script>
        function toggleFaq(button) {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('.faq-icon');
            
            // Toggle the answer
            answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
            
            // Toggle the icon rotation
            icon.style.transform = answer.style.display === 'block' ? 'rotate(180deg)' : 'rotate(0deg)';
            
            // Toggle active class
            button.classList.toggle('active');
        }
    </script>
</body>
</html> 