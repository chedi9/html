<?php
session_start();
header('Content-Type: application/json');
require 'db.php';
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
    $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')->execute([$user_id, $product_id]);
    echo json_encode(['status' => 'removed']);
    exit;
  } else {
    $pdo->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)')->execute([$user_id, $product_id]);
    echo json_encode(['status' => 'added']);
    exit;
  }
} elseif ($action === 'remove') {
  $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')->execute([$user_id, $product_id]);
  echo json_encode(['status' => 'removed']);
  exit;
}
echo json_encode(['status' => 'error']); 