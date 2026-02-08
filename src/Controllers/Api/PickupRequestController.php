<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;
use Models\User;
use Models\Vehicle;

class PickupRequestController extends BaseController
{
    private PickupRequest $pickupRequest;
    private User $userModel;
    private Vehicle $vehicleModel;

    public function __construct()
    {
        $this->pickupRequest = new PickupRequest();
        $this->userModel = new User();
        $this->vehicleModel = new Vehicle();
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

        // Collector Assignment
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
            // Unassign Collector - only if explicitly provided in payload (null or empty)
            // But logic above: if key exists? No, logic above check value != null/empty.
            // If value IS null/empty, we unassign.
            // But wait, existing logic was "else". 
            // If I just update status, collectorInput is null.
            // Original logic: $collectorInput = $payload['collectorId']...
            // If I pass ONLY status, collectorInput is null.
            // Original logic lines 72-79: "else { $updateData['collector_id'] = null ... }"
            // WAIT! This means if I update status without sending collectorId, it CLEARS collectorId?
            // That sounds like a bug in original code or intentional design for "replace" style update.
            // Let's check original code.
            /*
            if ($collectorInput !== null && $collectorInput !== '') { ... }
            else {
                $updateData['collector_id'] = null;
                ...
            }
            */
            // YES. The original code UNASSIGNS collector if not provided.
            // I should respect that or fix it if it's bad.
            // Assuming the frontend sends full object or at least collectorId.
            // I will keep the existing behavior to avoid regression, but maybe check if key exists?
            // Actually, for a partial update (PATCH), we shouldn't clear fields not sent.
            // But look at line 48: $collectorInput = $payload['collectorId'] ?? ... ?? null;
            // If I send {status: 'completed'}, collectorInput is null.
            // Then it goes to else block and sets collector_id = null.
            // This seems dangerous. But maybe the frontend ALWAYS sends collectorId?
            // "The edit modal will be updated to include a vehicle selection dropdown."
            // If I invoke this API, I better send everything.

            // To be safe and improve it: I will check if the key exists in payload.
            if (array_key_exists('collectorId', $payload) || array_key_exists('collector_id', $payload)) {
                $updateData['collector_id'] = null;
                $updateData['collector_name'] = null;
                $updateData['vehicle_id'] = null; // Also clear vehicle

                if ($statusInput === null || $statusInput === '') {
                    $statusInput = 'pending';
                }
            }
        }

        // Vehicle Assignment Logic
        $vehicleInput = $payload['vehicleId'] ?? $payload['vehicle_id'] ?? null;
        $oldVehicleId = $existing['vehicleId'] ?? null;
        $vehicleChanged = false;
        $newVehicleId = null;

        $hasVehicleInput = array_key_exists('vehicleId', $payload) || array_key_exists('vehicle_id', $payload);

        // Use auto-cleared vehicle if set
        $targetVal = null;
        if (array_key_exists('vehicle_id', $updateData) && $updateData['vehicle_id'] === null) {
            $hasVehicleInput = true;
            $targetVal = null;
        } elseif ($hasVehicleInput) {
            $targetVal = $vehicleInput;
        }

        if ($hasVehicleInput) {
            if ($targetVal !== null && $targetVal !== '' && $targetVal !== 0 && $targetVal !== '0') {
                if (!is_numeric($targetVal)) {
                    return Response::errorJson('Invalid vehicle id', 422, ['vehicleId' => 'Vehicle ID must be numeric.']);
                }
                $newVehicleId = (int) $targetVal;

                if ($newVehicleId !== (int) $oldVehicleId) {
                    $vehicle = $this->vehicleModel->find($newVehicleId);
                    if (!$vehicle) {
                        return Response::errorJson('Vehicle not found', 422, ['vehicleId' => 'Vehicle not found.']);
                    }

                    if (($vehicle['status'] ?? 'available') !== 'available') {
                        return Response::errorJson('Vehicle not available', 422, ['vehicleId' => 'Vehicle is currently in use.']);
                    }

                    $updateData['vehicle_id'] = $newVehicleId;
                    $vehicleChanged = true;
                }
            } else {
                if ($oldVehicleId !== null) {
                    $updateData['vehicle_id'] = null;
                    $vehicleChanged = true;
                }
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
            $updateData['time_slot'] = $timeSlot === '' ? null : $timeSlot;
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
            $updateData['address'] = trim((string) $payload['address']);
        }

        if (empty($updateData)) {
            return Response::json([
                'message' => 'No changes detected',
                'pickup' => $existing,
            ]);
        }

        try {
            $ok = $this->pickupRequest->update($pickupId, $updateData);

            if ($ok) {
                if ($vehicleChanged) {
                    if ($oldVehicleId) {
                        $this->vehicleModel->markStatus((int) $oldVehicleId, 'available');
                    }
                    if ($newVehicleId) {
                        $this->vehicleModel->markStatus((int) $newVehicleId, 'in-use');
                    }
                }

                $finalStatus = $updateData['status'] ?? $existing['statusRaw'] ?? 'pending';
                $finalVehicleId = array_key_exists('vehicle_id', $updateData) ? $updateData['vehicle_id'] : ($existing['vehicleId'] ?? null);

                if ($finalVehicleId) {
                    if (in_array($finalStatus, ['completed', 'cancelled'])) {
                        $this->vehicleModel->markStatus((int) $finalVehicleId, 'available');
                    } elseif (in_array($finalStatus, ['assigned', 'in_progress'])) {
                        $this->vehicleModel->markStatus((int) $finalVehicleId, 'in-use');
                    }
                }
            }
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

    public function complete(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if (!$id) {
            return Response::errorJson('Pickup request id is required', 400);
        }

        $currentUser = auth();
        if (!$currentUser) {
            return Response::errorJson('Unauthenticated', 401);
        }

        // Only collector assigned to the task (or admin) can complete it
        $pickup = $this->pickupRequest->find($id);
        if (!$pickup) {
            return Response::errorJson('Pickup request not found', 404);
        }

        $canComplete = false;
        $userRole = strtolower($currentUser['role'] ?? $currentUser['role_name'] ?? '');
        if (in_array($userRole, ['admin', 'manager'], true)) {
            $canComplete = true;
        } elseif (($currentUser['type'] ?? '') === 'collector') {
            if ((int) ($pickup['collectorId'] ?? 0) === (int) $currentUser['id']) {
                $canComplete = true;
            }
        }

        if (!$canComplete) {
            return Response::errorJson('Forbidden: You cannot complete this pickup', 403);
        }

        if ($pickup['status'] === 'completed') {
            return Response::errorJson('Pickup is already completed', 422);
        }

        $this->mergeJsonBody($request);
        $payload = $request->all();

        // 1. Validate Collected Waste
        $collectedWaste = $payload['collectedWaste'] ?? []; // Array of { id: int, quantity: float }
        if (empty($collectedWaste) || !is_array($collectedWaste)) {
            return Response::errorJson('Collected waste details are required', 422);
        }

        $wasteCategoryModel = new \Models\WasteCategory();
        $processedWaste = [];
        $totalPayoutAmount = 0.0;

        foreach ($collectedWaste as $item) {
            $catId = (int) ($item['id'] ?? $item['wasteCategoryId'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            $unit = $item['unit'] ?? 'kg';

            if ($catId <= 0 || $qty <= 0) {
                continue;
            }

            $category = $wasteCategoryModel->findById($catId);
            if (!$category) {
                continue;
            }

            // Calculate Price
            // Future improvement: Use tiered pricing from WasteCategory::calculatePrice
            // For now, simple linear: Qty * PricePerUnit
            $pricePerUnit = $category['pricePerUnit'] ?? 0.0;
            $lineTotal = round($qty * $pricePerUnit, 2);
            $totalPayoutAmount += $lineTotal;

            $processedWaste[] = [
                'id' => $catId,
                'quantity' => $qty,
                'unit' => $unit
            ];
        }

        if (empty($processedWaste)) {
            return Response::errorJson('Invalid waste details provided', 422);
        }

        // 2. Start Transaction
        $pdo = $this->pickupRequest->getDb()->pdo();
        $pdo->beginTransaction();

        try {
            // 3. Update Waste details in PickupRequest
            // This replaces the initial "estimates" with actual "collected" values
            $this->pickupRequest->updateForCustomer($id, (int) $pickup['customerId'], ['wasteCategories' => $processedWaste]);

            // 4. Update Status to Completed
            $this->pickupRequest->update($id, ['status' => 'completed']);

            // 5. Generate Payout Payment
            if ($totalPayoutAmount > 0) {
                $paymentService = new \Services\Payment\PaymentService();
                $paymentService->createManualPayment([
                    'type' => 'payout',
                    'recipientId' => (int) $pickup['customerId'],
                    'amount' => $totalPayoutAmount,
                    'status' => 'pending', // Pending until wallet processes it or admin approves cash? Assuming wallet credit logic in Service handles it.
                    'notes' => "Payout for Pickup #{$id}",
                    'txnId' => "PO-{$id}-" . time() // Auto-generate specific ref
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return Response::errorJson('Failed to complete pickup', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Pickup completed and payment generated',
            'payoutAmount' => $totalPayoutAmount,
            'pickup' => $this->pickupRequest->find($id)
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
