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
            <div><?= __('total') ?>: <?php echo $order['total']; ?> <?= __('currency') ?></div>
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
                                            <td><?php echo $item['price']; ?> <?= __('currency') ?></td>
                    <td><?php echo $item['subtotal']; ?> <?= __('currency') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; else: ?>
            <p><?= __('no_orders_yet') ?></p>
        <?php endif; ?>
        <a href="../index.php" class="checkout-btn"><?= __('back_to_home') ?></a>
    </div>
</body>
</html> 