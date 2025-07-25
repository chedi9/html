<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
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
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
    <style>
        .cart-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .cart-container h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .remove-btn { background: #c00; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none; font-size: 0.95em; margin: 0 4px; }
        .remove-btn:hover { background: #a00; }
        .checkout-btn { background: var(--primary-color); color: #fff; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-size: 1.1em; display: inline-block; margin-top: 20px; }
        .checkout-btn:hover { background: var(--secondary-color); }
    </style>
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
    <td><?php if ($item['image']): ?><img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="صورة المنتج" style="width:60px; height:60px; object-fit:cover; border-radius:6px; "><?php endif; ?></td>
    <td><a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
    <?php if (!empty($item['variant'])): ?>
      <div style="font-size:0.98em;color:#1A237E;margin-top:4px;">(<?php echo htmlspecialchars($item['variant']); ?>)</div>
    <?php endif; ?>
    </td>
    <td><?php echo htmlspecialchars($item['price']); ?> د.ت</td>
    <td><input type="number" name="qty[<?php echo htmlspecialchars($cart_key); ?>]" value="<?php echo $item['qty']; ?>" min="1" style="width:60px;"></td>
    <td><?php echo $item['subtotal']; ?> د.ت</td>
    <td><a href="cart.php?remove=<?php echo urlencode($cart_key); ?>" class="remove-btn">إزالة</a></td>
  </tr>
<?php endforeach; ?>
            </tbody>
        </table>
                  <button type="submit" name="update" class="checkout-btn" style="background:var(--secondary-color);margin-top:20px;">تحديث الكميات</button>
        </form>
        <h3 style="text-align:left; margin-top:30px;">الإجمالي: <?php echo $total; ?> د.ت</h3>
                  <a href="checkout.php" class="checkout-btn">إتمام الشراء</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="checkout.php?guest=1" class="checkout-btn" style="background: #28a745; margin-top: 10px;">🛒 Continue as Guest (Online Payments Only)</a>
            <?php endif; ?>
          <?php else: ?>
          <p style="text-align:center;">سلة التسوق فارغة</p>
        <?php endif; ?>
                  <a href="index.php" class="checkout-btn" style="background:var(--secondary-color);margin-top:30px;">العودة للتسوق</a>
    </div>
</div>
</body>
</html> 