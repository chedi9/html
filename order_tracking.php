<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require 'db.php';
$order = null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND email = ?');
    $stmt->execute([$order_id, $email]);
    $order = $stmt->fetch();
    if (!$order) {
        $error = 'لم يتم العثور على الطلب. تحقق من الرقم والبريد.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تتبع الطلب</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['is_mobile'])) {
        $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
        $_SESSION['is_mobile'] = $is_mobile ? true : false;
    }
    ?>
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
    <style>
        .track-container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .track-container h2 { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; }
        input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; }
        .track-btn { width: 100%; padding: 12px; font-size: 1.1em; border-radius: 8px; background: var(--primary-color); color: #fff; border: none; margin-top: 10px; }
        .order-block { margin-top: 30px; background: #f4f6fb; border-radius: 10px; padding: 18px; }
        .order-status { font-weight: bold; color: #228B22; }
        .error-msg { color: #c00; text-align: center; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="track-container">
        <h2>تتبع الطلب</h2>
        <form method="post">
            <div class="form-group">
                <label for="order_id">رقم الطلب:</label>
                <input type="number" id="order_id" name="order_id" required>
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <button type="submit" class="track-btn">تتبع</button>
        </form>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($order): ?>
        <div class="order-block">
            <div>رقم الطلب: <?php echo $order['id']; ?></div>
            <div class="order-status">الحالة: <?php echo htmlspecialchars($order['status']); ?></div>
            <div>الإجمالي: <?php echo $order['total']; ?> د.ت</div>
            <div>تاريخ الطلب: <?php echo $order['created_at']; ?></div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 