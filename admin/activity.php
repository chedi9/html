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
$logs = $pdo->query('SELECT activity_log.*, admins.username FROM activity_log LEFT JOIN admins ON activity_log.admin_id = admins.id ORDER BY timestamp DESC LIMIT 100')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سجل الأنشطة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .activity-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .activity-container h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .back-btn { display: block; margin: 30px auto 0; background: var(--secondary-color); text-align: center; text-decoration: none; color: #fff; padding: 10px 24px; border-radius: 5px; max-width: 200px; }
        .back-btn:hover { background: var(--primary-color); }
    </style>
</head>
<body>
    <div class="activity-container">
        <h2>سجل الأنشطة</h2>
        <?php
        // Assuming $current_admin is available from the session or elsewhere
        // For demonstration, let's fetch it from the admins table
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $permissions = [
            'superadmin' => [ 'view_activity' => true ],
            'admin' => [ 'view_activity' => true ],
            'moderator' => [ 'view_activity' => true ],
        ];
        $role = $current_admin['role'];
        ?>
        <?php if ($permissions[$role]['view_activity']): ?>
            <table>
                <thead>
                    <tr>
                        <th>المشرف</th>
                        <th>الإجراء</th>
                        <th>التفاصيل</th>
                        <th>الوقت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="dashboard.php" class="back-btn">العودة للوحة التحكم</a>
        <?php else: ?>
            <div style="text-align:center;color:#c00;font-size:1.2em;margin:40px 0;">ليس لديك صلاحية عرض سجل الأنشطة.</div>
        <?php endif; ?>
    </div>
</body>
</html> 