<?php
session_start();
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id || empty($_SESSION['wishlist'])) {
    header('Location: account.php');
    exit();
}
$key = array_search($id, $_SESSION['wishlist']);
if ($key !== false) {
    unset($_SESSION['wishlist'][$key]);
}
header('Location: account.php');
exit(); 