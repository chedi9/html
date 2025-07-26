<?php
/**
 * Priority Products Helper Functions
 * Since we can't create database views on shared hosting, we use direct queries
 */

/**
 * Get priority products from disabled sellers
 * @param PDO $pdo Database connection
 * @param int $limit Number of products to return
 * @return array Array of priority products
 */
function getPriorityProducts($pdo, $limit = 6) {
    $stmt = $pdo->prepare("
        SELECT p.*, ds.name as disabled_seller_name, ds.story as disabled_seller_story, 
               ds.disability_type, ds.seller_photo as disabled_seller_photo, ds.priority_level
        FROM products p 
        LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id 
        WHERE p.disabled_seller_id IS NOT NULL AND p.approved = 1 
        ORDER BY ds.priority_level DESC, p.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get all priority products for search/filtering
 * @param PDO $pdo Database connection
 * @param array $filters Additional filters
 * @return array Array of priority products
 */
function getAllPriorityProducts($pdo, $filters = []) {
    $where = ['p.disabled_seller_id IS NOT NULL', 'p.approved = 1'];
    $params = [];
    
    // Add additional filters
    if (!empty($filters['category_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = $filters['category_id'];
    }
    
    if (!empty($filters['min_price'])) {
        $where[] = 'p.price >= ?';
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $where[] = 'p.price <= ?';
        $params[] = $filters['max_price'];
    }
    
    $sql = "
        SELECT p.*, ds.name as disabled_seller_name, ds.story as disabled_seller_story, 
               ds.disability_type, ds.seller_photo as disabled_seller_photo, ds.priority_level
        FROM products p 
        LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id 
        WHERE " . implode(' AND ', $where) . "
        ORDER BY ds.priority_level DESC, p.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get priority products count
 * @param PDO $pdo Database connection
 * @return int Number of priority products
 */
function getPriorityProductsCount($pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM products p 
        WHERE p.disabled_seller_id IS NOT NULL AND p.approved = 1
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['count'];
}
?> 