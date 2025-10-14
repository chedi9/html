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
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
$page_title = __('shopping_cart') . ' - WeBuy';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- WeBuy Custom Bootstrap Configuration -->
    <link rel="stylesheet" href="css/bootstrap-custom.css">
    
    <!-- Legacy CSS for gradual migration -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.4" defer></script>
</head>
<body class="page-transition">
    <?php include 'header.php'; ?>
    
    <main id="main-content" role="main">
        <!-- Cart Section -->
        <section class="py-5">
            <div class="container">
                <h1 class="h2 mb-4"><?php echo $lang === 'ar' ? 'سلة التسوق' : 'Shopping Cart'; ?></h1>
                
                <?php if ($cart_items): ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <form method="post">
                                        <div class="table-responsive">
                                            <table class="table align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?php echo $lang === 'ar' ? 'الصورة' : 'Image'; ?></th>
                                                        <th><?php echo $lang === 'ar' ? 'المنتج' : 'Product'; ?></th>
                                                        <th><?php echo $lang === 'ar' ? 'السعر' : 'Price'; ?></th>
                                                        <th><?php echo $lang === 'ar' ? 'الكمية' : 'Quantity'; ?></th>
                                                        <th><?php echo $lang === 'ar' ? 'المجموع' : 'Subtotal'; ?></th>
                                                        <th><?php echo $lang === 'ar' ? 'إزالة' : 'Remove'; ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cart_items as $idx => $item): ?>
                                                        <?php $prod_name = $item['name_' . $lang] ?? $item['name']; ?>
                                                        <?php $cart_key = array_keys($_SESSION['cart'])[$idx]; ?>
                                                        <tr>
                                                            <td>
                                                                <?php if ($item['image']): ?>
                                                                    <?php 
                                                                    $optimized_image = get_optimized_image('uploads/' . $item['image'], 'card');
                                                                    ?>
                                                                    <img src="<?php echo $optimized_image['src']; ?>" 
                                                                         srcset="<?php echo $optimized_image['srcset']; ?>" 
                                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                                         loading="lazy" 
                                                                         class="img-thumbnail"
                                                                         style="width: 80px; height: 80px; object-fit: cover;">
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <a href="product.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark fw-semibold">
                                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                                </a>
                                                                <?php if (!empty($item['variant'])): ?>
                                                                    <div class="text-muted small"><?php echo htmlspecialchars($item['variant']); ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="fw-semibold"><?php echo number_format($item['price'], 2); ?> <?php echo __('currency'); ?></td>
                                                            <td>
                                                                <input type="number" 
                                                                       name="qty[<?php echo htmlspecialchars($cart_key); ?>]" 
                                                                       value="<?php echo $item['qty']; ?>" 
                                                                       min="1" 
                                                                       class="form-control form-control-sm" 
                                                                       style="width: 80px;">
                                                            </td>
                                                            <td class="fw-bold text-primary"><?php echo number_format($item['subtotal'], 2); ?> <?php echo __('currency'); ?></td>
                                                            <td>
                                                                <a href="cart.php?remove=<?php echo urlencode($cart_key); ?>" 
                                                                   class="btn btn-sm btn-danger">
                                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                    </svg>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-3">
                                            <a href="store.php" class="btn btn-outline-secondary">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                    <polyline points="15 18 9 12 15 6"></polyline>
                                                </svg>
                                                <?php echo $lang === 'ar' ? 'متابعة التسوق' : 'Continue Shopping'; ?>
                                            </a>
                                            <button type="submit" name="update" class="btn btn-primary">
                                                <?php echo $lang === 'ar' ? 'تحديث السلة' : 'Update Cart'; ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="col-lg-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="h5 mb-0"><?php echo $lang === 'ar' ? 'ملخص الطلب' : 'Order Summary'; ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span><?php echo $lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal'; ?>:</span>
                                        <span class="fw-semibold"><?php echo number_format($total, 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                        <span><?php echo $lang === 'ar' ? 'الشحن' : 'Shipping'; ?>:</span>
                                        <span class="text-muted"><?php echo $lang === 'ar' ? 'يتم حسابه عند الدفع' : 'Calculated at checkout'; ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-4">
                                        <span class="h5 mb-0"><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?>:</span>
                                        <span class="h4 mb-0 text-primary fw-bold"><?php echo number_format($total, 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="checkout.php" class="btn btn-primary btn-lg">
                                            <?php echo $lang === 'ar' ? 'إتمام الشراء' : 'Proceed to Checkout'; ?>
                                        </a>
                                        
                                        <?php if (!isset($_SESSION['user_id'])): ?>
                                            <a href="checkout.php?guest=1" class="btn btn-success">
                                                🛒 <?php echo __('continue_as_guest'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Empty Cart -->
                    <div class="text-center py-5">
                        <div class="display-1 mb-4">🛒</div>
                        <h2 class="h3 mb-3"><?php echo $lang === 'ar' ? 'سلة التسوق فارغة' : 'Your cart is empty'; ?></h2>
                        <p class="text-muted mb-4"><?php echo $lang === 'ar' ? 'ابدأ التسوق واضف المنتجات إلى سلتك' : 'Start shopping and add products to your cart'; ?></p>
                        <a href="store.php" class="btn btn-primary btn-lg">
                            <?php echo $lang === 'ar' ? 'تسوق الآن' : 'Shop Now'; ?>
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
