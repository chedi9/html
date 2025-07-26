-- Create newsletter_logs table for tracking sent newsletters
CREATE TABLE IF NOT EXISTS newsletter_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    recipients ENUM('all', 'sellers', 'customers') NOT NULL,
    sent_count INT(11) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_recipients (recipients)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 