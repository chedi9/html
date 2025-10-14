<?php
session_start();
require 'db.php';
require 'lang.php';

// Security headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$review_title = trim($_POST['review_title'] ?? '');
$overall_rating = floatval($_POST['overall_rating'] ?? 0);
$quality_rating = floatval($_POST['quality_rating'] ?? 0);
$value_rating = floatval($_POST['value_rating'] ?? 0);
$delivery_rating = floatval($_POST['delivery_rating'] ?? 0);

// Validation
if (!$product_id || $rating < 1 || $rating > 5) {
    header('Location: product.php?id=' . $product_id . '&review_error=invalid');
    exit();
}

$name = 'زائر';
$user_id = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $name = $user['name'];
        $user_id = $_SESSION['user_id'];
        
        // Check if user purchased this product
        $check = $pdo->prepare('SELECT 1 FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? LIMIT 1');
        $check->execute([$user_id, $product_id]);
        $is_verified_purchase = $check->fetch() ? 1 : 0;
    }
}

// Handle file uploads for review images
$uploaded_images = [];
if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
    $upload_dir = 'uploads/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    foreach ($_FILES['review_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['review_images']['error'][$key] === UPLOAD_ERR_OK) {
            $file_type = $_FILES['review_images']['type'][$key];
            $file_size = $_FILES['review_images']['size'][$key];
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                $file_extension = pathinfo($_FILES['review_images']['name'][$key], PATHINFO_EXTENSION);
                $file_name = uniqid('review_') . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploaded_images[] = [
                        'path' => $file_path,
                        'name' => $_FILES['review_images']['name'][$key],
                        'size' => $file_size
                    ];
                }
            }
        }
    }
}

function render_enhanced_reviews_html($pdo, $product_id) {
    // Get all user_ids who bought this product
    $buyers = [];
    $buyer_stmt = $pdo->prepare('SELECT DISTINCT o.user_id FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE oi.product_id = ?');
    $buyer_stmt->execute([$product_id]);
    foreach ($buyer_stmt->fetchAll() as $row) {
        if ($row['user_id']) $buyers[] = $row['user_id'];
    }
    
    ob_start();
    
    // Get reviews with images and seller responses
    $stmt = $pdo->prepare('
        SELECT r.*, 
               GROUP_CONCAT(ri.image_path ORDER BY ri.sort_order, ri.id) as images,
               GROUP_CONCAT(ri.image_name ORDER BY ri.sort_order, ri.id) as image_names
        FROM reviews r 
        LEFT JOIN review_images ri ON r.id = ri.review_id 
        WHERE r.product_id = ? AND r.status = "approved"
        GROUP BY r.id 
        ORDER BY r.helpful_votes DESC, r.created_at DESC
    ');
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    
    if ($reviews) {
        foreach ($reviews as $rev) {
            $is_verified = $rev['is_verified_purchase'] || ($rev['user_id'] && in_array($rev['user_id'], $buyers));
            $images = $rev['images'] ? explode(',', $rev['images']) : [];
            $image_names = $rev['image_names'] ? explode(',', $rev['image_names']) : [];
            
            echo '<div class="review-item">';
            
            // Review header
            echo '<div>';
            echo '<span>' . htmlspecialchars($rev['name']) . '</span>';
            if ($is_verified) {
                echo '<span class="verified-badge">✔ مشتري موثوق</span>';
            }
            echo '<span>' . str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5-(int)$rev['rating']) . '</span>';
            echo '</div>';
            
            // Review title
            if (!empty($rev['review_title'])) {
                echo '<h4>' . htmlspecialchars($rev['review_title']) . '</h4>';
            }
            
            // Detailed ratings
            if ($rev['overall_rating'] || $rev['quality_rating'] || $rev['value_rating'] || $rev['delivery_rating']) {
                echo '<div>';
                if ($rev['overall_rating']) echo '<div><small>التقييم العام:</small><br><strong>' . number_format($rev['overall_rating'], 1) . '/5</strong></div>';
                if ($rev['quality_rating']) echo '<div><small>الجودة:</small><br><strong>' . number_format($rev['quality_rating'], 1) . '/5</strong></div>';
                if ($rev['value_rating']) echo '<div><small>القيمة:</small><br><strong>' . number_format($rev['value_rating'], 1) . '/5</strong></div>';
                if ($rev['delivery_rating']) echo '<div><small>التوصيل:</small><br><strong>' . number_format($rev['delivery_rating'], 1) . '/5</strong></div>';
                echo '</div>';
            }
            
            // Review comment
            if (!empty($rev['comment'])) {
                echo '<div>' . nl2br(htmlspecialchars($rev['comment'])) . '</div>';
            }
            
            // Review images
            if (!empty($images)) {
                echo '<div>';
                echo '<div>';
                foreach ($images as $index => $image_path) {
                    $image_name = $image_names[$index] ?? 'صورة المراجعة';
                    echo '<div>';
                    echo '<img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($image_name) . '" onclick="openImageModal(\'' . htmlspecialchars($image_path) . '\', \'' . htmlspecialchars($image_name) . '\')">';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
            
            // Seller response
            if (!empty($rev['seller_response'])) {
                echo '<div>';
                echo '<div>رد البائع:</div>';
                echo '<div>' . nl2br(htmlspecialchars($rev['seller_response'])) . '</div>';
                if ($rev['seller_response_date']) {
                    echo '<div>' . date('j M Y', strtotime($rev['seller_response_date'])) . '</div>';
                }
                echo '</div>';
            }
            
            // Review footer
            echo '<div>';
            echo '<div>' . $rev['created_at'] . '</div>';
            
            // Helpfulness votes
            echo '<div>';
            echo '<button onclick="voteReview(' . $rev['id'] . ', \'helpful\')" class="vote-btn helpful" data-review-id="' . $rev['id'] . '" data-vote-type="helpful">';
            echo '👍 مفيد (' . $rev['helpful_votes'] . ')';
            echo '</button>';
            echo '<button onclick="voteReview(' . $rev['id'] . ', \'unhelpful\')" class="vote-btn unhelpful" data-review-id="' . $rev['id'] . '" data-vote-type="unhelpful">';
            echo '👎 غير مفيد (' . $rev['unhelpful_votes'] . ')';
            echo '</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
        }
    } else {
        echo '<p>لا توجد مراجعات بعد.</p>';
    }
    
    return ob_get_clean();
}

if ($user_id) {
    // Check if user already reviewed this product
    $existing = $pdo->prepare('SELECT id FROM reviews WHERE product_id = ? AND user_id = ?');
    $existing->execute([$product_id, $user_id]);
    $review_id = $existing->fetchColumn();
    
    try {
        $pdo->beginTransaction();
        
        if ($review_id) {
            // Update existing review
            $stmt = $pdo->prepare('
                UPDATE reviews SET 
                rating = ?, comment = ?, review_title = ?, 
                overall_rating = ?, quality_rating = ?, value_rating = ?, delivery_rating = ?,
                is_verified_purchase = ?, updated_at = NOW() 
                WHERE id = ?
            ');
            $stmt->execute([
                $rating, $comment, $review_title,
                $overall_rating, $quality_rating, $value_rating, $delivery_rating,
                $is_verified_purchase, $review_id
            ]);
        } else {
            // Insert new review
            $stmt = $pdo->prepare('
                INSERT INTO reviews (product_id, user_id, name, rating, comment, review_title, 
                overall_rating, quality_rating, value_rating, delivery_rating, is_verified_purchase) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $product_id, $user_id, $name, $rating, $comment, $review_title,
                $overall_rating, $quality_rating, $value_rating, $delivery_rating, $is_verified_purchase
            ]);
            $review_id = $pdo->lastInsertId();
        }
        
        // Handle review images
        if (!empty($uploaded_images)) {
            // Delete existing images for this review
            $pdo->prepare('DELETE FROM review_images WHERE review_id = ?')->execute([$review_id]);
            
            // Insert new images
            $image_stmt = $pdo->prepare('
                INSERT INTO review_images (review_id, image_path, image_name, image_size, sort_order) 
                VALUES (?, ?, ?, ?, ?)
            ');
            
            foreach ($uploaded_images as $index => $image) {
                $image_stmt->execute([
                    $review_id, $image['path'], $image['name'], $image['size'], $index
                ]);
            }
        }
        
        $pdo->commit();
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'success' => true, 
                'reviews_html' => render_enhanced_reviews_html($pdo, $product_id)
            ]);
            exit();
        }
        
        header('Location: product.php?id=' . $product_id . '#reviews');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Review submission error: ' . $e->getMessage());
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['error' => 'حدث خطأ أثناء حفظ المراجعة.']);
            exit();
        }
        
        header('Location: product.php?id=' . $product_id . '&review_error=system');
        exit();
    }
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['error' => 'يجب تسجيل الدخول لكتابة مراجعة.']);
    exit();
}

header('Location: product.php?id=' . $product_id . '#reviews');
exit();
?> 