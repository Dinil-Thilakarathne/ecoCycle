-- Add legacy-compatible profile_image column and backfill from profile_image_path
-- MySQL / MariaDB
ALTER TABLE users
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) NULL AFTER profile_image_path;

UPDATE users
SET profile_image = profile_image_path
WHERE (profile_image IS NULL OR profile_image = '')
  AND profile_image_path IS NOT NULL
  AND profile_image_path <> '';

-- PostgreSQL
ALTER TABLE users
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255);

UPDATE users
SET profile_image = profile_image_path
WHERE (profile_image IS NULL OR profile_image = '')
  AND profile_image_path IS NOT NULL
  AND profile_image_path <> '';
