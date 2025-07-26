<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) {
    die('Not logged in.');
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) die('Not a seller.');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=orders_export.csv');
$output = fopen('php://output', 'w');
fputcsv($output, ['Order ID', 'Date', 'Status', 'Product', 'Quantity', 'Price', 'Subtotal']);
$sql = 'SELECT o.id as order_id, o.created_at, o.status, p.name, oi.quantity, oi.price, (oi.price * oi.quantity) as subtotal
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ?
        ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$seller['id']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [$row['order_id'], $row['created_at'], $row['status'], $row['name'], $row['quantity'], $row['price'], $row['subtotal']]);
}
fclose($output);
exit; 