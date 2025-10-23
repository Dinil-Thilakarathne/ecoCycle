-- Migration: Add NIC field to users table
-- PostgreSQL Version

-- Add nic column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS nic VARCHAR(20) DEFAULT NULL;

-- Add comment for documentation
COMMENT ON COLUMN users.nic IS 'National Identity Card number';

-- Optional: Create an index if NIC will be searched frequently
-- CREATE INDEX idx_users_nic ON users(nic);
