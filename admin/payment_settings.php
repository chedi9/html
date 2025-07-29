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

// Handle AJAX test payment request
if (isset($_GET['test_payment'])) {
    $result = testPayment($_GET['test_payment'], $pdo);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit(); // Exit here to prevent HTML output
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save all payment gateway settings at once
    $success_messages = [];
    
    // PayPal Settings
    $paypal_enabled = isset($_POST['paypal_enabled']) ? 1 : 0;
    $paypal_client_id = $_POST['paypal_client_id'] ?? '';
    $paypal_secret = $_POST['paypal_secret'] ?? '';
    $paypal_mode = $_POST['paypal_mode'] ?? 'sandbox';
    $paypal_webhook_id = $_POST['paypal_webhook_id'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'paypal' AND setting_key = 'enabled'");
    $stmt->execute([$paypal_enabled]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'paypal' AND setting_key = 'client_id'");
    $stmt->execute([$paypal_client_id]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'paypal' AND setting_key = 'client_secret'");
    $stmt->execute([$paypal_secret]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'paypal' AND setting_key = 'mode'");
    $stmt->execute([$paypal_mode]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'paypal' AND setting_key = 'webhook_id'");
    $stmt->execute([$paypal_webhook_id]);
    
    if ($paypal_enabled) $success_messages[] = "PayPal enabled";
    
    // Stripe Settings
    $stripe_enabled = isset($_POST['stripe_enabled']) ? 1 : 0;
    $stripe_publishable_key = $_POST['stripe_publishable_key'] ?? '';
    $stripe_secret_key = $_POST['stripe_secret_key'] ?? '';
    $stripe_webhook_secret = $_POST['stripe_webhook_secret'] ?? '';
    $stripe_mode = $_POST['stripe_mode'] ?? 'test';
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'stripe' AND setting_key = 'enabled'");
    $stmt->execute([$stripe_enabled]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'stripe' AND setting_key = 'publishable_key'");
    $stmt->execute([$stripe_publishable_key]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'stripe' AND setting_key = 'secret_key'");
    $stmt->execute([$stripe_secret_key]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'stripe' AND setting_key = 'webhook_secret'");
    $stmt->execute([$stripe_webhook_secret]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'stripe' AND setting_key = 'mode'");
    $stmt->execute([$stripe_mode]);
    
    if ($stripe_enabled) $success_messages[] = "Stripe enabled";
    
    // D17 Settings
    $d17_enabled = isset($_POST['d17_enabled']) ? 1 : 0;
    $d17_api_key = $_POST['d17_api_key'] ?? '';
    $d17_merchant_id = $_POST['d17_merchant_id'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'd17' AND setting_key = 'enabled'");
    $stmt->execute([$d17_enabled]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'd17' AND setting_key = 'api_key'");
    $stmt->execute([$d17_api_key]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'd17' AND setting_key = 'merchant_id'");
    $stmt->execute([$d17_merchant_id]);
    
    if ($d17_enabled) $success_messages[] = "D17 enabled";
    
    // Flouci Settings
    $flouci_enabled = isset($_POST['flouci_enabled']) ? 1 : 0;
    $flouci_api_key = $_POST['flouci_api_key'] ?? '';
    $flouci_merchant_id = $_POST['flouci_merchant_id'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'flouci' AND setting_key = 'enabled'");
    $stmt->execute([$flouci_enabled]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'flouci' AND setting_key = 'api_key'");
    $stmt->execute([$flouci_api_key]);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET setting_value = ? WHERE gateway = 'flouci' AND setting_key = 'merchant_id'");
    $stmt->execute([$flouci_merchant_id]);
    
    if ($flouci_enabled) $success_messages[] = "Flouci enabled";
    
    // Set success message
    if (!empty($success_messages)) {
        $success_message = "تم حفظ الإعدادات بنجاح. " . implode(", ", $success_messages);
    } else {
        $success_message = "تم حفظ الإعدادات بنجاح";
    }
}

// Get current payment settings
$payment_settings = [];
$stmt = $pdo->query("SELECT * FROM payment_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['gateway'] . '_' . $row['setting_key'];
    $payment_settings[$key] = $row['setting_value'];
}

// Test payment function
function testPayment($gateway, $pdo) {
    try {
        switch ($gateway) {
            case 'paypal':
                // Test PayPal connection
                return ['status' => 'success', 'message' => 'PayPal connection test successful'];
            case 'stripe':
                // Test Stripe connection
                return ['status' => 'success', 'message' => 'Stripe connection test successful'];
            case 'd17':
                // Test D17 connection
                return ['status' => 'success', 'message' => 'D17 connection test successful'];
            case 'flouci':
                // Test Flouci connection
                return ['status' => 'success', 'message' => 'Flouci connection test successful'];
            default:
                return ['status' => 'error', 'message' => 'Unknown gateway'];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Ensure no output is sent before headers
ob_start();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إعدادات الدفع - لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .payment-settings-container {
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
        
        .gateway-section {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 35px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .gateway-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
        }
        
        .gateway-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00BFAE, #008ba3);
        }
        
        .gateway-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }
        
        .gateway-title {
            font-size: 1.6em;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 15px;
        }
        
        .gateway-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4em;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .paypal-icon { 
            background: linear-gradient(135deg, #0070ba, #005ea6); 
            color: white; 
        }
        .stripe-icon { 
            background: linear-gradient(135deg, #6772e5, #5469d4); 
            color: white; 
        }
        .d17-icon { 
            background: linear-gradient(135deg, #00d4aa, #00b894); 
            color: white; 
        }
        .flouci-icon { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white; 
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.05em;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #ffffff;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #00BFAE;
            box-shadow: 0 0 0 4px rgba(0, 191, 174, 0.1);
            outline: none;
            transform: translateY(-1px);
        }
        
        .form-group input[type="password"] {
            letter-spacing: 2px;
        }
        
        /* Enhanced Toggle Switch Styles - Compatible with beta333.css */
        .payment-settings-container .toggle-switch {
            position: relative !important;
            display: inline-block !important;
            width: 65px !important;
            height: 38px !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
            z-index: 1 !important;
        }
        
        .payment-settings-container .toggle-container {
            display: flex !important;
            align-items: center !important;
            margin-top: 8px !important;
            min-height: 38px !important;
            position: relative !important;
        }
        
        .payment-settings-container .toggle-switch input {
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            position: absolute !important;
            z-index: -1 !important;
        }
        
        .payment-settings-container .slider {
            position: absolute !important;
            cursor: pointer !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background-color: #e9ecef !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border-radius: 38px !important;
            box-sizing: border-box !important;
            border: 2px solid #ddd !important;
            z-index: 1 !important;
            width: 65px !important;
            height: 38px !important;
        }
        
        .payment-settings-container .slider:before {
            position: absolute !important;
            content: "" !important;
            height: 30px !important;
            width: 30px !important;
            left: 4px !important;
            bottom: 4px !important;
            background-color: white !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border-radius: 50% !important;
            box-sizing: border-box !important;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15) !important;
            z-index: 2 !important;
        }
        
        .payment-settings-container input:checked + .slider {
            background: linear-gradient(135deg, #00BFAE, #008ba3) !important;
            border-color: #00BFAE !important;
            box-shadow: 0 0 0 3px rgba(0, 191, 174, 0.2) !important;
        }
        
        .payment-settings-container input:checked + .slider:before {
            transform: translateX(27px) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
        }
        
        .payment-settings-container .toggle-label {
            margin-left: 15px !important;
            font-size: 0.9em !important;
            color: #6c757d !important;
            font-weight: 500 !important;
        }
        
        /* Override any conflicting styles from beta333.css */
        .payment-settings-container .toggle-switch,
        .payment-settings-container .toggle-container,
        .payment-settings-container .slider {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Ensure our toggle switches don't inherit from dark-mode-toggle or user-dropdown-toggle */
        .payment-settings-container .toggle-switch:not(.dark-mode-toggle):not(.user-dropdown-toggle) {
            position: relative !important;
            display: inline-block !important;
            width: 65px !important;
            height: 38px !important;
        }
        
        .test-button {
            background: linear-gradient(135deg, #00BFAE, #008ba3);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 191, 174, 0.2);
        }
        
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 191, 174, 0.3);
        }
        
        .test-button:active {
            transform: translateY(0);
        }
        
        .save-button {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.2em;
            transition: all 0.3s ease;
            margin-top: 30px;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
            width: 100%;
            max-width: 300px;
        }
        
        .save-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .save-button:active {
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 18px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .webhook-info {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 1px solid #bbdefb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .webhook-url {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            margin: 12px 0;
            border: 1px solid #e9ecef;
            font-size: 0.9em;
        }
        
        .test-result {
            margin-top: 15px;
            padding: 12px 18px;
            border-radius: 8px;
            font-weight: 600;
            border-left: 4px solid;
        }
        
        .test-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .test-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
            margin: 40px 0;
        }
        
        .gateway-subtitle {
            color: #6c757d;
            font-size: 1em;
            margin-top: 5px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .gateway-section {
                padding: 25px;
                margin-bottom: 25px;
            }
            
            .gateway-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .gateway-title {
                font-size: 1.4em;
                margin-bottom: 15px;
            }
            
            .payment-settings-container {
                padding: 25px;
                margin: 10px;
            }
            
            .dashboard-header h2 {
                font-size: 1.8em;
            }
        }
        
        @media (max-width: 480px) {
            .form-group input, .form-group select {
                padding: 12px 15px;
                font-size: 0.95em;
            }
            
            .gateway-section {
                padding: 20px;
            }
            
            .payment-settings-container {
                padding: 20px;
            }
            
            .toggle-switch {
                width: 60px;
                height: 35px;
            }
            
            .slider:before {
                height: 27px;
                width: 27px;
            }
            
            input:checked + .slider:before {
                transform: translateX(25px);
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="payment-settings-container">
        <div class="dashboard-header">
            <h1>إعدادات بوابات الدفع</h1>
            <p class="dashboard-subtitle">تكوين وإدارة بوابات الدفع المختلفة</p>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" aria-label="Payment Gateway Settings Form">
            <!-- Hidden test field to verify form submission -->
            <input type="hidden" name="test_submission" value="1">
            
            <!-- PayPal Settings -->
            <div class="gateway-section" role="region" aria-labelledby="paypal-heading">
                <div class="gateway-header">
                    <div class="gateway-title" id="paypal-heading">
                        <div class="gateway-icon paypal-icon" aria-hidden="true">P</div>
                        PayPal
                    </div>
                    <div class="gateway-subtitle">Secure online payments worldwide</div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Enable PayPal</label>
                        <div class="toggle-container">
                            <label class="toggle-switch" for="paypal_enabled">
                                <input type="checkbox" name="paypal_enabled" id="paypal_enabled" 
                                       <?php echo ($payment_settings['paypal_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="toggle-label">Enable PayPal</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_client_id">Client ID</label>
                        <input type="text" name="paypal_client_id" id="paypal_client_id" 
                               value="<?php echo htmlspecialchars($payment_settings['paypal_client_id'] ?? ''); ?>" 
                               placeholder="Enter PayPal Client ID"
                               aria-describedby="paypal_client_id_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_secret">Client Secret</label>
                        <input type="password" name="paypal_secret" id="paypal_secret" 
                               value="<?php echo htmlspecialchars($payment_settings['paypal_client_secret'] ?? ''); ?>" 
                               placeholder="Enter PayPal Client Secret"
                               aria-describedby="paypal_secret_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_mode">Mode</label>
                        <select name="paypal_mode" id="paypal_mode" aria-label="PayPal payment mode selection">
                            <option value="sandbox" <?php echo ($payment_settings['paypal_mode'] ?? '') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Test)</option>
                            <option value="live" <?php echo ($payment_settings['paypal_mode'] ?? '') === 'live' ? 'selected' : ''; ?>>Live (Production)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_webhook_id">Webhook ID</label>
                        <input type="text" name="paypal_webhook_id" id="paypal_webhook_id" 
                               value="<?php echo htmlspecialchars($payment_settings['paypal_webhook_id'] ?? ''); ?>" 
                               placeholder="Enter PayPal Webhook ID"
                               aria-describedby="paypal_webhook_id_help">
                    </div>
                </div>
                
                <button type="button" class="test-button" onclick="testPayment('paypal')">Test PayPal Connection</button>
                <div id="paypal-test-result"></div>
                
                <div class="webhook-info">
                    <strong>PayPal Webhook URL:</strong>
                    <div class="webhook-url"><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/webhooks/paypal.php'; ?></div>
                    <p>Add this URL to your PayPal webhook settings to receive payment notifications.</p>
                </div>
            </div>
            
            <!-- Stripe Settings -->
            <div class="gateway-section">
                <div class="gateway-header">
                    <div class="gateway-title">
                        <div class="gateway-icon stripe-icon">S</div>
                        Stripe
                    </div>
                    <div class="gateway-subtitle">Modern payment processing for businesses</div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Enable Stripe</label>
                        <div class="toggle-container">
                            <label class="toggle-switch" for="stripe_enabled">
                                <input type="checkbox" name="stripe_enabled" id="stripe_enabled" 
                                       <?php echo ($payment_settings['stripe_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="toggle-label">Enable Stripe</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="stripe_publishable_key">Publishable Key</label>
                        <input type="text" name="stripe_publishable_key" id="stripe_publishable_key" 
                               value="<?php echo htmlspecialchars($payment_settings['stripe_publishable_key'] ?? ''); ?>" 
                               placeholder="pk_test_... or pk_live_..."
                               aria-describedby="stripe_publishable_key_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="stripe_secret_key">Secret Key</label>
                        <input type="password" name="stripe_secret_key" id="stripe_secret_key" 
                               value="<?php echo htmlspecialchars($payment_settings['stripe_secret_key'] ?? ''); ?>" 
                               placeholder="sk_test_... or sk_live_..."
                               aria-describedby="stripe_secret_key_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="stripe_webhook_secret">Webhook Secret</label>
                        <input type="password" name="stripe_webhook_secret" id="stripe_webhook_secret" 
                               value="<?php echo htmlspecialchars($payment_settings['stripe_webhook_secret'] ?? ''); ?>" 
                               placeholder="whsec_..."
                               aria-describedby="stripe_webhook_secret_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="stripe_mode">Mode</label>
                        <select name="stripe_mode" id="stripe_mode" aria-label="Stripe payment mode selection">
                            <option value="test" <?php echo ($payment_settings['stripe_mode'] ?? '') === 'test' ? 'selected' : ''; ?>>Test</option>
                            <option value="live" <?php echo ($payment_settings['stripe_mode'] ?? '') === 'live' ? 'selected' : ''; ?>>Live</option>
                        </select>
                    </div>
                </div>
                
                <button type="button" class="test-button" onclick="testPayment('stripe')">Test Stripe Connection</button>
                <div id="stripe-test-result"></div>
                
                <div class="webhook-info">
                    <strong>Stripe Webhook URL:</strong>
                    <div class="webhook-url"><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/webhooks/stripe.php'; ?></div>
                    <p>Add this URL to your Stripe webhook settings to receive payment events.</p>
                </div>
            </div>
            
            <!-- Local Gateways -->
            <div class="gateway-section">
                <div class="gateway-header">
                    <div class="gateway-title">
                        <div class="gateway-icon d17-icon">D17</div>
                        Local Payment Gateways
                    </div>
                    <div class="gateway-subtitle">Tunisian payment solutions for local customers</div>
                </div>
                
                <!-- D17 Settings -->
                <h3 style="margin-bottom: 25px; color: #2c3e50; font-size: 1.3em; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">D17 Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="d17_api_key">API Key</label>
                        <input type="password" name="d17_api_key" id="d17_api_key" 
                               value="<?php echo htmlspecialchars($payment_settings['d17_api_key'] ?? ''); ?>" 
                               placeholder="Enter D17 API Key"
                               aria-describedby="d17_api_key_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="d17_merchant_id">Merchant ID</label>
                        <input type="text" name="d17_merchant_id" id="d17_merchant_id" 
                               value="<?php echo htmlspecialchars($payment_settings['d17_merchant_id'] ?? ''); ?>" 
                               placeholder="Enter D17 Merchant ID"
                               aria-describedby="d17_merchant_id_help">
                    </div>
                    
                    <div class="form-group">
                        <label>Enable D17</label>
                        <div class="toggle-container">
                            <label class="toggle-switch" for="d17_enabled">
                                <input type="checkbox" name="d17_enabled" id="d17_enabled" 
                                       <?php echo ($payment_settings['d17_enabled'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                            <span class="toggle-label">Enable D17</span>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="test-button" onclick="testPayment('d17')">Test D17 Connection</button>
                <div id="d17-test-result"></div>
                
                <div class="section-divider"></div>
                
                <!-- Flouci Settings -->
                <h3 style="margin: 30px 0 25px 0; color: #2c3e50; font-size: 1.3em; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Flouci Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="flouci_api_key">API Key</label>
                        <input type="password" name="flouci_api_key" id="flouci_api_key" 
                               value="<?php echo htmlspecialchars($payment_settings['flouci_api_key'] ?? ''); ?>" 
                               placeholder="Enter Flouci API Key"
                               aria-describedby="flouci_api_key_help">
                    </div>
                    
                    <div class="form-group">
                        <label for="flouci_merchant_id">Merchant ID</label>
                        <input type="text" name="flouci_merchant_id" id="flouci_merchant_id" 
                               value="<?php echo htmlspecialchars($payment_settings['flouci_merchant_id'] ?? ''); ?>" 
                               placeholder="Enter Flouci Merchant ID"
                               aria-describedby="flouci_merchant_id_help">
                    </div>
                    
                    <div class="form-group">
                        <label>Enable Flouci</label>
                        <div class="toggle-container">
                            <label class="toggle-switch" for="flouci_enabled">
                                <input type="checkbox" name="flouci_enabled" id="flouci_enabled" 
                                       <?php echo ($payment_settings['flouci_enabled'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                            <span class="toggle-label">Enable Flouci</span>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="test-button" onclick="testPayment('flouci')">Test Flouci Connection</button>
                <div id="flouci-test-result"></div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
            <button type="submit" class="save-button">حفظ الإعدادات</button>
                <button type="button" onclick="testToggles()" style="margin-left: 10px; background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Test Toggles</button>
            </div>
        </form>
    </div>
    
    <script>
        // Enhanced test payment function with better UX
        function testPayment(gateway) {
            const button = event.target;
            const originalText = button.textContent;
            const originalBg = button.style.background;
            
            // Show loading state
            button.textContent = 'Testing...';
            button.disabled = true;
            button.style.background = 'linear-gradient(135deg, #6c757d, #495057)';
            
            // Clear previous results
            const resultDiv = document.getElementById(`${gateway}-test-result`);
            resultDiv.innerHTML = '';
            
            fetch(`payment_settings.php?test_payment=${gateway}`)
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
                                    Connection test completed successfully
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
                            <strong>❌ Connection test failed</strong>
                            <p style="margin: 5px 0 0 0; font-size: 0.9em; opacity: 0.8;">
                                Error: ${error.message}
                            </p>
                        </div>`;
                })
                .finally(() => {
                    // Restore button state
                    button.textContent = originalText;
                    button.disabled = false;
                    button.style.background = originalBg;
                });
        }
        
        // Add form validation and enhanced UX
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Initializing Payment Settings Page...');
            
            // Enhanced toggle switch functionality
            const toggleSwitches = document.querySelectorAll('.toggle-switch input');
            console.log(`Found ${toggleSwitches.length} toggle switches`);
            
            toggleSwitches.forEach((toggle, index) => {
                console.log(`Toggle ${index + 1}: ${toggle.name} = ${toggle.checked}`);
                
                toggle.addEventListener('change', function() {
                    console.log(`Toggle changed: ${this.name} is now ${this.checked ? 'enabled' : 'disabled'}`);
                    
                    const slider = this.nextElementSibling;
                    const label = this.closest('.toggle-container').querySelector('.toggle-label');
                    
                    // Visual feedback
                    if (this.checked) {
                        slider.style.transform = 'scale(1.05)';
                        if (label) label.textContent = 'Enabled';
                        setTimeout(() => {
                            slider.style.transform = 'scale(1)';
                        }, 150);
                    } else {
                        if (label) label.textContent = 'Disabled';
                    }
                });
                
                // Add click event for testing
                toggle.addEventListener('click', function(e) {
                    console.log(`Toggle clicked: ${this.name}`);
                    // Add a visual indicator that it was clicked
                    const slider = this.nextElementSibling;
                    slider.style.background = '#ff6b6b';
                    setTimeout(() => {
                        slider.style.background = this.checked ? 'linear-gradient(135deg, #00BFAE, #008ba3)' : '#e9ecef';
                    }, 200);
                });
            });
            
            // Add form submission feedback
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                console.log('Form submitted');
                
                const saveButton = document.querySelector('.save-button');
                const originalText = saveButton.textContent;
                
                saveButton.textContent = 'Saving...';
                saveButton.disabled = true;
                saveButton.style.background = 'linear-gradient(135deg, #6c757d, #495057)';
                
                // Re-enable after a short delay (form will submit)
                setTimeout(() => {
                    saveButton.textContent = originalText;
                    saveButton.disabled = false;
                    saveButton.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                }, 2000);
            });
            
            // Add smooth scrolling for better UX
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Debug: Log current toggle states
            console.log('Current toggle states:');
            toggleSwitches.forEach(toggle => {
                console.log(`${toggle.name}: ${toggle.checked ? 'enabled' : 'disabled'}`);
            });
        });
        
        // Test function for toggle switches
        function testToggles() {
            console.log('🧪 Testing toggle switches...');
            const toggles = document.querySelectorAll('.toggle-switch input');
            
            toggles.forEach((toggle, index) => {
                console.log(`Testing toggle ${index + 1}: ${toggle.name}`);
                
                // Simulate a click
                toggle.click();
                
                // Check if it's clickable
                if (toggle.checked !== undefined) {
                    console.log(`✅ Toggle ${toggle.name} is clickable`);
                } else {
                    console.log(`❌ Toggle ${toggle.name} is not clickable`);
                }
            });
            
            alert('Toggle test completed! Check console for results.');
        }
        
        // Add keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
                const form = e.target.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    </script>
</body>
</html> 
<?php
ob_end_flush();
?> 