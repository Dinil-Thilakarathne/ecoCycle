<?php

namespace Models;

use Core\Database;

class CollectorFeedback
{
    private Database $db;
    private string $table = 'collector_feedback';

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get feedback for a specific collector
     */
    public function getCollectorFeedback(int $collectorId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT
                id,
                collector_id,
                collector_name,
                rating,
                description AS feedback,
                created_at
             FROM {$this->table}
             WHERE collector_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$collectorId, $limit, $offset]
        );
    }

    /**
     * Get average rating for collector
     */
    public function getAverageRating(int $collectorId): float
    {
        $row = $this->db->fetchOne(
            "SELECT AVG(rating) AS avg_rating
             FROM {$this->table}
             WHERE collector_id = ?",
            [$collectorId]
        );

        return (float) ($row['avg_rating'] ?? 0);
    }

    /**
     * Get total feedback count
     */
    public function getCollectorFeedbackCount(int $collectorId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS count
             FROM {$this->table}
             WHERE collector_id = ?",
            [$collectorId]
        );

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Get low ratings (≤ 2)
     */
    public function getLowRatings(int $collectorId, int $maxRating = 2): array
    {
        return $this->db->fetchAll(
            "SELECT
                id,
                collector_id,
                collector_name,
                rating,
                description AS feedback,
                created_at
             FROM {$this->table}
             WHERE collector_id = ? AND rating <= ?
             ORDER BY created_at DESC",
            [$collectorId, $maxRating]
        );
    }

    /**
     * Create new feedback
     */
    public function create(array $data): bool
    {
        return $this->db->insert($this->table, [
            'collector_id'   => $data['collector_id'],
            'customer_id'    => $data['customer_id'] ?? null,
            'collector_name' => $data['collector_name'] ?? null,
            'rating'         => $data['rating'],
            'description'    => $data['feedback'], // mapped correctly
            'created_at'     => date('Y-m-d H:i:s')
        ]);
    }
}
