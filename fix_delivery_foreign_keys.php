<?php
/**
 * Fix Delivery Foreign Keys
 * Adds foreign key constraints after verifying table structure
 */

require_once 'db.php';

try {
    // Check orders table structure
    $check_orders_sql = "
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME = 'id'
    ";
    
    $stmt = $pdo->query($check_orders_sql);
    $order_id_column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_id_column) {
        echo "❌ Orders table 'id' column not found\n";
        exit;
    }
    
    echo "✅ Orders table structure:\n";
    echo "   - Column: {$order_id_column['COLUMN_NAME']}\n";
    echo "   - Type: {$order_id_column['DATA_TYPE']}\n";
    echo "   - Nullable: {$order_id_column['IS_NULLABLE']}\n";
    echo "   - Key: {$order_id_column['COLUMN_KEY']}\n";
    
    // Check if delivery_routes table exists
    $check_routes_sql = "
    SELECT COUNT(*) as table_exists
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'delivery_routes'
    ";
    
    $stmt = $pdo->query($check_routes_sql);
    $routes_exists = $stmt->fetch(PDO::FETCH_ASSOC)['table_exists'] > 0;
    
    if (!$routes_exists) {
        echo "❌ Delivery routes table does not exist. Run setup_delivery_tables.php first.\n";
        exit;
    }
    
    // Check if foreign key already exists
    $check_fk_sql = "
    SELECT COUNT(*) as fk_exists
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'delivery_routes' 
    AND REFERENCED_TABLE_NAME = 'orders'
    ";
    
    $stmt = $pdo->query($check_fk_sql);
    $fk_exists = $stmt->fetch(PDO::FETCH_ASSOC)['fk_exists'] > 0;
    
    if ($fk_exists) {
        echo "✅ Foreign key constraint already exists\n";
    } else {
        // Add foreign key constraint
        $add_fk_sql = "
        ALTER TABLE delivery_routes 
        ADD CONSTRAINT fk_delivery_routes_order_id 
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ";
        
        $pdo->exec($add_fk_sql);
        echo "✅ Foreign key constraint added successfully\n";
    }
    
    echo "\n🎉 Delivery foreign keys setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    
    // Provide alternative solution
    echo "\n💡 Alternative solution:\n";
    echo "If the foreign key constraint fails, you can manually add it later:\n";
    echo "ALTER TABLE delivery_routes ADD CONSTRAINT fk_delivery_routes_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;\n";
}
?> 