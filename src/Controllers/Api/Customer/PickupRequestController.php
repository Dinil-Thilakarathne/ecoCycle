<?php

namespace Controllers\Api\Customer;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\PickupRequest;
use Models\WasteCategory;

class PickupRequestController extends BaseController
{
    private PickupRequest $pickupRequest;
    private WasteCategory $wasteCategory;
    private \Models\Notification $notification;

    public function __construct()
    {
        $this->pickupRequest = new PickupRequest();
        $this->wasteCategory = new WasteCategory();
        $this->notification = new \Models\Notification();
    }

    public function index(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $customerId = (int) $user['id'];
        $status = $request->query('status');

        try {
            $records = $this->pickupRequest->listForCustomer($customerId, $status);
        } catch (\Throwable $e) {
            return Response::errorJson('Unable to load pickup requests', 500);
        }

        return Response::json([
            'data' => $records,
        ]);
    }

    public function store(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $this->mergeJsonBody($request);

        $payload = $this->validatePayload($request, true);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        try {
            $record = $this->pickupRequest->createForCustomer((int) $user['id'], $payload['data']);

            // Trigger Notification to Admins
            if ($record && isset($record['id'])) {
                $notificationData = [
                    'type' => 'pickup_request',
                    'title' => 'New Pickup Request',
                    'message' => "New pickup request received (ID: {$record['id']}) from Customer " . ($user['username'] ?? $user['name'] ?? 'Unknown'),
                    'recipient_group' => 'admin',
                    'status' => 'pending'
                ];

                $this->notification->create($notificationData);

                // Send email notification
                sendNotificationEmail($notificationData);
            }

        } catch (\Throwable $e) {
            return Response::errorJson('Failed to create pickup request', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Pickup request created',
            'data' => $record,
        ], 201);
    }

    public function update(Request $request): Response
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

        $payload = $this->validatePayload($request, false);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        try {
            $ok = $this->pickupRequest->updateForCustomer($id, (int) $user['id'], $payload['data']);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update pickup request', 500, ['detail' => $e->getMessage()]);
        }

        if (!$ok) {
            return Response::errorJson('Pickup request not found or cannot be updated', 404);
        }

        $fresh = $this->pickupRequest->find($id);

        return Response::json([
            'message' => 'Pickup request updated',
            'data' => $fresh,
        ]);
    }

    public function destroy(Request $request): Response
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

        try {
            $ok = $this->pickupRequest->cancelForCustomer($id, (int) $user['id']);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to cancel pickup request', 500, ['detail' => $e->getMessage()]);
        }

        if (!$ok) {
            return Response::errorJson('Pickup request not found or cannot be cancelled', 404);
        }

        $record = $this->pickupRequest->find($id);

        return Response::json([
            'message' => 'Pickup request cancelled',
            'data' => $record,
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $errors = [];
        $data = [];
        $source = $request->all();

        $address = trim((string) ($source['address'] ?? ''));
        $timeSlot = trim((string) ($source['timeSlot'] ?? ''));
        $scheduledAt = $source['scheduledAt'] ?? null;
        $wasteCategories = $source['wasteCategories'] ?? null;

        if ($isCreate) {
            if ($address === '') {
                $errors['address'] = 'Address is required.';
            }

            if ($timeSlot === '') {
                $errors['timeSlot'] = 'Preferred time slot is required.';
            }
        }

        if ($address !== '') {
            $data['address'] = $address;
        }

        if ($timeSlot !== '') {
            $data['timeSlot'] = $timeSlot;
        }

        if ($scheduledAt !== null && $scheduledAt !== '') {
            $timestamp = strtotime((string) $scheduledAt);
            if ($timestamp === false) {
                $errors['scheduledAt'] = 'Invalid date provided for scheduled time.';
            } else {
                $data['scheduledAt'] = date('Y-m-d H:i:s', $timestamp);
            }
        }

        if (is_array($wasteCategories)) {
            $parsed = [];
            foreach ($wasteCategories as $index => $item) {
                $categoryId = $item['id'] ?? null;
                if (!$categoryId) {
                    $errors["wasteCategories.{$index}.id"] = 'Waste category id is required.';
                    continue;
                }

                if (!$this->wasteCategory->exists((int) $categoryId)) {
                    $errors["wasteCategories.{$index}.id"] = 'Invalid waste category selected.';
                    continue;
                }

                $quantity = $item['quantity'] ?? null;
                if ($quantity !== null && !is_numeric($quantity)) {
                    $errors["wasteCategories.{$index}.quantity"] = 'Quantity must be numeric.';
                    continue;
                }

                $parsed[] = [
                    'id' => (int) $categoryId,
                    'quantity' => $quantity !== null ? (float) $quantity : null,
                    'unit' => $item['unit'] ?? null,
                ];
            }

            if (!empty($parsed)) {
                $data['wasteCategories'] = $parsed;
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return ['data' => $data];
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
