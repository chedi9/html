-- Create email_campaigns table for tracking automated email campaigns
CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    type ENUM('price_reduction', 'wishlist_promo', 'newsletter') NOT NULL,
    product_id INT(11) NULL,
    discount_percent INT(3) NULL,
    promo_message TEXT NULL,
    sent_count INT(11) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_product_id (product_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 