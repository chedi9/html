<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

// Add error logging
error_log("Wishlist action called: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['status' => 'login']);
  exit;
}
$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$product_id) {
  echo json_encode(['status' => 'error']);
  exit;
}
if ($action === 'toggle') {
  $exists = $pdo->prepare('SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?');
  $exists->execute([$user_id, $product_id]);
  if ($exists->fetch()) {
    $delete_stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
    $delete_result = $delete_stmt->execute([$user_id, $product_id]);
    error_log("Wishlist delete result: " . ($delete_result ? 'success' : 'failed'));
    echo json_encode(['status' => 'removed']);
    exit;
  } else {
    $insert_stmt = $pdo->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)');
    $insert_result = $insert_stmt->execute([$user_id, $product_id]);
    error_log("Wishlist insert result: " . ($insert_result ? 'success' : 'failed'));
    if (!$insert_result) {
      error_log("Wishlist insert error: " . print_r($insert_stmt->errorInfo(), true));
    }
    echo json_encode(['status' => 'added']);
    exit;
  }
} elseif ($action === 'remove') {
  $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')->execute([$user_id, $product_id]);
  echo json_encode(['status' => 'removed']);
  exit;
}
echo json_encode(['status' => 'error']); 