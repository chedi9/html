<?php
// Security and compatibility headers
require_once '../security_integration.php';

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
// Fetch order history
$orders = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$orders->execute([$user_id]);
$orders = $orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
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
        // Remove active class from all navigation items
        document.querySelectorAll('.account-nav-item').forEach(btn => {
            btn.classList.remove('account-nav-item--active');
        });
        
        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
            content.classList.remove('tab-content--active');
        });
        
        // Show selected tab content
        const tabElement = document.getElementById(tab+'-content');
        if (tabElement) {
            tabElement.style.display = 'block';
            tabElement.classList.add('tab-content--active');
        }
        
        // Add active class to selected navigation item
        const navItem = document.querySelector(`[onclick="showTab('${tab}')"]`);
        if (navItem) {
            navItem.classList.add('account-nav-item--active');
        }
    }
    
    window.onload = function() { 
        // Show orders tab by default
        const ordersTab = document.getElementById('orders-content');
        if (ordersTab) {
            showTab('orders'); 
        } else {
            // If orders tab doesn't exist, show the first available tab
            const firstTab = document.querySelector('.tab-content');
            if (firstTab) {
                const tabId = firstTab.id.replace('-content', '');
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
        
        <!-- Sidebar Navigation -->
        <div class="account-sidebar">
            <div class="account-nav-groups">
                <!-- Primary Actions -->
                <div class="account-nav-group">
                    <div class="account-nav-title">الإجراءات الأساسية</div>
                    <button type="button" class="account-nav-item account-nav-item--active" onclick="showTab('orders')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c-1 0-2-.4-2-1V5c0-1.1-.9-2-2-2H7c-1.1 0-2 .9-2 2v6c0 .6-1 1-2 1"></path>
                            <path d="M21 12v7c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2v-7"></path>
                        </svg>
                        <?= __('order_history') ?>
                    </button>
                    <button type="button" class="account-nav-item" onclick="showTab('wishlist')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <?= __('wishlist') ?>
                    </button>
                    <button type="button" class="account-nav-item" onclick="showTab('viewed')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <?= __('viewed_products') ?>
                    </button>
                </div>

                <!-- Account Settings -->
                <div class="account-nav-group">
                    <div class="account-nav-title">إعدادات الحساب</div>
                    <button type="button" class="account-nav-item" onclick="showTab('credentials')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 1-7.743 5.743L11 17H9v2H7v2H4a1 1 0 0 1-1-1v-2.586a1 1 0 0 1 .293-.707l5.964-5.964A6 6 0 1 1 21 9z"></path>
                        </svg>
                        <?= __('change_email_password') ?>
                    </button>
                    <button type="button" class="account-nav-item" onclick="showTab('address')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?= __('address_and_phone') ?>
                    </button>
                    <button type="button" class="account-nav-item" onclick="showTab('saved-addresses')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                        </svg>
                        العناوين المحفوظة
                    </button>
                    <button type="button" class="account-nav-item" onclick="showTab('payment-methods')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        طرق الدفع المحفوظة
                    </button>
                </div>

                <!-- Account Activity -->
                <div class="account-nav-group">
                    <div class="account-nav-title">نشاط الحساب</div>
                    <button type="button" class="account-nav-item" onclick="showTab('returns')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12h18"></path>
                            <path d="M3 12l6-6"></path>
                            <path d="M3 12l6 6"></path>
                        </svg>
                        الإرجاعات والاسترداد
                    </button>
                    <button type="button" class="account-nav-item" onclick="showTab('notifications')">
                        <svg class="account-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        الإشعارات
                    </button>
                </div>
            </div>
        </div>

        <!-- Account Content Area -->
        <div class="account-content">
            <!-- Orders Content -->
            <div class="tab-content tab-content--active" id="orders-content">
                <div class="content-section">
            <h3><?= __('order_history') ?></h3>
            <?php if ($orders): ?>
                    <div class="table-container">
            <table class="orders-table">
                <thead>
                                <tr>
                                    <th><?= __('order_number') ?></th>
                                    <th>المنتجات</th>
                                    <th><?= __('total') ?></th>
                                    <th><?= __('status') ?></th>
                                    <th><?= __('order_date') ?></th>
                                    <th>الإجراءات</th>
                                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                                <?php
                                // Fetch order items with product images
                                $order_items_stmt = $pdo->prepare('
                                    SELECT oi.*, p.name as product_name, p.image as product_image 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?
                                ');
                                $order_items_stmt->execute([$order['id']]);
                                $order_items = $order_items_stmt->fetchAll();
                                ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <div class="order-products">
                                            <?php foreach ($order_items as $item): ?>
                                                <div class="order-product-item">
                                                    <div style="position: relative; overflow: hidden;">
                                                        <div class="skeleton skeleton--image" style="width: 36px; height: 36px; border-radius: 4px;"></div>
                                                        <img src="../uploads/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                             class="order-product-image" loading="lazy" style="position: relative; z-index: 2;">
                                                    </div>
                                                    <div class="order-product-info">
                                                        <div class="order-product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                        <div class="order-product-qty">الكمية: <?php echo $item['qty']; ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
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
                    </div>
            <?php else: ?>
                <p><?= __('no_orders_yet') ?></p>
            <?php endif; ?>
                </div>
        </div>
        
        <div class="tab-content" id="returns-content">
            <div class="content-section">
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
        </div>
        
        <div class="tab-content" id="notifications-content">
            <div class="content-section">
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
        </div>
        
        <div class="tab-content" id="wishlist-content">
            <div class="content-section">
            <h3><?= __('wishlist') ?></h3>
            <?php
            if (!empty($_SESSION['wishlist'])) {
                $ids = implode(',', array_map('intval', array_unique($_SESSION['wishlist'])));
                $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
                    echo '<div class="product-grid">';
                while ($prod = $stmt->fetch()) {
                        echo '<div class="product-card">';
                        echo '<a href="../product.php?id=' . $prod['id'] . '"><img src="../uploads/' . htmlspecialchars($prod['image']) . '" alt="' . htmlspecialchars($prod['name']) . '"></a>';
                        echo '<h4>' . htmlspecialchars($prod['name']) . '</h4>';
                        echo '<div class="price">' . $prod['price'] . ' ' . __('currency') . '</div>';
                        echo '<form method="post" action="remove_from_wishlist.php">';
                    echo '<input type="hidden" name="id" value="' . $prod['id'] . '">';
                        echo '<button type="submit" class="remove-btn">' . __('remove') . '</button>';
                    echo '</form>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>' . __('no_products_in_wishlist') . '</p>';
            }
            ?>
        </div>
        </div>
        
        <div class="tab-content" id="viewed-content">
            <div class="content-section">
            <h3><?= __('viewed_products') ?></h3>
            <?php
            if (!empty($_SESSION['viewed_products'])) {
                $ids = implode(',', array_map('intval', array_unique($_SESSION['viewed_products'])));
                $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
                    echo '<div class="product-grid">';
                while ($prod = $stmt->fetch()) {
                        echo '<div class="product-card">';
                        echo '<a href="../product.php?id=' . $prod['id'] . '"><img src="../uploads/' . htmlspecialchars($prod['image']) . '" alt="' . htmlspecialchars($prod['name']) . '"></a>';
                        echo '<h4>' . htmlspecialchars($prod['name']) . '</h4>';
                        echo '<div class="price">' . $prod['price'] . ' ' . __('currency') . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>' . __('no_products_viewed_yet') . '</p>';
            }
            ?>
        </div>
        </div>
        
        <div class="tab-content" id="address-content">
            <div class="content-section">
            <h3><?= __('address_and_phone') ?></h3>
            <form method="post" action="update_address.php" class="modern-form">
                <div class="form-group">
                        <label for="address"><?= __('address') ?>:</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="<?= __('address_placeholder') ?>" autocomplete="address">
                </div>
                <div class="form-group">
                        <label for="phone"><?= __('phone_number') ?>:</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="<?= __('phone_placeholder') ?>" autocomplete="tel">
                </div>
                <button type="submit" class="save-btn"><?= __('save_changes') ?></button>
            </form>
        </div>
        </div>
        
        <div class="tab-content" id="credentials-content">
            <div class="content-section">
            <h3><?= __('change_email_password') ?></h3>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="form-group" style="color: green; font-weight: bold;"> <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?> </div>
            <?php endif; ?>
            <form method="post" action="update_credentials.php" class="modern-form">
                <div class="form-group">
                        <label for="email"><?= __('email') ?>:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="<?= __('email_placeholder') ?>" autocomplete="email">
                </div>
                <button type="submit" class="save-btn"><?= __('update_email') ?></button>
            </form>
            <hr>
            <form method="post" action="change_password.php" class="modern-form">
                <div class="form-group">
                        <label for="current_password"><?= __('current_password') ?>:</label>
                    <input type="password" id="current_password" name="current_password" placeholder="<?= __('current_password_placeholder') ?>" autocomplete="current-password">
                </div>
                <div class="form-group">
                        <label for="new_password"><?= __('new_password') ?>:</label>
                    <input type="password" id="new_password" name="new_password" placeholder="<?= __('new_password_placeholder') ?>" autocomplete="new-password">
                </div>
                <div class="form-group">
                        <label for="confirm_password"><?= __('confirm_new_password') ?>:</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="<?= __('confirm_password_placeholder') ?>" autocomplete="new-password">
                </div>
                <button type="submit" class="save-btn"><?= __('change_password') ?></button>
            </form>
            </div>
        </div>
        
        <div class="tab-content" id="saved-addresses-content">
            <div class="content-section">
            <h3>العناوين المحفوظة</h3>
            <?php
            // Fetch user addresses
            $stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC LIMIT 3');
            $stmt->execute([$user_id]);
            $addresses = $stmt->fetchAll();
            ?>
            
            <?php if ($addresses): ?>
                    <div class="address-grid">
                    <?php foreach ($addresses as $address): ?>
                            <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                            <?php if ($address['is_default']): ?>
                                    <div class="default-badge">افتراضي</div>
                            <?php endif; ?>
                            
                                <div class="address-type">
                                <?php
                                switch ($address['type']) {
                                    case 'shipping': echo 'عنوان الشحن'; break;
                                    case 'billing': echo 'عنوان الفواتير'; break;
                                    case 'both': echo 'عنوان الشحن والفواتير'; break;
                                }
                                ?>
                            </div>
                            
                                <div class="address-name"><?php echo htmlspecialchars($address['full_name']); ?></div>
                                <div class="address-phone"><?php echo htmlspecialchars($address['phone']); ?></div>
                            
                                <div class="address-details">
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
                        <a href="manage_addresses.php" class="save-btn">إدارة جميع العناوين</a>
                </div>
            <?php else: ?>
                <p>لا توجد عناوين محفوظة</p>
                <div style="text-align: center; margin-top: 20px;">
                        <a href="manage_addresses.php" class="save-btn">إضافة عنوان جديد</a>
                </div>
            <?php endif; ?>
            </div>
        </div>
        
        <div class="tab-content" id="payment-methods-content">
            <div class="content-section">
            <h3>طرق الدفع المحفوظة</h3>
            <?php
            // Fetch user payment methods
            $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE user_id = ? AND is_active = 1 ORDER BY is_default DESC, created_at DESC LIMIT 3');
            $stmt->execute([$user_id]);
            $payment_methods = $stmt->fetchAll();
            ?>
            
            <?php if ($payment_methods): ?>
                    <div class="payment-grid">
                    <?php foreach ($payment_methods as $payment): ?>
                            <div class="payment-card <?php echo $payment['is_default'] ? 'default' : ''; ?>">
                            <?php if ($payment['is_default']): ?>
                                    <div class="default-badge">افتراضي</div>
                            <?php endif; ?>
                            
                                <div class="payment-type">
                                <?php
                                switch ($payment['type']) {
                                    case 'card': echo '💳 بطاقة بنكية'; break;
                                    case 'd17': echo '📱 D17'; break;
                                    case 'bank_transfer': echo '🏦 تحويل بنكي'; break;
                                }
                                ?>
                            </div>
                            
                                <div class="payment-name"><?php echo htmlspecialchars($payment['name']); ?></div>
                            
                            <?php if ($payment['card_number']): ?>
                                    <div class="card-number">
                                    **** **** **** <?php echo substr($payment['card_number'], -4); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($payment['card_type']): ?>
                                    <div class="card-details">النوع: <?php echo htmlspecialchars($payment['card_type']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($payment['expiry_month'] && $payment['expiry_year']): ?>
                                    <div class="card-details">تاريخ الانتهاء: <?php echo $payment['expiry_month']; ?>/<?php echo $payment['expiry_year']; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center;">
                        <a href="manage_payment_methods.php" class="save-btn">إدارة جميع طرق الدفع</a>
                </div>
            <?php else: ?>
                <p>لا توجد طرق دفع محفوظة</p>
                <div style="text-align: center; margin-top: 20px;">
                        <a href="manage_payment_methods.php" class="save-btn">إضافة طريقة دفع جديدة</a>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <script src="../main.js?v=1.3" defer></script>
    
    <!-- Cookie Consent Banner -->
    <?php include '../cookie_consent_banner.php'; ?>
</body>
</html> 