<?php
session_start();
require '../db.php';

// Security headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

// Check admin session
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tab = $_GET['tab'] ?? 'reviews';

// Handle AJAX actions
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'update_review_status':
            $review_id = intval($_POST['review_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $notes = trim($_POST['notes'] ?? '');
            
            if (!$review_id || !in_array($status, ['pending', 'approved', 'rejected', 'spam'])) {
                $response = ['success' => false, 'message' => 'بيانات غير صحيحة.'];
                break;
            }
            
            try {
                $stmt = $pdo->prepare('UPDATE reviews SET status = ?, moderator_notes = ? WHERE id = ?');
                $stmt->execute([$status, $notes, $review_id]);
                $response = ['success' => true, 'message' => 'تم تحديث حالة المراجعة بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء التحديث.'];
            }
            break;
            
        case 'add_seller_response':
            $review_id = intval($_POST['review_id'] ?? 0);
            $response_text = trim($_POST['response'] ?? '');
            
            if (!$review_id || empty($response_text)) {
                $response = ['success' => false, 'message' => 'يرجى كتابة الرد.'];
                break;
            }
            
            try {
                $stmt = $pdo->prepare('UPDATE reviews SET seller_response = ?, seller_response_date = NOW() WHERE id = ?');
                $stmt->execute([$response_text, $review_id]);
                $response = ['success' => true, 'message' => 'تم إضافة رد البائع بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء إضافة الرد.'];
            }
            break;
            
        case 'update_question_status':
            $question_id = intval($_POST['question_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if (!$question_id || !in_array($status, ['pending', 'approved', 'rejected'])) {
                $response = ['success' => false, 'message' => 'بيانات غير صحيحة.'];
                break;
            }
            
            try {
                $stmt = $pdo->prepare('UPDATE review_questions SET status = ? WHERE id = ?');
                $stmt->execute([$status, $question_id]);
                $response = ['success' => true, 'message' => 'تم تحديث حالة السؤال بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء التحديث.'];
            }
            break;
            
        case 'update_answer_status':
            $answer_id = intval($_POST['answer_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if (!$answer_id || !in_array($status, ['pending', 'approved', 'rejected'])) {
                $response = ['success' => false, 'message' => 'بيانات غير صحيحة.'];
                break;
            }
            
            try {
                $stmt = $pdo->prepare('UPDATE review_answers SET status = ? WHERE id = ?');
                $stmt->execute([$status, $answer_id]);
                $response = ['success' => true, 'message' => 'تم تحديث حالة الإجابة بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء التحديث.'];
            }
            break;
            
        case 'handle_report':
            $report_id = intval($_POST['report_id'] ?? 0);
            $action_taken = $_POST['action_taken'] ?? '';
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            if (!$report_id || !in_array($action_taken, ['reviewed', 'resolved'])) {
                $response = ['success' => false, 'message' => 'بيانات غير صحيحة.'];
                break;
            }
            
            try {
                $stmt = $pdo->prepare('UPDATE review_reports SET status = ?, admin_notes = ? WHERE id = ?');
                $stmt->execute([$action_taken, $admin_notes, $report_id]);
                $response = ['success' => true, 'message' => 'تم معالجة البلاغ بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء المعالجة.'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'إجراء غير معروف.'];
    }
    
    echo json_encode($response);
    exit();
}

// Get statistics
$stats = [];
$stats['total_reviews'] = $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$stats['pending_reviews'] = $pdo->query('SELECT COUNT(*) FROM reviews WHERE status = "pending"')->fetchColumn();
$stats['total_questions'] = $pdo->query('SELECT COUNT(*) FROM review_questions')->fetchColumn();
$stats['pending_questions'] = $pdo->query('SELECT COUNT(*) FROM review_questions WHERE status = "pending"')->fetchColumn();
$stats['total_reports'] = $pdo->query('SELECT COUNT(*) FROM review_reports WHERE status = "pending"')->fetchColumn();

include 'admin_header.php';
?>

<div class="admin-container">
    <h1>إدارة المراجعات والأسئلة والأجوبة</h1>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>المراجعات</h3>
            <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
            <div class="stat-label">إجمالي المراجعات</div>
        </div>
        <div class="stat-card">
            <h3>في الانتظار</h3>
            <div class="stat-number"><?php echo $stats['pending_reviews']; ?></div>
            <div class="stat-label">مراجعات تنتظر الموافقة</div>
        </div>
        <div class="stat-card">
            <h3>الأسئلة</h3>
            <div class="stat-number"><?php echo $stats['total_questions']; ?></div>
            <div class="stat-label">إجمالي الأسئلة</div>
        </div>
        <div class="stat-card">
            <h3>البلاغات</h3>
            <div class="stat-number"><?php echo $stats['total_reports']; ?></div>
            <div class="stat-label">بلاغات تنتظر المعالجة</div>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <div class="admin-tabs">
        <button class="tab-btn <?php echo $tab === 'reviews' ? 'active' : ''; ?>" onclick="showTab('reviews')">المراجعات</button>
        <button class="tab-btn <?php echo $tab === 'questions' ? 'active' : ''; ?>" onclick="showTab('questions')">الأسئلة والأجوبة</button>
        <button class="tab-btn <?php echo $tab === 'reports' ? 'active' : ''; ?>" onclick="showTab('reports')">البلاغات</button>
    </div>
    
    <!-- Reviews Tab -->
    <div id="reviews-tab" class="tab-content <?php echo $tab === 'reviews' ? 'active' : ''; ?>">
        <div class="tab-header">
            <h2>إدارة المراجعات</h2>
            <div class="filters">
                <select id="review-status-filter" onchange="filterReviews()">
                    <option value="">جميع الحالات</option>
                    <option value="pending">في الانتظار</option>
                    <option value="approved">معتمدة</option>
                    <option value="rejected">مرفوضة</option>
                    <option value="spam">سبام</option>
                </select>
            </div>
        </div>
        
        <div class="reviews-list">
            <?php
            $reviews_stmt = $pdo->prepare('
                SELECT r.*, p.name as product_name, u.name as user_name, s.store_name as seller_name
                FROM reviews r
                LEFT JOIN products p ON r.product_id = p.id
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN sellers s ON p.seller_id = s.user_id
                ORDER BY r.created_at DESC
                LIMIT 50
            ');
            $reviews_stmt->execute();
            $reviews = $reviews_stmt->fetchAll();
            ?>
            
            <?php foreach ($reviews as $review): ?>
                <div class="review-item" data-status="<?php echo $review['status']; ?>">
                    <div class="review-header">
                        <div class="review-info">
                            <h4><?php echo htmlspecialchars($review['product_name']); ?></h4>
                            <div class="review-meta">
                                <span class="user-name"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                <span class="rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5-$review['rating']); ?></span>
                                <span class="date"><?php echo date('j M Y', strtotime($review['created_at'])); ?></span>
                                <span class="status-badge status-<?php echo $review['status']; ?>"><?php echo $review['status']; ?></span>
                            </div>
                        </div>
                        <div class="review-actions">
                            <button onclick="openReviewModal(<?php echo $review['id']; ?>)" class="btn btn-primary">عرض التفاصيل</button>
                        </div>
                    </div>
                    
                    <?php if (!empty($review['review_title'])): ?>
                        <div class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></div>
                    <?php endif; ?>
                    
                    <div class="review-content"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                    
                    <?php if ($review['helpful_votes'] > 0 || $review['unhelpful_votes'] > 0): ?>
                        <div class="review-votes">
                            <span>👍 <?php echo $review['helpful_votes']; ?></span>
                            <span>👎 <?php echo $review['unhelpful_votes']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Questions Tab -->
    <div id="questions-tab" class="tab-content <?php echo $tab === 'questions' ? 'active' : ''; ?>">
        <div class="tab-header">
            <h2>إدارة الأسئلة والأجوبة</h2>
            <div class="filters">
                <select id="question-status-filter" onchange="filterQuestions()">
                    <option value="">جميع الحالات</option>
                    <option value="pending">في الانتظار</option>
                    <option value="approved">معتمدة</option>
                    <option value="rejected">مرفوضة</option>
                </select>
            </div>
        </div>
        
        <div class="questions-list">
            <?php
            $questions_stmt = $pdo->prepare('
                SELECT q.*, p.name as product_name, u.name as user_name, COUNT(a.id) as answer_count
                FROM review_questions q
                LEFT JOIN products p ON q.product_id = p.id
                LEFT JOIN users u ON q.user_id = u.id
                LEFT JOIN review_answers a ON q.id = a.question_id
                GROUP BY q.id
                ORDER BY q.created_at DESC
                LIMIT 50
            ');
            $questions_stmt->execute();
            $questions = $questions_stmt->fetchAll();
            ?>
            
            <?php foreach ($questions as $question): ?>
                <div class="question-item" data-status="<?php echo $question['status']; ?>">
                    <div class="question-header">
                        <div class="question-info">
                            <h4><?php echo htmlspecialchars($question['product_name']); ?></h4>
                            <div class="question-meta">
                                <span class="user-name"><?php echo $question['is_anonymous'] ? 'مجهول' : htmlspecialchars($question['user_name']); ?></span>
                                <span class="date"><?php echo date('j M Y', strtotime($question['created_at'])); ?></span>
                                <span class="answer-count"><?php echo $question['answer_count']; ?> إجابة</span>
                                <span class="status-badge status-<?php echo $question['status']; ?>"><?php echo $question['status']; ?></span>
                            </div>
                        </div>
                        <div class="question-actions">
                            <button onclick="openQuestionModal(<?php echo $question['id']; ?>)" class="btn btn-primary">عرض التفاصيل</button>
                        </div>
                    </div>
                    
                    <div class="question-content"><?php echo nl2br(htmlspecialchars($question['question'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Reports Tab -->
    <div id="reports-tab" class="tab-content <?php echo $tab === 'reports' ? 'active' : ''; ?>">
        <div class="tab-header">
            <h2>إدارة البلاغات</h2>
        </div>
        
        <div class="reports-list">
            <?php
            $reports_stmt = $pdo->prepare('
                SELECT rr.*, r.comment as review_comment, u.name as reporter_name, ru.name as review_author
                FROM review_reports rr
                LEFT JOIN reviews r ON rr.review_id = r.id
                LEFT JOIN users u ON rr.reporter_id = u.id
                LEFT JOIN users ru ON r.user_id = ru.id
                WHERE rr.status = "pending"
                ORDER BY rr.created_at DESC
            ');
            $reports_stmt->execute();
            $reports = $reports_stmt->fetchAll();
            ?>
            
            <?php foreach ($reports as $report): ?>
                <div class="report-item">
                    <div class="report-header">
                        <div class="report-info">
                            <h4>بلاغ من <?php echo htmlspecialchars($report['reporter_name']); ?></h4>
                            <div class="report-meta">
                                <span class="reason">السبب: <?php echo $report['reason']; ?></span>
                                <span class="date"><?php echo date('j M Y', strtotime($report['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <button onclick="openReportModal(<?php echo $report['id']; ?>)" class="btn btn-primary">عرض التفاصيل</button>
                        </div>
                    </div>
                    
                    <?php if (!empty($report['description'])): ?>
                        <div class="report-description"><?php echo nl2br(htmlspecialchars($report['description'])); ?></div>
                    <?php endif; ?>
                    
                    <div class="review-preview">
                        <strong>المراجعة المبلغ عنها:</strong><br>
                        <em><?php echo htmlspecialchars($report['review_comment']); ?></em>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>تفاصيل المراجعة</h2>
        <div id="reviewModalContent"></div>
    </div>
</div>

<!-- Question Modal -->
<div id="questionModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>تفاصيل السؤال</h2>
        <div id="questionModalContent"></div>
    </div>
</div>

<!-- Report Modal -->
<div id="reportModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>تفاصيل البلاغ</h2>
        <div id="reportModalContent"></div>
    </div>
</div>

<style>
.admin-container { max-width: 1200px; margin: 20px auto; padding: 20px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
.stat-number { font-size: 2em; font-weight: bold; color: var(--primary-color); }
.stat-label { color: #666; margin-top: 5px; }

.admin-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
.tab-btn { background: #f0f0f0; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
.tab-btn.active { background: var(--primary-color); color: #fff; }

.tab-content { display: none; }
.tab-content.active { display: block; }

.tab-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.filters select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }

.review-item, .question-item, .report-item { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 15px; }
.review-header, .question-header, .report-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
.review-meta, .question-meta, .report-meta { display: flex; gap: 15px; align-items: center; color: #666; font-size: 0.9em; }
.status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
.status-pending { background: #fff3cd; color: #856404; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-spam { background: #f5c6cb; color: #721c24; }

.review-content, .question-content, .report-description { color: #333; line-height: 1.5; margin-bottom: 10px; }
.review-votes { display: flex; gap: 15px; color: #666; font-size: 0.9em; }

.btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
.btn-primary { background: var(--primary-color); color: #fff; }

.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color: #fff; margin: 5% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.close:hover { color: #000; }
</style>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

function filterReviews() {
    const filter = document.getElementById('review-status-filter').value;
    document.querySelectorAll('.review-item').forEach(item => {
        if (!filter || item.dataset.status === filter) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterQuestions() {
    const filter = document.getElementById('question-status-filter').value;
    document.querySelectorAll('.question-item').forEach(item => {
        if (!filter || item.dataset.status === filter) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function openReviewModal(reviewId) {
    // Load review details via AJAX
    fetch('reviews_management.php?action=get_review&review_id=' + reviewId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('reviewModalContent').innerHTML = html;
            document.getElementById('reviewModal').style.display = 'block';
        });
}

function openQuestionModal(questionId) {
    // Load question details via AJAX
    fetch('reviews_management.php?action=get_question&question_id=' + questionId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('questionModalContent').innerHTML = html;
            document.getElementById('questionModal').style.display = 'block';
        });
}

function openReportModal(reportId) {
    // Load report details via AJAX
    fetch('reviews_management.php?action=get_report&report_id=' + reportId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('reportModalContent').innerHTML = html;
            document.getElementById('reportModal').style.display = 'block';
        });
}

// Close modals
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Show initial tab
document.addEventListener('DOMContentLoaded', function() {
    showTab('<?php echo $tab; ?>');
});
</script>

<?php include 'admin_footer.php'; ?> 