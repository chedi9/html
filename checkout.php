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

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$guest_checkout_allowed = false;

// Allow guest checkout only for online payments
if (isset($_GET['payment_method']) && in_array($_GET['payment_method'], ['card', 'd17'])) {
    $guest_checkout_allowed = true;
}

// Allow guest checkout if explicitly requested
if (isset($_GET['guest']) && $_GET['guest'] == '1') {
    $guest_checkout_allowed = true;
}

// Redirect to login if trying to use COD without being logged in
if (!$is_logged_in && !$guest_checkout_allowed) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: client/login.php?message=login_required_cod');
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
    if (!isset($products_map[$pid])) continue;
    $row = $products_map[$pid];
    $row['qty'] = $_SESSION['cart'][$cart_key];
    $row['subtotal'] = $row['qty'] * $row['price'];
    $row['variant'] = $variant;
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
        // Add variant_key column to order_items if not present
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, price, qty, subtotal, variant_key) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$order_id, $item['id'], $prod_name, $item['price'], $item['qty'], $item['subtotal'], $item['variant']]);
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
        
        <!-- Order Summary -->
        <div class="order-summary" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #1A237E;">ملخص الطلب</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #e3f2fd;">
                        <th style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">المنتج</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">الكمية</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">السعر</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if ($item['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="صورة المنتج" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: bold;"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <?php if (!empty($item['variant'])): ?>
                                        <div style="font-size: 0.9em; color: #1A237E;">(<?php echo htmlspecialchars($item['variant']); ?>)</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><?php echo $item['qty']; ?></td>
                        <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><?php echo $item['price']; ?> د.ت</td>
                        <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee; font-weight: bold;"><?php echo $item['subtotal']; ?> د.ت</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #e8f5e8;">
                        <td colspan="3" style="padding: 15px; text-align: left; font-weight: bold; font-size: 1.1em;">الإجمالي:</td>
                        <td style="padding: 15px; text-align: center; font-weight: bold; font-size: 1.1em; color: #1A237E;"><?php echo $total; ?> د.ت</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if ($success): ?>
            <div class="order-success">تم استلام طلبك بنجاح! سنقوم بالتواصل معك قريبًا.</div>
            <a href="index.php" class="checkout-btn">العودة للصفحة الرئيسية</a>
        <?php else: ?>
        
        <!-- Guest Checkout Notice -->
        <?php if (!$is_logged_in): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <strong>🛒 Guest Checkout</strong><br>
                You can complete your purchase without creating an account. 
                <strong>Note:</strong> Cash on delivery requires account registration for security.
            </div>
        <?php endif; ?>
        
        <form method="post" style="margin-top: 20px;">
            <div style="margin-bottom: 15px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">الاسم الكامل:</label>
                <input type="text" name="name" id="name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="address" style="display: block; margin-bottom: 5px; font-weight: bold;">العنوان:</label>
                <input type="text" name="address" id="address" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="phone" style="display: block; margin-bottom: 5px; font-weight: bold;">رقم الهاتف:</label>
                <input type="tel" name="phone" id="phone" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" maxlength="8" value="">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="email" style="display: block; margin-bottom: 5px; font-weight: bold;">البريد الإلكتروني:</label>
                <input type="email" name="email" id="email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="payment" style="display: block; margin-bottom: 5px; font-weight: bold;">طريقة الدفع:</label>
                <select id="payment" name="payment" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" onchange="handlePaymentChange(this.value)">
                    <option value="">اختر طريقة الدفع</option>
                    <option value="card">💳 بطاقة بنكية (Online Payment)</option>
                    <option value="d17">📱 D17 (Online Payment)</option>
                    <?php if ($is_logged_in): ?>
                        <option value="cod">💰 الدفع عند الاستلام (Cash on Delivery)</option>
                    <?php else: ?>
                        <option value="cod" disabled>💰 الدفع عند الاستلام (تسجيل الدخول مطلوب)</option>
                    <?php endif; ?>
                </select>
                <?php if (!$is_logged_in): ?>
                    <div id="cod-notice" style="display: none; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 8px; font-size: 0.9em;">
                        <strong>⚠️ Login Required:</strong> Cash on delivery requires account registration to prevent fraud and ensure payment security.
                        <br><a href="client/login.php" style="color: #721c24; text-decoration: underline;">Login or Register</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="shipping" style="display: block; margin-bottom: 5px; font-weight: bold;">طريقة الشحن:</label>
                <select id="shipping" name="shipping" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                    <option value="">اختر طريقة الشحن</option>
                    <option value="first">First Delivery</option>
                </select>
            </div>
            
            <button type="submit" style="width: 100%; padding: 16px; background: #00BFAE; color: white; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px;">تأكيد الطلب</button>
        </form>
        <?php endif; ?>
    </div>
    <script>
    // Handle payment method changes
    function handlePaymentChange(paymentMethod) {
        const codNotice = document.getElementById('cod-notice');
        const paymentSelect = document.getElementById('payment');
        
        if (paymentMethod === 'cod') {
            if (codNotice) {
                codNotice.style.display = 'block';
                paymentSelect.value = ''; // Reset selection
            }
        } else {
            if (codNotice) {
                codNotice.style.display = 'none';
            }
        }
    }
    
    // Simple form validation
    document.addEventListener('DOMContentLoaded', function() {
      var form = document.querySelector('form');
      if (!form) return;
      
      form.addEventListener('submit', function(e) {
        var phone = form.phone.value.trim();
        var email = form.email.value.trim();
        var payment = form.payment.value;
        
        // Check if COD is selected for guest users
        if (payment === 'cod' && !<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
          alert('Cash on delivery requires account registration. Please login or choose an online payment method.');
          form.payment.focus();
          e.preventDefault();
          return false;
        }
        
        // Simple phone validation
        if (phone.length !== 8 || !/^[0-9]+$/.test(phone)) {
          alert('يرجى إدخال رقم هاتف صحيح (8 أرقام)');
          form.phone.focus();
          e.preventDefault();
          return false;
        }
        
        // Simple email validation
        if (!email.includes('@') || !email.includes('.')) {
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