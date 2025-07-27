<?php
session_start();
require 'db.php';

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول للتصويت.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة.']);
    exit();
}

$review_id = intval($_POST['review_id'] ?? 0);
$vote_type = $_POST['vote_type'] ?? '';

if (!$review_id || !in_array($vote_type, ['helpful', 'unhelpful'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات التصويت غير صحيحة.']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Check if review exists and is approved
    $review_check = $pdo->prepare('SELECT id FROM reviews WHERE id = ? AND status = "approved"');
    $review_check->execute([$review_id]);
    if (!$review_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'المراجعة غير موجودة أو غير معتمدة.']);
        exit();
    }
    
    // Check if user already voted on this review
    $existing_vote = $pdo->prepare('SELECT id, vote_type FROM review_votes WHERE review_id = ? AND user_id = ?');
    $existing_vote->execute([$review_id, $user_id]);
    $current_vote = $existing_vote->fetch();
    
    $pdo->beginTransaction();
    
    if ($current_vote) {
        if ($current_vote['vote_type'] === $vote_type) {
            // Remove vote (user clicked the same vote type again)
            $pdo->prepare('DELETE FROM review_votes WHERE id = ?')->execute([$current_vote['id']]);
            $pdo->prepare('UPDATE reviews SET ' . $vote_type . '_votes = ' . $vote_type . '_votes - 1 WHERE id = ?')->execute([$review_id]);
            
            $message = 'تم إلغاء التصويت.';
        } else {
            // Change vote (user changed from helpful to unhelpful or vice versa)
            $pdo->prepare('UPDATE review_votes SET vote_type = ? WHERE id = ?')->execute([$vote_type, $current_vote['id']]);
            $pdo->prepare('UPDATE reviews SET ' . $current_vote['vote_type'] . '_votes = ' . $current_vote['vote_type'] . '_votes - 1, ' . $vote_type . '_votes = ' . $vote_type . '_votes + 1 WHERE id = ?')->execute([$review_id]);
            
            $message = 'تم تغيير التصويت.';
        }
    } else {
        // New vote
        $pdo->prepare('INSERT INTO review_votes (review_id, user_id, vote_type) VALUES (?, ?, ?)')->execute([$review_id, $user_id, $vote_type]);
        $pdo->prepare('UPDATE reviews SET ' . $vote_type . '_votes = ' . $vote_type . '_votes + 1 WHERE id = ?')->execute([$review_id]);
        
        $message = 'تم التصويت بنجاح.';
    }
    
    // Get updated vote counts
    $vote_counts = $pdo->prepare('SELECT helpful_votes, unhelpful_votes FROM reviews WHERE id = ?');
    $vote_counts->execute([$review_id]);
    $counts = $vote_counts->fetch();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'helpful_votes' => $counts['helpful_votes'],
        'unhelpful_votes' => $counts['unhelpful_votes']
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Review vote error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التصويت.']);
}
?> 