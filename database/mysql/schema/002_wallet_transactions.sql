CREATE TABLE IF NOT EXISTS `wallet_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `source_type` ENUM('pickup', 'payout', 'adjustment') NOT NULL,
    `source_id` INT NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_wt_user` (`user_id`),
    INDEX `idx_wt_source` (`source_type`, `source_id`),
    CONSTRAINT `fk_wt_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
