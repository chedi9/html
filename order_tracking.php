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
    <?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['is_mobile'])) {
        $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
        $_SESSION['is_mobile'] = $is_mobile ? true : false;
    }
    ?>
    
</head>
<body>
  <div>
    <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
      </svg>
    </button>
  </div>
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
            <div><?= __('total') ?>: <?php echo $order['total']; ?> <?= __('currency') ?></div>
            <div>تاريخ الطلب: <?php echo $order['created_at']; ?></div>
        </div>
        <?php endif; ?>
    </div>
    <script src="main.js?v=1.2"></script>
</body>
</html> 