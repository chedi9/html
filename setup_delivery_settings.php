<?php
/**
 * Setup Delivery Settings with New Pricing Structure
 * Updates delivery settings to make First Delivery default with new pricing:
 * - Standard: 7 TND
 * - Express: 12 TND  
 * - Free shipping above 105 TND
 */

require_once 'db.php';

try {
    // Create delivery_settings table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            delivery_company VARCHAR(50) NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_setting (delivery_company, setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert or update First Delivery settings with new pricing
    $first_delivery_settings = [
        ['first_delivery', 'enabled', '1'], // Always enabled
        ['first_delivery', 'base_cost', '7.00'], // Standard cost
        ['first_delivery', 'express_cost', '12.00'], // Express cost
        ['first_delivery', 'free_threshold', '105.00'], // Free shipping threshold
        ['first_delivery', 'per_km_cost', '0.50'], // Cost per kilometer
        ['first_delivery', 'mode', 'sandbox'], // Default mode
        ['first_delivery', 'api_key', ''], // Empty by default
        ['first_delivery', 'merchant_id', ''], // Empty by default
        ['first_delivery', 'webhook_secret', ''] // Empty by default
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO delivery_settings (delivery_company, setting_key, setting_value) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    
    foreach ($first_delivery_settings as $setting) {
        $stmt->execute($setting);
    }
    
    echo "✅ Delivery settings updated successfully!\n";
    echo "📋 Updated pricing structure:\n";
    echo "   - Standard delivery: 7 TND\n";
    echo "   - Express delivery: 12 TND\n";
    echo "   - Free shipping above: 105 TND\n";
    echo "   - First Delivery is now the default option\n";
    
} catch (Exception $e) {
    echo "❌ Error updating delivery settings: " . $e->getMessage() . "\n";
}
?> 