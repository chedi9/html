<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';

$user_id = $_SESSION['user_id'];
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' && $password === '') {
    $_SESSION['flash_message'] = __('enter_email_or_password');
    header('Location: account.php');
    exit();
}

if ($email !== '') {
    $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
    $stmt->execute([$email, $user_id]);
}
if ($password !== '') {
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$new_hash, $user_id]);
}

$_SESSION['flash_message'] = __('info_updated');
header('Location: account.php');
exit(); 