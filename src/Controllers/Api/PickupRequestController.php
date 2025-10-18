<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;
use Models\User;
use PDOException;

class PickupRequestController extends BaseController
{
    private PickupRequest $pickups;
    private User $users;

    public function __construct()
    {
        $this->pickups = new PickupRequest();
        $this->users = new User();
    }

    public function update(Request $request): Response
    {
        $id = trim((string) $request->route('id'));
        if ($id === '') {
            return $this->json([
                'success' => false,
                'message' => 'Pickup request id is required.',
            ], 400);
        }

        $existing = $this->pickups->find($id);
        if (!$existing) {
            return $this->json([
                'success' => false,
                'message' => 'Pickup request not found.',
            ], 404);
        }

        $payload = $request->json();
        if (!is_array($payload)) {
            $payload = $request->all();
        }
        if (!is_array($payload)) {
            $payload = [];
        }

        $collectorId = $payload['collectorId'] ?? $payload['collector_id'] ?? null;
        $collectorId = ($collectorId === '' || $collectorId === null) ? null : $collectorId;
        $status = $payload['status'] ?? null;

        $errors = [];
        $collectorName = null;

        if ($collectorId !== null) {
            if (!is_numeric($collectorId)) {
                $errors['collectorId'] = 'Collector id must be numeric.';
            } else {
                $collectorId = (int) $collectorId;
                if ($collectorId <= 0) {
                    $errors['collectorId'] = 'Collector id must be greater than zero.';
                } else {
                    $collector = $this->users->findById($collectorId);
                    if (!$collector || strtolower((string) ($collector['type'] ?? '')) !== 'collector') {
                        $errors['collectorId'] = 'Collector not found.';
                    } else {
                        $collectorName = $collector['name'] ?? null;
                    }
                }
            }
        }

        if (is_string($status)) {
            $status = strtolower(trim($status));
        } else {
            $status = null;
        }

        if ($status !== null && $status !== '') {
            $allowedStatuses = ['pending', 'assigned', 'completed', 'cancelled'];
            if (!in_array($status, $allowedStatuses, true)) {
                $errors['status'] = 'Status must be one of: ' . implode(', ', $allowedStatuses) . '.';
            }
        } else {
            $status = null;
        }

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        if ($status === null) {
            $currentStatus = strtolower((string) ($existing['status'] ?? ''));
            if ($currentStatus === 'completed') {
                $status = 'completed';
            } elseif ($collectorId !== null) {
                $status = 'assigned';
            } else {
                $status = 'pending';
            }
        }

        if ($status === 'assigned' && $collectorId === null) {
            $status = 'pending';
        }

        $updateData = [
            'collector_id' => $collectorId,
            'collector_name' => $collectorName,
            'status' => $status,
        ];

        try {
            $updated = $this->pickups->update($id, $updateData);
        } catch (PDOException $e) {
            return $this->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }

        if (!$updated) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update pickup request.',
            ], 500);
        }

        $fresh = $this->pickups->find($id);

        return $this->json([
            'success' => true,
            'pickup' => $fresh,
        ]);
    }
}
