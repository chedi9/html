<?php
require_once 'db.php';

/**
 * Fetches priority products (from disabled sellers or marked as priority) for homepage display.
 * Each product includes disabled seller info if available.
 *
 * @param int $limit Number of products to return (default 6)
 * @return array List of products with seller info
 */
function getPriorityProducts($limit = 6) {
    global $pdo;
    
    try {
        // First try the query with disabled_sellers table
        $sql = "SELECT p.*, ds.name AS disabled_seller_name, ds.disability_type, ds.priority_level
                FROM products p
                LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
                WHERE (p.is_priority_product = 1 OR p.disabled_seller_id IS NOT NULL)
                  AND p.approved = 1
                ORDER BY ds.priority_level DESC, p.created_at DESC
                LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If disabled_sellers table doesn't exist or query fails, fallback to simple query
        try {
            $sql = "SELECT p.* FROM products p 
                    WHERE p.approved = 1 
                    ORDER BY p.created_at DESC 
                    LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            // If all fails, return empty array
            return [];
        }
    }
} 