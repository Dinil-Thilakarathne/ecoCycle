<?php

namespace Controllers\Api;

use Core\Database;
use Core\Http\Request;
use Core\Http\Response;
use Controllers\BaseController;

class CollectorStatsController extends BaseController
{
    private Database $db;
    private \Models\Notification $notificationModel;

    public function __construct()
    {
        $this->db = new Database();
        $this->notificationModel = new \Models\Notification();
    }

    /**
     * GET /api/collector/stats
     * Returns counts and total weight for pickups assigned to the logged-in collector
     */
    public function stats(Request $request): Response
    {
        try {
            // Get the logged-in collector's ID from session
            $collectorId = session()->get('user_id');
            if (!$collectorId) {
                return $this->json(['status' => 'error', 'message' => 'Collector not authenticated'], 401);
            }

            // Total assigned pickups for this collector (all statuses except pending)
            $tasksSql = "SELECT COUNT(*) AS count FROM pickup_requests WHERE collector_id = ? AND status IN ('assigned', 'in_progress', 'completed')";
            $tasks = $this->db->fetch($tasksSql, [$collectorId]);

            // Completed pickups: status = completed for this collector
            $completedSql = "SELECT COUNT(*) AS count FROM pickup_requests WHERE collector_id = ? AND status = 'completed'";
            $completed = $this->db->fetch($completedSql, [$collectorId]);

            // Pending tasks: status = 'in_progress' for this collector
            $pendingSql = "SELECT COUNT(*) AS count FROM pickup_requests WHERE collector_id = ? AND status = 'in_progress'";
            $pending = $this->db->fetch($pendingSql, [$collectorId]);

            // Total weight for all pickups assigned to this collector
            $weightSql = "SELECT COALESCE(SUM(CAST(weight AS DECIMAL(12,2))), 0) AS total_weight FROM pickup_requests WHERE collector_id = ?";
            $weight = $this->db->fetch($weightSql, [$collectorId]);

            return $this->json([
                'status' => 'success',
                'data' => [
                    'todays_tasks' => (int) ($tasks['count'] ?? 0),
                    'completed' => (int) ($completed['count'] ?? 0),
                    'pending' => (int) ($pending['count'] ?? 0),
                    'total_weight' => (float) ($weight['total_weight'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch collector stats', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/collector/material-prices
     * Returns current price_per_unit for all waste categories
     */
    public function materialPrices(Request $request): Response
    {
        try {
            $prices = $this->db->fetchAll("SELECT id, name, price_per_unit FROM waste_categories WHERE price_per_unit IS NOT NULL ORDER BY name ASC");

            return $this->json([
                'status' => 'success',
                'data' => $prices ?: [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch material prices', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/collector/notifications
     * Returns real-time notifications for the logged-in collector
     */
    public function notifications(Request $request): Response
    {
        try {
            $collectorId = session()->get('user_id');
            if (!$collectorId) {
                return $this->json(['status' => 'error', 'message' => 'Collector not authenticated'], 401);
            }

            // Fetch notifications for this collector using the model
            $notifications = $this->notificationModel->forUser($collectorId, 'collector', 100);

            return $this->json([
                'status' => 'success',
                'data' => $notifications ?: [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to fetch notifications', 'details' => $e->getMessage()], 500);
        }
    }
}
