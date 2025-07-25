<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';
// Handle moderation actions
if (isset($_POST['action'], $_POST['review_id'])) {
    $review_id = intval($_POST['review_id']);
    $admin_id = $_SESSION['admin_id'];
    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
        $stmt->execute([$review_id]);
        // Log activity
        $action = 'delete_review';
        $details = 'Deleted review ID: ' . $review_id;
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    } elseif ($_POST['action'] === 'hide') {
        $stmt = $pdo->prepare('UPDATE reviews SET hidden = 1 WHERE id = ?');
        $stmt->execute([$review_id]);
        // Log activity
        $action = 'hide_review';
        $details = 'Hid review ID: ' . $review_id;
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    } elseif ($_POST['action'] === 'approve') {
        $stmt = $pdo->prepare('UPDATE reviews SET hidden = 0 WHERE id = ?');
        $stmt->execute([$review_id]);
        // Log activity
        $action = 'approve_review';
        $details = 'Approved review ID: ' . $review_id;
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    }
    header('Location: reviews.php');
    exit();
}
// Fetch all reviews with product and user info
$stmt = $pdo->query('SELECT r.*, p.name AS product_name, u.name AS user_name FROM reviews r LEFT JOIN products p ON r.product_id = p.id LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC');
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المراجعات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .reviews-container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .reviews-container h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .action-btn { background: #c00; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none; font-size: 0.95em; margin: 0 4px; }
        .action-btn:hover { background: #a00; }
        .approve-btn { background: #43A047; }
        .approve-btn:hover { background: #228B22; }
        .hide-btn { background: #c90; }
        .hide-btn:hover { background: #a80; }
        .hidden-row { background: #fff3e0; }
    </style>
</head>
<body>
    <div class="reviews-container">
        <h2>إدارة المراجعات</h2>
        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>المستخدم</th>
                    <th>التقييم</th>
                    <th>التعليق</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $rev): ?>
                <tr class="<?php echo !empty($rev['hidden']) ? 'hidden-row' : ''; ?>">
                    <td><?php echo htmlspecialchars($rev['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($rev['user_name'] ?? $rev['name']); ?></td>
                    <td style="color:#FFD600;">
                        <?php for ($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                    </td>
                    <td><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></td>
                    <td><?php echo $rev['created_at']; ?></td>
                    <td><?php echo !empty($rev['hidden']) ? 'مخفي' : 'ظاهر'; ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                            <?php
                            // Assuming $current_admin is available from session or elsewhere
                            // For demonstration, let's create a dummy admin for this example
                            // In a real application, you'd fetch the admin's role from the database
                            $current_admin = [ 'role' => 'admin' ]; // Placeholder for actual admin data
                            $permissions = [
                                'superadmin' => [ 'manage_reviews' => true ],
                                'admin' => [ 'manage_reviews' => true ],
                                'moderator' => [ 'manage_reviews' => true ],
                            ];
                            $role = $current_admin['role'];
                            ?>
                            <button type="submit" name="action" value="approve" class="action-btn approve-btn"<?php if (!$permissions[$role]['manage_reviews']) echo ' disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>إظهار</button>
                            <button type="submit" name="action" value="hide" class="action-btn hide-btn"<?php if (!$permissions[$role]['manage_reviews']) echo ' disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>إخفاء</button>
                            <button type="submit" name="action" value="delete" class="action-btn" onclick="return confirm('هل أنت متأكد من حذف هذه المراجعة؟');"<?php if (!$permissions[$role]['manage_reviews']) echo ' disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="add-btn" style="background:var(--secondary-color);margin-top:30px;">العودة للوحة التحكم</a>
    </div>
</body>
</html> 