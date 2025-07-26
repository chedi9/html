<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();

if (!$seller) {
    echo 'You are not a seller.';
    exit();
}

// Get categories for the template
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Create CSV content
$csv_content = "name,description,price,stock,category_id,image_url\n";
$csv_content .= "iPhone 13 Pro,Latest iPhone with amazing camera and A15 chip,999.99,50,1,https://example.com/iphone.jpg\n";
$csv_content .= "Samsung Galaxy S21,5G smartphone with 8K video recording,899.99,30,1,https://example.com/samsung.jpg\n";
$csv_content .= "MacBook Pro 14,Professional laptop with M1 Pro chip,1999.99,20,2,https://example.com/macbook.jpg\n";

// Add category reference at the end
$csv_content .= "\n# Category Reference:\n";
foreach ($categories as $category) {
    $csv_content .= "# ID {$category['id']}: {$category['name']}\n";
}

// Set headers for file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="product_upload_template.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output the CSV content
echo $csv_content;
exit();
?> 