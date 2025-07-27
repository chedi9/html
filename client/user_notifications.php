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
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .notifications-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-header h1 {
            margin: 0;
            font-size: 1.5em;
            font-weight: 600;
        }

        .unread-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .notifications-actions {
            padding: 20px 24px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-btn {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }

        .notification-btn:hover {
            background: var(--secondary-color);
        }

        .notification-btn.secondary {
            background: #6c757d;
        }

        .notification-btn.secondary:hover {
            background: #5a6268;
        }

        .mark-all-read {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .mark-all-read:hover {
            text-decoration: underline;
        }

        .notifications-list {
            padding: 0;
        }

        .notification-item {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: background 0.2s;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #f0f8ff;
            border-left: 4px solid var(--primary-color);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            flex-shrink: 0;
        }

        .notification-icon.order {
            background: #e3f2fd;
            color: #1976d2;
        }

        .notification-icon.promotion {
            background: #fff3e0;
            color: #f57c00;
        }

        .notification-icon.system {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .notification-icon.security {
            background: #ffebee;
            color: #d32f2f;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .notification-message {
            color: #666;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 0.85em;
            color: #999;
        }

        .notification-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .notification-action {
            background: none;
            border: 1px solid #ddd;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .notification-action:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #666;
        }

        .empty-state .icon {
            font-size: 3em;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin: 0 0 8px 0;
            color: #333;
        }

        .empty-state p {
            margin: 0;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .notifications-container {
                margin: 20px;
                border-radius: 8px;
            }

            .notifications-header {
                padding: 16px;
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .notifications-actions {
                padding: 16px;
                flex-direction: column;
                gap: 12px;
            }

            .notification-item {
                padding: 16px;
                flex-direction: column;
                gap: 12px;
            }

            .notification-icon {
                align-self: center;
            }
        }
    </style>
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