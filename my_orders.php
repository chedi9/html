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
</head>
<body>
<div id="pageContent">
  <div class="container">
    <h2><?= __('my_orders') ?></h2>
    <table>
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
          <td>#<?= htmlspecialchars($order['id']) ?></td>
          <td><?= htmlspecialchars($order['created_at']) ?></td>
          <td><?= htmlspecialchars($order['total']) ?> د.ت</td>
          <td><?= htmlspecialchars($order['status']) ?></td>
          <td><a href="order_details.php?id=<?= $order['id'] ?>" class="view-details-btn"><?= __('view_details') ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
        <tr><td colspan="5">لا توجد طلبات بعد.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html> 