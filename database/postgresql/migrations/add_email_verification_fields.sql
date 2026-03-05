-- Add email verification fields to users table
-- Migration: add_email_verification_fields.sql

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS email_verification_sent_at TIMESTAMP NULL;

-- Add index for faster token lookups
CREATE INDEX IF NOT EXISTS idx_users_verification_token 
ON users(email_verification_token) 
WHERE email_verification_token IS NOT NULL;

-- Update existing users to be verified (grandfather clause)
-- This ensures existing users don't need to verify their emails
UPDATE users 
SET email_verified = TRUE 
WHERE email_verified IS NULL OR email_verified = FALSE;

-- Add comment for documentation
COMMENT ON COLUMN users.email_verified IS 'Whether the user has verified their email address';
COMMENT ON COLUMN users.email_verification_token IS 'Token used for email verification';
COMMENT ON COLUMN users.email_verification_sent_at IS 'When the verification email was last sent';
