-- Add gender column to users table for targeted advertising and email campaigns
-- This script adds a gender field to the users table

-- Add gender column to users table
ALTER TABLE `users` 
ADD COLUMN `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') 
DEFAULT 'prefer_not_to_say' 
AFTER `email`;

-- Add index for better performance on gender-based queries
CREATE INDEX `idx_users_gender` ON `users` (`gender`);

-- Add comment to document the purpose
ALTER TABLE `users` 
MODIFY COLUMN `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') 
DEFAULT 'prefer_not_to_say' 
COMMENT 'Gender for targeted advertising and email campaigns';

-- Verify the change
DESCRIBE users; 