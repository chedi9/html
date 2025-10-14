<?php
// Security and compatibility headers
require_once 'security_integration.php';

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'payment_gateway_processor.php';
require 'pci_compliant_payment_handler.php';
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
    header('Location: login.php?message=login_required_cod');
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
    $shipping_id = $_POST['shipping'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Get shipping method details
    $shipping_method_name = 'Standard Delivery';
    $shipping_cost = 0;
    if ($shipping_id && is_numeric($shipping_id)) {
        $stmt = $pdo->prepare('SELECT name, price, free_shipping_threshold FROM shipping_methods WHERE id = ? AND is_active = 1');
        $stmt->execute([$shipping_id]);
        $shipping_method = $stmt->fetch();
        if ($shipping_method) {
            $shipping_method_name = $shipping_method['name'];
            $shipping_cost = $shipping_method['price'];
            
            // Check if free shipping applies
            if ($shipping_method['free_shipping_threshold'] > 0 && $total >= $shipping_method['free_shipping_threshold']) {
                $shipping_cost = 0;
            }
        }
    } else {
        // Handle legacy shipping method names
        $shipping_method_name = $shipping_id;
    }
    
    // Calculate final total with shipping
    $final_total = $total + $shipping_cost;
    
    // Prepare payment details based on payment method
    $payment_details = [];
    
    if ($payment === 'card') {
        $payment_details = [
            'card_number' => substr(trim($_POST['card_number'] ?? ''), -4), // Only store last 4 digits
            'card_holder' => trim($_POST['card_holder'] ?? ''),
            'card_type' => trim($_POST['card_type'] ?? ''),
            'expiry_month' => trim($_POST['expiry_month'] ?? ''),
            'expiry_year' => trim($_POST['expiry_year'] ?? ''),
            'cvv_provided' => !empty($_POST['cvv'] ?? '')
        ];
    } elseif ($payment === 'd17') {
        $payment_details = [
            'd17_phone' => trim($_POST['d17_phone'] ?? ''),
            'd17_email' => trim($_POST['d17_email'] ?? '')
        ];
    } elseif ($payment === 'flouci') {
        $payment_details = [
            'flouci_phone' => trim($_POST['flouci_phone'] ?? ''),
            'flouci_email' => trim($_POST['flouci_email'] ?? ''),
            'flouci_account_type' => trim($_POST['flouci_account_type'] ?? '')
        ];
    } elseif ($payment === 'bank_transfer') {
        $bank_name = trim($_POST['bank_name'] ?? '');
        if ($bank_name === 'other') {
            $bank_name = trim($_POST['other_bank_name'] ?? '');
        }
        $payment_details = [
            'bank_name' => $bank_name,
            'account_holder' => trim($_POST['account_holder'] ?? ''),
            'reference_number' => trim($_POST['reference_number'] ?? '')
        ];
    }
    
    // Store payment details as JSON
    $payment_details_json = json_encode($payment_details, JSON_UNESCAPED_UNICODE);
    
    // Process payment using PCI-compliant payment handler
    $pci_payment_handler = new PCICompliantPaymentHandler($pdo);
    
    try {
        // Process the payment with PCI compliance
        $payment_result = $pci_payment_handler->processPayment($payment, $payment_details, $final_total, null);
        
        // Create order with PCI compliance data
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, name, address, phone, email, payment_method, payment_details, payment_token, gateway_transaction_id, payment_gateway, pci_compliant, shipping_method, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $user_id, 
            $name, 
            $address, 
            $phone, 
            $email, 
            $payment, 
            $payment_result['stored_data'], // Encrypted payment data
            $payment_result['payment_token'] ?? null,
            $payment_result['transaction_id'] ?? null,
            $payment_result['gateway_response']['gateway'] ?? null,
            1, // PCI compliant
            $shipping_method_name, 
            $final_total, 
            $payment_result['status']
        ]);
        $order_id = $pdo->lastInsertId();
        
        // Store encrypted payment data separately
        $stmt = $pdo->prepare('INSERT INTO encrypted_payment_data (order_id, payment_method, encrypted_data, payment_token, transaction_id, gateway, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $order_id,
            $payment,
            $payment_result['stored_data'],
            $payment_result['payment_token'] ?? null,
            $payment_result['transaction_id'] ?? null,
            $payment_result['gateway_response']['gateway'] ?? null,
            $final_total,
            $payment_result['status']
        ]);
        
        // Update order with encrypted payment data ID
        $encrypted_data_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare('UPDATE orders SET encrypted_payment_data_id = ? WHERE id = ?');
        $stmt->execute([$encrypted_data_id, $order_id]);
        
    } catch (Exception $e) {
        // Payment failed
        $error_message = $e->getMessage();
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, name, address, phone, email, payment_method, payment_details, pci_compliant, shipping_method, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $name, $address, $phone, $email, $payment, json_encode(['error' => $error_message]), 0, $shipping_method_name, $final_total, 'failed']);
        $order_id = $pdo->lastInsertId();
        
        // Log payment error in audit logs
        $audit_logger = new PaymentAuditLogger($pdo);
        $audit_logger->logPaymentError($payment, $final_total, $error_message, $order_id);
    }
    foreach ($cart_items as $item) {
        $prod_name = $item['name_' . $lang] ?? $item['name'];
        // Add variant_key column to order_items if not present
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, price, qty, subtotal, variant_key) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$order_id, $item['id'], $prod_name, $item['price'], $item['qty'], $item['subtotal'], $item['variant']]);
    }
    // Clear cart
    $_SESSION['cart'] = [];
    
    // Send order confirmation email
    if ($email) {
        require_once 'client/mailer.php';
        
        // Prepare order data for email
        $order_data = [
            'order' => [
                'id' => $order_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'payment_method' => $payment,
                'shipping_method' => $shipping_method_name,
                'subtotal' => $total,
                'shipping_cost' => $shipping_cost,
                'total_amount' => $final_total,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ],
            'order_items' => array_map(function($item) {
                return [
                    'product_name' => $item['name'],
                    'product_image' => $item['image'] ?? '',
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'seller_name' => 'WeBuy'
                ];
            }, $cart_items),
            'payment_details' => $payment_details
        ];
        
        // Send order confirmation email
        send_order_confirmation_email($email, $name, $order_data);
    }
    
    // Redirect to order confirmation page
    header("Location: order_confirmation.php?order_id=" . $order_id);
    exit;
}
// Prefill user data if logged in
$user = null;
$saved_addresses = [];
$saved_payment_methods = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Fetch saved addresses
    $stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    $saved_addresses = $stmt->fetchAll();
    
    // Fetch saved payment methods
    $stmt = $pdo->prepare('SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    $saved_payment_methods = $stmt->fetchAll();
}

// Load available shipping methods from database
$shipping_methods = [];
$stmt = $pdo->prepare('SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
$stmt->execute();
$shipping_methods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الشراء</title>
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- WeBuy Custom Bootstrap Configuration -->
    <link rel="stylesheet" href="css/bootstrap-custom.css">
    
    <!-- Legacy CSS for gradual migration -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- CSS Files - Load in correct order -->
    <link rel="stylesheet" href="css/base/_variables.css">
    <link rel="stylesheet" href="css/base/_reset.css">
    <link rel="stylesheet" href="css/base/_typography.css">
    <link rel="stylesheet" href="css/base/_utilities.css">
    <link rel="stylesheet" href="css/components/_buttons.css">
    <link rel="stylesheet" href="css/components/_forms.css">
    <link rel="stylesheet" href="css/components/_cards.css">
    <link rel="stylesheet" href="css/components/_navigation.css">
    <link rel="stylesheet" href="css/layout/_grid.css">
    <link rel="stylesheet" href="css/layout/_sections.css">
    <link rel="stylesheet" href="css/layout/_footer.css">
    <link rel="stylesheet" href="css/themes/_light.css">
    <link rel="stylesheet" href="css/themes/_dark.css">
    <link rel="stylesheet" href="css/build.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.2" defer></script>
    
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
        
        /* Tunisia Address Autocomplete Styles */
        .suggestion-item:hover {
            background-color: #f5f5f5 !important;
        }
        
        #address-suggestions {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }
        
        #address-search:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 191, 174, 0.2);
        }
        
        @media (max-width: 700px) {
            .checkout-container { padding: 10px; }
            table, th, td { font-size: 0.95em; }
        }
    </style>
</head>
<body class="page-transition">
<?php include 'header.php'; ?>
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
                    <tr style="background: #f8f9fa;">
                        <td colspan="3" style="padding: 12px; text-align: left; font-weight: bold;">الإجمالي الفرعي:</td>
                        <td style="padding: 12px; text-align: center; font-weight: bold;"><?php echo $total; ?> د.ت</td>
                    </tr>
                    <tr style="background: #f0f8ff;" id="shipping-row" style="display: none;">
                        <td colspan="3" style="padding: 12px; text-align: left; font-weight: bold;">رسوم التوصيل:</td>
                        <td style="padding: 12px; text-align: center; font-weight: bold;" id="shipping-cost">0.00 د.ت</td>
                    </tr>
                    <tr style="background: #e8f5e8;">
                        <td colspan="3" style="padding: 15px; text-align: left; font-weight: bold; font-size: 1.1em;">المجموع الكلي:</td>
                        <td style="padding: 15px; text-align: center; font-weight: bold; font-size: 1.1em; color: #1A237E;" class="order-total"><?php echo $total; ?> د.ت</td>
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
                <strong>🛒 <?php echo __('guest_checkout'); ?></strong><br>
                <?php echo __('guest_checkout_notice'); ?> 
                <strong><?php echo __('note'); ?>:</strong> <?php echo __('guest_checkout_note'); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" style="margin-top: 20px;">
            <div style="margin-bottom: 15px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">الاسم الكامل:</label>
                <input type="text" name="name" id="name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="address" style="display: block; margin-bottom: 5px; font-weight: bold;">العنوان:</label>
                
                <?php if (!empty($saved_addresses)): ?>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 5px; color: #666; font-size: 0.9em;">العناوين المحفوظة:</label>
                        <select id="saved-address-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;" onchange="selectSavedAddress(this.value)">
                            <option value="">اختر عنوان محفوظ أو أدخل عنوان جديد</option>
                            <?php foreach ($saved_addresses as $address): ?>
                                <option value="<?php echo $address['id']; ?>">
                                    <?php echo htmlspecialchars($address['full_name']); ?> - 
                                    <?php echo htmlspecialchars($address['address_line1']); ?>, 
                                    <?php echo htmlspecialchars($address['city']); ?>
                                    <?php echo $address['is_default'] ? ' (افتراضي)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <!-- Tunisia Address Autocomplete -->
                <div style="position: relative;">
                    <input type="text" id="address-search" placeholder="🔍 ابحث عن مدينة أو محافظة في تونس..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; margin-bottom: 10px;">
                    <div id="address-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
                </div>
                
                <textarea id="address" name="address" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical; min-height: 80px;" placeholder="أدخل عنوان الشحن الكامل (الشارع، الحي، إلخ)"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                
                <div style="margin-top: 8px; font-size: 0.9em; color: #666;">
                    💡 <strong>نصيحة:</strong> ابحث عن مدينتك أولاً، ثم أضف تفاصيل العنوان
                </div>
                
                <?php if ($is_logged_in): ?>
                    <div style="margin-top: 10px;">
                        <a href="client/manage_addresses.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.9em;">إدارة العناوين المحفوظة</a>
                    </div>
                <?php endif; ?>
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
                
                <?php if (!empty($saved_payment_methods)): ?>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 5px; color: #666; font-size: 0.9em;">طرق الدفع المحفوظة:</label>
                        <select id="saved-payment-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;" onchange="selectSavedPayment(this.value)">
                            <option value="">اختر طريقة دفع محفوظة أو طريقة جديدة</option>
                            <?php foreach ($saved_payment_methods as $payment): ?>
                                <option value="<?php echo $payment['id']; ?>">
                                    <?php echo htmlspecialchars($payment['name']); ?> 
                                    (<?php 
                                    switch ($payment['type']) {
                                        case 'card': echo 'بطاقة بنكية'; break;
                                        case 'd17': echo 'D17'; break;
                                        case 'bank_transfer': echo 'تحويل بنكي'; break;
                                    }
                                    ?>)
                                    <?php echo $payment['is_default'] ? ' (افتراضي)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <select id="payment" name="payment" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" onchange="handlePaymentChange(this.value)">
                    <option value="">اختر طريقة الدفع</option>
                    <option value="card">💳 بطاقة بنكية (Online Payment)</option>
                    <option value="d17">📱 D17 (Online Payment)</option>
                    <option value="flouci">🟢 Flouci (Online Payment)</option>
                    <?php if ($is_logged_in): ?>
                        <option value="cod">💰 الدفع عند الاستلام (Cash on Delivery)</option>
                    <?php else: ?>
                        <option value="cod" disabled>💰 الدفع عند الاستلام (تسجيل الدخول مطلوب)</option>
                    <?php endif; ?>
                </select>
                <?php if (!$is_logged_in): ?>
                    <div id="cod-notice" style="display: none; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 8px; font-size: 0.9em;">
                        <strong>⚠️ Login Required:</strong> Cash on delivery requires account registration to prevent fraud and ensure payment security.
                        <br><a href="login.php" style="color: #721c24; text-decoration: underline;">Login or Register</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_logged_in): ?>
                    <div style="margin-top: 10px;">
                        <a href="client/manage_payment_methods.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.9em;">إدارة طرق الدفع المحفوظة</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Dynamic Payment Fields -->
            <div id="payment-fields" style="display: none; margin-bottom: 15px;">
                <!-- Credit Card Fields -->
                <div id="card-fields" style="display: none;">
                    <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; color: #495057;">💳 تفاصيل البطاقة البنكية</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="card_number" style="display: block; margin-bottom: 5px; font-weight: bold;">رقم البطاقة:</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" 
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; font-family: monospace;"
                                   maxlength="19" oninput="formatCardNumber(this)" required>
                            <div id="card-type-indicator" style="margin-top: 5px; font-size: 0.9em; color: #666;"></div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label for="card_holder" style="display: block; margin-bottom: 5px; font-weight: bold;">اسم حامل البطاقة:</label>
                                <input type="text" id="card_holder" name="card_holder" placeholder="اسم حامل البطاقة"
                                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                            </div>
                            <div>
                                <label for="card_type" style="display: block; margin-bottom: 5px; font-weight: bold;">نوع البطاقة:</label>
                                <select id="card_type" name="card_type" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                                    <option value="">اختر نوع البطاقة</option>
                                    <option value="visa">Visa</option>
                                    <option value="mastercard">Mastercard</option>
                                    <option value="amex">American Express</option>
                                    <option value="discover">Discover</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div>
                                <label for="expiry_month" style="display: block; margin-bottom: 5px; font-weight: bold;">شهر الانتهاء:</label>
                                <select id="expiry_month" name="expiry_month" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                                    <option value="">الشهر</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label for="expiry_year" style="display: block; margin-bottom: 5px; font-weight: bold;">سنة الانتهاء:</label>
                                <select id="expiry_year" name="expiry_year" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                                    <option value="">السنة</option>
                                    <?php for ($i = date('Y'); $i <= date('Y') + 15; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label for="cvv" style="display: block; margin-bottom: 5px; font-weight: bold;">CVV:</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123" 
                                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; font-family: monospace;"
                                       maxlength="4" oninput="formatCVV(this)" required>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 5px; font-size: 0.9em; color: #0066cc;">
                            <strong>🔒 أمان:</strong> جميع بيانات البطاقة مشفرة ومؤمنة. لن يتم حفظ رقم البطاقة الكامل.
                        </div>
                    </div>
                </div>
                
                <!-- D17 Fields -->
                <div id="d17-fields" style="display: none;">
                    <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; color: #495057;">📱 تفاصيل D17</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="d17_phone" style="display: block; margin-bottom: 5px; font-weight: bold;">رقم الهاتف المرتبط بـ D17:</label>
                            <input type="tel" id="d17_phone" name="d17_phone" placeholder="+216 XX XXX XXX"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="d17_email" style="display: block; margin-bottom: 5px; font-weight: bold;">البريد الإلكتروني المرتبط بـ D17:</label>
                            <input type="email" id="d17_email" name="d17_email" placeholder="example@email.com"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                        </div>
                        
                        <div style="padding: 10px; background: #fff3cd; border-radius: 5px; font-size: 0.9em; color: #856404;">
                            <strong>ℹ️ ملاحظة:</strong> سيتم إرسال رابط الدفع إلى رقم الهاتف والبريد الإلكتروني المحددين.
                        </div>
                    </div>
                </div>
                
                <!-- Flouci Fields -->
                <div id="flouci-fields" style="display: none;">
                    <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; color: #495057;">🟢 تفاصيل Flouci</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="flouci_phone" style="display: block; margin-bottom: 5px; font-weight: bold;">رقم الهاتف المرتبط بـ Flouci:</label>
                            <input type="tel" id="flouci_phone" name="flouci_phone" placeholder="+216 XX XXX XXX"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="flouci_email" style="display: block; margin-bottom: 5px; font-weight: bold;">البريد الإلكتروني المرتبط بـ Flouci:</label>
                            <input type="email" id="flouci_email" name="flouci_email" placeholder="example@email.com"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="flouci_account_type" style="display: block; margin-bottom: 5px; font-weight: bold;">نوع الحساب:</label>
                            <select id="flouci_account_type" name="flouci_account_type" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                                <option value="">اختر نوع الحساب</option>
                                <option value="personal">حساب شخصي</option>
                                <option value="business">حساب تجاري</option>
                            </select>
                        </div>
                        
                        <div style="padding: 10px; background: #d4edda; border-radius: 5px; font-size: 0.9em; color: #155724;">
                            <strong>🟢 Flouci:</strong> تطبيق الدفع الرقمي الأسرع نمواً في تونس. سيتم إرسال رابط الدفع إلى رقم الهاتف والبريد الإلكتروني المحددين.
                        </div>
                    </div>
                </div>
                
                <!-- Bank Transfer Fields -->
                <div id="bank-transfer-fields" style="display: none;">
                    <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 15px 0; color: #495057;">🏦 تفاصيل التحويل البنكي</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="bank_name" style="display: block; margin-bottom: 5px; font-weight: bold;">اسم البنك:</label>
                            <select id="bank_name" name="bank_name" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                                <option value="">اختر البنك</option>
                                <option value="biat">BIAT - البنك التونسي العربي الدولي</option>
                                <option value="stb">STB - البنك التونسي السعودي</option>
                                <option value="bte">BTE - البنك التونسي الإماراتي</option>
                                <option value="attijari">Attijari Bank - بنك العائلة</option>
                                <option value="amen">Amen Bank - بنك الأمان</option>
                                <option value="bh">BH - البنك المغاربي</option>
                                <option value="other">بنك آخر</option>
                            </select>
                        </div>
                        
                        <div id="other-bank-field" style="display: none; margin-bottom: 15px;">
                            <label for="other_bank_name" style="display: block; margin-bottom: 5px; font-weight: bold;">اسم البنك:</label>
                            <input type="text" id="other_bank_name" name="other_bank_name" placeholder="أدخل اسم البنك"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="account_holder" style="display: block; margin-bottom: 5px; font-weight: bold;">اسم صاحب الحساب:</label>
                            <input type="text" id="account_holder" name="account_holder" placeholder="اسم صاحب الحساب البنكي"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" required>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="reference_number" style="display: block; margin-bottom: 5px; font-weight: bold;">رقم المرجع (اختياري):</label>
                            <input type="text" id="reference_number" name="reference_number" placeholder="رقم المرجع للتحويل"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        
                        <div style="padding: 10px; background: #d1ecf1; border-radius: 5px; font-size: 0.9em; color: #0c5460;">
                            <strong>💡 تلميح:</strong> سيتم إرسال تفاصيل الحساب البنكي عبر البريد الإلكتروني بعد تأكيد الطلب.
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="shipping" style="display: block; margin-bottom: 5px; font-weight: bold;">طريقة التوصيل:</label>
                <select id="shipping" name="shipping" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;" onchange="updateShippingCost(this.value)">
                    <option value="">اختر طريقة التوصيل</option>
                    
                    <!-- First Delivery Options - Default and Always Available -->
                    <optgroup label="🚚 First Delivery (الخيار الافتراضي)">
                        <option value="first_delivery_standard" 
                                data-price="7.00" 
                                data-delivery-company="first_delivery"
                                data-delivery-type="standard"
                                data-free-threshold="105.00">
                            🚚 التوصيل القياسي - First Delivery
                            (7.00 دينار)
                        </option>
                        <option value="first_delivery_express" 
                                data-price="12.00" 
                                data-delivery-company="first_delivery"
                                data-delivery-type="express"
                                data-free-threshold="105.00">
                            ⚡ التوصيل السريع - First Delivery
                            (12.00 دينار)
                        </option>
                    </optgroup>
                    
                    <!-- Standard Shipping Methods -->
                    <optgroup label="📦 التوصيل التقليدي">
                        <?php if (empty($shipping_methods)): ?>
                            <option value="standard" data-price="7.00" data-free-threshold="105.00">🚚 التوصيل القياسي (7 دينار)</option>
                            <option value="express" data-price="12.00" data-free-threshold="105.00">⚡ التوصيل السريع (12 دينار)</option>
                            <option value="free" data-price="0.00" data-free-threshold="105.00">🆓 التوصيل المجاني (للطلبات فوق 105 دينار)</option>
                        <?php else: ?>
                            <?php foreach ($shipping_methods as $method): ?>
                                <option value="<?php echo htmlspecialchars($method['id']); ?>" 
                                        data-price="<?php echo htmlspecialchars($method['price']); ?>"
                                        data-free-threshold="<?php echo htmlspecialchars($method['free_shipping_threshold']); ?>"
                                        data-estimated-days="<?php echo htmlspecialchars($method['estimated_days']); ?>">
                                    <?php 
                                    // Add emoji based on method name
                                    $emoji = '🚚';
                                    if (strpos(strtolower($method['name']), 'express') !== false || strpos(strtolower($method['name']), 'سريع') !== false) {
                                        $emoji = '⚡';
                                    } elseif (strpos(strtolower($method['name']), 'free') !== false || strpos(strtolower($method['name']), 'مجاني') !== false) {
                                        $emoji = '🆓';
                                    } elseif (strpos(strtolower($method['name']), 'premium') !== false || strpos(strtolower($method['name']), 'مميز') !== false) {
                                        $emoji = '⭐';
                                    }
                                    echo $emoji . ' ' . htmlspecialchars($method['name']);
                                    ?>
                                    <?php if ($method['price'] > 0): ?>
                                        (<?php echo number_format($method['price'], 2); ?> دينار)
                                    <?php else: ?>
                                        (مجاني)
                                    <?php endif; ?>
                                    <?php if ($method['estimated_days']): ?>
                                        - <?php echo htmlspecialchars($method['estimated_days']); ?> أيام
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </optgroup>
                </select>
                <div id="shipping-info" style="margin-top: 8px; font-size: 0.9em; color: #666; display: none;"></div>
            </div>
            
            <button type="submit" style="width: 100%; padding: 16px; background: #00BFAE; color: white; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px;">تأكيد الطلب</button>
        </form>
        <?php endif; ?>
    </div>
    <script>
    // Saved addresses data
    const savedAddresses = <?php echo json_encode($saved_addresses); ?>;
    const savedPaymentMethods = <?php echo json_encode($saved_payment_methods); ?>;
    
    // Handle saved address selection
    function selectSavedAddress(addressId) {
        if (!addressId) return;
        
        const address = savedAddresses.find(addr => addr.id == addressId);
        if (address) {
            const addressTextarea = document.getElementById('address');
            const nameInput = document.getElementById('name');
            const phoneInput = document.getElementById('phone');
            
            // Format address
            let formattedAddress = address.address_line1;
            if (address.address_line2) {
                formattedAddress += '\n' + address.address_line2;
            }
            formattedAddress += '\n' + address.city;
            if (address.state) {
                formattedAddress += ', ' + address.state;
            }
            if (address.postal_code) {
                formattedAddress += ', ' + address.postal_code;
            }
            formattedAddress += '\n' + address.country;
            
            addressTextarea.value = formattedAddress;
            nameInput.value = address.full_name;
            phoneInput.value = address.phone;
        }
    }
    
    // Handle saved payment method selection
    function selectSavedPayment(paymentId) {
        if (!paymentId) return;
        
        const payment = savedPaymentMethods.find(pay => pay.id == paymentId);
        if (payment) {
            const paymentSelect = document.getElementById('payment');
            paymentSelect.value = payment.type;
            handlePaymentChange(payment.type);
        }
    }
    
    // Handle payment method changes
    function handlePaymentChange(paymentMethod) {
        const codNotice = document.getElementById('cod-notice');
        const paymentFields = document.getElementById('payment-fields');
        const cardFields = document.getElementById('card-fields');
        const d17Fields = document.getElementById('d17-fields');
        const flouciFields = document.getElementById('flouci-fields');
        const bankTransferFields = document.getElementById('bank-transfer-fields');

        // Hide all payment fields first
        paymentFields.style.display = 'none';
        cardFields.style.display = 'none';
        d17Fields.style.display = 'none';
        flouciFields.style.display = 'none';
        bankTransferFields.style.display = 'none';

        // Show relevant fields based on selected payment method
        if (paymentMethod === 'card') {
            paymentFields.style.display = 'block';
            cardFields.style.display = 'block';
        } else if (paymentMethod === 'd17') {
            paymentFields.style.display = 'block';
            d17Fields.style.display = 'block';
        } else if (paymentMethod === 'flouci') {
            paymentFields.style.display = 'block';
            flouciFields.style.display = 'block';
        } else if (paymentMethod === 'bank_transfer') {
            paymentFields.style.display = 'block';
            bankTransferFields.style.display = 'block';
        }

        // Show/hide COD notice
        if (paymentMethod === 'cod') {
            if (codNotice) {
                codNotice.style.display = 'block';
            }
        } else {
            if (codNotice) {
                codNotice.style.display = 'none';
            }
        }
    }
    
    // Handle shipping method changes and update costs
    function updateShippingCost(shippingMethodId) {
        const shippingSelect = document.getElementById('shipping');
        const shippingInfo = document.getElementById('shipping-info');
        const selectedOption = shippingSelect.options[shippingSelect.selectedIndex];
        
        if (!shippingMethodId) {
            shippingInfo.style.display = 'none';
            return;
        }
        
        const price = parseFloat(selectedOption.dataset.price) || 0;
        const freeThreshold = parseFloat(selectedOption.dataset.freeThreshold) || 105.00; // Default to 105 TND
        const estimatedDays = selectedOption.dataset.estimatedDays || '';
        
        // Calculate current cart total (excluding shipping)
        const cartTotal = <?php echo $total; ?>;
        
        let shippingCost = price;
        let message = '';
        
        if (freeThreshold > 0 && cartTotal >= freeThreshold) {
            shippingCost = 0;
            message = `🎉 التوصيل مجاني! (للطلبات فوق ${freeThreshold} دينار)`;
        } else if (price > 0) {
            message = `💰 رسوم التوصيل: ${price.toFixed(2)} دينار`;
        } else {
            message = `🆓 التوصيل مجاني`;
        }
        
        if (estimatedDays) {
            message += ` | ⏱️ وقت التوصيل المتوقع: ${estimatedDays} أيام`;
        }
        
        shippingInfo.innerHTML = message;
        shippingInfo.style.display = 'block';
        
        // Update shipping cost display in table
        const shippingRow = document.getElementById('shipping-row');
        const shippingCostElement = document.getElementById('shipping-cost');
        const totalElement = document.querySelector('.order-total');
        
        if (shippingCost > 0) {
            shippingRow.style.display = 'table-row';
            shippingCostElement.textContent = `${shippingCost.toFixed(2)} د.ت`;
        } else {
            shippingRow.style.display = 'none';
            shippingCostElement.textContent = '0.00 د.ت';
        }
        
        // Update total display
        if (totalElement) {
            const newTotal = cartTotal + shippingCost;
            totalElement.textContent = `${newTotal.toFixed(2)} د.ت`;
        }
    }
    
    // Tunisia Address Autocomplete
    let addressSearchTimeout;
    const addressSearchInput = document.getElementById('address-search');
    const addressSuggestions = document.getElementById('address-suggestions');
    const addressTextarea = document.getElementById('address');
    
    if (addressSearchInput) {
        addressSearchInput.addEventListener('input', function() {
            clearTimeout(addressSearchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                addressSuggestions.style.display = 'none';
                return;
            }
            
            addressSearchTimeout = setTimeout(() => {
                searchTunisiaAddresses(query);
            }, 300);
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!addressSearchInput.contains(e.target) && !addressSuggestions.contains(e.target)) {
                addressSuggestions.style.display = 'none';
            }
        });
    }
    
    function searchTunisiaAddresses(query) {
        fetch(`tunisia_addresses.php?action=search&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displayAddressSuggestions(data);
            })
            .catch(error => {
                console.error('Error searching addresses:', error);
            });
    }
    
    function displayAddressSuggestions(suggestions) {
        if (suggestions.length === 0) {
            addressSuggestions.style.display = 'none';
            return;
        }
        
        addressSuggestions.innerHTML = suggestions.map(item => `
            <div class="suggestion-item" onclick="selectAddressSuggestion('${item.name}', '${item.delegation || ''}', '${item.governorate || ''}', '${item.postal_code || ''}', '${item.type}')" 
                style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; hover:background-color: #f5f5f5;">
                <div style="font-weight: bold;">${item.display}</div>
                ${item.postal_code ? `<div style="font-size: 0.9em; color: #666;">الرمز البريدي: ${item.postal_code}</div>` : '<div style="font-size: 0.9em; color: #666;">📍 اختر هذا العنوان</div>'}
            </div>
        `).join('');
        
        addressSuggestions.style.display = 'block';
    }
    
    function selectAddressSuggestion(name, delegation, governorate, postalCode, type) {
        let addressText = '';
        
        if (type === 'city') {
            addressText = `${name}, ${delegation}, ${governorate}`;
        } else if (type === 'delegation') {
            addressText = `${name}, ${governorate}`;
        } else {
            addressText = `${name}`;
        }
        
        if (postalCode) {
            addressText += `\nالرمز البريدي: ${postalCode}`;
        }
        
        addressText += '\nتونس';
        
        // Update the address textarea
        addressTextarea.value = addressText;
        
        // Hide suggestions
        addressSuggestions.style.display = 'none';
        addressSearchInput.value = '';
        
        // Focus on address textarea for additional details
        addressTextarea.focus();
    }
    
    // Card number formatting
    function formatCardNumber(input) {
        let value = input.value.replace(/\s/g, '');
        let formattedValue = '';
        let cardType = '';

        if (value.length > 0) {
            if (value.length > 4) {
                formattedValue += value.substring(0, 4) + ' ';
                if (value.length > 8) {
                    formattedValue += value.substring(4, 8) + ' ';
                    if (value.length > 12) {
                        formattedValue += value.substring(8, 12) + ' ';
                        if (value.length > 16) {
                            formattedValue += value.substring(12, 16);
                        }
                    }
                }
            }
        }
        input.value = formattedValue.trim();

        // Detect card type
        if (input.value.length >= 4) {
            if (input.value.substring(0, 4).includes('4')) {
                cardType = 'Visa';
            } else if (input.value.substring(0, 4).includes('51') || input.value.substring(0, 4).includes('52') || input.value.substring(0, 4).includes('53') || input.value.substring(0, 4).includes('54') || input.value.substring(0, 4).includes('55')) {
                cardType = 'Mastercard';
            } else if (input.value.substring(0, 4).includes('34') || input.value.substring(0, 4).includes('37')) {
                cardType = 'American Express';
            } else if (input.value.substring(0, 4).includes('6011')) {
                cardType = 'Discover';
            }
        }
        document.getElementById('card-type-indicator').textContent = cardType ? `(${cardType})` : '';
    }

    // CVV formatting
    function formatCVV(input) {
        let value = input.value.replace(/\s/g, '');
        let formattedValue = '';
        if (value.length > 0) {
            if (value.length > 3) {
                formattedValue += value.substring(0, 3);
            }
        }
        input.value = formattedValue.trim();
    }

    // Handle bank name change for bank transfer
    document.getElementById('bank_name').addEventListener('change', function() {
        const otherBankField = document.getElementById('other-bank-field');
        if (this.value === 'other') {
            otherBankField.style.display = 'block';
        } else {
            otherBankField.style.display = 'none';
            otherBankField.querySelector('input').value = ''; // Clear other bank name
        }
    });
    
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

        // Card number validation (if card payment is selected)
        if (payment === 'card') {
            const cardNumber = form.card_number.value.trim();
            if (cardNumber.length < 16 || !/^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/.test(cardNumber)) {
                alert('رقم البطاقة البنكية غير صالح. يرجى إدخال رقم بطاقة مكون من 16 رقمًا.');
                form.card_number.focus();
                e.preventDefault();
                return false;
            }
            const cvv = form.cvv.value.trim();
            if (cvv.length < 3 || !/^\d{3}$/.test(cvv)) {
                alert('CVV غير صالح. يرجى إدخال 3 أرقام فقط.');
                form.cvv.focus();
                e.preventDefault();
                return false;
            }
        }

        // D17 phone validation
        if (payment === 'd17') {
            const d17Phone = form.d17_phone.value.trim();
            if (d17Phone.length < 10 || !/^(\+216|00216)?\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/.test(d17Phone)) {
                alert('رقم الهاتف المرتبط بـ D17 غير صالح. يرجى إدخال رقم هاتف مثل +216 XX XXX XXX أو 00216 XX XXX XXX أو XX XX XXX XXX.');
                form.d17_phone.focus();
                e.preventDefault();
                return false;
            }
            const d17Email = form.d17_email.value.trim();
            if (!d17Email.includes('@') || !d17Email.includes('.')) {
                alert('البريد الإلكتروني المرتبط بـ D17 غير صالح.');
                form.d17_email.focus();
                e.preventDefault();
                return false;
            }
        }

        // Flouci phone validation
        if (payment === 'flouci') {
            const flouciPhone = form.flouci_phone.value.trim();
            if (flouciPhone.length < 10 || !/^(\+216|00216)?\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/.test(flouciPhone)) {
                alert('رقم الهاتف المرتبط بـ Flouci غير صالح. يرجى إدخال رقم هاتف مثل +216 XX XXX XXX أو 00216 XX XXX XXX أو XX XX XXX XXX.');
                form.flouci_phone.focus();
                e.preventDefault();
                return false;
            }
            const flouciEmail = form.flouci_email.value.trim();
            if (!flouciEmail.includes('@') || !flouciEmail.includes('.')) {
                alert('البريد الإلكتروني المرتبط بـ Flouci غير صالح.');
                form.flouci_email.focus();
                e.preventDefault();
                return false;
            }
            const flouciAccountType = form.flouci_account_type.value.trim();
            if (flouciAccountType === '') {
                alert('يرجى اختيار نوع الحساب لـ Flouci.');
                form.flouci_account_type.focus();
                e.preventDefault();
                return false;
            }
        }

        // Bank transfer fields validation
        if (payment === 'bank_transfer') {
            const bankName = form.bank_name.value.trim();
            if (bankName === '') {
                alert('يرجى اختيار اسم البنك.');
                form.bank_name.focus();
                e.preventDefault();
                return false;
            }
            if (bankName === 'other') {
                const otherBankName = form.other_bank_name.value.trim();
                if (otherBankName === '') {
                    alert('يرجى إدخال اسم البنك الآخر.');
                    form.other_bank_name.focus();
                    e.preventDefault();
                    return false;
                }
            }
            const accountHolder = form.account_holder.value.trim();
            if (accountHolder === '') {
                alert('يرجى إدخال اسم صاحب الحساب.');
                form.account_holder.focus();
                e.preventDefault();
                return false;
            }
        }
      });
    });
    </script>
<?php include 'footer.php'; ?>
</body>
</html>