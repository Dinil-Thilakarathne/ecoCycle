<?php

namespace Controllers\Api\Customer;

use Controllers\BaseController;
use Core\Database;
use Core\Http\Request;
use Core\Http\Response;
use Models\CollectorRating;

class CollectorRatingController extends BaseController
{
    private CollectorRating $collectorRating;

    public function __construct()
    {
        $this->collectorRating = new CollectorRating();
        // Ensure table exists
        try {
            $this->collectorRating->createTableIfNotExists();
        } catch (\Throwable $e) {
            // ignore - will surface when saving
        }
    }

    public function store(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        $this->mergeJsonBody($request);

        $payload = $this->validatePayload($request);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        try {
            $data = $payload['data'];
            $pickupRequestId = trim((string) ($data['pickupRequestId'] ?? ''));

            $pickupDetails = $this->resolvePickupRequestDetails($pickupRequestId, (int) $user['id']);
            $collectorId = (int) ($pickupDetails['collector_id'] ?? 0);
            $resolvedCustomerId = (int) ($pickupDetails['customer_id'] ?? 0);

            if ($collectorId <= 0 || $resolvedCustomerId <= 0) {
                return Response::errorJson('Validation failed', 422, [
                    'pickup_request_id' => 'Could not resolve id, customer_id, collector_id from pickup_requests.'
                ]);
            }

            $data['collectorId'] = $collectorId;
            $data['pickupRequestId'] = (string) ($pickupDetails['id'] ?? $pickupRequestId);

            $record = $this->collectorRating->createForCustomer($resolvedCustomerId, $data);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to save rating', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Rating submitted',
            'data' => $record,
        ], 201);
    }

    private function validatePayload(Request $request): array
    {
        $errors = [];
        $data = [];
        $source = $request->all();

        $customerName = trim((string) ($source['customerName'] ?? ''));
        $address = trim((string) ($source['address'] ?? ''));
        $date = $source['date'] ?? null;
        $pickupRequestId = trim((string) ($source['pickup_request_id'] ?? $source['pickupRequestId'] ?? ''));
        $collectorName = trim((string) ($source['collectorName'] ?? ''));
        $rating = $source['rating'] ?? null;
        $description = trim((string) ($source['description'] ?? ''));
        $pickupRequestId = trim((string) ($source['pickupRequestId'] ?? ''));

        if ($pickupRequestId === '') {
            $errors['pickupRequestId'] = 'Pickup request id is required.';
        }

        if ($pickupRequestId === '') {
            $errors['pickup_request_id'] = 'Pickup request ID is required.';
        }

        if ($rating === null || $rating === '') {
            $errors['rating'] = 'Rating is required.';
        } elseif (!is_numeric($rating)) {
            $errors['rating'] = 'Rating must be a number.';
        } else {
            $rating = (int) $rating;
            if ($rating < 1 || $rating > 5) {
                $errors['rating'] = 'Rating must be between 1 and 5.';
            }
        }

        if ($date !== null && $date !== '') {
            $ts = strtotime((string) $date);
            if ($ts === false) {
                $errors['date'] = 'Invalid date provided.';
            } else {
                $data['date'] = date('Y-m-d', $ts);
            }
        }

        if ($customerName !== '') {
            $data['customerName'] = $customerName;
        }
        if ($address !== '') {
            $data['address'] = $address;
        }

        if ($pickupRequestId !== '') {
            $data['pickupRequestId'] = $pickupRequestId;
        }

        $data['collectorName'] = $collectorName;
        $data['pickupRequestId'] = $pickupRequestId;
        $data['rating'] = (int) $rating;
        $data['description'] = $description !== '' ? $description : null;

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

    private function resolvePickupRequestDetails(string $pickupRequestId, int $expectedCustomerId): array
    {
        if ($pickupRequestId === '') {
            return [];
        }

        $db = new Database();
        $row = $db->fetch(
            "SELECT id, customer_id, collector_id
             FROM pickup_requests
             WHERE id = ?
               AND customer_id = ?
             LIMIT 1",
            [$pickupRequestId, $expectedCustomerId]
        );

        return is_array($row) ? $row : [];
    }
}