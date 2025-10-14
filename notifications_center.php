<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle mark as read action
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

// Handle delete notification
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
}

// Get notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_notifications = $stmt->fetchColumn();
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $per_page, $offset]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

// Get notification statistics
$stmt = $pdo->prepare("
    SELECT 
        type,
        COUNT(*) as count,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
    FROM notifications 
    WHERE user_id = ? 
    GROUP BY type
");
$stmt->execute([$user_id]);
$notification_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>



<div class="notifications-container">
    <div class="notifications-header">
        <h1>🔔 <?php echo __('notifications_center'); ?></h1>
        <p><?php echo __('manage_your_notifications'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="notifications-stats">
        <div class="stat-card">
            <div class="number"><?php echo $total_notifications; ?></div>
            <div class="label"><?php echo __('total_notifications'); ?></div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $unread_count; ?></div>
            <div class="label"><?php echo __('unread_notifications'); ?></div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo count($notification_stats); ?></div>
            <div class="label"><?php echo __('notification_types'); ?></div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_pages; ?></div>
            <div class="label"><?php echo __('pages'); ?></div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="notifications-actions">
        <div class="filters">
            <select id="type-filter">
                <option value=""><?php echo __('all_types'); ?></option>
                <option value="order"><?php echo __('order_notifications'); ?></option>
                <option value="promotion"><?php echo __('promotion_notifications'); ?></option>
                <option value="system"><?php echo __('system_notifications'); ?></option>
                <option value="security"><?php echo __('security_notifications'); ?></option>
            </select>
            <select id="status-filter">
                <option value=""><?php echo __('all_status'); ?></option>
                <option value="unread"><?php echo __('unread_only'); ?></option>
                <option value="read"><?php echo __('read_only'); ?></option>
            </select>
        </div>
        <div class="actions">
            <form method="POST">
                <button type="submit" name="mark_all_read" class="btn-secondary">
                    <?php echo __('mark_all_read'); ?>
                </button>
            </form>
            <button onclick="refreshNotifications()" class="btn-secondary">
                <?php echo __('refresh'); ?>
            </button>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="icon">🔔</div>
                <h3><?php echo __('no_notifications'); ?></h3>
                <p><?php echo __('no_notifications_message'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="notification-icon <?php echo $notification['type']; ?>">
                        <?php
                        switch ($notification['type']) {
                            case 'order':
                                echo '📦';
                                break;
                            case 'promotion':
                                echo '🎉';
                                break;
                            case 'system':
                                echo '⚙️';
                                break;
                            case 'security':
                                echo '🔒';
                                break;
                            default:
                                echo '📢';
                        }
                        ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-meta">
                            <div class="notification-time">
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="mark_read" class="btn-mark-read">
                                            <?php echo __('mark_read'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('<?php echo __('delete_confirmation'); ?>')">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="delete" class="btn-delete">
                                        <?php echo __('delete'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>"><?php echo __('previous'); ?></a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>"><?php echo __('next'); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function refreshNotifications() {
    location.reload();
}

// Filter functionality
document.getElementById('type-filter').addEventListener('change', function() {
    const type = this.value;
    const status = document.getElementById('status-filter').value;
    applyFilters(type, status);
});

document.getElementById('status-filter').addEventListener('change', function() {
    const type = document.getElementById('type-filter').value;
    const status = this.value;
    applyFilters(type, status);
});

function applyFilters(type, status) {
    const notifications = document.querySelectorAll('.notification-item');
    
    notifications.forEach(notification => {
        let show = true;
        
        // Type filter
        if (type && !notification.querySelector('.notification-icon').classList.contains(type)) {
            show = false;
        }
        
        // Status filter
        if (status === 'unread' && !notification.classList.contains('unread')) {
            show = false;
        } else if (status === 'read' && notification.classList.contains('unread')) {
            show = false;
        }
        
        notification.style.display = show ? 'flex' : 'none';
    });
}

// Auto-refresh every 30 seconds
setInterval(function() {
    // Only refresh if user is on the notifications page
    if (document.visibilityState === 'visible') {
        // Check for new notifications without full page reload
        fetch('check_new_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_count > 0) {
                    // Show notification badge or update count
                    updateNotificationCount(data.new_count);
                }
            });
    }
}, 30000);

function updateNotificationCount(count) {
    // Update notification count in header if it exists
    const headerCount = document.querySelector('.notification-count');
    if (headerCount) {
        headerCount.textContent = count;
        headerCount.style.display = count > 0 ? 'inline' : 'none';
    }
}
</script>

<?php include 'footer.php'; ?> 