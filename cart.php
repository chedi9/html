<?php
// Security and compatibility headers
require_once 'security_integration.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require_once 'includes/thumbnail_helper.php';
require_once 'lang.php';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['qty'] as $cart_key => $qty) {
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key] = max(1, intval($qty));
        }
    }
}
// Handle remove
if (isset($_GET['remove'])) {
    $cart_key = $_GET['remove'];
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
    }
}
// Fetch product details
$cart_items = [];
$total = 0;
if ($_SESSION['cart']) {
    $cart_keys = array_keys($_SESSION['cart']);
    $ids = array_map(function($k){ return explode('|', $k)[0]; }, $cart_keys);
    $ids_str = implode(',', array_map('intval', $ids));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids_str)");
    $products_map = [];
    while ($row = $stmt->fetch()) {
        $products_map[$row['id']] = $row;
    }
    
    foreach ($cart_keys as $cart_key) {
        $parts = explode('|', $cart_key, 2);
        $pid = intval($parts[0]);
        $variant = isset($parts[1]) ? $parts[1] : '';
        
        if (!isset($products_map[$pid])) {
            // Remove the missing product from cart
            unset($_SESSION['cart'][$cart_key]);
            continue;
        }
        $row = $products_map[$pid];
        $row['qty'] = $_SESSION['cart'][$cart_key];
        $row['subtotal'] = $row['qty'] * $row['price'];
        $row['variant'] = $variant;
        $cart_items[] = $row;
        $total += $row['subtotal'];
    }
}
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('shopping_cart'); ?> - WeBuy</title>
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.4" defer></script>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link"><?php echo __('skip_to_main_content'); ?></a>
    
    <?php include 'header.php'; ?>
    
    <main id="main-content" role="main">
        <!-- Cart Section -->
        <section class="py-5">
            <div class="container">
                <h1 class="display-5 fw-bold mb-4"><?php echo __('shopping_cart'); ?></h1>
                
                <?php if ($cart_items): ?>
                    <div class="row g-4">
                        <!-- Cart Items -->
                        <div class="col-lg-8">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <form method="post">
                                        <div class="table-responsive">
                                            <table class="table align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?php echo __('product'); ?></th>
                                                        <th class="text-center"><?php echo __('price'); ?></th>
                                                        <th class="text-center"><?php echo __('quantity'); ?></th>
                                                        <th class="text-center"><?php echo __('subtotal'); ?></th>
                                                        <th class="text-center"><?php echo __('remove'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cart_items as $idx => $item): ?>
                                                        <?php $prod_name = $item['name_' . $lang] ?? $item['name']; ?>
                                                        <?php $cart_key = array_keys($_SESSION['cart'])[$idx]; ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <?php if ($item['image']): ?>
                                                                        <?php 
                                                                        $optimized_image = get_optimized_image('uploads/' . $item['image'], 'card');
                                                                        ?>
                                                                        <img src="<?php echo $optimized_image['src']; ?>" 
                                                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                                             loading="lazy" 
                                                                             class="rounded"
                                                                             style="width: 80px; height: 80px; object-fit: cover;">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <a href="product.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark fw-semibold">
                                                                            <?php echo htmlspecialchars($prod_name); ?>
                                                                        </a>
                                                                        <?php if (!empty($item['variant'])): ?>
                                                                            <div class="small text-muted mt-1">
                                                                                <?php echo htmlspecialchars($item['variant']); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="fw-semibold"><?php echo number_format($item['price'], 2); ?> <?php echo __('currency'); ?></span>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="number" 
                                                                       name="qty[<?php echo htmlspecialchars($cart_key); ?>]" 
                                                                       value="<?php echo $item['qty']; ?>" 
                                                                       min="1" 
                                                                       class="form-control form-control-sm text-center" 
                                                                       style="width: 80px; display: inline-block;">
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="fw-bold text-primary"><?php echo number_format($item['subtotal'], 2); ?> <?php echo __('currency'); ?></span>
                                                            </td>
                                                            <td class="text-center">
                                                                <a href="cart.php?remove=<?php echo urlencode($cart_key); ?>" 
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('<?php echo __('confirm_remove'); ?>');">
                                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                                    </svg>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <a href="store.php" class="btn btn-outline-secondary">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                                    <polyline points="12,19 5,12 12,5"></polyline>
                                                </svg>
                                                <?php echo __('continue_shopping'); ?>
                                            </a>
                                            <button type="submit" name="update" class="btn btn-primary">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                    <path d="M21.5 2v6h-6"></path>
                                                    <path d="M2.5 12a10 10 0 0 1 18.8-4.5L21.5 8"></path>
                                                    <path d="M2.5 22v-6h6"></path>
                                                    <path d="M21.5 12a10 10 0 0 1-18.8 4.5L2.5 16"></path>
                                                </svg>
                                                <?php echo __('update_cart'); ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="col-lg-4">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold mb-4"><?php echo __('cart_summary'); ?></h5>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><?php echo __('subtotal'); ?>:</span>
                                        <span class="fw-semibold"><?php echo number_format($total, 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><?php echo __('shipping'); ?>:</span>
                                        <span class="fw-semibold"><?php echo __('calculated_at_checkout'); ?></span>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between mb-4">
                                        <span class="h5 mb-0"><?php echo __('total'); ?>:</span>
                                        <span class="h5 mb-0 text-primary fw-bold"><?php echo number_format($total, 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="checkout.php" class="btn btn-primary btn-lg">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                                <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                            <?php echo __('proceed_to_checkout'); ?>
                                        </a>
                                        
                                        <?php if (!isset($_SESSION['user_id'])): ?>
                                            <a href="checkout.php?guest=1" class="btn btn-success">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                    <circle cx="12" cy="7" r="4"></circle>
                                                </svg>
                                                <?php echo __('continue_as_guest'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Trust Badges -->
                                    <div class="mt-4 pt-4 border-top">
                                        <h6 class="text-muted small mb-3"><?php echo __('we_accept'); ?>:</h6>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <span class="badge bg-light text-dark">💳 Visa</span>
                                            <span class="badge bg-light text-dark">💳 Mastercard</span>
                                            <span class="badge bg-light text-dark">💰 D17</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Promo Code (Optional) -->
                            <div class="card shadow-sm mt-3">
                                <div class="card-body">
                                    <h6 class="card-title mb-3"><?php echo __('have_promo_code'); ?></h6>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="<?php echo __('enter_code'); ?>">
                                        <button class="btn btn-outline-primary" type="button">
                                            <?php echo __('apply'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Empty Cart -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="text-muted">
                                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                        </div>
                        <h2 class="h3 mb-3"><?php echo __('cart_is_empty'); ?></h2>
                        <p class="text-muted mb-4"><?php echo __('add_products_to_cart'); ?></p>
                        <a href="store.php" class="btn btn-primary btn-lg">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                            <?php echo __('browse_products'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html>
