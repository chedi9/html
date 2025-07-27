<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user wallet information
    $stmt = $pdo->prepare("
        SELECT wallet_balance, loyalty_points, total_spent 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user_wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'balance' => $user_wallet['wallet_balance'] ?? 0,
        'points' => $user_wallet['loyalty_points'] ?? 0,
        'total_spent' => $user_wallet['total_spent'] ?? 0
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?> 