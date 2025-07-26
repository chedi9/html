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

$page_title = 'سجل النشاطات';
$page_subtitle = 'عرض سجل جميع النشاطات الإدارية';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'dashboard.php'],
    ['title' => 'سجل النشاطات']
];

require '../db.php';
require 'admin_header.php';

// Get activity logs with admin information
$activities = $pdo->query('
    SELECT al.*, a.username as admin_username, a.email as admin_email
    FROM activity_log al
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.created_at DESC
    LIMIT 100
')->fetchAll();
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($activities); ?></div>
                <div class="stat-label">آخر النشاطات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $todayActivities = array_filter($activities, function($a) {
                        return date('Y-m-d', strtotime($a['created_at'])) === date('Y-m-d');
                    });
                    echo count($todayActivities);
                    ?>
                </div>
                <div class="stat-label">نشاطات اليوم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $uniqueAdmins = array_unique(array_column($activities, 'admin_id'));
                    echo count($uniqueAdmins);
                    ?>
                </div>
                <div class="stat-label">مدراء نشطين</div>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if ($activities): ?>
            <div class="activity-timeline">
                <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php
                        $icon = '📝';
                        switch ($activity['action']) {
                            case 'add_product': $icon = '➕'; break;
                            case 'edit_product': $icon = '✏️'; break;
                            case 'delete_product': $icon = '🗑️'; break;
                            case 'add_category': $icon = '📁'; break;
                            case 'edit_category': $icon = '📝'; break;
                            case 'delete_category': $icon = '🗑️'; break;
                            case 'update_order_status': $icon = '📦'; break;
                            case 'delete_review': $icon = '⭐'; break;
                            case 'add_disabled_seller': $icon = '♿'; break;
                            case 'edit_disabled_seller': $icon = '✏️'; break;
                            case 'delete_disabled_seller': $icon = '🗑️'; break;
                            default: $icon = '📝';
                        }
                        ?>
                        <span class="icon"><?php echo $icon; ?></span>
                    </div>
                    
                    <div class="activity-content">
                        <div class="activity-header">
                            <div class="activity-info">
                                <h4 class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></h4>
                                <div class="activity-meta">
                                    <span class="activity-admin"><?php echo htmlspecialchars($activity['admin_username']); ?></span>
                                    <span class="activity-time"><?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="activity-details">
                                <span class="details-text"><?php echo htmlspecialchars($activity['details']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($activity['table_name'] || $activity['record_id']): ?>
                        <div class="activity-target">
                            <?php if ($activity['table_name']): ?>
                                <span class="target-table">جدول: <?php echo htmlspecialchars($activity['table_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($activity['record_id']): ?>
                                <span class="target-id">ID: <?php echo htmlspecialchars($activity['record_id']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($activity['ip_address']): ?>
                        <div class="activity-ip">
                            <span class="ip-label">IP:</span>
                            <span class="ip-address"><?php echo htmlspecialchars($activity['ip_address']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <h3>لا توجد نشاطات</h3>
                <p>لم يتم تسجيل أي نشاطات إدارية بعد.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.header-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

.activity-timeline {
    position: relative;
    padding-left: 30px;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #667eea, #764ba2);
    border-radius: 1px;
}

.activity-item {
    position: relative;
    margin-bottom: 25px;
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.activity-item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 25px;
    width: 12px;
    height: 12px;
    background: white;
    border: 3px solid #667eea;
    border-radius: 50%;
    z-index: 1;
}

.activity-icon {
    position: absolute;
    left: -30px;
    top: 20px;
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    z-index: 2;
}

.activity-content {
    margin-left: 10px;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.activity-info {
    flex: 1;
}

.activity-action {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1em;
    font-weight: 600;
    text-transform: capitalize;
}

.activity-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85em;
    color: #666;
}

.activity-admin {
    font-weight: 600;
    color: #667eea;
}

.activity-time {
    color: #999;
}

.activity-details {
    max-width: 300px;
    text-align: right;
}

.details-text {
    font-size: 0.9em;
    color: #555;
    line-height: 1.4;
}

.activity-target {
    display: flex;
    gap: 15px;
    margin-bottom: 8px;
    font-size: 0.85em;
}

.target-table {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
}

.target-id {
    background: #f3e5f5;
    color: #7b1fa2;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
}

.activity-ip {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8em;
    color: #666;
}

.ip-label {
    font-weight: 600;
}

.ip-address {
    font-family: monospace;
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 4px;
    color: #333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    margin-bottom: 8px;
    color: #333;
}

.empty-state p {
    margin-bottom: 24px;
    color: #666;
}

@media (max-width: 768px) {
    .activity-timeline {
        padding-left: 20px;
    }
    
    .activity-timeline::before {
        left: 10px;
    }
    
    .activity-item::before {
        left: -17px;
    }
    
    .activity-icon {
        left: -25px;
        width: 25px;
        height: 25px;
        font-size: 12px;
    }
    
    .activity-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .activity-details {
        max-width: none;
        text-align: left;
    }
    
    .activity-target {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<?php require 'admin_footer.php'; ?> 