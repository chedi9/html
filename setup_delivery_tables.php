<?php
/**
 * Setup Delivery Tables
 * Creates all necessary database tables for delivery tracking
 */

require_once 'db.php';

try {
    // Create delivery_webhook_logs table
    $create_webhook_logs_sql = "
    CREATE TABLE IF NOT EXISTS delivery_webhook_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        delivery_company VARCHAR(50) NOT NULL,
        order_id VARCHAR(100) NOT NULL,
        tracking_id VARCHAR(100),
        status VARCHAR(50) NOT NULL,
        webhook_type VARCHAR(50),
        payload TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_delivery_company (delivery_company),
        INDEX idx_order_id (order_id),
        INDEX idx_tracking_id (tracking_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_webhook_logs_sql);
    echo "✅ Delivery webhook logs table created successfully\n";
    
    // Add delivery columns to orders table if they don't exist
    $check_columns_sql = "
    SELECT COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME IN ('delivery_company', 'delivery_tracking_id', 'delivery_status', 'delivery_cost')
    ";
    
    $stmt = $pdo->query($check_columns_sql);
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columns_to_add = [
        'delivery_company' => "ALTER TABLE orders ADD COLUMN delivery_company VARCHAR(50) NULL AFTER status",
        'delivery_tracking_id' => "ALTER TABLE orders ADD COLUMN delivery_tracking_id VARCHAR(100) NULL AFTER delivery_company",
        'delivery_status' => "ALTER TABLE orders ADD COLUMN delivery_status VARCHAR(50) NULL AFTER delivery_tracking_id",
        'delivery_cost' => "ALTER TABLE orders ADD COLUMN delivery_cost DECIMAL(10,2) NULL AFTER delivery_status"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $pdo->exec($sql);
            echo "✅ Added column: $column to orders table\n";
        } else {
            echo "⚠️  Column already exists: $column\n";
        }
    }
    
    // Create delivery_territories table for First Delivery territories
    $create_territories_sql = "
    CREATE TABLE IF NOT EXISTS delivery_territories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        delivery_company VARCHAR(50) NOT NULL,
        territory_id VARCHAR(100) NOT NULL,
        name VARCHAR(255) NOT NULL,
        shortcode VARCHAR(10) NOT NULL,
        zone_data JSON,
        center_data JSON,
        auto_dispatch BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_territory (delivery_company, territory_id),
        INDEX idx_delivery_company (delivery_company),
        INDEX idx_shortcode (shortcode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_territories_sql);
    echo "✅ Delivery territories table created successfully\n";
    
    // Create delivery_routes table for tracking delivery routes
    $create_routes_sql = "
    CREATE TABLE IF NOT EXISTS delivery_routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        delivery_company VARCHAR(50) NOT NULL,
        pickup_address TEXT NOT NULL,
        dropoff_address TEXT NOT NULL,
        distance_km DECIMAL(10,2),
        estimated_time_minutes INT,
        actual_time_minutes INT,
        route_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_delivery_company (delivery_company)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_routes_sql);
    echo "✅ Delivery routes table created successfully\n";
    
    // Create delivery_analytics table for delivery performance tracking
    $create_analytics_sql = "
    CREATE TABLE IF NOT EXISTS delivery_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        delivery_company VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        total_orders INT DEFAULT 0,
        completed_orders INT DEFAULT 0,
        cancelled_orders INT DEFAULT 0,
        total_revenue DECIMAL(10,2) DEFAULT 0.00,
        avg_delivery_time_minutes DECIMAL(10,2),
        customer_satisfaction_score DECIMAL(3,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_analytics (delivery_company, date),
        INDEX idx_delivery_company (delivery_company),
        INDEX idx_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_analytics_sql);
    echo "✅ Delivery analytics table created successfully\n";
    
    echo "\n🎉 All delivery tables setup completed!\n";
    echo "📊 Tables created/updated:\n";
    echo "   - delivery_webhook_logs\n";
    echo "   - orders (delivery columns added)\n";
    echo "   - delivery_territories\n";
    echo "   - delivery_routes\n";
    echo "   - delivery_analytics\n";
    echo "\n🔗 You can now use the delivery system with First Delivery!\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
}
?> 