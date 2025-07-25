<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';
require '../lang.php';
$user_id = $_SESSION['user_id'];
$orders = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$orders->execute([$user_id]);
$orders = $orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= __('my_orders') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .orders-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .orders-container h2 { text-align: center; margin-bottom: 30px; }
        .order-block { margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .order-block:last-child { border-bottom: none; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .order-status { font-weight: bold; }
        .order-status.pending { color: #c90; }
        .order-status.shipped { color: #09c; }
        .order-status.delivered { color: #228B22; }
        .order-status.cancelled { color: #c00; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <div class="orders-container">
        <a href="../index.php" class="back-home-btn"><span class="arrow">&#8592;</span> <?= __('return_to_home') ?></a>
        <h2><?= __('my_orders') ?></h2>
        <?php if ($orders): foreach ($orders as $order): ?>
        <div class="order-block">
            <div class="order-header">
                <div><?= __('order_number') ?>: <?php echo $order['id']; ?></div>
                <div class="order-status <?php echo htmlspecialchars($order['status']); ?>">
                    <?php
                    switch ($order['status']) {
                        case 'pending': echo __('order_status_pending'); break;
                        case 'shipped': echo __('order_status_shipped'); break;
                        case 'delivered': echo __('order_status_delivered'); break;
                        case 'cancelled': echo __('order_status_cancelled'); break;
                        default: echo htmlspecialchars($order['status']);
                    }
                    ?>
                </div>
                <div><?php echo $order['created_at']; ?></div>
            </div>
            <div><?= __('total') ?>: <?php echo $order['total']; ?> د.ت</div>
            <table>
                <thead>
                    <tr>
                        <th><?= __('product') ?></th>
                        <th><?= __('quantity') ?></th>
                        <th><?= __('price') ?></th>
                        <th><?= __('subtotal') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $items->execute([$order['id']]);
                foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo $item['price']; ?> د.ت</td>
                        <td><?php echo $item['subtotal']; ?> د.ت</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; else: ?>
            <p style="text-align:center;"><?= __('no_orders_yet') ?></p>
        <?php endif; ?>
        <a href="../index.php" class="checkout-btn" style="background:var(--secondary-color);margin-top:30px;"><?= __('back_to_home') ?></a>
    </div>
</body>
</html> 