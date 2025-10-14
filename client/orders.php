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
<html lang="<?php echo $lang ?? 'ar'; ?>" dir="<?php echo ($lang ?? 'ar') === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= __('my_orders') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- WeBuy Custom Bootstrap Configuration -->
    <link rel="stylesheet" href="../css/bootstrap-custom.css">
    
    <!-- Legacy CSS for gradual migration -->
    <link rel="stylesheet" href="../css/main.css">
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
    <script src="../js/theme-controller.js" defer></script>
</head>
<body class="page-transition">
    <?php include '../header.php'; ?>
    <div class="orders-container container py-4">
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
            <table class="table table-striped">
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
            <p style="text-align:center;"><?= __('no_orders_yet') ?></p>
        <?php endif; ?>
        <a href="../index.php" class="btn btn-secondary mt-3"><?= __('back_to_home') ?></a>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html> 