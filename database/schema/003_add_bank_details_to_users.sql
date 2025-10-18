-- Add dedicated bank detail columns for user profiles
ALTER TABLE users
  ADD COLUMN bank_account_name VARCHAR(255) NULL AFTER address,
  ADD COLUMN bank_account_number VARCHAR(100) NULL AFTER bank_account_name,
  ADD COLUMN bank_name VARCHAR(150) NULL AFTER bank_account_number,
  ADD COLUMN bank_branch VARCHAR(150) NULL AFTER bank_name;
