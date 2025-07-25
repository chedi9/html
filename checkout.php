<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
// Fetch cart items
$cart_items = [];
$total = 0;
$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
while ($row = $stmt->fetch()) {
    $row['qty'] = $_SESSION['cart'][$row['id']];
    $row['subtotal'] = $row['qty'] * $row['price'];
    $cart_items[] = $row;
    $total += $row['subtotal'];
}
// Handle order submission
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $payment = $_POST['payment'];
    $shipping = $_POST['shipping'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, name, address, phone, email, payment_method, shipping_method, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $name, $address, $phone, $email, $payment, $shipping, $total]);
    $order_id = $pdo->lastInsertId();
    foreach ($cart_items as $item) {
        $prod_name = $item['name_' . $lang] ?? $item['name'];
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$order_id, $item['id'], $prod_name, $item['price'], $item['qty'], $item['subtotal']]);
    }
    // Clear cart
    $_SESSION['cart'] = [];
    $success = true;
}
// Prefill user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إتمام الشراء</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
    <style>
        .checkout-container { max-width: 700px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .checkout-container h2 { text-align: center; margin-bottom: 30px; }
        .progress-steps { display: flex; justify-content: center; align-items: center; gap: 18px; margin-bottom: 30px; }
        .step { display: flex; flex-direction: column; align-items: center; font-size: 1em; color: #888; }
        .step.active { color: var(--primary-color); font-weight: bold; }
        .step-circle { width: 32px; height: 32px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 1.1em; margin-bottom: 4px; }
        .step.active .step-circle { background: var(--primary-color); color: #fff; }
        .step-line { width: 40px; height: 3px; background: #eee; margin: 0 4px; }
        .step.active ~ .step-line { background: var(--primary-color); }
        label { display: block; margin-bottom: 8px; }
        input, select { width: 100%; padding: 14px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; font-size: 1em; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .order-success { background: #e6ffe6; color: #228B22; border: 1px solid #b2ffb2; padding: 10px 16px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .checkout-btn { width: 100%; padding: 16px; font-size: 1.1em; border-radius: 6px; margin-top: 18px; }
        @media (max-width: 700px) {
            .checkout-container { padding: 10px; }
            table, th, td { font-size: 0.95em; }
        }
    </style>
</head>
<body>
<div id="pageContent">
    <div class="checkout-container">
        <a href="index.php" class="back-home-btn"><span class="arrow">&#8592;</span> العودة للرئيسية</a>
        <div class="progress-steps">
            <div class="step">
                <div class="step-circle">1</div>
                <div>السلة</div>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-circle">2</div>
                <div>إتمام الشراء</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-circle">3</div>
                <div>تأكيد</div>
            </div>
        </div>
        <h2>إتمام الشراء</h2>
        <?php if ($success): ?>
            <div class="order-success">تم استلام طلبك بنجاح! سنقوم بالتواصل معك قريبًا.</div>
            <a href="index.php" class="checkout-btn">العودة للصفحة الرئيسية</a>
        <?php else: ?>
        <form method="post" class="modern-form">
            <div class="form-group">
                <label for="name">الاسم الكامل:</label>
                <input type="text" name="name" id="name" required autocomplete="name" placeholder=" " value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="address">العنوان:</label>
                <input type="text" name="address" id="address" required autocomplete="street-address" placeholder=" " value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="phone">رقم الهاتف:</label>
                <input type="tel" name="phone" id="phone" required autocomplete="tel" placeholder=" " pattern="^(2|5|9|4)[0-9]{7}$" inputmode="numeric" maxlength="8" title="رقم هاتف تونسي صحيح (8 أرقام يبدأ بـ 2 أو 5 أو 9 أو 4)" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" name="email" id="email" required autocomplete="email" placeholder=" " value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" title="يرجى إدخال بريد إلكتروني صحيح">
            </div>
            <div class="form-group">
                <label for="payment">طريقة الدفع:</label>
                <select id="payment" name="payment" required autocomplete="off">
                    <option value="">اختر طريقة الدفع</option>
                    <option value="card">بطاقة بنكية</option>
                    <option value="d17">D17</option>
                    <option value="cod">الدفع عند الاستلام</option>
                </select>
            </div>
            <div class="form-group">
                <label for="shipping">طريقة الشحن:</label>
                <select id="shipping" name="shipping" required autocomplete="off">
                    <option value="">اختر طريقة الشحن</option>
                    <option value="first">First Delivery</option>
                </select>
            </div>
            <button type="submit" class="checkout-btn">تأكيد الطلب</button>
        </form>
        <?php endif; ?>
    </div>
    <script>
    // Extra JS validation for phone and email
    document.addEventListener('DOMContentLoaded', function() {
      var form = document.querySelector('.modern-form');
      if (!form) return;
      form.addEventListener('submit', function(e) {
        var phone = form.phone.value.trim();
        var email = form.email.value.trim();
        var phonePattern = /^(2|5|9|4)[0-9]{7}$/;
        var emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
        if (!phonePattern.test(phone)) {
          alert('يرجى إدخال رقم هاتف تونسي صحيح (8 أرقام يبدأ بـ 2 أو 5 أو 9 أو 4)');
          form.phone.focus();
          e.preventDefault();
          return false;
        }
        if (!emailPattern.test(email)) {
          alert('يرجى إدخال بريد إلكتروني صحيح');
          form.email.focus();
          e.preventDefault();
          return false;
        }
      });
    });
    </script>
</div>
</body>
</html> 