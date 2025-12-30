<?php

namespace Models;

class CollectorRating extends BaseModel
{
    protected string $table = 'collector_ratings';

    public function createTableIfNotExists(): bool
    {
        if ($this->db->isPgsql()) {
            $sql = "CREATE TABLE IF NOT EXISTS collector_ratings (
                id SERIAL PRIMARY KEY,
                customer_id INT NOT NULL,
                collector_id INT DEFAULT NULL,
                collector_name VARCHAR(255) NOT NULL,
                rating INT NOT NULL,
                description TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                rating_date DATE DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            return $this->db->query($sql);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `collector_ratings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT NOT NULL,
            `collector_id` INT DEFAULT NULL,
            `collector_name` VARCHAR(255) NOT NULL,
            `rating` INT NOT NULL,
            `description` TEXT DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `rating_date` DATE DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_cr_customer` (`customer_id`),
            INDEX `idx_cr_collector` (`collector_id`),
            CONSTRAINT `fk_cr_customer` FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_cr_collector` FOREIGN KEY (`collector_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        return $this->createTable($sql);
    }

    public function createForCustomer(int $customerId, array $payload): array
    {
        $data = [
            'customer_id' => $customerId,
            'collector_id' => $payload['collectorId'] ?? null,
            'collector_name' => $payload['collectorName'] ?? '',
            'rating' => (int) ($payload['rating'] ?? 0),
            'description' => $payload['description'] ?? null,
            'address' => $payload['address'] ?? null,
            'rating_date' => $payload['date'] ?? null,
        ];

        $inserted = $this->insert($this->table, $data);
        if ($inserted === false) {
            throw new \RuntimeException('Failed to save rating');
        }

        $id = (int) $inserted;
        $row = $this->find($id);
        return $row ?: [];
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        if (!$row) {
            return null;
        }

        return $row;
    }
}
