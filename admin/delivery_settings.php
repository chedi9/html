<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_messages = [];
    
    // First Delivery Settings - Always enabled by default
    $first_delivery_enabled = 1; // Always enabled
    $first_delivery_api_key = $_POST['first_delivery_api_key'] ?? '';
    $first_delivery_merchant_id = $_POST['first_delivery_merchant_id'] ?? '';
    $first_delivery_webhook_secret = $_POST['first_delivery_webhook_secret'] ?? '';
    $first_delivery_mode = $_POST['first_delivery_mode'] ?? 'sandbox';
    $first_delivery_base_cost = $_POST['first_delivery_base_cost'] ?? '7.00'; // Updated to 7 TND
    $first_delivery_express_cost = $_POST['first_delivery_express_cost'] ?? '12.00'; // New express cost
    $first_delivery_free_threshold = $_POST['first_delivery_free_threshold'] ?? '105.00'; // Free shipping threshold
    $first_delivery_per_km_cost = $_POST['first_delivery_per_km_cost'] ?? '0.50';
    
    // Update or insert First Delivery settings
    $stmt = $pdo->prepare("INSERT INTO delivery_settings (delivery_company, setting_key, setting_value) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    $first_delivery_settings = [
        ['first_delivery', 'enabled', $first_delivery_enabled],
        ['first_delivery', 'api_key', $first_delivery_api_key],
        ['first_delivery', 'merchant_id', $first_delivery_merchant_id],
        ['first_delivery', 'webhook_secret', $first_delivery_webhook_secret],
        ['first_delivery', 'mode', $first_delivery_mode],
        ['first_delivery', 'base_cost', $first_delivery_base_cost],
        ['first_delivery', 'express_cost', $first_delivery_express_cost],
        ['first_delivery', 'free_threshold', $first_delivery_free_threshold],
        ['first_delivery', 'per_km_cost', $first_delivery_per_km_cost]
    ];
    
    foreach ($first_delivery_settings as $setting) {
        $stmt->execute($setting);
    }
    
    if ($first_delivery_enabled) $success_messages[] = "First Delivery enabled";
    
    // Set success message
    if (!empty($success_messages)) {
        $success_message = "تم حفظ إعدادات التوصيل بنجاح. " . implode(", ", $success_messages);
    } else {
        $success_message = "تم حفظ إعدادات التوصيل بنجاح";
    }
}

// Get current delivery settings
$delivery_settings = [];
$stmt = $pdo->query("SELECT * FROM delivery_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['delivery_company'] . '_' . $row['setting_key'];
    $delivery_settings[$key] = $row['setting_value'];
}

// Test delivery company function
function testDeliveryCompany($company, $pdo) {
    try {
        switch ($company) {
            case 'first_delivery':
                // Get First Delivery settings
                $delivery_settings = [];
                $stmt = $pdo->query("SELECT * FROM delivery_settings WHERE delivery_company = 'first_delivery'");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $delivery_settings[$row['setting_key']] = $row['setting_value'];
                }
                
                // Check if required settings are configured
                if (empty($delivery_settings['api_key'])) {
                    return ['status' => 'error', 'message' => 'API Key is required'];
                }
                
                if (empty($delivery_settings['merchant_id'])) {
                    return ['status' => 'error', 'message' => 'Merchant ID is required'];
                }
                
                // Include First Delivery API class
                require_once '../includes/first_delivery_api.php';
                
                // Initialize API
                $api = new FirstDeliveryAPI(
                    $delivery_settings['api_key'],
                    $delivery_settings['merchant_id'],
                    $delivery_settings['webhook_secret'] ?? '',
                    $delivery_settings['mode'] ?? 'sandbox'
                );
                
                // Test connection
                $result = $api->testConnection();
                return $result;
                
            default:
                return ['status' => 'error', 'message' => 'Unknown delivery company'];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Handle AJAX test delivery request
if (isset($_GET['test_delivery'])) {
    $result = testDeliveryCompany($_GET['test_delivery'], $pdo);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// Ensure no output is sent before headers
ob_start();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إعدادات التوصيل - لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .delivery-settings-container {
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .dashboard-header h2 {
            font-size: 2.2em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-subtitle {
            font-size: 1.1em;
            color: #7f8c8d;
            margin: 0;
        }
        
        .delivery-section {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #2196F3;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .toggle-label {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .test-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 15px;
        }
        
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .save-button {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }
        
        .test-result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .test-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .test-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
            margin: 40px 0;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="delivery-settings-container">
        <div class="dashboard-header">
            <h2>🚚 إعدادات التوصيل</h2>
            <p class="dashboard-subtitle">إدارة شركات التوصيل والخدمات اللوجستية</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <!-- First Delivery Settings -->
            <div class="delivery-section">
                <h3 style="margin: 0 0 25px 0; color: #2c3e50; font-size: 1.3em; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    🚚 First Delivery Settings
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_delivery_api_key">API Key</label>
                        <input type="password" name="first_delivery_api_key" id="first_delivery_api_key" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_api_key'] ?? ''); ?>" 
                               placeholder="Enter First Delivery API Key">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_delivery_merchant_id">Merchant ID</label>
                        <input type="text" name="first_delivery_merchant_id" id="first_delivery_merchant_id" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_merchant_id'] ?? ''); ?>" 
                               placeholder="Enter First Delivery Merchant ID">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_delivery_webhook_secret">Webhook Secret</label>
                        <input type="password" name="first_delivery_webhook_secret" id="first_delivery_webhook_secret" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_webhook_secret'] ?? ''); ?>" 
                               placeholder="Enter First Delivery Webhook Secret">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_delivery_mode">Mode</label>
                        <select name="first_delivery_mode" id="first_delivery_mode">
                            <option value="sandbox" <?php echo ($delivery_settings['first_delivery_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                            <option value="production" <?php echo ($delivery_settings['first_delivery_mode'] ?? 'sandbox') === 'production' ? 'selected' : ''; ?>>Production (Live)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_delivery_base_cost">Base Cost (TND)</label>
                        <input type="number" step="0.01" name="first_delivery_base_cost" id="first_delivery_base_cost" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_base_cost'] ?? '7.00'); ?>" 
                               placeholder="7.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_delivery_express_cost">Express Cost (TND)</label>
                        <input type="number" step="0.01" name="first_delivery_express_cost" id="first_delivery_express_cost" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_express_cost'] ?? '12.00'); ?>" 
                               placeholder="12.00">
                    </div>

                    <div class="form-group">
                        <label for="first_delivery_free_threshold">Free Shipping Threshold (TND)</label>
                        <input type="number" step="0.01" name="first_delivery_free_threshold" id="first_delivery_free_threshold" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_free_threshold'] ?? '105.00'); ?>" 
                               placeholder="105.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_delivery_per_km_cost">Cost per KM (TND)</label>
                        <input type="number" step="0.01" name="first_delivery_per_km_cost" id="first_delivery_per_km_cost" 
                               value="<?php echo htmlspecialchars($delivery_settings['first_delivery_per_km_cost'] ?? '0.50'); ?>" 
                               placeholder="0.50">
                    </div>
                    
                    <div class="form-group">
                        <label>Enable First Delivery</label>
                        <div class="toggle-container">
                            <label class="toggle-switch" for="first_delivery_enabled">
                                <input type="checkbox" name="first_delivery_enabled" id="first_delivery_enabled" 
                                       <?php echo ($delivery_settings['first_delivery_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="toggle-label">Enable First Delivery</span>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="test-button" onclick="testDelivery('first_delivery')">Test First Delivery API</button>
                <div id="first_delivery-test-result"></div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <button type="submit" class="save-button">حفظ إعدادات التوصيل</button>
            </div>
        </form>
    </div>
    
    <script>
        // Test delivery company function
        function testDelivery(company) {
            const button = event.target;
            const originalText = button.textContent;
            
            // Show loading state
            button.textContent = 'Testing...';
            button.disabled = true;
            
            // Clear previous results
            const resultDiv = document.getElementById(`${company}-test-result`);
            resultDiv.innerHTML = '';
            
            fetch(`delivery_settings.php?test_delivery=${company}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        resultDiv.innerHTML = `
                            <div class="test-result test-success">
                                <strong>✅ ${data.message}</strong>
                                <p style="margin: 5px 0 0 0; font-size: 0.9em; opacity: 0.8;">
                                    API connection test completed successfully
                                </p>
                            </div>`;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="test-result test-error">
                                <strong>❌ ${data.message}</strong>
                                <p style="margin: 5px 0 0 0; font-size: 0.9em; opacity: 0.8;">
                                    Please check your configuration
                                </p>
                            </div>`;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="test-result test-error">
                            <strong>❌ API connection test failed</strong>
                            <p style="margin: 5px 0 0 0; font-size: 0.9em; opacity: 0.8;">
                                Error: ${error.message}
                            </p>
                        </div>`;
                })
                .finally(() => {
                    // Restore button
                    button.textContent = originalText;
                    button.disabled = false;
                });
        }
    </script>
</body>
</html> 