<?php
/**
 * Setup First Delivery Payment Settings
 * This script adds First Delivery payment gateway settings to the database
 */

require_once 'db.php';

try {
    // First Delivery payment settings
    $first_delivery_settings = [
        ['gateway' => 'first_delivery', 'setting_key' => 'enabled', 'setting_value' => '0'],
        ['gateway' => 'first_delivery', 'setting_key' => 'api_key', 'setting_value' => ''],
        ['gateway' => 'first_delivery', 'setting_key' => 'merchant_id', 'setting_value' => ''],
        ['gateway' => 'first_delivery', 'setting_key' => 'webhook_secret', 'setting_value' => ''],
        ['gateway' => 'first_delivery', 'setting_key' => 'mode', 'setting_value' => 'sandbox']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO payment_settings (gateway, setting_key, setting_value) VALUES (?, ?, ?)");
    
    $inserted = 0;
    foreach ($first_delivery_settings as $setting) {
        try {
            $stmt->execute([$setting['gateway'], $setting['setting_key'], $setting['setting_value']]);
            $inserted++;
            echo "✅ Added: {$setting['gateway']} - {$setting['setting_key']}\n";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                echo "⚠️  Already exists: {$setting['gateway']} - {$setting['setting_key']}\n";
            } else {
                echo "❌ Error adding {$setting['gateway']} - {$setting['setting_key']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 First Delivery settings setup completed!\n";
    echo "📊 Total settings processed: " . count($first_delivery_settings) . "\n";
    echo "✅ Successfully inserted: $inserted\n";
    echo "\n🔗 You can now configure First Delivery in: admin/payment_settings.php\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
}
?> 