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
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .account-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .account-tabs { display: flex; gap: 10px; margin-bottom: 24px; }
        .account-tab { background: #eee; color: #333; border: none; border-radius: 6px 6px 0 0; padding: 10px 24px; cursor: pointer; font-size: 1em; }
        .account-tab.active { background: var(--primary-color); color: #fff; }
        .tab-content { background: #fafafa; border-radius: 0 0 10px 10px; padding: 20px; margin-top: -2px; }
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .orders-table th, .orders-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        .orders-table th { background: #f4f4f4; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password] { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
        .save-btn { background: var(--primary-color); color: #fff; border: none; border-radius: 6px; padding: 10px 24px; font-size: 1em; cursor: pointer; }
    </style>
    <script>
    function showTab(tab) {
        document.querySelectorAll('.account-tab').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.style.display = 'none');
        document.getElementById(tab+'-tab').classList.add('active');
        document.getElementById(tab+'-content').style.display = 'block';
    }
    window.onload = function() { showTab('orders'); };
    </script>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="account-container">
        <a href="../index.php" class="back-home-btn"><span class="arrow">&#8592;</span> <?= __('return_to_home') ?></a>
        <div class="account-tabs">
            <button type="button" class="account-tab" id="orders-tab" onclick="showTab('orders')"><?= __('order_history') ?></button>
            <button type="button" class="account-tab" id="wishlist-tab" onclick="showTab('wishlist')"><?= __('wishlist') ?></button>
            <button type="button" class="account-tab" id="viewed-tab" onclick="showTab('viewed')"><?= __('viewed_products') ?></button>
            <button type="button" class="account-tab" id="address-tab" onclick="showTab('address')"><?= __('address_and_phone') ?></button>
            <button type="button" class="account-tab" id="credentials-tab" onclick="showTab('credentials')"><?= __('change_email_password') ?></button>
        </div>
        <div class="tab-content" id="orders-content">
            <h3><?= __('order_history') ?></h3>
            <?php if ($orders): ?>
            <table class="orders-table">
                <thead>
                    <tr><th><?= __('order_number') ?></th><th><?= __('total') ?></th><th><?= __('status') ?></th><th><?= __('order_date') ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo $order['total']; ?> <?= __('currency') ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo $order['created_at']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p><?= __('no_orders_yet') ?></p>
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
    </div>
    <script src="../main.js"></script>
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