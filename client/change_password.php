<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($new_password !== $confirm_password) {
    $_SESSION['flash_message'] = __('passwords_do_not_match');
    header('Location: account.php');
    exit();
}

$stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !password_verify($current_password, $user['password'])) {
    $_SESSION['flash_message'] = __('current_password_incorrect');
    header('Location: account.php');
    exit();
}

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
$stmt->execute([$new_hash, $user_id]);

$_SESSION['flash_message'] = __('password_changed');
header('Location: account.php');
exit(); 