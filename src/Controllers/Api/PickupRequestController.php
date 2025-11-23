<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;
use Models\User;

class PickupRequestController extends BaseController
{
    private PickupRequest $pickupRequest;
    private User $userModel;

    public function __construct()
    {
        $this->pickupRequest = new PickupRequest();
        $this->userModel = new User();
    }

    public function update(Request $request): Response
    {
        $this->mergeJsonBody($request);

        $currentUser = auth();
        if (!$currentUser) {
            return Response::errorJson('Unauthenticated', 401);
        }

        if (!$this->canManagePickups($currentUser)) {
            return Response::errorJson('Forbidden', 403);
        }

        $pickupId = $this->resolveRouteId($request);
        if ($pickupId === null) {
            return Response::errorJson('Pickup request id is required', 400);
        }

        $existing = $this->pickupRequest->find($pickupId);
        if (!$existing) {
            return Response::errorJson('Pickup request not found', 404);
        }

        $payload = $request->all();

        $updateData = [];
        $collectorInput = $payload['collectorId'] ?? $payload['collector_id'] ?? null;
        $statusInput = $payload['status'] ?? null;

        if ($collectorInput !== null && $collectorInput !== '') {
            if (!is_numeric($collectorInput)) {
                return Response::errorJson('Invalid collector id', 422, ['collectorId' => 'Collector id must be numeric.']);
            }

            $collectorId = (int) $collectorInput;
            if ($collectorId <= 0) {
                return Response::errorJson('Invalid collector id', 422, ['collectorId' => 'Collector id must be greater than zero.']);
            }

            $collector = $this->userModel->findById($collectorId);
            if (!$collector || ($collector['type'] ?? '') !== 'collector') {
                return Response::errorJson('Collector not found', 422, ['collectorId' => 'Collector not found.']);
            }

            $updateData['collector_id'] = $collectorId;
            $updateData['collector_name'] = $collector['name'] ?? ($collector['email'] ?? '');

            if ($statusInput === null || $statusInput === '') {
                $statusInput = 'assigned';
            }
        } else {
            $updateData['collector_id'] = null;
            $updateData['collector_name'] = null;

            if ($statusInput === null || $statusInput === '') {
                $statusInput = 'pending';
            }
        }

        if ($statusInput !== null && $statusInput !== '') {
            $normalizedStatus = strtolower((string) $statusInput);
            $allowedStatuses = ['pending', 'assigned', 'in_progress', 'in-progress', 'in progress', 'completed', 'cancelled', 'confirmed'];
            if (!in_array($normalizedStatus, $allowedStatuses, true)) {
                return Response::errorJson('Invalid status provided', 422, ['status' => 'Unsupported pickup status.']);
            }

            if ($normalizedStatus === 'in-progress') {
                $normalizedStatus = 'in_progress';
            }

            $updateData['status'] = $normalizedStatus;
        }

        if (array_key_exists('timeSlot', $payload)) {
            $timeSlot = trim((string) $payload['timeSlot']);
            if ($timeSlot !== '') {
                $updateData['time_slot'] = $timeSlot;
            }
        }

        if (array_key_exists('scheduledAt', $payload)) {
            $scheduled = $payload['scheduledAt'];
            if ($scheduled !== null && $scheduled !== '') {
                $timestamp = strtotime((string) $scheduled);
                if ($timestamp === false) {
                    return Response::errorJson('Invalid scheduled time provided', 422, ['scheduledAt' => 'Unable to parse the provided date/time.']);
                }
                $updateData['scheduled_at'] = date('Y-m-d H:i:s', $timestamp);
            } else {
                $updateData['scheduled_at'] = null;
            }
        }

        if (array_key_exists('address', $payload)) {
            $address = trim((string) $payload['address']);
            if ($address !== '') {
                $updateData['address'] = $address;
            }
        }

        if (empty($updateData)) {
            return Response::json([
                'message' => 'No changes detected',
                'pickup' => $existing,
            ]);
        }

        try {
            $ok = $this->pickupRequest->update($pickupId, $updateData);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update pickup request', 500, ['detail' => $e->getMessage()]);
        }

        if (!$ok) {
            return Response::errorJson('Pickup request not found or not updated', 404);
        }

        $fresh = $this->pickupRequest->find($pickupId);
        if (!$fresh) {
            return Response::errorJson('Pickup request not found after update', 404);
        }

        return Response::json([
            'message' => 'Pickup request updated',
            'pickup' => $fresh,
        ]);
    }

    private function canManagePickups(array $user): bool
    {
        $role = strtolower((string) ($user['role'] ?? $user['role_name'] ?? ''));
        return in_array($role, ['admin', 'manager'], true);
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
}
