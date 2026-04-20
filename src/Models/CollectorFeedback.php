<?php

namespace Models;

use Core\Database;

class CollectorFeedback
{
    private Database $db;
    private string $table = 'collector_ratings';
    private ?bool $pickupRequestIdColumnExists = null;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getCollectorFeedback(int $collectorId, int $limit = 50, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = "
            SELECT
                cr.id,
                cr.collector_id,
                cr.customer_id,
                u.name AS customer_name,
                cr.rating,
                cr.description,
                cr.rating_date
            FROM {$this->table} cr
            LEFT JOIN users u ON u.id = cr.customer_id
            WHERE cr.collector_id = ?
            ORDER BY cr.rating_date DESC
            LIMIT $limit OFFSET $offset
        ";

        return $this->db->fetchAll($sql, [$collectorId]);
    }

    public function getAverageRating(int $collectorId): float
    {
        $sql = "
            SELECT AVG(rating) AS avg_rating
            FROM {$this->table}
            WHERE collector_id = ?
        ";

        $row = $this->db->fetchOne($sql, [$collectorId]);
        return round((float)($row['avg_rating'] ?? 0), 1);
    }

    public function getCollectorFeedbackCount(int $collectorId): int
    {
        $sql = "
            SELECT COUNT(*) AS count
            FROM {$this->table}
            WHERE collector_id = ?
        ";

        $row = $this->db->fetchOne($sql, [$collectorId]);
        return (int)($row['count'] ?? 0);
    }

    public function getLowRatings(int $collectorId, int $maxRating = 2): array
    {
        $sql = "
            SELECT
                cr.id,
                cr.collector_id,
                cr.customer_id,
                u.name AS customer_name,
                cr.rating,
                cr.description,
                cr.rating_date
            FROM {$this->table} cr
            LEFT JOIN users u ON u.id = cr.customer_id
            WHERE cr.collector_id = ? AND cr.rating <= ?
            ORDER BY cr.rating_date DESC
        ";

        return $this->db->fetchAll($sql, [$collectorId, $maxRating]);
    }


    public function create(array $data): bool
    {
        if (
            empty($data['collector_id']) ||
            empty($data['rating']) ||
            empty($data['description'])
        ) {
            return false;
        }

        $insertData = [
            'collector_id' => (int) $data['collector_id'],
            'customer_id'  => !empty($data['customer_id']) ? (int) $data['customer_id'] : null,
            'rating'       => (int) $data['rating'],
            'description'  => trim($data['description']),
            'rating_date'  => date('Y-m-d H:i:s')
        ];

        $pickupRequestId = trim((string) ($data['pickup_request_id'] ?? ''));
        if ($pickupRequestId !== '' && $this->hasPickupRequestIdColumn()) {
            $insertData['pickup_request_id'] = $pickupRequestId;
        }

        return $this->db->insert($this->table, $insertData);
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
                $this->pickupRequestIdColumnExists = (bool) ($row['exists_flag'] ?? false);
            } else {
                $row = $this->db->fetch(
                    'SELECT COUNT(*) AS count
                     FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                    ['collector_ratings', 'pickup_request_id']
                );
                $this->pickupRequestIdColumnExists = ((int) ($row['count'] ?? 0)) > 0;
            }
        } catch (\Throwable $e) {
            $this->pickupRequestIdColumnExists = false;
        }

        return $this->pickupRequestIdColumnExists;
    }
}
