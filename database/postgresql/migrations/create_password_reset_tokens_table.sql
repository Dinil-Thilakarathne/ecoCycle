-- Create password reset tokens table
-- Migration: create_password_reset_tokens_table.sql

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_password_reset_token ON password_reset_tokens(token);
CREATE INDEX IF NOT EXISTS idx_password_reset_email ON password_reset_tokens(email);
CREATE INDEX IF NOT EXISTS idx_password_reset_expires ON password_reset_tokens(expires_at);

-- Add comments for documentation
COMMENT ON TABLE password_reset_tokens IS 'Stores password reset tokens for user password recovery';
COMMENT ON COLUMN password_reset_tokens.email IS 'Email address of the user requesting password reset';
COMMENT ON COLUMN password_reset_tokens.token IS 'Unique token for password reset verification';
COMMENT ON COLUMN password_reset_tokens.expires_at IS 'When this token expires (typically 1 hour from creation)';
COMMENT ON COLUMN password_reset_tokens.used IS 'Whether this token has been used';
COMMENT ON COLUMN password_reset_tokens.used_at IS 'When this token was used';
