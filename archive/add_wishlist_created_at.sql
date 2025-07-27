-- Add created_at column to wishlist table
-- This fixes the "Undefined array key created_at" error

ALTER TABLE wishlist 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Update existing records to have a created_at value
UPDATE wishlist 
SET created_at = CURRENT_TIMESTAMP 
WHERE created_at IS NULL;

-- Add index for better performance
CREATE INDEX idx_wishlist_created_at ON wishlist(created_at);

-- Verify the change
DESCRIBE wishlist; 