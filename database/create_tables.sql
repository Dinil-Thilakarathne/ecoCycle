-- Schema for ecoCycle admin backend
-- Run with: mysql -u <user> -p <database> < database/create_tables.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `label` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vehicles (create before users so users.vehicle_id FK can reference it)
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plate_number` VARCHAR(32) NOT NULL UNIQUE,
  `type` VARCHAR(64) DEFAULT NULL,
  `capacity` INT DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'available',
  `last_maintenance` DATE DEFAULT NULL,
  `next_maintenance` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table (customers/companies/collectors/admins)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('customer','company','collector','admin') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(255) DEFAULT NULL,
  `username` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `profile_image_path` VARCHAR(255) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `role_id` INT DEFAULT NULL,
  `vehicle_id` INT DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'active',
  `total_pickups` INT DEFAULT 0,
  `total_earnings` DECIMAL(12,2) DEFAULT 0.00,
  `total_bids` INT DEFAULT 0,
  `total_purchases` INT DEFAULT 0,
  `metadata` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `users_email_unique` (`email`),
  INDEX `idx_users_type` (`type`),
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_role` (`role_id`),
  INDEX `idx_users_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_vehicles` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Waste categories
CREATE TABLE IF NOT EXISTS `waste_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL UNIQUE,
  `color` VARCHAR(32) DEFAULT NULL,
  `default_minimum_bid` DECIMAL(12,2) DEFAULT NULL,
  `unit` VARCHAR(16) DEFAULT 'kg',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pickup requests
CREATE TABLE IF NOT EXISTS `pickup_requests` (
  `id` VARCHAR(64) PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `address` TEXT DEFAULT NULL,
  `time_slot` VARCHAR(64) DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'pending',
  `collector_id` INT DEFAULT NULL,
  `collector_name` VARCHAR(255) DEFAULT NULL,
  `scheduled_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_pickup_status` (`status`),
  INDEX `idx_pickup_time_slot` (`time_slot`),
  INDEX `idx_pickup_customer` (`customer_id`),
  INDEX `idx_pickup_collector` (`collector_id`),
  CONSTRAINT `fk_pickup_customer` FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pickup_collector` FOREIGN KEY (`collector_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pivot: pickup_request_wastes
CREATE TABLE IF NOT EXISTS `pickup_request_wastes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pickup_id` VARCHAR(64) NOT NULL,
  `waste_category_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) DEFAULT NULL,
  `unit` VARCHAR(16) DEFAULT NULL,
  INDEX `idx_prw_pickup` (`pickup_id`),
  INDEX `idx_prw_category` (`waste_category_id`),
  CONSTRAINT `fk_prw_pickup` FOREIGN KEY (`pickup_id`) REFERENCES `pickup_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prw_category` FOREIGN KEY (`waste_category_id`) REFERENCES `waste_categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bidding rounds / lots
CREATE TABLE IF NOT EXISTS `bidding_rounds` (
  `id` VARCHAR(64) PRIMARY KEY,
  `lot_id` VARCHAR(64) DEFAULT NULL UNIQUE,
  `waste_category_id` INT DEFAULT NULL,
  `quantity` DECIMAL(12,2) DEFAULT NULL,
  `unit` VARCHAR(16) DEFAULT 'kg',
  `starting_bid` DECIMAL(12,2) DEFAULT 0.00,
  `current_highest_bid` DECIMAL(12,2) DEFAULT 0.00,
  `leading_company_id` INT DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'active',
  `end_time` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_bidding_status` (`status`),
  INDEX `idx_bidding_end` (`end_time`),
  INDEX `idx_bidding_category` (`waste_category_id`),
  CONSTRAINT `fk_bidding_category` FOREIGN KEY (`waste_category_id`) REFERENCES `waste_categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bidding_leader` FOREIGN KEY (`leading_company_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bids history
CREATE TABLE IF NOT EXISTS `bids` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `bidding_round_id` VARCHAR(64) NOT NULL,
  `company_id` INT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `is_winner` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_bids_round` (`bidding_round_id`),
  INDEX `idx_bids_company` (`company_id`),
  CONSTRAINT `fk_bids_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bids_company` FOREIGN KEY (`company_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments / transactions
CREATE TABLE IF NOT EXISTS `payments` (
  `id` VARCHAR(64) PRIMARY KEY,
  `txn_id` VARCHAR(128) DEFAULT NULL,
  `type` VARCHAR(32) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `recipient_id` INT DEFAULT NULL,
  `recipient_name` VARCHAR(255) DEFAULT NULL,
  `date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(32) DEFAULT 'pending',
  `gateway_response` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_payments_type` (`type`),
  INDEX `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` VARCHAR(64) PRIMARY KEY,
  `type` VARCHAR(64) DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `recipients` JSON DEFAULT NULL,
  `recipient_group` VARCHAR(64) DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'pending',
  `sent_at` DATETIME DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_notifications_type` (`type`),
  INDEX `idx_notifications_status` (`status`),
  CONSTRAINT `fk_notifications_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System alerts (config)
CREATE TABLE IF NOT EXISTS `system_alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'inactive',
  `settings` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional analytics cache table
CREATE TABLE IF NOT EXISTS `analytics_aggregates` (
  `key` VARCHAR(128) PRIMARY KEY,
  `value` JSON DEFAULT NULL,
  `computed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Small seeds for roles
INSERT IGNORE INTO roles (name, label) VALUES
  ('admin', 'Administrator'),
  ('manager', 'Manager'),
  ('collector', 'Collector'),
  ('company', 'Company'),
  ('customer', 'Customer');

-- End of schema
