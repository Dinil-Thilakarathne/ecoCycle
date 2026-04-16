-- MySQL Migration: Create collector_daily_status table
-- Purpose: Track daily availability status for each collector-vehicle pair

CREATE TABLE IF NOT EXISTS `collector_daily_status` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `collector_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `is_available` TINYINT(1) DEFAULT 1,
  `status_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_collector_date` (`collector_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for performance
CREATE INDEX `idx_cds_collector` ON `collector_daily_status`(`collector_id`);
CREATE INDEX `idx_cds_date` ON `collector_daily_status`(`date`);
CREATE INDEX `idx_cds_vehicle` ON `collector_daily_status`(`vehicle_id`);
CREATE INDEX `idx_cds_availability` ON `collector_daily_status`(`is_available`);

-- Add foreign key constraints
ALTER TABLE `collector_daily_status`
  ADD CONSTRAINT `fk_cds_collector` 
  FOREIGN KEY (`collector_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `collector_daily_status`
  ADD CONSTRAINT `fk_cds_vehicle` 
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
