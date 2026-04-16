<?php

namespace Services;

use Core\Database;

/**
 * Service to handle waste category event broadcasting
 * Emits events when waste categories are created, updated, or deleted
 */
class WasteCategoryEventService
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Broadcast category created event
     */
    public function broadcastCreated(array $category): void
    {
        $this->logEvent('category_created', $category);
    }

    /**
     * Broadcast category updated event
     */
    public function broadcastUpdated(array $category, array $oldData = []): void
    {
        $this->logEvent('category_updated', [
            'category' => $category,
            'oldData' => $oldData
        ]);
    }

    /**
     * Broadcast category deleted event
     */
    public function broadcastDeleted(int $categoryId): void
    {
        $this->logEvent('category_deleted', ['id' => $categoryId]);
    }

    /**
     * Log event to database for polling/notification system
     */
    private function logEvent(string $eventType, array $data): void
    {
        try {
            $sql = "INSERT INTO waste_category_events (event_type, event_data, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
            $this->db->query($sql, [$eventType, json_encode($data)]);
        } catch (\Throwable $e) {
            error_log('Failed to log waste category event: ' . $e->getMessage());
        }
    }

    /**
     * Get recent events (for polling)
     */
    public function getRecentEvents(int $limit = 50): array
    {
        try {
            $sql = "SELECT id, event_type, event_data, created_at FROM waste_category_events ORDER BY created_at DESC LIMIT ?";
            $rows = $this->db->fetchAll($sql, [$limit]);

            return array_map(static fn($row) => [
                'id' => $row['id'] ?? 0,
                'event_type' => $row['event_type'] ?? '',
                'data' => isset($row['event_data']) ? json_decode($row['event_data'], true) : [],
                'created_at' => $row['created_at'] ?? ''
            ], $rows ?: []);
        } catch (\Throwable $e) {
            error_log('Failed to fetch waste category events: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear old events (older than 24 hours)
     */
    public function clearOldEvents(): void
    {
        try {
            $sql = "DELETE FROM waste_category_events WHERE created_at < NOW() - INTERVAL 1 DAY";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            error_log('Failed to clear old waste category events: ' . $e->getMessage());
        }
    }
}
