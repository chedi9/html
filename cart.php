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
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سلة التسوق</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="main.js?v=1.4" defer></script>
    
</head>
<body>
<div id="pageContent">
    <div class="cart-container">
        <h2>سلة التسوق</h2>
        <?php if ($cart_items): ?>
        <form method="post">
        <table>
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <th>المجموع الفرعي</th>
                    <th>إزالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $idx => $item): ?>
  <?php $prod_name = $item['name_' . $lang] ?? $item['name']; ?>
  <?php $cart_key = array_keys($_SESSION['cart'])[$idx]; ?>
  <tr>
    <td><?php if ($item['image']): ?>
            <div class="product-img-wrap">
                <div class="skeleton skeleton--image"></div>
                <?php 
                $optimized_image = get_optimized_image('uploads/' . $item['image'], 'card');
                ?>
                <img src="<?php echo $optimized_image['src']; ?>" 
                     srcset="<?php echo $optimized_image['srcset']; ?>" 
                     sizes="<?php echo $optimized_image['sizes']; ?>"
                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                     loading="lazy" 
                     width="80" 
                     height="80" 
                     onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
            </div>
    <?php endif; ?></td>
    <td><a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
    <?php if (!empty($item['variant'])): ?>
      <div>(<?php echo htmlspecialchars($item['variant']); ?>)</div>
    <?php endif; ?>
    </td>
          <td><?php echo htmlspecialchars($item['price']); ?> <?php echo __('currency'); ?></td>
    <td><input type="number" name="qty[<?php echo htmlspecialchars($cart_key); ?>]" value="<?php echo $item['qty']; ?>" min="1"></td>
          <td><?php echo $item['subtotal']; ?> <?php echo __('currency'); ?></td>
                        <td><a href="cart.php?remove=<?php echo urlencode($cart_key); ?>" class="remove-btn"><?php echo __('remove'); ?></a></td>
  </tr>
<?php endforeach; ?>
            </tbody>
        </table>
                  <button type="submit" name="update" class="checkout-btn">تحديث الكميات</button>
        </form>
        <h3>الإجمالي: <?php echo $total; ?> د.ت</h3>
                  <a href="checkout.php" class="checkout-btn">إتمام الشراء</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="checkout.php?guest=1" class="checkout-btn">🛒 <?php echo __('continue_as_guest'); ?></a>
            <?php endif; ?>
          <?php else: ?>
          <p>سلة التسوق فارغة</p>
        <?php endif; ?>
                  <a href="index.php" class="checkout-btn">العودة للتسوق</a>
    </div>
</div>

<!-- Cookie Consent Banner -->
<?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 