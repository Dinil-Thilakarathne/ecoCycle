-- 002_add_profile_image_to_users.sql
-- Adds profile image path column to users table

ALTER TABLE users
  ADD COLUMN profile_image_path VARCHAR(255) NULL AFTER address;
