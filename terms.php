<?php
require_once 'db.php';
require_once 'lang.php';

// Set language
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('terms_conditions'); ?> - WeBuy</title>
    
    <!-- CSS Files -->
    
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
                    <h1 class="page-header__title"><?php echo __('terms_conditions'); ?></h1>
                    <p class="page-header__subtitle"><?php echo __('terms_last_updated'); ?>: <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </section>
        
        <section class="terms-section">
            <div class="container">
                <div class="terms-content">
                    
                    <!-- Introduction -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_introduction'); ?></h2>
                        <p class="terms-text">
                            <?php echo __('terms_intro_text'); ?>
                        </p>
                    </div>
                    
                    <!-- Acceptance of Terms -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_acceptance'); ?></h2>
                        <p class="terms-text">
                            <?php echo __('terms_acceptance_text'); ?>
                        </p>
                    </div>
                    
                    <!-- User Accounts -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_user_accounts'); ?></h2>
                        <div class="terms-list">
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_account_creation'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_account_creation_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_account_security'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_account_security_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_account_termination'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_account_termination_text'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Listings -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_product_listings'); ?></h2>
                        <div class="terms-list">
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_product_accuracy'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_product_accuracy_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_prohibited_items'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_prohibited_items_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_product_images'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_product_images_text'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders and Payment -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_orders_payment'); ?></h2>
                        <div class="terms-list">
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_order_confirmation'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_order_confirmation_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_payment_methods'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_payment_methods_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_pricing'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_pricing_text'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping and Delivery -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_shipping_delivery'); ?></h2>
                        <div class="terms-list">
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_delivery_times'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_delivery_times_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_delivery_areas'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_delivery_areas_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_delivery_costs'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_delivery_costs_text'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Returns and Refunds -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_returns_refunds'); ?></h2>
                        <div class="terms-list">
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_return_policy'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_return_policy_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_refund_process'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_refund_process_text'); ?></p>
                            </div>
                            <div class="terms-list-item">
                                <h3 class="terms-subtitle"><?php echo __('terms_defective_items'); ?></h3>
                                <p class="terms-text"><?php echo __('terms_defective_items_text'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Privacy and Data Protection -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_privacy_data'); ?></h2>
                        <p class="terms-text"><?php echo __('terms_privacy_data_text'); ?></p>
                        <p class="terms-text">
                            <a href="privacy.php" class="terms-link"><?php echo __('view_privacy_policy'); ?></a>
                        </p>
                    </div>
                    
                    <!-- Intellectual Property -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_intellectual_property'); ?></h2>
                        <p class="terms-text"><?php echo __('terms_intellectual_property_text'); ?></p>
                    </div>
                    
                    <!-- Limitation of Liability -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_limitation_liability'); ?></h2>
                        <p class="terms-text"><?php echo __('terms_limitation_liability_text'); ?></p>
                    </div>
                    
                    <!-- Dispute Resolution -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_dispute_resolution'); ?></h2>
                        <p class="terms-text"><?php echo __('terms_dispute_resolution_text'); ?></p>
                    </div>
                    
                    <!-- Changes to Terms -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_changes'); ?></h2>
                        <p class="terms-text"><?php echo __('terms_changes_text'); ?></p>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="terms-section-block">
                        <h2 class="terms-section-title"><?php echo __('terms_contact'); ?></h2>
                        <p class="terms-text"><?php echo __('terms_contact_text'); ?></p>
                        <div class="terms-contact-info">
                            <p><strong><?php echo __('email'); ?>:</strong> support@webuy.tn</p>
                            <p><strong><?php echo __('phone'); ?>:</strong> +216 XX XXX XXX</p>
                            <p><strong><?php echo __('address'); ?>:</strong> Tunis, Tunisia</p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 