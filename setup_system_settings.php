<?php
/**
 * Setup System Settings Table
 * Creates the system_settings table and inserts default email configurations
 */

// Database connection
require_once 'includes/db_connection.php';

try {
    // Create system_settings table
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type ENUM('string', 'boolean', 'integer', 'json') DEFAULT 'string',
        description TEXT,
        is_public BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ System settings table created successfully\n";
    
    // Insert default email settings
    $email_settings = [
        [
            'setting_key' => 'email_smtp_host',
            'setting_value' => 'smtp.gmail.com',
            'setting_type' => 'string',
            'description' => 'SMTP server host for email sending',
            'is_public' => false
        ],
        [
            'setting_key' => 'email_smtp_port',
            'setting_value' => '587',
            'setting_type' => 'integer',
            'description' => 'SMTP server port',
            'is_public' => false
        ],
        [
            'setting_key' => 'email_smtp_username',
            'setting_value' => '',
            'setting_type' => 'string',
            'description' => 'SMTP username/email',
            'is_public' => false
        ],
        [
            'setting_key' => 'email_smtp_password',
            'setting_value' => '',
            'setting_type' => 'string',
            'description' => 'SMTP password',
            'is_public' => false
        ],
        [
            'setting_key' => 'email_from_name',
            'setting_value' => 'WeBuy Store',
            'setting_type' => 'string',
            'description' => 'Default sender name for emails',
            'is_public' => true
        ],
        [
            'setting_key' => 'email_from_address',
            'setting_value' => 'noreply@webuytn.infy.uk',
            'setting_type' => 'string',
            'description' => 'Default sender email address',
            'is_public' => true
        ],
        [
            'setting_key' => 'email_enabled',
            'setting_value' => '1',
            'setting_type' => 'boolean',
            'description' => 'Enable/disable email system',
            'is_public' => true
        ],
        [
            'setting_key' => 'email_encryption',
            'setting_value' => 'tls',
            'setting_type' => 'string',
            'description' => 'Email encryption type (tls, ssl, none)',
            'is_public' => false
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) 
        VALUES (:key, :value, :type, :description, :public)
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value),
        setting_type = VALUES(setting_type),
        description = VALUES(description),
        is_public = VALUES(is_public)
    ");
    
    foreach ($email_settings as $setting) {
        $stmt->execute([
            ':key' => $setting['setting_key'],
            ':value' => $setting['setting_value'],
            ':type' => $setting['setting_type'],
            ':description' => $setting['description'],
            ':public' => $setting['is_public']
        ]);
    }
    
    echo "✅ Default email settings inserted successfully\n";
    
    // Insert other system settings
    $other_settings = [
        [
            'setting_key' => 'site_name',
            'setting_value' => 'WeBuy Store',
            'setting_type' => 'string',
            'description' => 'Website name',
            'is_public' => true
        ],
        [
            'setting_key' => 'site_description',
            'setting_value' => 'Your trusted online marketplace',
            'setting_type' => 'string',
            'description' => 'Website description',
            'is_public' => true
        ],
        [
            'setting_key' => 'maintenance_mode',
            'setting_value' => '0',
            'setting_type' => 'boolean',
            'description' => 'Enable maintenance mode',
            'is_public' => true
        ],
        [
            'setting_key' => 'max_upload_size',
            'setting_value' => '5242880',
            'setting_type' => 'integer',
            'description' => 'Maximum file upload size in bytes (5MB)',
            'is_public' => true
        ],
        [
            'setting_key' => 'allowed_file_types',
            'setting_value' => 'jpg,jpeg,png,gif,pdf,doc,docx',
            'setting_type' => 'string',
            'description' => 'Allowed file types for uploads',
            'is_public' => true
        ]
    ];
    
    foreach ($other_settings as $setting) {
        $stmt->execute([
            ':key' => $setting['setting_key'],
            ':value' => $setting['setting_value'],
            ':type' => $setting['setting_type'],
            ':description' => $setting['description'],
            ':public' => $setting['is_public']
        ]);
    }
    
    echo "✅ Other system settings inserted successfully\n";
    echo "✅ System settings setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 