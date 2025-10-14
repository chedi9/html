<?php
session_start();
require '../db.php';
require '../lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark notification as read
if (isset($_GET['mark_read']) && $_GET['mark_read']) {
    $notification_id = intval($_GET['mark_read']);
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$notification_id, $user_id]);
    header('Location: user_notifications.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$user_id]);
    header('Location: user_notifications.php');
    exit();
}

// Fetch notifications
$notifications = $pdo->prepare('
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
');
$notifications->execute([$user_id]);
$notifications = $notifications->fetchAll();

// Count unread notifications
$unread_count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$unread_count->execute([$user_id]);
$unread_count = $unread_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - WeBuy</title>
    
</head>
<body>
    <div class="notifications-container">
        <div class="notifications-header">
            <h1>🔔 الإشعارات</h1>
            <?php if ($unread_count > 0): ?>
                <div class="unread-badge"><?php echo $unread_count; ?> غير مقروء</div>
            <?php endif; ?>
        </div>
        
        <div class="notifications-actions">
            <a href="account.php" class="notification-btn secondary">← العودة للحساب</a>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="mark-all-read">تحديد الكل كمقروء</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="icon">🔔</div>
                <h3>لا توجد إشعارات بعد</h3>
                <p>ستظهر هنا الإشعارات الخاصة بالطلبات الجديدة والتحديثات المهمة.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon <?php echo $notification['type']; ?>">
                            <?php
                            switch ($notification['type']) {
                                case 'order': echo '🛒'; break;
                                case 'promotion': echo '🎉'; break;
                                case 'system': echo 'ℹ️'; break;
                                case 'security': echo '🔒'; break;
                                default: echo '📢';
                            }
                            ?>
                        </div>
                        
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-time">
                                <?php echo date('j M Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                            
                            <?php if (!$notification['is_read']): ?>
                                <div class="notification-actions">
                                    <button class="notification-action" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                        تحديد كمقروء
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function markAsRead(notificationId) {
            window.location.href = '?mark_read=' + notificationId;
        }
    </script>
</body>
</html> 