<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';
require '../lang.php';

$user_id = $_SESSION['user_id'];

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification_id = intval($_POST['notification_id']);
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$notification_id, $user_id]);
        
        header('Location: notifications.php');
        exit();
    }
    
    if (isset($_POST['mark_all_read'])) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$user_id]);
        
        header('Location: notifications.php');
        exit();
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = intval($_POST['notification_id']);
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$notification_id, $user_id]);
        
        header('Location: notifications.php');
        exit();
    }
}

// Fetch notifications
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Count unread notifications
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('notifications') ?> - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }
        .notification-item {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            border-left-color: #00BFAE;
            background: linear-gradient(135deg, #f8ffff, #e8f8f7);
        }
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .notification-title {
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .notification-time {
            font-size: 0.9em;
            color: #666;
        }
        .notification-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            background: linear-gradient(45deg, #1A237E, #00BFAE);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: linear-gradient(45deg, #74b9ff, #0984e3);
        }
        .btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }
        .unread-badge {
            background: #00BFAE;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .notification-icon {
            font-size: 1.5em;
            margin-right: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <div class="notifications-header">
            <div>
                <h1><?= __('notifications') ?></h1>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">
                    <?= $unread_count ?> <?= __('unread_notifications') ?>
                </p>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="account.php" class="btn btn-secondary"><?= __('back_to_account') ?></a>
                <?php if ($unread_count > 0): ?>
                    <form method="post" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn"><?= __('mark_all_read') ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,17A1.5,1.5 0 0,1 10.5,15.5A1.5,1.5 0 0,1 12,14A1.5,1.5 0 0,1 13.5,15.5A1.5,1.5 0 0,1 12,17M12,10A1,1 0 0,1 13,11V14A1,1 0 0,1 11,14V11A1,1 0 0,1 12,10M8,5A1,1 0 0,1 9,6V9A1,1 0 0,1 7,9V6A1,1 0 0,1 8,5M16,5A1,1 0 0,1 17,6V9A1,1 0 0,1 15,9V6A1,1 0 0,1 16,5Z"/>
                </svg>
                <h3><?= __('no_notifications') ?></h3>
                <p><?= __('no_notifications_desc') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
                    <div class="notification-header">
                        <div style="display: flex; align-items: center;">
                            <span class="notification-icon">
                                <?php
                                switch($notification['type']) {
                                    case 'order': echo '📦'; break;
                                    case 'review': echo '⭐'; break;
                                    case 'promotion': echo '🎉'; break;
                                    case 'system': echo '🔔'; break;
                                    default: echo '📢'; break;
                                }
                                ?>
                            </span>
                            <h3 class="notification-title"><?= htmlspecialchars($notification['title']) ?></h3>
                            <?php if (!$notification['is_read']): ?>
                                <span class="unread-badge"><?= __('new') ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="notification-time">
                            <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                        </span>
                    </div>
                    
                    <div class="notification-content">
                        <?= nl2br(htmlspecialchars($notification['message'])) ?>
                    </div>
                    
                    <div class="notification-actions">
                        <?php if (!$notification['is_read']): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <button type="submit" name="mark_read" class="btn btn-secondary"><?= __('mark_as_read') ?></button>
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('<?= __('confirm_delete_notification') ?>')">
                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                            <button type="submit" name="delete_notification" class="btn btn-danger"><?= __('delete') ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            if (!document.hidden) {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>