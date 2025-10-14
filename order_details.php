<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'lang.php';
if (!isset($_SESSION['user_id'])) {
  echo '<div>'.__('login_to_view_orders').'</div>';
  exit;
}
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
  echo '<div>'.__('order_not_found').'</div>';
  exit;
}
$stmt = $pdo->prepare('SELECT p.*, oi.quantity, oi.price, oi.variant_key FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$status_steps = ['pending' => 'قيد الانتظار', 'processing' => 'قيد المعالجة', 'shipped' => 'تم الشحن', 'delivered' => 'تم التسليم', 'cancelled' => 'ملغي'];
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= __('order_details') ?> #<?= htmlspecialchars($order['id']) ?></title>
  
</head>
<body>
  <div>
    <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
      </svg>
    </button>
  </div>
<div id="pageContent">
  <div class="container">
    <h2><?= __('order_details') ?> #<?= htmlspecialchars($order['id']) ?></h2>
    <div><b><?= __('date') ?>:</b> <?= htmlspecialchars($order['created_at']) ?></div>
    <div><b><?= __('status') ?>:</b> <?= $status_steps[$order['status']] ?? htmlspecialchars($order['status']) ?></div>
    <div><b><?= __('payment') ?>:</b> <?= htmlspecialchars($order['payment_method']) ?></div>
    <div><b><?= __('shipping') ?>:</b> <?= htmlspecialchars($order['shipping_method']) ?></div>
    <div><b><?= __('address') ?>:</b> <?= htmlspecialchars($order['shipping_address']) ?></div>
    <div class="progress-bar">
      <?php $statuses = array_keys($status_steps); $current = array_search($order['status'], $statuses); ?>
      <?php foreach ($statuses as $i => $step): ?>
        <div class="progress-step<?= $i < $current ? ' done' : ($i == $current ? ' active' : '') ?>"> <?= $status_steps[$step] ?> </div>
      <?php endforeach; ?>
    </div>
    <h3><?= __('order_items') ?></h3>
    <table>
      <thead>
        <tr>
          <th><?= __('product') ?></th>
          <th><?= __('quantity') ?></th>
          <th><?= __('price') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <?php $prod_name = $item['name_' . $lang] ?? $item['name']; ?>
          <tr>
            <td><?= htmlspecialchars($prod_name) ?>
            <?php if (!empty($item['variant_key'])): ?>
              <div>(<?= htmlspecialchars($item['variant_key']) ?>)</div>
            <?php endif; ?>
            </td>
            <td><?= $item['quantity'] ?></td>
            <td><?= $item['price'] ?> د.ت</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div> <?= __('total') ?>: <?= htmlspecialchars($order['total']) ?> د.ت</div>
    <div> <?= __('order_id') ?>: #<?= htmlspecialchars($order['id']) ?> </div>
  </div>
</div>
<script src="main.js?v=1.2"></script>
</body>
</html> 