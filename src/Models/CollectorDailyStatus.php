<?php

namespace Models;

class CollectorDailyStatus extends BaseModel
{
    protected string $table = 'collector_daily_status';

    /**
     * Get today's status for a specific collector
     */
    public function getTodayStatus(int $collectorId): ?array
    {
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
