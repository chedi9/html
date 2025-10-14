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