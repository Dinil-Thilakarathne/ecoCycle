<?php

namespace Models;

class CollectorDailyStatus extends BaseModel
{
    protected string $table = 'collector_daily_status';
    private ?bool $tableReady = null;

    private function ensureTableReady(): bool
    {
        if ($this->tableReady !== null) {
            return $this->tableReady;
        }

        if ($this->tableExists($this->table)) {
            $this->tableReady = true;
            return true;
        }

        $created = $this->createTableIfMissing();
        $this->tableReady = $created && $this->tableExists($this->table);

        return $this->tableReady;
    }

    private function createTableIfMissing(): bool
    {
        try {
            if ($this->db->isPgsql()) {
                return $this->db->query(
                    'CREATE TABLE IF NOT EXISTS collector_daily_status (
                        id SERIAL PRIMARY KEY,
                        collector_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                        vehicle_id INT NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE ON UPDATE CASCADE,
                        date DATE NOT NULL,
                        is_available BOOLEAN DEFAULT true,
                        status_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        notes TEXT DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT NULL,
                        CONSTRAINT unique_collector_date UNIQUE(collector_id, date)
                    )'
                )
                && $this->db->query('CREATE INDEX IF NOT EXISTS idx_cds_collector ON collector_daily_status(collector_id)')
                && $this->db->query('CREATE INDEX IF NOT EXISTS idx_cds_date ON collector_daily_status(date)')
                && $this->db->query('CREATE INDEX IF NOT EXISTS idx_cds_vehicle ON collector_daily_status(vehicle_id)')
                && $this->db->query('CREATE INDEX IF NOT EXISTS idx_cds_availability ON collector_daily_status(is_available)');
            }

            return $this->db->query(
                'CREATE TABLE IF NOT EXISTS `collector_daily_status` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `collector_id` INT NOT NULL,
                    `vehicle_id` INT NOT NULL,
                    `date` DATE NOT NULL,
                    `is_available` TINYINT(1) DEFAULT 1,
                    `status_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `notes` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_collector_date` (`collector_id`, `date`),
                    KEY `idx_cds_collector` (`collector_id`),
                    KEY `idx_cds_date` (`date`),
                    KEY `idx_cds_vehicle` (`vehicle_id`),
                    KEY `idx_cds_availability` (`is_available`),
                    CONSTRAINT `fk_cds_collector` FOREIGN KEY (`collector_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_cds_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get today's status for a specific collector
     */
    public function getTodayStatus(int $collectorId): ?array
    {
        if (!$this->ensureTableReady()) {
            return null;
        }

        $today = date('Y-m-d');
        $sql = "SELECT * FROM {$this->table} WHERE collector_id = ? AND date = ?";
        $row = $this->db->fetch($sql, [$collectorId, $today]);

        if (!$row) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    /**
     * Get all statuses for today
     */
    public function getAllTodayStatuses(): array
    {
        if (!$this->ensureTableReady()) {
            return [];
        }

        $today = date('Y-m-d');
        $sql = "SELECT cds.*, u.name as collector_name, v.plate_number 
                FROM {$this->table} cds
                JOIN users u ON cds.collector_id = u.id
                JOIN vehicles v ON cds.vehicle_id = v.id
                WHERE cds.date = ?
                ORDER BY u.name ASC";

        $rows = $this->db->fetchAll($sql, [$today]);

        if (!$rows) {
            return [];
        }

        return array_map([$this, 'normalizeRow'], $rows);
    }

    /**
     * Update or create today's status for a collector
     */
    public function updateStatus(int $collectorId, int $vehicleId, bool $isAvailable, ?string $notes = null): array
    {
        if (!$this->ensureTableReady()) {
            throw new \RuntimeException('collector_daily_status table is not available.');
        }

        $today = date('Y-m-d');

        // Check if record exists for today
        $existing = $this->getTodayStatus($collectorId);

        if ($existing) {
            // Update existing record
            $sql = "UPDATE {$this->table} 
                    SET is_available = ?, 
                        notes = ?, 
                        status_updated_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE collector_id = ? AND date = ?";

            $this->db->query($sql, [$isAvailable, $notes, $collectorId, $today]);

            return $this->getTodayStatus($collectorId) ?? [];
        } else {
            // Create new record
            $sql = "INSERT INTO {$this->table} 
                    (collector_id, vehicle_id, date, is_available, notes, status_updated_at, created_at)
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

            $params = [$collectorId, $vehicleId, $today, $isAvailable, $notes];

            if ($this->db->isPgsql()) {
                $row = $this->db->fetch($sql . ' RETURNING id', $params);
                $id = $row && isset($row['id']) ? (int) $row['id'] : 0;
            } else {
                $this->db->query($sql, $params);
                $id = (int) $this->db->lastInsertId();
            }

            return $this->find($id) ?? [];
        }
    }

    /**
     * Reset all statuses to available for a new day
     * This should be called by a cron job daily
     */
    public function resetDailyStatuses(): bool
    {
        if (!$this->ensureTableReady()) {
            return false;
        }

        $today = date('Y-m-d');

        // Get all collectors with assigned vehicles
        $sql = "SELECT u.id as collector_id, u.vehicle_id 
                FROM users u 
                WHERE u.type = 'collector' 
                AND u.vehicle_id IS NOT NULL 
                AND u.status = 'active'";

        $collectors = $this->db->fetchAll($sql);

        if (!$collectors) {
            return true;
        }

        // Create today's status records for all collectors (set to available)
        foreach ($collectors as $collector) {
            $collectorId = (int) $collector['collector_id'];
            $vehicleId = (int) $collector['vehicle_id'];

            // Check if record already exists
            $existing = $this->getTodayStatus($collectorId);

            if (!$existing) {
                // Only create if doesn't exist (don't override manual updates)
                $insertSql = "INSERT INTO {$this->table} 
                             (collector_id, vehicle_id, date, is_available, status_updated_at, created_at)
                             VALUES (?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

                $this->db->query($insertSql, [$collectorId, $vehicleId, $today]);
            }
        }

        return true;
    }

    /**
     * Get status for a specific collector on a specific date
     */
    public function getStatusByDate(int $collectorId, string $date): ?array
    {
        if (!$this->ensureTableReady()) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE collector_id = ? AND date = ?";
        $row = $this->db->fetch($sql, [$collectorId, $date]);

        if (!$row) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    /**
     * Get availability history for a collector
     */
    public function getCollectorHistory(int $collectorId, int $limit = 30): array
    {
        if (!$this->ensureTableReady()) {
            return [];
        }

        $sql = "SELECT * FROM {$this->table} 
                WHERE collector_id = ? 
                ORDER BY date DESC 
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$collectorId, $limit]);

        if (!$rows) {
            return [];
        }

        return array_map([$this, 'normalizeRow'], $rows);
    }

    /**
     * Find a status record by ID
     */
    public function find(int $id): ?array
    {
        if (!$this->ensureTableReady()) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $row = $this->db->fetch($sql, [$id]);

        if (!$row) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    /**
     * Normalize database row to camelCase
     */
    private function normalizeRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'collectorId' => (int) $row['collector_id'],
            'vehicleId' => (int) $row['vehicle_id'],
            'date' => $row['date'] ?? null,
            'isAvailable' => (bool) ($row['is_available'] ?? true),
            'statusUpdatedAt' => $row['status_updated_at'] ?? null,
            'notes' => $row['notes'] ?? null,
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
            // Additional fields if joined
            'collectorName' => $row['collector_name'] ?? null,
            'plateNumber' => $row['plate_number'] ?? null,
        ];
    }
}
