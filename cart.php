<?php
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
    foreach ($_POST['qty'] as $id => $qty) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = max(1, intval($qty));
        }
    }
}
// Handle remove
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    unset($_SESSION['cart'][$id]);
}
// Fetch product details
$cart_items = [];
$total = 0;
if ($_SESSION['cart']) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    while ($row = $stmt->fetch()) {
        $row['qty'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['qty'] * $row['price'];
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
                <?php foreach ($cart_items as $item): ?>
  <?php $prod_name = $item['name_' . $lang] ?? $item['name']; ?>
  <tr>
    <td><?php if ($item['image']): ?><img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?= __('product_image') ?>" style="width:60px; height:60px; object-fit:cover; border-radius:6px; "><?php endif; ?></td>
    <td><a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></td>
    <td><?php echo htmlspecialchars($item['price']); ?> <?= __('currency') ?></td>
    <td><?php echo $item['qty']; ?></td>
    <td><?php echo $item['subtotal']; ?> <?= __('currency') ?></td>
    <td><a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn"><?= __('remove') ?></a></td>
  </tr>
<?php endforeach; ?>
            </tbody>
        </table>
                  <button type="submit" name="update" class="checkout-btn" style="background:var(--secondary-color);margin-top:20px;"><?= __('update_quantities') ?></button>
        </form>
        <h3 style="text-align:left; margin-top:30px;"><?= __('total') ?>: <?php echo $total; ?> <?= __('currency') ?></h3>
                  <a href="checkout.php" class="checkout-btn"><?= __('complete_purchase') ?></a>
          <?php else: ?>
          <p style="text-align:center;"><?= __('cart_empty') ?></p>
        <?php endif; ?>
                  <a href="index.php" class="checkout-btn" style="background:var(--secondary-color);margin-top:30px;"><?= __('continue_shopping') ?></a>
    </div>
</div>
</body>
</html> 