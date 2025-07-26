<?php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search in product names and descriptions
    $sql = "SELECT DISTINCT p.name, p.name_ar, p.name_en, p.name_fr, 'product' as type
            FROM products p 
            WHERE p.approved = 1 
            AND (p.name LIKE ? OR p.name_ar LIKE ? OR p.name_en LIKE ? OR p.name_fr LIKE ?)
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();
    
    // Search in categories
    $sql2 = "SELECT DISTINCT c.name, c.name_ar, c.name_en, c.name_fr, 'category' as type
             FROM categories c
             WHERE c.name LIKE ? OR c.name_ar LIKE ? OR c.name_en LIKE ? OR c.name_fr LIKE ?
             LIMIT 5";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $categories = $stmt2->fetchAll();
    
    // Search in seller store names
    $sql3 = "SELECT DISTINCT s.store_name as name, s.store_name as name_ar, s.store_name as name_en, s.store_name as name_fr, 'brand' as type
             FROM sellers s
             JOIN products p ON s.id = p.seller_id
             WHERE p.approved = 1 
             AND s.store_name LIKE ?
             LIMIT 5";
    
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute([$searchTerm]);
    $brands = $stmt3->fetchAll();
    
    // Combine and format results
    $suggestions = [];
    
    foreach ($products as $product) {
        $name = $product['name_' . $lang] ?? $product['name'];
        $suggestions[] = [
            'name' => $name,
            'type' => 'product',
            'icon' => '📦'
        ];
    }
    
    foreach ($categories as $category) {
        $name = $category['name_' . $lang] ?? $category['name'];
        $suggestions[] = [
            'name' => $name,
            'type' => 'category',
            'icon' => '📂'
        ];
    }
    
    foreach ($brands as $brand) {
        $name = $brand['name_' . $lang] ?? $brand['name'];
        $suggestions[] = [
            'name' => $name,
            'type' => 'brand',
            'icon' => '🏪'
        ];
    }
    
    // Remove duplicates and limit total results
    $unique = [];
    $seen = [];
    foreach ($suggestions as $suggestion) {
        if (!in_array($suggestion['name'], $seen)) {
            $unique[] = $suggestion;
            $seen[] = $suggestion['name'];
        }
        if (count($unique) >= 15) break;
    }
    
    echo json_encode($unique);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?> 