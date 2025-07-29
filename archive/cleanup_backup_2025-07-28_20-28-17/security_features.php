<?php
/**
 * Security Features Management
 * Allows security officers and superadmins to enable/disable security features
 */

require_once '../security_integration_admin.php';
require_once '../db.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$allowed_roles = ['superadmin', 'security_personnel'];
if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    echo "<h1>Access Denied</h1>";
    echo "<p>You don't have permission to access this page.</p>";
    echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_features'])) {
    $features = [
        'waf_enabled' => isset($_POST['waf_enabled']) ? 1 : 0,
        'rate_limiting_enabled' => isset($_POST['rate_limiting_enabled']) ? 1 : 0,
        'ip_blocking_enabled' => isset($_POST['ip_blocking_enabled']) ? 1 : 0,
        'security_logging_enabled' => isset($_POST['security_logging_enabled']) ? 1 : 0,
        'fraud_detection_enabled' => isset($_POST['fraud_detection_enabled']) ? 1 : 0,
        'pci_compliance_enabled' => isset($_POST['pci_compliance_enabled']) ? 1 : 0,
        'cookie_consent_enabled' => isset($_POST['cookie_consent_enabled']) ? 1 : 0,
        'security_headers_enabled' => isset($_POST['security_headers_enabled']) ? 1 : 0,
        'csrf_protection_enabled' => isset($_POST['csrf_protection_enabled']) ? 1 : 0,
        'session_security_enabled' => isset($_POST['session_security_enabled']) ? 1 : 0
    ];
    
    try {
        // Create security_features table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_features (
                id INT PRIMARY KEY AUTO_INCREMENT,
                feature_name VARCHAR(100) UNIQUE NOT NULL,
                is_enabled TINYINT(1) DEFAULT 1,
                updated_by INT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (updated_by) REFERENCES admins(id)
            )
        ");
        
        // Update each feature
        foreach ($features as $feature_name => $is_enabled) {
            $stmt = $pdo->prepare("
                INSERT INTO security_features (feature_name, is_enabled, updated_by) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                is_enabled = VALUES(is_enabled), 
                updated_by = VALUES(updated_by)
            ");
            $stmt->execute([$feature_name, $is_enabled, $_SESSION['admin_id']]);
        }
        
        $success_message = "Security features updated successfully!";
        
        // Log the action
        logSecurityEvent('security_features_updated', [
            'features' => $features,
            'admin_id' => $_SESSION['admin_id'],
            'admin_role' => $_SESSION['admin_role']
        ]);
        
    } catch (Exception $e) {
        $error_message = "Error updating security features: " . $e->getMessage();
    }
}

// Get current feature status
try {
    $stmt = $pdo->query("SELECT feature_name, is_enabled FROM security_features");
    $current_features = [];
    while ($row = $stmt->fetch()) {
        $current_features[$row['feature_name']] = $row['is_enabled'];
    }
} catch (Exception $e) {
    $current_features = [];
}

// Default features if none exist
$default_features = [
    'waf_enabled' => 1,
    'rate_limiting_enabled' => 1,
    'ip_blocking_enabled' => 1,
    'security_logging_enabled' => 1,
    'fraud_detection_enabled' => 1,
    'pci_compliance_enabled' => 1,
    'cookie_consent_enabled' => 1,
    'security_headers_enabled' => 1,
    'csrf_protection_enabled' => 1,
    'session_security_enabled' => 1
];

$features = array_merge($default_features, $current_features);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Features Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .feature-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }
        .feature-card.enabled {
            border-color: #27ae60;
            background: #f8fff9;
        }
        .feature-card.disabled {
            border-color: #e74c3c;
            background: #fff8f8;
        }
        .feature-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .feature-name {
            font-weight: bold;
            font-size: 16px;
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
            background-color: #27ae60;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .feature-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .feature-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-enabled {
            background: #27ae60;
            color: white;
        }
        .status-disabled {
            background: #e74c3c;
            color: white;
        }
        .buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .bulk-actions {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .bulk-actions h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .bulk-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Security Features Management</h1>
            <p>Enable or disable security features for the WeBuy platform</p>
            <p><strong>User:</strong> <?php echo htmlspecialchars($_SESSION['admin_username']); ?> 
               <strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['admin_role']); ?></p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="bulk-actions">
            <h3>🚀 Bulk Actions</h3>
            <div class="bulk-buttons">
                <button class="btn btn-primary" onclick="enableAll()">Enable All Features</button>
                <button class="btn btn-secondary" onclick="disableAll()">Disable All Features</button>
                <button class="btn btn-primary" onclick="enableCritical()">Enable Critical Only</button>
                <button class="btn btn-secondary" onclick="resetToDefaults()">Reset to Defaults</button>
            </div>
        </div>

        <form method="POST">
            <div class="feature-grid">
                <!-- WAF -->
                <div class="feature-card <?php echo $features['waf_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Web Application Firewall (WAF)</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="waf_enabled" <?php echo $features['waf_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Protects against SQL injection, XSS, and other web attacks
                    </div>
                    <div class="feature-status <?php echo $features['waf_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['waf_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- Rate Limiting -->
                <div class="feature-card <?php echo $features['rate_limiting_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Rate Limiting</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="rate_limiting_enabled" <?php echo $features['rate_limiting_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Prevents brute force attacks and API abuse
                    </div>
                    <div class="feature-status <?php echo $features['rate_limiting_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['rate_limiting_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- IP Blocking -->
                <div class="feature-card <?php echo $features['ip_blocking_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">IP Blocking</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="ip_blocking_enabled" <?php echo $features['ip_blocking_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Automatically blocks malicious IP addresses
                    </div>
                    <div class="feature-status <?php echo $features['ip_blocking_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['ip_blocking_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- Security Logging -->
                <div class="feature-card <?php echo $features['security_logging_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Security Logging</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="security_logging_enabled" <?php echo $features['security_logging_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Logs all security events for monitoring and auditing
                    </div>
                    <div class="feature-status <?php echo $features['security_logging_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['security_logging_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- Fraud Detection -->
                <div class="feature-card <?php echo $features['fraud_detection_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Fraud Detection</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="fraud_detection_enabled" <?php echo $features['fraud_detection_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Detects suspicious activities and high-value transactions
                    </div>
                    <div class="feature-status <?php echo $features['fraud_detection_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['fraud_detection_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- PCI Compliance -->
                <div class="feature-card <?php echo $features['pci_compliance_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">PCI Compliance</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="pci_compliance_enabled" <?php echo $features['pci_compliance_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Ensures secure payment processing standards
                    </div>
                    <div class="feature-status <?php echo $features['pci_compliance_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['pci_compliance_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- Cookie Consent -->
                <div class="feature-card <?php echo $features['cookie_consent_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Cookie Consent</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="cookie_consent_enabled" <?php echo $features['cookie_consent_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        GDPR-compliant cookie consent banner
                    </div>
                    <div class="feature-status <?php echo $features['cookie_consent_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['cookie_consent_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- Security Headers -->
                <div class="feature-card <?php echo $features['security_headers_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Security Headers</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="security_headers_enabled" <?php echo $features['security_headers_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Sets HTTP security headers (CSP, HSTS, etc.)
                    </div>
                    <div class="feature-status <?php echo $features['security_headers_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['security_headers_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- CSRF Protection -->
                <div class="feature-card <?php echo $features['csrf_protection_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">CSRF Protection</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="csrf_protection_enabled" <?php echo $features['csrf_protection_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Protects against Cross-Site Request Forgery attacks
                    </div>
                    <div class="feature-status <?php echo $features['csrf_protection_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['csrf_protection_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>

                <!-- Session Security -->
                <div class="feature-card <?php echo $features['session_security_enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="feature-header">
                        <div class="feature-name">Session Security</div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="session_security_enabled" <?php echo $features['session_security_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="feature-description">
                        Secure session management and validation
                    </div>
                    <div class="feature-status <?php echo $features['session_security_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $features['session_security_enabled'] ? 'ENABLED' : 'DISABLED'; ?>
                    </div>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" name="update_features" class="btn btn-primary">💾 Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary">🏠 Back to Dashboard</a>
            </div>
        </form>
    </div>

    <script>
        // Update card styling when toggles change
        document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const card = this.closest('.feature-card');
                const status = card.querySelector('.feature-status');
                
                if (this.checked) {
                    card.classList.remove('disabled');
                    card.classList.add('enabled');
                    status.classList.remove('status-disabled');
                    status.classList.add('status-enabled');
                    status.textContent = 'ENABLED';
                } else {
                    card.classList.remove('enabled');
                    card.classList.add('disabled');
                    status.classList.remove('status-enabled');
                    status.classList.add('status-disabled');
                    status.textContent = 'DISABLED';
                }
            });
        });

        // Bulk action functions
        function enableAll() {
            if (confirm('Enable all security features?')) {
                document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change'));
                });
            }
        }

        function disableAll() {
            if (confirm('Disable all security features? This may reduce security!')) {
                document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change'));
                });
            }
        }

        function enableCritical() {
            if (confirm('Enable only critical security features?')) {
                const criticalFeatures = ['waf_enabled', 'rate_limiting_enabled', 'security_headers_enabled', 'csrf_protection_enabled'];
                document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    const isCritical = criticalFeatures.some(feature => checkbox.name === feature);
                    checkbox.checked = isCritical;
                    checkbox.dispatchEvent(new Event('change'));
                });
            }
        }

        function resetToDefaults() {
            if (confirm('Reset all features to default settings?')) {
                document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change'));
                });
            }
        }
    </script>
</body>
</html> 