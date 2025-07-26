<?php
session_start();
require '../db.php';
require '../lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();

if (!$seller) {
    echo 'You are not a seller.';
    exit();
}

// Mark notification as read
if (isset($_GET['mark_read']) && $_GET['mark_read']) {
    $notification_id = intval($_GET['mark_read']);
    $stmt = $pdo->prepare('UPDATE seller_notifications SET is_read = 1 WHERE id = ? AND seller_id = ?');
    $stmt->execute([$notification_id, $seller['id']]);
    header('Location: notifications.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare('UPDATE seller_notifications SET is_read = 1 WHERE seller_id = ?');
    $stmt->execute([$seller['id']]);
    header('Location: notifications.php');
    exit();
}

// Fetch notifications
$notifications = $pdo->prepare('
    SELECT * FROM seller_notifications 
    WHERE seller_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
');
$notifications->execute([$seller['id']]);
$notifications = $notifications->fetchAll();

// Count unread notifications
$unread_count = $pdo->prepare('SELECT COUNT(*) FROM seller_notifications WHERE seller_id = ? AND is_read = 0');
$unread_count->execute([$seller['id']]);
$unread_count = $unread_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Seller Notifications</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .notifications-header {
            background: linear-gradient(120deg, var(--primary-color) 60%, var(--accent-color) 100%);
            color: #fff;
            padding: 24px;
            text-align: center;
        }
        
        .notifications-header h1 {
            margin: 0;
            font-size: 1.8em;
            color: #FFD600;
        }
        
        .notifications-header .unread-badge {
            background: #FFD600;
            color: #1A237E;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            margin-top: 8px;
            display: inline-block;
        }
        
        .notifications-actions {
            padding: 16px 24px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mark-all-read {
            background: #00BFAE;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
        
        .mark-all-read:hover {
            background: #009688;
        }
        
        .notification-item {
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e3f2fd;
            border-left: 4px solid #00BFAE;
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
        
        .notification-icon.order { background: #e8f5e8; color: #2e7d32; }
        .notification-icon.review { background: #fff3e0; color: #f57c00; }
        .notification-icon.stock { background: #ffebee; color: #c62828; }
        .notification-icon.system { background: #e3f2fd; color: #1976d2; }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: bold;
            color: #1A237E;
            margin-bottom: 4px;
        }
        
        .notification-message {
            color: #666;
            line-height: 1.4;
            margin-bottom: 8px;
        }
        
        .notification-time {
            color: #999;
            font-size: 0.9em;
        }
        
        .notification-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .notification-btn {
            padding: 4px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .notification-btn.primary {
            background: #00BFAE;
            color: #fff;
        }
        
        .notification-btn.secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #666;
        }
        
        .empty-state .icon {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 16px;
        }
        
        @media (max-width: 768px) {
            .notifications-container {
                margin: 20px;
                border-radius: 8px;
            }
            
            .notification-item {
                padding: 16px;
                flex-direction: column;
                gap: 12px;
            }
            
            .notifications-actions {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <div class="notifications-header">
            <h1>🔔 Notifications</h1>
            <?php if ($unread_count > 0): ?>
                <div class="unread-badge"><?php echo $unread_count; ?> unread</div>
            <?php endif; ?>
        </div>
        
        <div class="notifications-actions">
            <a href="seller_dashboard.php" class="notification-btn secondary">← Back to Dashboard</a>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="mark-all-read">Mark All as Read</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="icon">🔔</div>
                <h3>No notifications yet</h3>
                <p>You'll see notifications here for new orders, reviews, and important updates.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="notification-icon <?php echo $notification['type']; ?>">
                        <?php
                        switch ($notification['type']) {
                            case 'order': echo '🛒'; break;
                            case 'review': echo '⭐'; break;
                            case 'stock': echo '⚠️'; break;
                            case 'system': echo 'ℹ️'; break;
                            default: echo '📢';
                        }
                        ?>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time">
                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                        </div>
                        
                        <?php if ($notification['action_url']): ?>
                            <div class="notification-actions">
                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="notification-btn primary">
                                    View Details
                                </a>
                                <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="notification-btn secondary">
                                        Mark as Read
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html> 