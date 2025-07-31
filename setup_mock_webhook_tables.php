<?php
/**
 * Setup Mock Webhook System Tables
 * Creates necessary database tables for mock webhook testing
 */

require_once 'db.php';

echo "🚚 Setting up Mock Webhook System Tables...\n\n";

try {
    // Create delivery_notifications table for storing mock notifications
    $sql = "
    CREATE TABLE IF NOT EXISTS delivery_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        status VARCHAR(100) NOT NULL,
        status_arabic VARCHAR(255) NOT NULL,
        runner_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "✅ Delivery notifications table created successfully\n";
    
    // Create mock_delivery_simulation table for tracking simulation runs
    $sql = "
    CREATE TABLE IF NOT EXISTS mock_delivery_simulation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        initial_status VARCHAR(100) NOT NULL,
        final_status VARCHAR(100) NOT NULL,
        simulation_type ENUM('manual', 'automatic', 'complete') NOT NULL,
        runner_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_simulation_type (simulation_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "✅ Mock delivery simulation table created successfully\n";
    
    // Create mock_webhook_logs table for detailed webhook simulation logs
    $sql = "
    CREATE TABLE IF NOT EXISTS mock_webhook_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status VARCHAR(100) NOT NULL,
        status_arabic VARCHAR(255) NOT NULL,
        runner_info JSON,
        estimated_delivery VARCHAR(100),
        location VARCHAR(255),
        notes TEXT,
        simulation_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_status (status),
        INDEX idx_simulation_timestamp (simulation_timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "✅ Mock webhook logs table created successfully\n";
    
    // Insert sample mock runners data (if not exists)
    $sql = "
    INSERT IGNORE INTO delivery_webhook_logs 
    (delivery_company, order_id, tracking_id, status, webhook_type, payload, created_at) 
    VALUES 
    ('first_delivery', 1, 'mock_track_1', 'pending_assign', 'mock_webhook', '{\"type\":\"order_status_update\",\"order_id\":1,\"status\":\"pending_assign\",\"tracking_id\":\"mock_track_1\",\"timestamp\":\"2024-01-01T10:00:00Z\",\"runner_info\":{\"id\":\"mock_runner_001\",\"name\":\"أحمد محمد\",\"phone\":\"+216 50 123 456\",\"transport_type\":\"car\",\"rating\":4.8},\"estimated_delivery\":\"+15 minutes\",\"location\":\"مركز التوزيع\",\"notes\":\"جاري البحث عن سائق متاح\"}', NOW())
    ";
    
    try {
        $pdo->exec($sql);
        echo "✅ Sample mock webhook log created\n";
    } catch (Exception $e) {
        echo "ℹ️ Sample data already exists or table not ready\n";
    }
    
    echo "\n🎉 Mock Webhook System Setup Complete!\n\n";
    echo "📋 Available Features:\n";
    echo "   • Manual status updates via admin interface\n";
    echo "   • Automatic delivery progress simulation\n";
    echo "   • Complete delivery cycle simulation\n";
    echo "   • Realistic timing and random events\n";
    echo "   • Comprehensive logging and tracking\n\n";
    
    echo "🔧 Usage Instructions:\n";
    echo "   1. Go to admin/mock_webhook_tester.php for manual testing\n";
    echo "   2. Set up cron job for automatic simulation:\n";
    echo "      */5 * * * * php /path/to/cron/mock_webhook_simulator.php\n";
    echo "   3. Monitor logs at cron/mock_webhook_simulator.log\n\n";
    
    echo "📊 Mock System Features:\n";
    echo "   • 3 Mock Runners (2 cars, 1 motorcycle)\n";
    echo "   • 9 Delivery Statuses with Arabic translations\n";
    echo "   • Realistic timing progression\n";
    echo "   • Random cancellation events (5% chance)\n";
    echo "   • Comprehensive notification system\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up mock webhook system: " . $e->getMessage() . "\n";
    exit(1);
}
?> 