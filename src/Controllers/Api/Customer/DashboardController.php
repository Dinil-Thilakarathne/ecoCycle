<?php

namespace Controllers\Api\Customer;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;
use Models\User;
use Models\WasteCategory;

class DashboardController extends BaseController
{
    private PickupRequest $pickupRequest;
    private User $user;
    private WasteCategory $wasteCategory;

    public function __construct()
    {
        $this->pickupRequest = new PickupRequest();
        $this->user = new User();
        $this->wasteCategory = new WasteCategory();
    }

    /**
     * Get dashboard statistics for the authenticated customer
     */
    public function stats(Request $request): Response
    {
        $authUser = auth();
        if (!$authUser) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $customerId = (int) $authUser['id'];

        try {
            // Get pickup request counts by status
            $totalPickups = $this->pickupRequest->countByCustomer($customerId);
            $pendingCount = $this->pickupRequest->countByCustomerAndStatus($customerId, 'pending');
            $scheduledCount = $this->pickupRequest->countByCustomerAndStatus($customerId, ['assigned', 'confirmed']);
            $completedCount = $this->pickupRequest->countByCustomerAndStatus($customerId, 'completed');

            // Calculate total income from completed pickup requests
            $totalEarnings = $this->getTotalIncome($customerId);

            // Calculate total weight from pickup requests
            $totalWeight = $this->getTotalWeight($customerId);

            // Get completion rate
            $completionRate = $totalPickups > 0 ? round(($completedCount / $totalPickups) * 100) : 0;

            return Response::json([
                'success' => true,
                'data' => [
                    'totalPickups' => (int) $totalPickups,
                    'totalIncome' => $totalEarnings,
                    'totalWeight' => round($totalWeight, 2),
                    'pendingCount' => (int) $pendingCount,
                    'scheduledCount' => (int) $scheduledCount,
                    'completedCount' => (int) $completedCount,
                    'completionRate' => (int) $completionRate,
                ]
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Unable to load dashboard stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate total weight from pickup requests
     */
    private function getTotalWeight(int $customerId): float
    {
        try {
            $db = app('db');
            $query = "
                SELECT COALESCE(SUM(COALESCE(prw.weight, prw.quantity, 0)), 0) as total_weight
                FROM pickup_request_wastes prw
                INNER JOIN pickup_requests pr ON pr.id = prw.pickup_id
                WHERE pr.customer_id = ? AND pr.status = 'completed'
            ";
            $result = $db->fetchOne($query, [$customerId]);
            return (float) ($result['total_weight'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Calculate total income from completed pickup requests
     */
    private function getTotalIncome(int $customerId): float
    {
        try {
            $db = app('db');
            $result = $db->fetchOne(
                "SELECT COALESCE(SUM(COALESCE(price, 0)), 0) AS total_income
                 FROM pickup_requests
                 WHERE customer_id = ? AND status = 'completed'",
                [$customerId]
            );

            return (float) ($result['total_income'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get current material prices for customer dashboard price-per-unit card
     */
    public function materialPrices(Request $request): Response
    {
        $authUser = auth();
        if (!$authUser) {
            return Response::errorJson('Unauthenticated', 401);
        }

        try {
            $categories = $this->wasteCategory->listAll();

            $prices = array_values(array_map(static function (array $category): array {
                return [
                    'id' => (int) ($category['id'] ?? 0),
                    'name' => (string) ($category['name'] ?? ''),
                    'unit' => (string) ($category['unit'] ?? 'kg'),
                    'price_per_unit' => (float) ($category['pricePerUnit'] ?? 0),
                    'color' => $category['color'] ?? null,
                ];
            }, $categories));

            return Response::json([
                'success' => true,
                'data' => $prices,
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Unable to load material prices: ' . $e->getMessage(), 500);
        }
    }
}
