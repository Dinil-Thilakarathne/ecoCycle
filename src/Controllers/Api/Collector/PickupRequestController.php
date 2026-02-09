<?php

namespace Controllers\Api\Collector;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;

class PickupRequestController extends BaseController
{
    private PickupRequest $pickupRequest;
    private \Models\Notification $notification;

    public function __construct()
    {
        $this->pickupRequest = new PickupRequest();
        $this->notification = new \Models\Notification();
    }

    public function updateStatus(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $id = $this->resolveRouteId($request);
        error_log("DEBUG: updateStatus hit. User ID: " . ($user['id'] ?? 'null') . ", Route ID: " . ($id ?? 'null'));

        if ($id === null) {
            return Response::errorJson('Pickup request id is required', 400);
        }

        $this->mergeJsonBody($request);

        $statusInput = $request->input('status');
        $normalizedStatus = $this->normalizeStatus((string) $statusInput);
        if ($normalizedStatus === null) {
            return Response::errorJson('Invalid status provided', 422, ['status' => 'Status is required and must be a valid value.']);
        }

        // If completing, expect an array of weights [{ category_id: 1, weight: 10 }, ...]
        $weightsInput = $request->input('weights', []);
        $weights = [];
        if (is_array($weightsInput)) {
            $weights = $weightsInput;
        }

        $collectorId = (int) $user['id'];

        try {
            $record = $this->pickupRequest->find($id);
            error_log("DEBUG: Record found: " . ($record ? 'yes' : 'no'));
        } catch (\Throwable $e) {
            error_log("DEBUG: Exception finding record: " . $e->getMessage());
            return Response::errorJson('Failed to load pickup request', 500, ['detail' => $e->getMessage()]);
        }

        if (!$record) {
            error_log("DEBUG: Record not found for ID: $id");
            return Response::errorJson("Pickup request not found (Invalid ID: $id)", 404);
        }

        $recColId = (int) ($record['collectorId'] ?? 0);
        if ($recColId !== $collectorId) {
            error_log("DEBUG: Access Denied. Record Owner: $recColId, Me: $collectorId");
            return Response::errorJson("Pickup request not found (Access Denied: Record Owner $recColId vs User $collectorId)", 404);
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

        // If moving to completed, ensure weights provided
        if ($normalizedStatus === 'completed') {
            if (empty($weights)) {
                return Response::errorJson('Measured weights are required when completing a pickup', 422, ['weights' => 'Provide weights for each category.']);
            }
            // Basic validation of structure
            foreach ($weights as $w) {
                if (!isset($w['category_id']) || !isset($w['weight'])) {
                    return Response::errorJson('Invalid weights format', 422, ['weights' => 'Each entry must have category_id and weight.']);
                }
                if (!is_numeric($w['weight']) || $w['weight'] < 0) {
                    return Response::errorJson('Invalid weight value', 422, ['weights' => 'Weight must be a non-negative number.']);
                }
            }
        }

        try {
            // Pass the array of weights instead of single float
            $success = $this->pickupRequest->updateStatusForCollector($id, $collectorId, $dbStatus, $weights);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update pickup status', 500, ['detail' => $e->getMessage()]);
        }

        if (!$success) {
            return Response::errorJson('Pickup request not found or cannot be updated', 404);
        }

        // Trigger Notification to Customer
        if ($success && !empty($record['customerId'])) {
            $statusMsg = ucfirst($normalizedStatus);
            $notificationData = [
                'type' => 'pickup_status_update',
                'title' => 'Pickup Status Updated',
                'message' => "Your pickup request status has been updated to: {$statusMsg}",
                'recipients' => ['user:' . $record['customerId']],
                'status' => 'pending'
            ];

            $this->notification->create($notificationData);

            // Send email notification
            sendNotificationEmail($notificationData);
        }

        // Notify admins when waste collection is completed
        if ($success && $normalizedStatus === 'completed') {
            $this->notification->create([
                'type' => 'waste_collected',
                'title' => 'New Waste Collected',
                'message' => "Pickup #{$id} completed. Waste is now available for bidding round creation.",
                'recipient_group' => 'admin',
                'status' => 'pending'
            ]);
        }


        // [FUTURE] Wallet Integration
        // When status is 'completed', we should calculate the total value of the pickup
        // (based on waste weights * price per unit) and credit the customer's wallet.
        // currently, the Pricing Engine is not implemented (Phase 2).
        /*
        if ($normalizedStatus === 'completed') {
             // $totalValue = $this->pricingEngine->calculate($id);
             // $wallet = new \Models\WalletTransaction();
             // $wallet->logTransaction($record['customer_id'], $totalValue, 'credit', 'pickup', $id, 'Pickup Completed');
        }
        */

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
