<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include language support
if (!function_exists('__')) {
    require_once 'lang.php';
}

require_once 'db.php';

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total = 0;

if (!empty($cart)) {
    try {
        foreach ($cart as $cart_key => $item) {
            $product_id = $item['id'];
            
            // Get product details from database
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $price = floatval($item['price']);
                $quantity = intval($item['qty']);
                $subtotal = $price * $quantity;
                
                // Get product image
                $image_path = 'uploads/products/' . $product['id'] . '_1.jpg';
                if (!file_exists($image_path)) {
                    $image_path = 'uploads/products/default.jpg';
                }
                
                $cart_items[] = [
                    'id' => $product['id'],
                    'name' => htmlspecialchars($product['name']),
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'image' => $image_path,
                    'variant' => $item['variant'] ?? ''
                ];
                
                $total += $subtotal;
            }
        }
    } catch (Exception $e) {
        error_log("Cart preview error: " . $e->getMessage());
    }
}

$response = [
    'success' => true,
    'items' => $cart_items,
    'total' => $total,
    'count' => count($cart_items),
    'currency' => __('currency'),
    'empty_message' => ($lang ?? 'en') === 'ar' ? 'عربة التسوق فارغة' : 'Your cart is empty',
    'total_label' => ($lang ?? 'en') === 'ar' ? 'المجموع:' : 'Total:'
];

echo json_encode($response);
?>