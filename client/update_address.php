<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';

$user_id = $_SESSION['user_id'];
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($address !== '' && $phone !== '') {
    $stmt = $pdo->prepare('UPDATE users SET address = ?, phone = ? WHERE id = ?');
    $stmt->execute([$address, $phone, $user_id]);
    $_SESSION['flash_message'] = __('address_updated');
} else {
    $_SESSION['flash_message'] = __('enter_address_and_phone');
}

header('Location: account.php');
exit();
?>