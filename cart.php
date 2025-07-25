<?php
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
    <td><?php if ($item['image']): ?><img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="صورة المنتج" style="width:60px; height:60px; object-fit:cover; border-radius:6px; "><?php endif; ?></td>
    <td><?php echo htmlspecialchars($prod_name); ?></td>
    <td><?php echo htmlspecialchars($item['price']); ?> د.ت</td>
    <td><input type="number" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['qty']; ?>" min="1" style="width:60px;"></td>
    <td><?php echo $item['subtotal']; ?> د.ت</td>
    <td><a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn">إزالة</a></td>
  </tr>
<?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="update" class="checkout-btn" style="background:var(--secondary-color);margin-top:20px;">تحديث الكميات</button>
        </form>
        <h3 style="text-align:left; margin-top:30px;">الإجمالي: <?php echo $total; ?> د.ت</h3>
        <a href="checkout.php" class="checkout-btn">إتمام الشراء</a>
        <?php else: ?>
        <p style="text-align:center;">سلة التسوق فارغة.</p>
        <?php endif; ?>
        <a href="index.html" class="checkout-btn" style="background:var(--secondary-color);margin-top:30px;">العودة للتسوق</a>
    </div>
</div>
</body>
</html> 