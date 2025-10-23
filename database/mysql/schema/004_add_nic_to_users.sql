-- Migration: Add NIC field to users table
-- MySQL Version

-- Add nic column to users table
ALTER TABLE `users` ADD COLUMN `nic` VARCHAR(20) DEFAULT NULL AFTER `phone`;

-- Add comment for documentation
ALTER TABLE `users` MODIFY COLUMN `nic` VARCHAR(20) DEFAULT NULL COMMENT 'National Identity Card number';

-- Optional: Create an index if NIC will be searched frequently
-- CREATE INDEX idx_users_nic ON users(nic);
