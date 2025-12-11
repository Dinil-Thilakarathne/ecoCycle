<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Database;
use Core\Http\Request;
use Core\Http\Response;
use Models\CollectorFeedback;
use Models\PickupRequest;

class AnalyticsController extends BaseController
{
    private Database $db;
    private CollectorFeedback $feedback;
    private PickupRequest $pickupRequest;

    public function __construct()
    {
        $this->db = new Database();
        $this->feedback = new CollectorFeedback();
        $this->pickupRequest = new PickupRequest();
    }

    /**
     * GET /api/analytics/dashboard
     * Main analytics dashboard with key metrics
     */
    public function dashboard(Request $request): Response
    {
        try {
            $userId = session()->get('user_id');
            $userRole = session()->get('user_role');

            // Get summary metrics
            $totalUsers = $this->db->fetchOne("SELECT COUNT(*) as count FROM users");
            $activeBids = $this->db->fetchOne("SELECT COUNT(*) as count FROM bidding_rounds WHERE status = 'active'");
            $totalPickups = $this->db->fetchOne("SELECT COUNT(*) as count FROM pickup_requests");
            $monthlyRevenue = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())"
            );

            return Response::json([
                'status' => 'success',
                'data' => [
                    'role' => $userRole ?? 'guest',
                    'total_users' => (int)($totalUsers['count'] ?? 0),
                    'active_bids' => (int)($activeBids['count'] ?? 0),
                    'total_pickups' => (int)($totalPickups['count'] ?? 0),
                    'monthly_revenue' => (float)($monthlyRevenue['total'] ?? 0),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch dashboard data', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/analytics/collector-feedback
     * Get collector feedback and ratings
     */
    public function getCollectorFeedback(Request $request): Response
    {
        try {
            $limit = (int)($request->get('limit', 50));
            $offset = (int)($request->get('offset', 0));
            $collectorId = $request->get('collector_id');

            // Fetch feedback
            if ($collectorId) {
                $feedbackData = $this->feedback->getCollectorFeedback($collectorId, $limit, $offset);
                $avgRating = $this->feedback->getAverageRating($collectorId);
                $count = $this->feedback->getCollectorFeedbackCount($collectorId);
            } else {
                $feedbackData = $this->feedback->getAllFeedback($limit, $offset);
                $avgRating = $this->db->fetchOne(
                    "SELECT AVG(rating) as avg_rating FROM collector_feedback WHERE status = 'active'"
                )['avg_rating'] ?? 0;
                $count = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM collector_feedback WHERE status = 'active'"
                )['count'] ?? 0;
            }

            return Response::json([
                'status' => 'success',
                'data' => $feedbackData,
                'metrics' => [
                    'average_rating' => (float)$avgRating,
                    'total_feedback' => (int)$count,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch feedback data', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/analytics/waste-stats
     * Get waste collection statistics by category
     */
    public function getWasteStats(Request $request): Response
    {
        try {
            $monthsBack = (int)($request->get('months', 6));
            $categoryId = $request->get('category_id');

            $query = "
                SELECT wc.id, wc.name, 
                       SUM(pr.total_weight) as total_collected,
                       COUNT(DISTINCT pr.id) as pickup_count,
                       AVG(pr.total_weight) as avg_per_pickup,
                       DATE_TRUNC('month', pr.created_at) as month
                FROM waste_categories wc
                LEFT JOIN pickup_request_wastes prw ON wc.id = prw.waste_category_id
                LEFT JOIN pickup_requests pr ON prw.pickup_request_id = pr.id
                WHERE pr.created_at >= NOW() - INTERVAL '{$monthsBack} months'
            ";

            if ($categoryId) {
                $query .= " AND wc.id = {$categoryId}";
            }

            $query .= " GROUP BY wc.id, wc.name, DATE_TRUNC('month', pr.created_at)
                        ORDER BY wc.name, month DESC";

            $stats = $this->db->fetchAll($query);

            return Response::json([
                'status' => 'success',
                'data' => $stats,
                'period_months' => $monthsBack,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch waste statistics', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/analytics/metrics
     * Get comprehensive analytics metrics
     */
    public function getMetrics(Request $request): Response
    {
        try {
            // Average ratings
            $avgRating = $this->db->fetchOne(
                "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM collector_feedback WHERE status = 'active'"
            );

            // Pending reviews
            $pending = $this->feedback->getPendingFeedback(100);

            // Low ratings (potential issues)
            $lowRatings = $this->feedback->getLowRatings(2, 20);

            // Waste collection summary
            $wasteStats = $this->db->fetchAll(
                "SELECT wc.name, SUM(prw.quantity) as total_kg, COUNT(DISTINCT prw.pickup_request_id) as collections
                 FROM waste_categories wc
                 LEFT JOIN pickup_request_wastes prw ON wc.id = prw.waste_category_id
                 WHERE prw.created_at >= DATE_TRUNC('month', NOW())
                 GROUP BY wc.id, wc.name
                 ORDER BY total_kg DESC"
            );

            return Response::json([
                'status' => 'success',
                'data' => [
                    'feedback_metrics' => [
                        'average_rating' => (float)($avgRating['avg_rating'] ?? 0),
                        'total_feedback' => (int)($avgRating['total'] ?? 0),
                        'pending_review_count' => count($pending),
                        'low_rating_count' => count($lowRatings)
                    ],
                    'waste_collection' => $wasteStats,
                    'pending_reviews' => array_slice($pending, 0, 5)
                ],
                'generated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch metrics', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST /api/analytics/feedback
     * Add new feedback entry
     */
    public function addFeedback(Request $request): Response
    {
        try {
            $this->mergeJsonBody($request);
            $data = $request->all();

            // Validate required fields
            if (empty($data['collector_id']) || empty($data['rating'])) {
                return Response::errorJson('Validation failed', 422, [
                    'errors' => [
                        'collector_id' => 'Collector ID is required',
                        'rating' => 'Rating is required (1-5)'
                    ]
                ]);
            }

            // Create feedback
            $feedback = $this->feedback->create([
                'collector_id' => (int)$data['collector_id'],
                'customer_id' => (int)($data['customer_id'] ?? null),
                'pickup_request_id' => (int)($data['pickup_request_id'] ?? null),
                'rating' => (int)$data['rating'],
                'feedback' => $data['feedback'] ?? null,
                'status' => $data['status'] ?? 'active',
                'feedback_type' => $data['feedback_type'] ?? 'general'
            ]);

            return Response::json([
                'status' => 'success',
                'message' => 'Feedback created successfully',
                'data' => $feedback
            ], 201);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to create feedback', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (is_array($json) && method_exists($request, 'mergeBody')) {
            $request->mergeBody($json);
        }
    }
}
