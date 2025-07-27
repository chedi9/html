<?php
session_start();

// Accept product ID from GET or POST
$product_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($product_id > 0) {
    // Initialize cart if not set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    // Add or increment product in cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    // Optional: Add a success message
    $_SESSION['cart_message'] = 'تمت إضافة المنتج إلى السلة بنجاح!';
}

// Redirect back to referring page or cart
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php';
header('Location: ' . $redirect);
exit(); 