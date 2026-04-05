<?php

namespace Models;

class CollectorRating extends BaseModel
{
    protected string $table = 'collector_ratings';

    public function createTableIfNotExists(): bool
    {
        $ok = false;

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
            $ok = $this->db->query($sql);
        } else {
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

            $ok = $this->createTable($sql);
        }

        $this->ensureSchema();
        return $ok;
    }

    public function createForCustomer(int $customerId, array $payload): array
    {
        $pickupRequestId = trim((string) ($payload['pickupRequestId'] ?? ''));
        if ($pickupRequestId === '') {
            throw new \InvalidArgumentException('Pickup request id is required.');
        }

        if (!$this->canCustomerRatePickupRequest($customerId, $pickupRequestId)) {
            throw new \InvalidArgumentException('You can only rate your own completed pickup requests.');
        }

        if ($this->hasCustomerRatedPickupRequest($customerId, $pickupRequestId)) {
            throw new \RuntimeException('You have already rated this pickup request.');
        }

        $data = [
            'customer_id' => $customerId,
            'pickup_request_id' => $pickupRequestId,
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

    public function hasCustomerRatedPickupRequest(int $customerId, string $pickupRequestId): bool
    {
        $pickupRequestId = trim($pickupRequestId);
        if ($customerId <= 0 || $pickupRequestId === '') {
            return false;
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS count
             FROM {$this->table}
             WHERE customer_id = ? AND pickup_request_id = ?",
            [$customerId, $pickupRequestId]
        );

        return (int) ($row['count'] ?? 0) > 0;
    }

    public function getRatedPickupRequestIds(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $rows = $this->db->fetchAll(
            "SELECT DISTINCT pickup_request_id
             FROM {$this->table}
             WHERE customer_id = ? AND pickup_request_id IS NOT NULL AND pickup_request_id <> ''",
            [$customerId]
        );

        if (!$rows) {
            return [];
        }

        $ids = array_map(
            static fn(array $row): string => trim((string) ($row['pickup_request_id'] ?? '')),
            $rows
        );
        $ids = array_filter($ids, static fn(string $id): bool => $id !== '');

        return array_values(array_unique($ids));
    }

    private function canCustomerRatePickupRequest(int $customerId, string $pickupRequestId): bool
    {
        if ($customerId <= 0 || $pickupRequestId === '') {
            return false;
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS count
             FROM pickup_requests
             WHERE id = ?
               AND customer_id = ?
               AND status = 'completed'",
            [$pickupRequestId, $customerId]
        );

        return (int) ($row['count'] ?? 0) > 0;
    }

    private function ensureSchema(): void
    {
        if ($this->db->isPgsql()) {
            try {
                $this->db->query("ALTER TABLE {$this->table} ADD COLUMN IF NOT EXISTS pickup_request_id VARCHAR(64) DEFAULT NULL");
                $this->db->query("CREATE INDEX IF NOT EXISTS idx_cr_pickup_request ON {$this->table} (pickup_request_id)");
                $this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS uq_cr_customer_pickup_request ON {$this->table} (customer_id, pickup_request_id) WHERE pickup_request_id IS NOT NULL");
            } catch (\Throwable $e) {
                // Best effort schema sync.
            }

            return;
        }

        try {
            if (!$this->mysqlSchemaHas('COLUMNS', "AND COLUMN_NAME = 'pickup_request_id'")) {
                $this->db->query("ALTER TABLE {$this->table} ADD COLUMN pickup_request_id VARCHAR(64) DEFAULT NULL AFTER customer_id");
            }

            if (!$this->mysqlSchemaHas('STATISTICS', "AND INDEX_NAME = 'idx_cr_pickup_request'")) {
                $this->db->query("CREATE INDEX idx_cr_pickup_request ON {$this->table} (pickup_request_id)");
            }

            if (!$this->mysqlSchemaHas('STATISTICS', "AND INDEX_NAME = 'uq_cr_customer_pickup_request'")) {
                $this->db->query("CREATE UNIQUE INDEX uq_cr_customer_pickup_request ON {$this->table} (customer_id, pickup_request_id)");
            }
        } catch (\Throwable $e) {
            // Best effort schema sync.
        }
    }

    private function mysqlSchemaHas(string $table, string $extraWhere): bool
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS count
             FROM information_schema.{$table}
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               {$extraWhere}",
            [$this->table]
        );

        return (int) ($row['count'] ?? 0) > 0;
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        return $row ?: null;
    }
}
