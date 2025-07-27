<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: client/login.php');
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

<style>
.notifications-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.notifications-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.notifications-header h1 {
    margin: 0;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.notifications-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.notifications-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-card .number {
    font-size: 2.5em;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 10px;
}

.stat-card .label {
    color: #666;
    font-size: 0.9em;
}

.notifications-actions {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.notifications-actions .filters {
    display: flex;
    gap: 15px;
    align-items: center;
}

.notifications-actions select,
.notifications-actions button {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.notifications-actions button {
    background: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
}

.notifications-actions button:hover {
    background: #0056b3;
}

.notifications-actions .btn-secondary {
    background: #6c757d;
}

.notifications-actions .btn-secondary:hover {
    background: #545b62;
}

.notifications-list {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.notification-item {
    padding: 20px;
    border-bottom: 1px solid #f8f9fa;
    transition: background 0.3s;
    display: flex;
    align-items: center;
    gap: 15px;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: #e7f3ff;
    border-left: 4px solid #007bff;
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    flex-shrink: 0;
}

.notification-icon.order {
    background: #e3f2fd;
    color: #1976d2;
}

.notification-icon.promotion {
    background: #f3e5f5;
    color: #7b1fa2;
}

.notification-icon.system {
    background: #e8f5e8;
    color: #388e3c;
}

.notification-icon.security {
    background: #fff3e0;
    color: #f57c00;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.notification-message {
    color: #666;
    margin-bottom: 10px;
    line-height: 1.4;
}

.notification-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9em;
    color: #999;
}

.notification-time {
    font-style: italic;
}

.notification-actions {
    display: flex;
    gap: 10px;
}

.notification-actions button {
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.3s;
}

.notification-actions .btn-mark-read {
    background: #28a745;
    color: white;
}

.notification-actions .btn-mark-read:hover {
    background: #218838;
}

.notification-actions .btn-delete {
    background: #dc3545;
    color: white;
}

.notification-actions .btn-delete:hover {
    background: #c82333;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a,
.pagination span {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-decoration: none;
    color: #007bff;
    transition: all 0.3s;
}

.pagination a:hover {
    background: #007bff;
    color: white;
}

.pagination .current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state .icon {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: #333;
}

@media (max-width: 768px) {
    .notifications-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .notifications-actions .filters {
        flex-direction: column;
    }
    
    .notification-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .notification-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

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
            <form method="POST" style="display: inline;">
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
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="mark_read" class="btn-mark-read">
                                            <?php echo __('mark_read'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo __('delete_confirmation'); ?>')">
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