<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'إدارة الإرجاعات';
$page_subtitle = 'عرض وإدارة طلبات الإرجاع من العملاء';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'dashboard.php'],
    ['title' => 'إدارة الإرجاعات']
];

require '../db.php';
require 'admin_header.php';

// Handle status updates
if (isset($_POST['update_status'])) {
    $return_id = intval($_POST['return_id']);
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    // Update return status
    $stmt = $pdo->prepare('UPDATE returns SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$new_status, $admin_notes, $return_id]);
    
    // Get return details for notification
    $stmt = $pdo->prepare('SELECT r.*, u.id as user_id FROM returns r JOIN orders o ON r.order_id = o.id JOIN users u ON o.user_id = u.id WHERE r.id = ?');
    $stmt->execute([$return_id]);
    $return = $stmt->fetch();
    
    if ($return) {
        // Create notification for user
        $notification_title = '';
        $notification_message = '';
        
        switch ($new_status) {
            case 'approved':
                $notification_title = 'تمت الموافقة على طلب الإرجاع';
                $notification_message = "تمت الموافقة على طلب الإرجاع رقم #{$return['return_number']}. سيتم التواصل معك قريباً لترتيب عملية الإرجاع.";
                break;
            case 'rejected':
                $notification_title = 'تم رفض طلب الإرجاع';
                $notification_message = "تم رفض طلب الإرجاع رقم #{$return['return_number']}. إذا كان لديك أي استفسار، يرجى التواصل معنا.";
                break;
            case 'completed':
                $notification_title = 'تم إكمال عملية الإرجاع';
                $notification_message = "تم إكمال عملية الإرجاع رقم #{$return['return_number']}. شكراً لتعاونك معنا.";
                break;
        }
        
        if ($notification_title && $notification_message) {
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, "order", ?, ?)');
            $stmt->execute([$return['user_id'], $notification_title, $notification_message]);
        }
    }
    
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'update_return_status';
    $details = "Updated return #{$return_id} status to {$new_status}";
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    
    header('Location: returns.php');
    exit();
}

// Get returns with user and order information
$returns = $pdo->query('
    SELECT r.*, u.name as user_name, u.email as user_email, o.total as order_total, o.status as order_status
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON o.user_id = u.id
    ORDER BY r.created_at DESC
')->fetchAll();

// Count returns by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$completed_count = 0;

foreach ($returns as $return) {
    switch ($return['status']) {
        case 'pending': $pending_count++; break;
        case 'approved': $approved_count++; break;
        case 'rejected': $rejected_count++; break;
        case 'completed': $completed_count++; break;
    }
}
?>

<div class="dashboard-content">
    <div class="content-header">
        <h1><?php echo $page_title; ?></h1>
        <p><?php echo $page_subtitle; ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending">📋</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">قيد المراجعة</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $approved_count; ?></div>
                <div class="stat-label">تمت الموافقة</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected">❌</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $rejected_count; ?></div>
                <div class="stat-label">مرفوض</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed">🎉</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $completed_count; ?></div>
                <div class="stat-label">مكتمل</div>
            </div>
        </div>
    </div>

    <!-- Returns List -->
    <div class="content-section">
        <div class="section-header">
            <h2>طلبات الإرجاع</h2>
        </div>

        <?php if (empty($returns)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>لا توجد طلبات إرجاع</h3>
                <p>لم يتم تقديم أي طلبات إرجاع حتى الآن.</p>
            </div>
        <?php else: ?>
            <div class="returns-grid">
                <?php foreach ($returns as $return): ?>
                    <div class="return-card">
                        <div class="return-header">
                            <div class="return-number">
                                <h3>#<?php echo $return['return_number']; ?></h3>
                                <span class="return-date"><?php echo date('j M Y', strtotime($return['created_at'])); ?></span>
                            </div>
                            <div class="return-status-badge <?php echo $return['status']; ?>">
                                <?php
                                switch ($return['status']) {
                                    case 'pending': echo 'قيد المراجعة'; break;
                                    case 'approved': echo 'تمت الموافقة'; break;
                                    case 'rejected': echo 'مرفوض'; break;
                                    case 'completed': echo 'مكتمل'; break;
                                }
                                ?>
                            </div>
                        </div>

                        <div class="return-details">
                            <div class="detail-row">
                                <span class="detail-label">العميل:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($return['user_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">البريد الإلكتروني:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($return['user_email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">رقم الطلب:</span>
                                <span class="detail-value">#<?php echo $return['order_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">مبلغ الطلب:</span>
                                <span class="detail-value"><?php echo $return['order_total']; ?> د.ت</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">السبب:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($return['reason']); ?></span>
                            </div>
                            <?php if ($return['description']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">التفاصيل:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($return['description']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($return['status'] === 'pending'): ?>
                            <div class="return-actions">
                                <button class="action-btn approve" onclick="showStatusModal(<?php echo $return['id']; ?>, 'approved')">
                                    ✅ موافقة
                                </button>
                                <button class="action-btn reject" onclick="showStatusModal(<?php echo $return['id']; ?>, 'rejected')">
                                    ❌ رفض
                                </button>
                            </div>
                        <?php elseif ($return['status'] === 'approved'): ?>
                            <div class="return-actions">
                                <button class="action-btn complete" onclick="showStatusModal(<?php echo $return['id']; ?>, 'completed')">
                                    🎉 إكمال
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($return['admin_notes']): ?>
                            <div class="admin-notes">
                                <strong>ملاحظات الإدارة:</strong>
                                <p><?php echo htmlspecialchars($return['admin_notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>تحديث حالة الإرجاع</h3>
            <span class="close" onclick="closeStatusModal()">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="return_id" id="modalReturnId">
            <input type="hidden" name="status" id="modalStatus">
            <input type="hidden" name="update_status" value="1">
            
            <div class="form-group">
                <label for="admin_notes">ملاحظات الإدارة (اختياري):</label>
                <textarea name="admin_notes" id="admin_notes" rows="4" placeholder="اكتب ملاحظات إضافية..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeStatusModal()">إلغاء</button>
                <button type="submit" class="btn-primary">تحديث الحالة</button>
            </div>
        </form>
    </div>
</div>

<style>
.returns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.return-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.return-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.return-number h3 {
    margin: 0 0 4px 0;
    color: #333;
    font-size: 1.1em;
}

.return-date {
    font-size: 0.85em;
    color: #666;
}

.return-status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}

.return-status-badge.pending {
    background: #fff3e0;
    color: #f57c00;
}

.return-status-badge.approved {
    background: #e8f5e8;
    color: #388e3c;
}

.return-status-badge.rejected {
    background: #ffebee;
    color: #d32f2f;
}

.return-status-badge.completed {
    background: #e3f2fd;
    color: #1976d2;
}

.return-details {
    margin-bottom: 16px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 4px 0;
}

.detail-label {
    font-weight: 500;
    color: #666;
    min-width: 120px;
}

.detail-value {
    color: #333;
    text-align: left;
    flex: 1;
}

.return-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.9em;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn.approve {
    background: #4caf50;
    color: white;
}

.action-btn.approve:hover {
    background: #388e3c;
}

.action-btn.reject {
    background: #f44336;
    color: white;
}

.action-btn.reject:hover {
    background: #d32f2f;
}

.action-btn.complete {
    background: #2196f3;
    color: white;
}

.action-btn.complete:hover {
    background: #1976d2;
}

.admin-notes {
    background: #f5f5f5;
    padding: 12px;
    border-radius: 6px;
    margin-top: 12px;
}

.admin-notes strong {
    color: #333;
    display: block;
    margin-bottom: 4px;
}

.admin-notes p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

.modal-content form {
    padding: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    resize: vertical;
    font-family: inherit;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-primary, .btn-secondary {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary-color);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .returns-grid {
        grid-template-columns: 1fr;
    }
    
    .return-header {
        flex-direction: column;
        gap: 8px;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .detail-label {
        min-width: auto;
    }
}
</style>

<script>
function showStatusModal(returnId, status) {
    document.getElementById('modalReturnId').value = returnId;
    document.getElementById('modalStatus').value = status;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('statusModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require 'admin_footer.php'; ?> 