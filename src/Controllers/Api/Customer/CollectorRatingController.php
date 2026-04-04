<?php

namespace Controllers\Api\Customer;

use Controllers\BaseController;
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
            $record = $this->collectorRating->createForCustomer((int) $user['id'], $payload['data']);
        } catch (\InvalidArgumentException $e) {
            return Response::errorJson('Validation failed', 422, ['detail' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->runtimeErrorResponse($e);
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
        $collectorName = trim((string) ($source['collectorName'] ?? ''));
        $rating = $source['rating'] ?? null;
        $description = trim((string) ($source['description'] ?? ''));
        $pickupRequestId = trim((string) ($source['pickupRequestId'] ?? ''));

        if ($pickupRequestId === '') {
            $errors['pickupRequestId'] = 'Pickup request id is required.';
        }

        if ($collectorName === '') {
            $errors['collectorName'] = 'Collector name is required.';
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

        $this->setIfNotEmpty($data, 'customerName', $customerName);
        $this->setIfNotEmpty($data, 'address', $address);

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

    private function runtimeErrorResponse(\RuntimeException $e): Response
    {
        if (stripos($e->getMessage(), 'already rated') !== false) {
            return Response::errorJson('Rating already exists', 409, ['detail' => $e->getMessage()]);
        }

        return Response::errorJson('Failed to save rating', 500, ['detail' => $e->getMessage()]);
    }

    private function setIfNotEmpty(array &$target, string $key, string $value): void
    {
        if ($value !== '') {
            $target[$key] = $value;
        }
    }
}
