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
     * Get feedback for a specific collector with customer name
     */
    public function getCollectorFeedback(int $collectorId, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $sql = "
            SELECT
                cf.id,
                cf.collector_id,
                cf.customer_id,
                u.name AS customer_name,
                cf.rating,
                cf.description AS feedback,
                cf.created_at
            FROM {$this->table} AS cf
            LEFT JOIN users AS u ON u.id = cf.customer_id
            WHERE cf.collector_id = ?
            ORDER BY cf.created_at DESC
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
        $sql = "SELECT COUNT(*) AS count FROM {$this->table} WHERE collector_id = ?";
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
                cf.id,
                cf.collector_id,
                cf.customer_id,
                u.name AS customer_name,
                cf.rating,
                cf.description AS feedback,
                cf.created_at
            FROM {$this->table} AS cf
            LEFT JOIN users AS u ON u.id = cf.customer_id
            WHERE cf.collector_id = ? AND cf.rating <= ?
            ORDER BY cf.created_at DESC
        ";

        return $this->db->fetchAll($sql, [$collectorId, $maxRating]);
    }

    // /**
    //  * Create new feedback
    //  */
    // public function create(array $data): bool
    // {
    //     if (empty($data['collector_id']) || empty($data['rating']) || empty($data['feedback'])) {
    //         return false;
    //     }

    //     return $this->db->insert($this->table, [
    //         'collector_id' => (int) $data['collector_id'],
    //         'customer_id'  => isset($data['customer_id']) ? (int) $data['customer_id'] : null,
    //         'rating'       => (int) $data['rating'],
    //         'description'  => trim($data['feedback']),
    //         'created_at'   => date('Y-m-d H:i:s')
    //     ]);
    // }

    /**
 * Create new feedback
 */
public function create(array $data): bool
{
    // Fix: Validate all required fields strictly
    if (empty($data['collector_id']) || empty($data['rating']) || empty($data['feedback'])) {
        return false;
    }

    return $this->db->insert($this->table, [
        'collector_id' => (int) $data['collector_id'],
        // Fix: Ensure customer_id is NULL if 0 or empty to satisfy DB constraints
        'customer_id'  => (!empty($data['customer_id'])) ? (int) $data['customer_id'] : null,
        'rating'       => (int) $data['rating'],
        'description'  => trim($data['feedback']),
        'created_at'   => date('Y-m-d H:i:s')
    ]);
}
}
