<?php
session_start();
require 'db.php';
require 'lang.php';

// Security headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

if (!$product_id) {
    header('Location: index.php');
    exit();
}

// Get product info
$product_stmt = $pdo->prepare('SELECT p.*, s.store_name FROM products p LEFT JOIN sellers s ON p.seller_id = s.user_id WHERE p.id = ?');
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit();
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'ask_question':
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'يجب تسجيل الدخول لطرح سؤال.'];
                break;
            }
            
            $question = trim($_POST['question'] ?? '');
            $is_anonymous = intval($_POST['is_anonymous'] ?? 0);
            
            if (empty($question)) {
                $response = ['success' => false, 'message' => 'يرجى كتابة السؤال.'];
                break;
            }
            
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO review_questions (product_id, user_id, question, is_anonymous, status) 
                    VALUES (?, ?, ?, ?, "approved")
                ');
                $stmt->execute([$product_id, $_SESSION['user_id'], $question, $is_anonymous]);
                
                $response = ['success' => true, 'message' => 'تم إرسال السؤال بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء إرسال السؤال.'];
            }
            break;
            
        case 'answer_question':
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'يجب تسجيل الدخول للإجابة على السؤال.'];
                break;
            }
            
            $question_id = intval($_POST['question_id'] ?? 0);
            $answer = trim($_POST['answer'] ?? '');
            $is_anonymous = intval($_POST['is_anonymous'] ?? 0);
            
            if (!$question_id || empty($answer)) {
                $response = ['success' => false, 'message' => 'يرجى كتابة الإجابة.'];
                break;
            }
            
            // Check if user is the seller
            $seller_check = $pdo->prepare('SELECT 1 FROM sellers WHERE user_id = ?');
            $seller_check->execute([$_SESSION['user_id']]);
            $is_seller_answer = $seller_check->fetch() ? 1 : 0;
            $seller_id = $is_seller_answer ? $_SESSION['user_id'] : null;
            
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO review_answers (question_id, user_id, seller_id, answer, is_seller_answer, is_anonymous, status) 
                    VALUES (?, ?, ?, ?, ?, ?, "approved")
                ');
                $stmt->execute([$question_id, $_SESSION['user_id'], $seller_id, $answer, $is_seller_answer, $is_anonymous]);
                
                $response = ['success' => true, 'message' => 'تم إرسال الإجابة بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء إرسال الإجابة.'];
            }
            break;
            
        case 'vote_answer':
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => 'يجب تسجيل الدخول للتصويت.'];
                break;
            }
            
            $answer_id = intval($_POST['answer_id'] ?? 0);
            $vote_type = $_POST['vote_type'] ?? '';
            
            if (!$answer_id || !in_array($vote_type, ['helpful', 'unhelpful'])) {
                $response = ['success' => false, 'message' => 'تصويت غير صحيح.'];
                break;
            }
            
            try {
                // Check if user already voted
                $existing_vote = $pdo->prepare('SELECT id, vote_type FROM answer_votes WHERE answer_id = ? AND user_id = ?');
                $existing_vote->execute([$answer_id, $_SESSION['user_id']]);
                $current_vote = $existing_vote->fetch();
                
                if ($current_vote) {
                    if ($current_vote['vote_type'] === $vote_type) {
                        // Remove vote
                        $pdo->prepare('DELETE FROM answer_votes WHERE id = ?')->execute([$current_vote['id']]);
                        $pdo->prepare('UPDATE review_answers SET ' . $vote_type . '_votes = ' . $vote_type . '_votes - 1 WHERE id = ?')->execute([$answer_id]);
                    } else {
                        // Change vote
                        $pdo->prepare('UPDATE answer_votes SET vote_type = ? WHERE id = ?')->execute([$vote_type, $current_vote['id']]);
                        $pdo->prepare('UPDATE review_answers SET ' . $current_vote['vote_type'] . '_votes = ' . $current_vote['vote_type'] . '_votes - 1, ' . $vote_type . '_votes = ' . $vote_type . '_votes + 1 WHERE id = ?')->execute([$answer_id]);
                    }
                } else {
                    // New vote
                    $pdo->prepare('INSERT INTO answer_votes (answer_id, user_id, vote_type) VALUES (?, ?, ?)')->execute([$answer_id, $_SESSION['user_id'], $vote_type]);
                    $pdo->prepare('UPDATE review_answers SET ' . $vote_type . '_votes = ' . $vote_type . '_votes + 1 WHERE id = ?')->execute([$answer_id]);
                }
                
                $response = ['success' => true, 'message' => 'تم التصويت بنجاح.'];
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'حدث خطأ أثناء التصويت.'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'إجراء غير معروف.'];
    }
    
    echo json_encode($response);
    exit();
}

// Get questions and answers for this product
function getProductQA($pdo, $product_id) {
    $stmt = $pdo->prepare('
        SELECT 
            q.*,
            u.name as user_name,
            COUNT(a.id) as answer_count,
            GROUP_CONCAT(DISTINCT a.id ORDER BY a.is_seller_answer DESC, a.helpful_votes DESC, a.created_at ASC) as answer_ids
        FROM review_questions q
        LEFT JOIN users u ON q.user_id = u.id
        LEFT JOIN review_answers a ON q.id = a.question_id AND a.status = "approved"
        WHERE q.product_id = ? AND q.status = "approved"
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ');
    $stmt->execute([$product_id]);
    $questions = $stmt->fetchAll();
    
    // Get answers for each question
    foreach ($questions as &$question) {
        if ($question['answer_ids']) {
            $answer_ids = explode(',', $question['answer_ids']);
            $placeholders = str_repeat('?,', count($answer_ids) - 1) . '?';
            
            $answer_stmt = $pdo->prepare('
                SELECT 
                    a.*,
                    u.name as user_name,
                    s.store_name as seller_name
                FROM review_answers a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN sellers s ON a.seller_id = s.user_id
                WHERE a.id IN (' . $placeholders . ') AND a.status = "approved"
                ORDER BY a.is_seller_answer DESC, a.helpful_votes DESC, a.created_at ASC
            ');
            $answer_stmt->execute($answer_ids);
            $question['answers'] = $answer_stmt->fetchAll();
        } else {
            $question['answers'] = [];
        }
    }
    
    return $questions;
}

$questions = getProductQA($pdo, $product_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الأسئلة والأجوبة - <?php echo htmlspecialchars($product['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="qa-container">
        <a href="product.php?id=<?php echo $product_id; ?>" class="back-btn">← العودة للمنتج</a>
        
        <div class="qa-header">
            <h1>الأسئلة والأجوبة</h1>
            <div class="product-info">
                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                <?php if ($product['store_name']): ?>
                    <br><small>من <?php echo htmlspecialchars($product['store_name']); ?></small>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="ask-question-section">
            <h3>اطرح سؤالاً</h3>
            <form class="question-form" id="questionForm">
                <input type="hidden" name="action" value="ask_question">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <textarea name="question" placeholder="اكتب سؤالك هنا..." required></textarea>
                
                <div class="form-row">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1">
                        <label for="is_anonymous">نشر السؤال بشكل مجهول</label>
                    </div>
                    <button type="submit">إرسال السؤال</button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="ask-question-section">
            <p>يجب <a href="login.php">تسجيل الدخول</a> لطرح سؤال.</p>
        </div>
        <?php endif; ?>
        
        <div class="questions-list">
            <?php if ($questions): ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-item">
                        <div class="question-header">
                            <div class="question-meta">
                                <span class="question-author">
                                    <?php echo $question['is_anonymous'] ? 'مستخدم مجهول' : htmlspecialchars($question['user_name']); ?>
                                </span>
                                <span class="question-date"><?php echo date('j M Y', strtotime($question['created_at'])); ?></span>
                            </div>
                            <span class="answer-count"><?php echo $question['answer_count']; ?> إجابة</span>
                        </div>
                        
                        <div class="question-text"><?php echo nl2br(htmlspecialchars($question['question'])); ?></div>
                        
                        <div class="answers-section">
                            <?php if ($question['answers']): ?>
                                <?php foreach ($question['answers'] as $answer): ?>
                                    <div class="answer-item">
                                        <div class="answer-header">
                                            <div class="answer-author">
                                                <?php if ($answer['is_seller_answer']): ?>
                                                    <span class="seller-badge">البائع</span>
                                                <?php endif; ?>
                                                <?php echo $answer['is_anonymous'] ? 'مستخدم مجهول' : htmlspecialchars($answer['user_name'] ?? $answer['seller_name']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="answer-text"><?php echo nl2br(htmlspecialchars($answer['answer'])); ?></div>
                                        
                                        <div class="answer-meta">
                                            <div class="answer-date"><?php echo date('j M Y', strtotime($answer['created_at'])); ?></div>
                                            <div class="answer-votes">
                                                <button class="vote-btn" onclick="voteAnswer(<?php echo $answer['id']; ?>, 'helpful')" data-answer-id="<?php echo $answer['id']; ?>" data-vote-type="helpful">
                                                    👍 مفيد (<?php echo $answer['helpful_votes']; ?>)
                                                </button>
                                                <button class="vote-btn" onclick="voteAnswer(<?php echo $answer['id']; ?>, 'unhelpful')" data-answer-id="<?php echo $answer['id']; ?>" data-vote-type="unhelpful">
                                                    👎 غير مفيد (<?php echo $answer['unhelpful_votes']; ?>)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="add-answer-section">
                                <button class="add-answer-btn" onclick="toggleAnswerForm(<?php echo $question['id']; ?>)">أضف إجابة</button>
                                
                                <form class="answer-form" id="answerForm<?php echo $question['id']; ?>">
                                    <input type="hidden" name="action" value="answer_question">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    
                                    <textarea name="answer" placeholder="اكتب إجابتك هنا..." required></textarea>
                                    
                                    <div class="form-row">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="is_anonymous_<?php echo $question['id']; ?>" name="is_anonymous" value="1">
                                            <label for="is_anonymous_<?php echo $question['id']; ?>">نشر الإجابة بشكل مجهول</label>
                                        </div>
                                        <button type="submit">إرسال الإجابة</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-questions">
                    <p>لا توجد أسئلة بعد.</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <p>كن أول من يطرح سؤالاً!</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Handle question form submission
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('product_qa.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إرسال السؤال.');
            });
        });
        
        // Handle answer form submission
        function submitAnswer(questionId) {
            const form = document.getElementById('answerForm' + questionId);
            const formData = new FormData(form);
            
            fetch('product_qa.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إرسال الإجابة.');
            });
        }
        
        // Toggle answer form visibility
        function toggleAnswerForm(questionId) {
            const form = document.getElementById('answerForm' + questionId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        // Handle answer voting
        function voteAnswer(answerId, voteType) {
            const formData = new FormData();
            formData.append('action', 'vote_answer');
            formData.append('answer_id', answerId);
            formData.append('vote_type', voteType);
            
            fetch('product_qa.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء التصويت.');
            });
        }
        
        // Add event listeners for answer forms
        document.addEventListener('DOMContentLoaded', function() {
            const answerForms = document.querySelectorAll('.answer-form');
            answerForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const questionId = this.querySelector('input[name="question_id"]').value;
                    submitAnswer(questionId);
                });
            });
        });
    </script>
</body>
</html> 