<?php

namespace Models;

class CollectorRating extends BaseModel
{
    protected string $table = 'collector_ratings';
    private ?bool $pickupRequestIdColumnExists = null;

    public function createTableIfNotExists(): bool
    {
        $created = false;

        if ($this->db->isPgsql()) {
            $sql = "CREATE TABLE IF NOT EXISTS collector_ratings (
                id SERIAL PRIMARY KEY,
                customer_id INT NOT NULL,
                collector_id INT DEFAULT NULL,
                collector_name VARCHAR(255) NOT NULL,
                pickup_request_id VARCHAR(255) DEFAULT NULL,
                rating INT NOT NULL,
                description TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                rating_date DATE DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_cr_pickup_request FOREIGN KEY (pickup_request_id) REFERENCES pickup_requests(id) ON DELETE SET NULL ON UPDATE CASCADE
            )";
            $created = $this->db->query($sql);
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `collector_ratings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `customer_id` INT NOT NULL,
                `collector_id` INT DEFAULT NULL,
                `collector_name` VARCHAR(255) NOT NULL,
                `pickup_request_id` VARCHAR(255) DEFAULT NULL,
                `rating` INT NOT NULL,
                `description` TEXT DEFAULT NULL,
                `address` TEXT DEFAULT NULL,
                `rating_date` DATE DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_cr_customer` (`customer_id`),
                INDEX `idx_cr_collector` (`collector_id`),
                INDEX `idx_cr_pickup_request` (`pickup_request_id`),
                CONSTRAINT `fk_cr_customer` FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_cr_collector` FOREIGN KEY (`collector_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_cr_pickup_request` FOREIGN KEY (`pickup_request_id`) REFERENCES `pickup_requests`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $created = $this->createTable($sql);
        }

        if (!$created) {
            return false;
        }

        $this->ensurePickupRequestIdColumnExists();
        $this->backfillCollectorIdFromPickupRequests();
        $this->syncCollectorIdsByCustomer();

        return true;
    }

    public function createForCustomer(int $customerId, array $payload): array
    {
        $this->ensurePickupRequestIdColumnExists();

        $pickupRequestId = trim((string) ($payload['pickupRequestId'] ?? ''));
        $collectorId = (int) ($payload['collectorId'] ?? 0);

        if ($pickupRequestId !== '') {
            $resolvedCollectorId = $this->resolveCollectorIdByPickupRequest($pickupRequestId, $customerId);
            if ($resolvedCollectorId > 0) {
                $collectorId = $resolvedCollectorId;
            }
        }

        $data = [
            'customer_id' => $customerId,
            'collector_id' => $collectorId > 0 ? $collectorId : null,
            'collector_name' => $payload['collectorName'] ?? '',
            'rating' => (int) ($payload['rating'] ?? 0),
            'description' => $payload['description'] ?? null,
            'address' => $payload['address'] ?? null,
            'rating_date' => $payload['date'] ?? null,
        ];

        if ($pickupRequestId !== '' && $this->hasPickupRequestIdColumn()) {
            $data['pickup_request_id'] = $pickupRequestId;
        }

        $inserted = $this->insert($this->table, $data);
        if ($inserted === false) {
            throw new \RuntimeException('Failed to save rating');
        }

        $this->syncCollectorIdsByCustomer();

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

    private function hasPickupRequestIdColumn(): bool
    {
        if ($this->pickupRequestIdColumnExists !== null) {
            return $this->pickupRequestIdColumnExists;
        }

        try {
            if ($this->db->isPgsql()) {
                $row = $this->db->fetch(
                    'SELECT EXISTS (
                        SELECT 1
                        FROM information_schema.columns
                        WHERE table_schema = ? AND table_name = ? AND column_name = ?
                    ) AS exists_flag',
                    ['public', 'collector_ratings', 'pickup_request_id']
                );
            } else {
                $row = $this->db->fetch(
                    'SELECT COUNT(*) AS count
                     FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                    ['collector_ratings', 'pickup_request_id']
                );
            }

            $flag = $row['exists_flag'] ?? $row['count'] ?? false;
            $this->pickupRequestIdColumnExists = $this->db->isPgsql()
                ? (bool) $flag
                : ((int) $flag > 0);
        } catch (\Throwable $e) {
            $this->pickupRequestIdColumnExists = false;
        }

        return $this->pickupRequestIdColumnExists;
    }

    private function ensurePickupRequestIdColumnExists(): void
    {
        if ($this->hasPickupRequestIdColumn()) {
            return;
        }

        try {
            if ($this->db->isPgsql()) {
                $this->db->query('ALTER TABLE collector_ratings ADD COLUMN IF NOT EXISTS pickup_request_id VARCHAR(255) DEFAULT NULL');
                $this->db->query('CREATE INDEX IF NOT EXISTS idx_cr_pickup_request ON collector_ratings (pickup_request_id)');
            } else {
                $this->db->query('ALTER TABLE `collector_ratings` ADD COLUMN `pickup_request_id` VARCHAR(255) DEFAULT NULL');
                $this->db->query('CREATE INDEX `idx_cr_pickup_request` ON `collector_ratings` (`pickup_request_id`)');
            }
            $this->pickupRequestIdColumnExists = true;
        } catch (\Throwable $e) {
           
        }
    }

    private function resolveCollectorIdByPickupRequest(string $pickupRequestId, int $customerId): int
    {
        if ($pickupRequestId === '' || $customerId <= 0) {
            return 0;
        }

        $row = $this->db->fetch(
            'SELECT collector_id FROM pickup_requests WHERE id = ? AND customer_id = ? LIMIT 1',
            [$pickupRequestId, $customerId]
        );

        return (int) ($row['collector_id'] ?? 0);
    }

    private function backfillCollectorIdFromPickupRequests(): void
    {
        if (!$this->hasPickupRequestIdColumn()) {
            return;
        }

        try {
            if ($this->db->isPgsql()) {
                $this->db->query(
                    "UPDATE collector_ratings cr
                     SET collector_id = pr.collector_id
                     FROM pickup_requests pr
                     WHERE cr.pickup_request_id = pr.id
                       AND (cr.collector_id IS NULL OR cr.collector_id = 0)
                       AND pr.collector_id IS NOT NULL"
                );
            } else {
                $this->db->query(
                    "UPDATE collector_ratings cr
                     INNER JOIN pickup_requests pr ON pr.id = cr.pickup_request_id
                     SET cr.collector_id = pr.collector_id
                     WHERE (cr.collector_id IS NULL OR cr.collector_id = 0)
                       AND pr.collector_id IS NOT NULL"
                );
            }
        } catch (\Throwable $e) {
        
        }
    }

    private function syncCollectorIdsByCustomer(): void
    {
        try {
            if ($this->db->isPgsql()) {
              
                $this->db->query(
                    "WITH latest_pickup AS (
                        SELECT DISTINCT ON (customer_id)
                            customer_id,
                            collector_id
                        FROM pickup_requests
                        WHERE collector_id IS NOT NULL
                        ORDER BY customer_id, COALESCE(updated_at, created_at) DESC
                    )
                    UPDATE collector_ratings cr
                    SET collector_id = lp.collector_id
                    FROM latest_pickup lp
                    WHERE cr.customer_id = lp.customer_id
                      AND (cr.collector_id IS NULL OR cr.collector_id = 0)"
                );

               
                $this->db->query(
                    "WITH latest_rating AS (
                        SELECT DISTINCT ON (customer_id)
                            customer_id,
                            collector_id
                        FROM collector_ratings
                        WHERE collector_id IS NOT NULL
                        ORDER BY customer_id, created_at DESC
                    )
                    UPDATE pickup_requests pr
                    SET collector_id = lr.collector_id
                    FROM latest_rating lr
                    WHERE pr.customer_id = lr.customer_id
                      AND (pr.collector_id IS NULL OR pr.collector_id = 0)"
                );
            } else {
               
                $this->db->query(
                    "UPDATE collector_ratings cr
                     INNER JOIN (
                        SELECT p1.customer_id, p1.collector_id
                        FROM pickup_requests p1
                        INNER JOIN (
                            SELECT customer_id, MAX(COALESCE(updated_at, created_at)) AS max_ts
                            FROM pickup_requests
                            WHERE collector_id IS NOT NULL
                            GROUP BY customer_id
                        ) p2 ON p1.customer_id = p2.customer_id AND COALESCE(p1.updated_at, p1.created_at) = p2.max_ts
                        WHERE p1.collector_id IS NOT NULL
                     ) src ON src.customer_id = cr.customer_id
                     SET cr.collector_id = src.collector_id
                     WHERE cr.collector_id IS NULL OR cr.collector_id = 0"
                );

               
                $this->db->query(
                    "UPDATE pickup_requests pr
                     INNER JOIN (
                        SELECT r1.customer_id, r1.collector_id
                        FROM collector_ratings r1
                        INNER JOIN (
                            SELECT customer_id, MAX(created_at) AS max_ts
                            FROM collector_ratings
                            WHERE collector_id IS NOT NULL
                            GROUP BY customer_id
                        ) r2 ON r1.customer_id = r2.customer_id AND r1.created_at = r2.max_ts
                        WHERE r1.collector_id IS NOT NULL
                     ) src ON src.customer_id = pr.customer_id
                     SET pr.collector_id = src.collector_id
                     WHERE pr.collector_id IS NULL OR pr.collector_id = 0"
                );
            }
        } catch (\Throwable $e) {
        
        }
    }
}
