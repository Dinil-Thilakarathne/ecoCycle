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
     * Get all feedback for a specific collector
     */
    public function getCollectorFeedback(int $collectorId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT cf.id, cf.rating, cf.feedback, cf.feedback_type, cf.status, 
                    cf.created_at, u.name as collector_name, cu.name as customer_name
             FROM {$this->table} cf
             LEFT JOIN users u ON cf.collector_id = u.id
             LEFT JOIN users cu ON cf.customer_id = cu.id
             WHERE cf.collector_id = ? AND cf.status = 'active'
             ORDER BY cf.created_at DESC
             LIMIT ? OFFSET ?",
            [$collectorId, $limit, $offset]
        );
    }

    /**
     * Get average rating for a collector
     */
    public function getAverageRating(int $collectorId): float
    {
        $result = $this->db->fetchOne(
            "SELECT AVG(rating) as avg_rating FROM {$this->table} 
             WHERE collector_id = ? AND status = 'active'",
            [$collectorId]
        );
        return (float)($result['avg_rating'] ?? 0);
    }

    /**
     * Get rating breakdown (count by star rating)
     */
    public function getRatingBreakdown(int $collectorId): array
    {
        return $this->db->fetchAll(
            "SELECT rating, COUNT(*) as count
             FROM {$this->table}
             WHERE collector_id = ? AND status = 'active'
             GROUP BY rating
             ORDER BY rating DESC",
            [$collectorId]
        );
    }

    /**
     * Get all feedback with optional filtering
     */
    public function getAllFeedback(
        int $limit = 50,
        int $offset = 0,
        string $status = 'active',
        ?string $sortBy = 'created_at'
    ): array {
        $allowed_sorts = ['created_at', 'rating', 'collector_id'];
        $sortBy = in_array($sortBy, $allowed_sorts) ? $sortBy : 'created_at';

        return $this->db->fetchAll(
            "SELECT cf.id, cf.collector_id, cf.rating, cf.feedback, cf.feedback_type, 
                    cf.status, cf.created_at, u.name as collector_name, cu.name as customer_name
             FROM {$this->table} cf
             LEFT JOIN users u ON cf.collector_id = u.id
             LEFT JOIN users cu ON cf.customer_id = cu.id
             WHERE cf.status = ?
             ORDER BY cf.{$sortBy} DESC
             LIMIT ? OFFSET ?",
            [$status, $limit, $offset]
        );
    }

    /**
     * Add new feedback
     */
    public function create(array $data): array
    {
        $this->db->insert($this->table, [
            'collector_id' => $data['collector_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'pickup_request_id' => $data['pickup_request_id'] ?? null,
            'rating' => $data['rating'],
            'feedback' => $data['feedback'] ?? null,
            'status' => $data['status'] ?? 'active',
            'feedback_type' => $data['feedback_type'] ?? 'general',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $id = $this->db->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Find feedback by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT cf.*, u.name as collector_name, cu.name as customer_name
             FROM {$this->table} cf
             LEFT JOIN users u ON cf.collector_id = u.id
             LEFT JOIN users cu ON cf.customer_id = cu.id
             WHERE cf.id = ?",
            [$id]
        );
    }

    /**
     * Update feedback
     */
    public function update(int $id, array $data): bool
    {
        $updateFields = [];
        $values = [];

        if (isset($data['rating'])) {
            $updateFields[] = 'rating = ?';
            $values[] = $data['rating'];
        }
        if (isset($data['feedback'])) {
            $updateFields[] = 'feedback = ?';
            $values[] = $data['feedback'];
        }
        if (isset($data['status'])) {
            $updateFields[] = 'status = ?';
            $values[] = $data['status'];
        }
        if (isset($data['feedback_type'])) {
            $updateFields[] = 'feedback_type = ?';
            $values[] = $data['feedback_type'];
        }

        if (empty($updateFields)) {
            return false;
        }

        $updateFields[] = 'updated_at = ?';
        $values[] = date('Y-m-d H:i:s');
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $this->db->query($sql, $values);

        return true;
    }

    /**
     * Delete feedback (soft delete - mark as archived)
     */
    public function delete(int $id): bool
    {
        return $this->update($id, ['status' => 'archived']);
    }

    /**
     * Get feedback count for collector
     */
    public function getCollectorFeedbackCount(int $collectorId): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE collector_id = ? AND status = 'active'",
            [$collectorId]
        );
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get pending feedback (flagged for review)
     */
    public function getPendingFeedback(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT cf.*, u.name as collector_name, cu.name as customer_name
             FROM {$this->table} cf
             LEFT JOIN users u ON cf.collector_id = u.id
             LEFT JOIN users cu ON cf.customer_id = cu.id
             WHERE cf.status = 'flagged'
             ORDER BY cf.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get low ratings (complaints)
     */
    public function getLowRatings(int $maxRating = 2, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT cf.*, u.name as collector_name, cu.name as customer_name
             FROM {$this->table} cf
             LEFT JOIN users u ON cf.collector_id = u.id
             LEFT JOIN users cu ON cf.customer_id = cu.id
             WHERE cf.rating <= ? AND cf.status = 'active'
             ORDER BY cf.created_at DESC
             LIMIT ?",
            [$maxRating, $limit]
        );
    }
}
