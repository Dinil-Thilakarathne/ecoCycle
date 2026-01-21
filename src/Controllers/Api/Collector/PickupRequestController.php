<?php

namespace Controllers\Api\Collector;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;

class PickupRequestController extends BaseController
{
    private PickupRequest $pickupRequest;

    public function __construct()
    {
        $this->pickupRequest = new PickupRequest();
    }

    public function updateStatus(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Pickup request id is required', 400);
        }

        $this->mergeJsonBody($request);

        $statusInput = $request->input('status');
        $normalizedStatus = $this->normalizeStatus((string) $statusInput);
        if ($normalizedStatus === null) {
            return Response::errorJson('Invalid status provided', 422, ['status' => 'Status is required and must be a valid value.']);
        }

        // If completing, expect a measured weight (optional for other transitions)
        $weightInput = $request->input('weight', null);
        $weight = null;
        if ($weightInput !== null && $weightInput !== '') {
            $weight = is_numeric($weightInput) ? (float) $weightInput : null;
        }

        $collectorId = (int) $user['id'];

        try {
            $record = $this->pickupRequest->find($id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load pickup request', 500, ['detail' => $e->getMessage()]);
        }

        if (!$record || (int) ($record['collectorId'] ?? 0) !== $collectorId) {
            return Response::errorJson('Pickup request not found', 404);
        }

        $currentStatus = $this->normalizeStatus((string) ($record['status'] ?? ''));
        $allowedNext = $this->nextStatus($currentStatus);

        if ($normalizedStatus === $currentStatus) {
            return Response::json([
                'message' => 'Pickup status already up to date',
                'data' => $record,
            ]);
        }

        if ($allowedNext === null || $normalizedStatus !== $allowedNext) {
            return Response::errorJson('Status transition not allowed', 422, [
                'status' => 'Cannot transition from ' . ($currentStatus ?? 'unknown') . ' to ' . $normalizedStatus . '.',
            ]);
        }

        $dbStatus = $this->mapStatusForDatabase($normalizedStatus);

        // If moving to completed, ensure weight provided and valid
        if ($normalizedStatus === 'completed') {
            if ($weight === null || $weight <= 0) {
                return Response::errorJson('Measured weight is required when completing a pickup', 422, ['weight' => 'Provide a numeric weight greater than 0.']);
            }
        }

        try {
            $this->pickupRequest->updateStatus((int)$id, $dbStatus);
            $success = true;
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update pickup status', 500, ['detail' => $e->getMessage()]);
        }

        if (!$success) {
            return Response::errorJson('Pickup request not found or cannot be updated', 404);
        }

        $fresh = $this->pickupRequest->find($id);

        return Response::json([
            'message' => 'Pickup status updated',
            'data' => $fresh,
        ]);
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

    private function resolveRouteId(Request $request): ?string
    {
        $id = $request->route('id');
        if ($id === null || $id === '') {
            $id = $request->get('id');
        }

        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }

    private function normalizeStatus(string $status): ?string
    {
        $value = strtolower(trim($status));
        if ($value === '') {
            return null;
        }

        if ($value === 'in_progress' || $value === 'in-progress') {
            $value = 'in progress';
        }

        $allowed = ['assigned', 'in progress', 'completed'];

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function mapStatusForDatabase(string $status): string
    {
        if ($status === 'in progress') {
            return 'in_progress';
        }

        return $status;
    }

    private function nextStatus(?string $current): ?string
    {
        $map = [
            'assigned' => 'in progress',
            'in progress' => 'completed',
        ];

        if ($current === null) {
            return null;
        }

        return $map[$current] ?? null;
    }
}
