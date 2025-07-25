<?php
require 'db.php';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    echo json_encode(['products' => [], 'categories' => []]);
    exit();
}
// Product suggestions
$stmt = $pdo->prepare('SELECT id, name FROM products WHERE name LIKE ? ORDER BY name LIMIT 10');
$stmt->execute(['%' . $q . '%']);
$productResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Category suggestions
$stmt2 = $pdo->prepare('SELECT id, name FROM categories WHERE name LIKE ? ORDER BY name LIMIT 5');
$stmt2->execute(['%' . $q . '%']);
$categoryResults = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo json_encode([
    'products' => $productResults,
    'categories' => $categoryResults
]);
exit(); 