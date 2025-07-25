<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'lang.php';
if (!isset($_SESSION['user_id'])) {
  echo '<div style="max-width:600px;margin:40px auto;text-align:center;font-size:1.2em;">'.__('login_to_view_orders').'</div>';
  exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= __('my_orders') ?></title>
  <link rel="stylesheet" href="beta333.css">
  <?php if (!empty($_SESSION['is_mobile'])): ?>
  <link rel="stylesheet" href="mobile.css">
  <?php endif; ?>
</head>
<body>
<div id="pageContent">
  <div class="container">
    <h2><?= __('my_orders') ?></h2>
    <table style="width:100%;margin-top:24px;">
      <thead>
        <tr>
          <th><?= __('order_id') ?></th>
          <th><?= __('date') ?></th>
          <th><?= __('total') ?></th>
          <th><?= __('status') ?></th>
          <th><?= __('actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td style="font-weight:bold;">#<?= htmlspecialchars($order['id']) ?></td>
          <td><?= htmlspecialchars($order['created_at']) ?></td>
          <td><?= htmlspecialchars($order['total']) ?> د.ت</td>
          <td><?= htmlspecialchars($order['status']) ?></td>
          <td><a href="order_details.php?id=<?= $order['id'] ?>" class="view-details-btn"><?= __('view_details') ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
        <tr><td colspan="5" style="text-align:center;color:#888;">لا توجد طلبات بعد.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html> 