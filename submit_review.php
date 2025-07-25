<?php
session_start();
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}
$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
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
        if (!$check->fetch()) {
            header('Location: product.php?id=' . $product_id . '&review_error=not_purchased');
            exit();
        }
    }
}
function render_reviews_html($pdo, $product_id) {
    // Get all user_ids who bought this product
    $buyers = [];
    $buyer_stmt = $pdo->prepare('SELECT DISTINCT o.user_id FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE oi.product_id = ?');
    $buyer_stmt->execute([$product_id]);
    foreach ($buyer_stmt->fetchAll() as $row) {
        if ($row['user_id']) $buyers[] = $row['user_id'];
    }
    ob_start();
    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC');
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    if ($reviews) {
        foreach ($reviews as $rev) {
            $is_verified = $rev['user_id'] && in_array($rev['user_id'], $buyers);
            echo '<div style="background:#fff;border:1.5px solid #E3E7ED;border-radius:10px;padding:16px 18px 12px 18px;margin-bottom:18px;box-shadow:0 2px 8px rgba(26,35,126,0.04);">';
            echo '<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">';
            echo '<span style="font-weight:bold;color:#00BFAE;font-size:1.08em;">' . htmlspecialchars($rev['name']) . '</span>';
            if ($is_verified) echo '<span class="verified-badge" style="background:#FFD600;color:#23263a;font-size:0.95em;padding:2px 10px;border-radius:8px;font-weight:bold;margin-right:6px;">✔ مشتري موثوق</span>';
            echo '<span style="color:#FFD600;font-size:1.15em;letter-spacing:1px;">' . str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5-(int)$rev['rating']) . '</span>';
            echo '</div>';
            if (!empty($rev['comment'])) echo '<div style="margin-bottom:6px;font-size:1.08em;color:#222;">' . nl2br(htmlspecialchars($rev['comment'])) . '</div>';
            echo '<div style="color:#888;font-size:0.97em;text-align:left;">' . $rev['created_at'] . '</div>';
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
    if ($review_id) {
        // Update existing review
        $stmt = $pdo->prepare('UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?');
        $stmt->execute([$rating, $comment, $review_id]);
    } else {
        // Insert new review
        $stmt = $pdo->prepare('INSERT INTO reviews (product_id, user_id, name, rating, comment) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$product_id, $user_id, $name, $rating, $comment]);
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true, 'reviews_html' => render_reviews_html($pdo, $product_id)]);
        exit();
    }
    header('Location: product.php?id=' . $product_id . '#reviews');
    exit();
}
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['error' => 'حدث خطأ.']);
    exit();
}
header('Location: product.php?id=' . $product_id . '#reviews');
exit(); 