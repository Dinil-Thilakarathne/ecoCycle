<?php

namespace Models;

use Core\Database;

class CollectorFeedback
{
    private Database $db;
    private string $table = 'customer_ratings';

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get feedback for a specific collector with customer name
     */
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

    /**
     * Get average rating for collector
     */
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

    /**
     * Get total feedback count
     */
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

    /**
     * Get low ratings (≤ 2)
     */
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

    /**
     * Create new feedback
     */
    public function create(array $data): bool
    {
        if (
            empty($data['collector_id']) ||
            empty($data['rating']) ||
            empty($data['description'])
        ) {
            return false;
        }

        return $this->db->insert($this->table, [
            'collector_id' => (int) $data['collector_id'],
            'customer_id'  => !empty($data['customer_id']) ? (int) $data['customer_id'] : null,
            'rating'       => (int) $data['rating'],
            'description'  => trim($data['description']),
            'rating_date'  => date('Y-m-d H:i:s')
        ]);
    }
}
