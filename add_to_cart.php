<?php
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$variant_key = isset($_GET['variant_key']) ? $_GET['variant_key'] : (isset($_POST['variant_key']) ? $_POST['variant_key'] : '');
$cart_key = $id > 0 ? $id . ($variant_key ? '|' . $variant_key : '') : '';

// Debug logging
error_log("Add to cart - ID: $id, Variant: $variant_key, Cart Key: $cart_key");

if ($id > 0) {
    if ($cart_key && isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]++;
    } elseif ($cart_key) {
        $_SESSION['cart'][$cart_key] = 1;
    }
    $_SESSION['flash_message'] = 'تمت إضافة المنتج إلى السلة!';
}

$cart_count = array_sum($_SESSION['cart']);
error_log("Cart contents: " . print_r($_SESSION['cart'], true));
error_log("Cart count: $cart_count");

// If AJAX request, return JSON
if (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    exit();
}
// Otherwise, normal redirect
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $redirect);
exit(); 