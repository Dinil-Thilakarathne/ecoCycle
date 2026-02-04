<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\CollectorDailyStatus;
use Models\User;

class CollectorAvailabilityController extends BaseController
{
    private CollectorDailyStatus $statusModel;
    private User $userModel;

    public function __construct()
    {
        $this->statusModel = new CollectorDailyStatus();
        $this->userModel = new User();
    }

    /**
     * Collector updates their own availability status for today
     * POST /api/collector/availability
     */
    public function updateMyAvailability(Request $request): Response
    {
        $user = auth();

        if (!$user || $user['type'] !== 'collector') {
            return Response::errorJson('Unauthorized', 401);
        }

        $collectorId = $user['id'];
        $vehicleId = $user['vehicleId'] ?? null;

        if (!$vehicleId) {
            return Response::errorJson('No vehicle assigned to this collector', 400);
        }

        $this->mergeJsonBody($request);
        $input = $request->all();

        $isAvailable = isset($input['isAvailable']) ? (bool) $input['isAvailable'] : true;
        $notes = $input['notes'] ?? null;

        try {
            $status = $this->statusModel->updateStatus($collectorId, $vehicleId, $isAvailable, $notes);

            return Response::json([
                'message' => 'Availability status updated successfully',
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update availability', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get collector's own status for today
     * GET /api/collector/availability
     */
    public function getMyStatus(Request $request): Response
    {
        $user = auth();

        if (!$user || $user['type'] !== 'collector') {
            return Response::errorJson('Unauthorized', 401);
        }

        $collectorId = $user['id'];

        try {
            $status = $this->statusModel->getTodayStatus($collectorId);

            // If no status exists for today, create default (available)
            if (!$status) {
                $vehicleId = $user['vehicleId'] ?? null;

                if ($vehicleId) {
                    $status = $this->statusModel->updateStatus($collectorId, $vehicleId, true, null);
                } else {
                    return Response::json([
                        'status' => null,
                        'message' => 'No vehicle assigned',
                    ]);
                }
            }

            return Response::json([
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch status', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all collectors' availability for today (Admin only)
     * GET /api/admin/collectors/availability
     */
    public function getTodayAvailability(Request $request): Response
    {
        $user = auth();

        if (!$user || $user['type'] !== 'admin') {
            return Response::errorJson('Unauthorized - Admin access required', 403);
        }

        try {
            $statuses = $this->statusModel->getAllTodayStatuses();

            return Response::json([
                'statuses' => $statuses,
                'date' => date('Y-m-d'),
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch availability', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check specific collector's availability (Admin only)
     * GET /api/admin/collectors/availability/{id}
     */
    public function checkCollectorAvailability(Request $request): Response
    {
        $user = auth();

        if (!$user || $user['type'] !== 'admin') {
            return Response::errorJson('Unauthorized - Admin access required', 403);
        }

        $collectorId = $this->resolveRouteId($request);

        if ($collectorId === null) {
            return Response::errorJson('Collector ID is required', 400);
        }

        try {
            // Verify collector exists
            $collector = $this->userModel->findById($collectorId);

            if (!$collector || $collector['type'] !== 'collector') {
                return Response::errorJson('Collector not found', 404);
            }

            $status = $this->statusModel->getTodayStatus($collectorId);

            // If no status for today, assume available if vehicle assigned
            if (!$status) {
                $vehicleId = $collector['vehicleId'] ?? null;

                if ($vehicleId) {
                    $status = [
                        'collectorId' => $collectorId,
                        'vehicleId' => $vehicleId,
                        'date' => date('Y-m-d'),
                        'isAvailable' => true,
                        'notes' => null,
                        'statusUpdatedAt' => null,
                    ];
                } else {
                    return Response::json([
                        'collector' => [
                            'id' => $collector['id'],
                            'name' => $collector['name'],
                        ],
                        'status' => null,
                        'message' => 'No vehicle assigned to this collector',
                    ]);
                }
            }

            return Response::json([
                'collector' => [
                    'id' => $collector['id'],
                    'name' => $collector['name'],
                    'vehicleId' => $collector['vehicleId'],
                ],
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to check availability', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reset daily availability for all collectors (Admin/System only)
     * POST /api/admin/vehicles/daily-reset
     */
    public function resetDailyAvailability(Request $request): Response
    {
        $user = auth();

        if (!$user || $user['type'] !== 'admin') {
            return Response::errorJson('Unauthorized - Admin access required', 403);
        }

        try {
            $success = $this->statusModel->resetDailyStatuses();

            if ($success) {
                return Response::json([
                    'message' => 'Daily availability reset successfully',
                    'date' => date('Y-m-d'),
                ]);
            } else {
                return Response::errorJson('Failed to reset daily availability', 500);
            }
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to reset availability', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get collector's availability history
     * GET /api/collector/availability/history
     */
    public function getMyHistory(Request $request): Response
    {
        $user = auth();

        if (!$user || $user['type'] !== 'collector') {
            return Response::errorJson('Unauthorized', 401);
        }

        $collectorId = $user['id'];
        $limit = (int) ($request->get('limit') ?? 30);
        $limit = min(max($limit, 1), 90); // Between 1 and 90 days

        try {
            $history = $this->statusModel->getCollectorHistory($collectorId, $limit);

            return Response::json([
                'history' => $history,
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch history', 500, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (!is_array($json)) {
            return;
        }

        if (method_exists($request, 'mergeBody')) {
            $request->mergeBody($json);
        }
    }

    private function resolveRouteId(Request $request): ?int
    {
        $id = $request->route('id');
        if ($id === null || $id === '') {
            $id = $request->get('id');
        }

        if ($id === null || $id === '') {
            return null;
        }

        if (!is_numeric($id)) {
            return null;
        }

        $parsed = (int) $id;
        return $parsed > 0 ? $parsed : null;
    }
}
