<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';
require '../lang.php';
$user_id = $_SESSION['user_id'];
// Fetch user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
// Fetch seller info if user is a seller
$seller = null;
if (!empty($user['is_seller'])) {
    $stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $seller = $stmt->fetch();
}
// Fetch order history
$orders = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$orders->execute([$user_id]);
$orders = $orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= __('my_account') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/base/_variables.css">
    <link rel="stylesheet" href="../css/base/_reset.css">
    <link rel="stylesheet" href="../css/base/_typography.css">
    <link rel="stylesheet" href="../css/base/_utilities.css">
    <link rel="stylesheet" href="../css/components/_buttons.css">
    <link rel="stylesheet" href="../css/components/_forms.css">
    <link rel="stylesheet" href="../css/components/_cards.css">
    <link rel="stylesheet" href="../css/components/_navigation.css">
    <link rel="stylesheet" href="../css/layout/_grid.css">
    <link rel="stylesheet" href="../css/layout/_sections.css">
    <link rel="stylesheet" href="../css/layout/_footer.css">
    <link rel="stylesheet" href="../css/themes/_light.css">
    <link rel="stylesheet" href="../css/themes/_dark.css">
    <link rel="stylesheet" href="../css/build.css">
    <link rel="stylesheet" href="../css/pages/_account.css">
    <script>
    function showTab(tab) {
        // Remove active class from all tabs
        document.querySelectorAll('.account-tab').forEach(btn => btn.classList.remove('active'));
        
        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(tc => {
            if (tc) tc.style.display = 'none';
        });
        
        // Add active class to selected tab
        const tabElement = document.getElementById(tab+'-tab');
        if (tabElement) {
            tabElement.classList.add('active');
        }
        
        // Show selected tab content
        const contentElement = document.getElementById(tab+'-content');
        if (contentElement) {
            contentElement.style.display = 'block';
        } else {
            console.warn('Tab content element not found:', tab+'-content');
        }
    }
    
    window.onload = function() { 
        // Check if orders tab exists before trying to show it
        const ordersTab = document.getElementById('orders-tab');
        if (ordersTab) {
            showTab('orders'); 
        } else {
            // If orders tab doesn't exist, show the first available tab
            const firstTab = document.querySelector('.account-tab');
            if (firstTab) {
                const tabId = firstTab.id.replace('-tab', '');
                showTab(tabId);
            }
        }
    };
    </script>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="account-container">
        <a href="../index.php" class="back-home-btn"><span class="arrow">&#8592;</span> <?= __('return_to_home') ?></a>
        <div class="account-tabs">
            <button type="button" class="account-tab" id="orders-tab" onclick="showTab('orders')"><?= __('order_history') ?></button>
            <button type="button" class="account-tab" id="returns-tab" onclick="showTab('returns')">الإرجاعات والاسترداد</button>
            <button type="button" class="account-tab" id="notifications-tab" onclick="showTab('notifications')">الإشعارات</button>
            <button type="button" class="account-tab" id="saved-addresses-tab" onclick="showTab('saved-addresses')">العناوين المحفوظة</button>
            <button type="button" class="account-tab" id="payment-methods-tab" onclick="showTab('payment-methods')">طرق الدفع المحفوظة</button>
            <button type="button" class="account-tab" id="wishlist-tab" onclick="showTab('wishlist')"><?= __('wishlist') ?></button>
            <button type="button" class="account-tab" id="viewed-tab" onclick="showTab('viewed')"><?= __('viewed_products') ?></button>
            <button type="button" class="account-tab" id="address-tab" onclick="showTab('address')"><?= __('address_and_phone') ?></button>
            <button type="button" class="account-tab" id="credentials-tab" onclick="showTab('credentials')"><?= __('change_email_password') ?></button>
            <?php if ($seller): ?>
            <button type="button" class="account-tab" id="seller-tab" onclick="showTab('seller')">My Seller Dashboard</button>
            <?php endif; ?>
        </div>
        <div class="tab-content" id="orders-content">
            <h3><?= __('order_history') ?></h3>
            <?php if ($orders): ?>
            <table class="orders-table">
                <thead>
                    <tr><th><?= __('order_number') ?></th><th><?= __('total') ?></th><th><?= __('status') ?></th><th><?= __('order_date') ?></th><th>الإجراءات</th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo $order['total']; ?> <?= __('currency') ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo $order['created_at']; ?></td>
                        <td>
                            <?php if ($order['status'] === 'delivered' && ($order['return_status'] ?? 'none') !== 'return_requested'): ?>
                                <a href="request_return.php?order_id=<?php echo $order['id']; ?>" class="return-btn">طلب إرجاع</a>
                            <?php elseif (($order['return_status'] ?? 'none') === 'return_requested'): ?>
                                <span class="return-status">طلب إرجاع قيد المراجعة</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p><?= __('no_orders_yet') ?></p>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="returns-content" style="display:none;">
            <h3>الإرجاعات والاسترداد</h3>
            <?php
            // Fetch user returns
            $returns = $pdo->prepare('
                SELECT r.*, o.total as order_total 
                FROM returns r 
                JOIN orders o ON r.order_id = o.id 
                WHERE r.user_id = ? 
                ORDER BY r.created_at DESC
            ');
            $returns->execute([$user_id]);
            $returns = $returns->fetchAll();
            ?>
            
            <?php if ($returns): ?>
                <div class="returns-list">
                    <?php foreach ($returns as $return): ?>
                        <div class="return-item">
                            <div class="return-header">
                                <h4>طلب إرجاع #<?php echo $return['return_number']; ?></h4>
                                <span class="return-status <?php echo $return['status']; ?>">
                                    <?php
                                    switch ($return['status']) {
                                        case 'pending': echo 'قيد المراجعة'; break;
                                        case 'approved': echo 'تمت الموافقة'; break;
                                        case 'rejected': echo 'مرفوض'; break;
                                        case 'completed': echo 'مكتمل'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="return-details">
                                <p><strong>الطلب:</strong> #<?php echo $return['order_id']; ?></p>
                                <p><strong>السبب:</strong> <?php echo htmlspecialchars($return['reason']); ?></p>
                                <p><strong>التاريخ:</strong> <?php echo date('j M Y', strtotime($return['created_at'])); ?></p>
                                <?php if ($return['description']): ?>
                                    <p><strong>التفاصيل:</strong> <?php echo htmlspecialchars($return['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>لا توجد طلبات إرجاع حتى الآن.</p>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="notifications-content" style="display:none;">
            <h3>الإشعارات</h3>
            <?php
            // Fetch user notifications
            $notifications = $pdo->prepare('
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ');
            $notifications->execute([$user_id]);
            $notifications = $notifications->fetchAll();
            
            // Count unread notifications
            $unread_count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
            $unread_count->execute([$user_id]);
            $unread_count = $unread_count->fetchColumn();
            ?>
            
            <?php if ($unread_count > 0): ?>
                <div class="notifications-header">
                    <span class="unread-badge"><?php echo $unread_count; ?> غير مقروء</span>
                    <a href="user_notifications.php" class="view-all-btn">عرض جميع الإشعارات</a>
                </div>
            <?php endif; ?>
            
            <?php if ($notifications): ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="notification-time">
                                    <?php echo date('j M Y g:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="view-all-section">
                    <a href="user_notifications.php" class="view-all-btn">عرض جميع الإشعارات</a>
                </div>
            <?php else: ?>
                <p>لا توجد إشعارات حتى الآن.</p>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="wishlist-content" style="display:none;">
            <h3><?= __('wishlist') ?></h3>
            <?php
            if (!empty($_SESSION['wishlist'])) {
                $ids = implode(',', array_map('intval', array_unique($_SESSION['wishlist'])));
                $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
                echo '<div style="display: flex; flex-wrap: wrap; gap: 18px;">';
                while ($prod = $stmt->fetch()) {
                    echo '<div style="background:#f4f6fb; border-radius:8px; box-shadow:0 1px 4px #0001; padding:12px; width:180px; text-align:center; position:relative;">';
                    echo '<a href="../product.php?id=' . $prod['id'] . '"><img src="../uploads/' . htmlspecialchars($prod['image']) . '" alt="' . htmlspecialchars($prod['name']) . '" style="max-width:100%;height:120px;object-fit:cover;border-radius:6px;"></a>';
                    echo '<div style="margin:8px 0 4px;font-weight:bold;">' . htmlspecialchars($prod['name']) . '</div>';
                    echo '<div style="color:#00BFAE;font-weight:bold;">' . $prod['price'] . ' ' . __('currency') . '</div>';
                    echo '<form method="post" action="remove_from_wishlist.php" style="margin-top:8px;">';
                    echo '<input type="hidden" name="id" value="' . $prod['id'] . '">';
                    echo '<button type="submit" style="background:#c00;color:#fff;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;">' . __('remove') . '</button>';
                    echo '</form>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>' . __('no_products_in_wishlist') . '</p>';
            }
            ?>
        </div>
        <div class="tab-content" id="viewed-content" style="display:none;">
            <h3><?= __('viewed_products') ?></h3>
            <?php
            if (!empty($_SESSION['viewed_products'])) {
                $ids = implode(',', array_map('intval', array_unique($_SESSION['viewed_products'])));
                $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
                echo '<div style="display: flex; flex-wrap: wrap; gap: 18px;">';
                while ($prod = $stmt->fetch()) {
                    echo '<div style="background:#f4f6fb; border-radius:8px; box-shadow:0 1px 4px #0001; padding:12px; width:180px; text-align:center;">';
                    echo '<a href="../product.php?id=' . $prod['id'] . '"><img src="../uploads/' . htmlspecialchars($prod['image']) . '" alt="' . htmlspecialchars($prod['name']) . '" style="max-width:100%;height:120px;object-fit:cover;border-radius:6px;"></a>';
                    echo '<div style="margin:8px 0 4px;font-weight:bold;">' . htmlspecialchars($prod['name']) . '</div>';
                    echo '<div style="color:#00BFAE;font-weight:bold;">' . $prod['price'] . ' ' . __('currency') . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>' . __('no_products_viewed_yet') . '</p>';
            }
            ?>
        </div>
        <div class="tab-content" id="address-content" style="display:none;">
            <h3><?= __('address_and_phone') ?></h3>
            <form method="post" action="update_address.php" class="modern-form">
                <div class="form-group">
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="<?= __('address_placeholder') ?>" autocomplete="address">
                    <label for="address"><?= __('address') ?>:</label>
                </div>
                <div class="form-group">
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="<?= __('phone_placeholder') ?>" autocomplete="tel">
                    <label for="phone"><?= __('phone_number') ?>:</label>
                </div>
                <button type="submit" class="save-btn"><?= __('save_changes') ?></button>
            </form>
        </div>
        <div class="tab-content" id="credentials-content" style="display:none;">
            <h3><?= __('change_email_password') ?></h3>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="form-group" style="color: green; font-weight: bold;"> <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?> </div>
            <?php endif; ?>
            <form method="post" action="update_credentials.php" class="modern-form">
                <div class="form-group">
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="<?= __('email_placeholder') ?>" autocomplete="email">
                    <label for="email"><?= __('email') ?>:</label>
                </div>
                <button type="submit" class="save-btn"><?= __('update_email') ?></button>
            </form>
            <hr>
            <form method="post" action="change_password.php" class="modern-form">
                <div class="form-group">
                    <input type="password" id="current_password" name="current_password" placeholder="<?= __('current_password_placeholder') ?>" autocomplete="current-password">
                    <label for="current_password"><?= __('current_password') ?>:</label>
                </div>
                <div class="form-group">
                    <input type="password" id="new_password" name="new_password" placeholder="<?= __('new_password_placeholder') ?>" autocomplete="new-password">
                    <label for="new_password"><?= __('new_password') ?>:</label>
                </div>
                <div class="form-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="<?= __('confirm_password_placeholder') ?>" autocomplete="new-password">
                    <label for="confirm_password"><?= __('confirm_new_password') ?>:</label>
                </div>
                <button type="submit" class="save-btn"><?= __('change_password') ?></button>
            </form>
        </div>
        
        <div class="tab-content" id="saved-addresses-content" style="display:none;">
            <h3>العناوين المحفوظة</h3>
            <?php
            // Fetch user addresses
            $stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC LIMIT 3');
            $stmt->execute([$user_id]);
            $addresses = $stmt->fetchAll();
            ?>
            
            <?php if ($addresses): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <?php foreach ($addresses as $address): ?>
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; background: <?php echo $address['is_default'] ? '#f0f8ff' : '#f9f9f9'; ?>;">
                            <?php if ($address['is_default']): ?>
                                <div style="background: var(--primary-color); color: #fff; padding: 4px 8px; border-radius: 12px; font-size: 0.8em; display: inline-block; margin-bottom: 8px;">افتراضي</div>
                            <?php endif; ?>
                            
                            <div style="color: #666; font-size: 0.9em; margin-bottom: 8px;">
                                <?php
                                switch ($address['type']) {
                                    case 'shipping': echo 'عنوان الشحن'; break;
                                    case 'billing': echo 'عنوان الفواتير'; break;
                                    case 'both': echo 'عنوان الشحن والفواتير'; break;
                                }
                                ?>
                            </div>
                            
                            <div style="font-weight: bold; margin-bottom: 6px;"><?php echo htmlspecialchars($address['full_name']); ?></div>
                            <div style="color: #666; margin-bottom: 8px;"><?php echo htmlspecialchars($address['phone']); ?></div>
                            
                            <div style="line-height: 1.4; margin-bottom: 12px;">
                                <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                <?php if ($address['address_line2']): ?>
                                    <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($address['city']); ?>
                                <?php if ($address['state']): ?>
                                    , <?php echo htmlspecialchars($address['state']); ?>
                                <?php endif; ?>
                                <?php if ($address['postal_code']): ?>
                                    , <?php echo htmlspecialchars($address['postal_code']); ?>
                                <?php endif; ?><br>
                                <?php echo htmlspecialchars($address['country']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center;">
                    <a href="manage_addresses.php" class="save-btn" style="background: var(--secondary-color); text-decoration: none; display: inline-block;">إدارة جميع العناوين</a>
                </div>
            <?php else: ?>
                <p>لا توجد عناوين محفوظة</p>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="manage_addresses.php" class="save-btn" style="background: var(--primary-color); text-decoration: none; display: inline-block;">إضافة عنوان جديد</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="payment-methods-content" style="display:none;">
            <h3>طرق الدفع المحفوظة</h3>
            <?php
            // Fetch user payment methods
            $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE user_id = ? AND is_active = 1 ORDER BY is_default DESC, created_at DESC LIMIT 3');
            $stmt->execute([$user_id]);
            $payment_methods = $stmt->fetchAll();
            ?>
            
            <?php if ($payment_methods): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <?php foreach ($payment_methods as $payment): ?>
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; background: <?php echo $payment['is_default'] ? '#f0f8ff' : '#f9f9f9'; ?>;">
                            <?php if ($payment['is_default']): ?>
                                <div style="background: var(--primary-color); color: #fff; padding: 4px 8px; border-radius: 12px; font-size: 0.8em; display: inline-block; margin-bottom: 8px;">افتراضي</div>
                            <?php endif; ?>
                            
                            <div style="color: #666; font-size: 0.9em; margin-bottom: 8px;">
                                <?php
                                switch ($payment['type']) {
                                    case 'card': echo '💳 بطاقة بنكية'; break;
                                    case 'd17': echo '📱 D17'; break;
                                    case 'bank_transfer': echo '🏦 تحويل بنكي'; break;
                                }
                                ?>
                            </div>
                            
                            <div style="font-weight: bold; margin-bottom: 8px;"><?php echo htmlspecialchars($payment['name']); ?></div>
                            
                            <?php if ($payment['card_number']): ?>
                                <div style="font-family: monospace; margin-bottom: 6px;">
                                    **** **** **** <?php echo substr($payment['card_number'], -4); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($payment['card_type']): ?>
                                <div style="color: #666; margin-bottom: 6px;">النوع: <?php echo htmlspecialchars($payment['card_type']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($payment['expiry_month'] && $payment['expiry_year']): ?>
                                <div style="color: #666;">تاريخ الانتهاء: <?php echo $payment['expiry_month']; ?>/<?php echo $payment['expiry_year']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center;">
                    <a href="manage_payment_methods.php" class="save-btn" style="background: var(--secondary-color); text-decoration: none; display: inline-block;">إدارة جميع طرق الدفع</a>
                </div>
            <?php else: ?>
                <p>لا توجد طرق دفع محفوظة</p>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="manage_payment_methods.php" class="save-btn" style="background: var(--primary-color); text-decoration: none; display: inline-block;">إضافة طريقة دفع جديدة</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($seller): ?>
        <div class="tab-content" id="seller-content" style="display:none;">
            <h3>Seller Dashboard</h3>
            <p>Welcome, <b><?php echo htmlspecialchars($seller['store_name']); ?></b>!</p>
            <a href="seller_dashboard.php" class="save-btn">Go to Seller Dashboard</a>
            <a href="../store.php?seller_id=<?php echo $seller['id']; ?>" class="save-btn" style="background:#888;">View My Store</a>
        </div>
        <?php endif; ?>
    </div>
    <script src="../main.js?v=1.3" defer></script>
    <script>
if (!localStorage.getItem('cookiesAccepted')) {
  // do nothing, wait for accept
} else {
  var gaScript = document.createElement('script');
  gaScript.src = '../https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
  gaScript.async = true;
  document.head.appendChild(gaScript);
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-PVP8CCFQPL');
}
</script>
<script>
var acceptBtn = document.getElementById('acceptCookiesBtn');
if (acceptBtn) {
  acceptBtn.addEventListener('click', function() {
    var gaScript = document.createElement('script');
    gaScript.src = '../https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
    gaScript.async = true;
    document.head.appendChild(gaScript);
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-PVP8CCFQPL');
  });
}
</script>
</body>
</html> 