<?php
session_start();
require_once 'db.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Accept product ID from GET or POST
$product_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$is_ajax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
           (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0,
    'product_name' => ''
];

if ($product_id > 0) {
    try {
        // Get product details for the response
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ? AND approved = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
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
            
            // Calculate total cart count
            $cart_count = array_sum($_SESSION['cart']);
            
            // Set success response
            $response['success'] = true;
            $response['message'] = 'Product added to cart successfully!';
            $response['cart_count'] = $cart_count;
            $response['product_name'] = $product['name'];
            
            // Add success message to session for non-AJAX requests
            $_SESSION['cart_message'] = 'Product added to cart successfully!';
        } else {
            $response['message'] = 'Product not found or not available.';
        }
    } catch (Exception $e) {
        $response['message'] = 'An error occurred while adding the product to cart.';
    }
} else {
    $response['message'] = 'Invalid product ID.';
}

// Return JSON response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit();
}

// For non-AJAX requests, redirect as before
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php';
header('Location: ' . $redirect);
exit(); 