<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\Vehicle;
use PDOException;

class VehicleController extends BaseController
{
    private Vehicle $vehicles;

    public function __construct()
    {
        $this->vehicles = new Vehicle();
    }

    public function index(Request $request): Response
    {
        $items = $this->vehicles->listAll();
        return $this->json([
            'success' => true,
            'vehicles' => $items,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) ($request->route('id') ?? 0);
        if ($id <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Vehicle id is required.',
            ], 400);
        }

        $vehicle = $this->vehicles->find($id);
        if (!$vehicle) {
            return $this->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'vehicle' => $vehicle,
        ]);
    }

    public function store(Request $request): Response
    {
        $payload = $this->extractPayload($request);
        if (!$payload['valid']) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $payload['errors'],
            ], 422);
        }

        try {
            $vehicle = $this->vehicles->create($payload['data']);
        } catch (PDOException $e) {
            return $this->json([
                'success' => false,
                'message' => $this->mapPdoError($e, $payload['data']),
            ], 400);
        }

        return $this->json([
            'success' => true,
            'vehicle' => $vehicle,
        ], 201);
    }

    public function update(Request $request): Response
    {
        $id = (int) ($request->route('id') ?? 0);
        if ($id <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Vehicle id is required.',
            ], 400);
        }

        if (!$this->vehicles->exists($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        $payload = $this->extractPayload($request);
        if (!$payload['valid']) {
            return $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $payload['errors'],
            ], 422);
        }

        try {
            $this->vehicles->update($id, $payload['data']);
            $vehicle = $this->vehicles->find($id);
        } catch (PDOException $e) {
            return $this->json([
                'success' => false,
                'message' => $this->mapPdoError($e, $payload['data']),
            ], 400);
        }

        return $this->json([
            'success' => true,
            'vehicle' => $vehicle,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) ($request->route('id') ?? 0);
        if ($id <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Vehicle id is required.',
            ], 400);
        }

        if (!$this->vehicles->exists($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        $this->vehicles->delete($id);

        return $this->json([
            'success' => true,
            'message' => 'Vehicle removed.',
        ]);
    }

    private function extractPayload(Request $request): array
    {
        $input = $request->json() ?? $request->all();
        $data = [
            'plate_number' => trim($input['plateNumber'] ?? $input['plate_number'] ?? ''),
            'type' => trim($input['type'] ?? ''),
            'capacity' => $input['capacity'] ?? null,
            'status' => strtolower(trim($input['status'] ?? 'available')),
            'last_maintenance' => $input['lastMaintenance'] ?? $input['last_maintenance'] ?? null,
            'next_maintenance' => $input['nextMaintenance'] ?? $input['next_maintenance'] ?? null,
        ];

        $errors = [];

        if ($data['plate_number'] === '') {
            $errors['plateNumber'] = 'Plate number is required.';
        }

        if ($data['type'] === '') {
            $errors['type'] = 'Vehicle type is required.';
        }

        if ($data['capacity'] !== null && $data['capacity'] !== '') {
            if (!is_numeric($data['capacity']) || $data['capacity'] <= 0) {
                $errors['capacity'] = 'Capacity must be a positive number.';
            } else {
                $data['capacity'] = (int) $data['capacity'];
            }
        } else {
            $errors['capacity'] = 'Capacity is required.';
        }

        $allowedStatuses = ['available', 'in-use', 'maintenance'];
        if (!in_array($data['status'], $allowedStatuses, true)) {
            $errors['status'] = 'Status must be one of: ' . implode(', ', $allowedStatuses) . '.';
        }

        $data['last_maintenance'] = $this->normalizeDate($data['last_maintenance']);
        $data['next_maintenance'] = $this->normalizeDate($data['next_maintenance']);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data,
        ];
    }

    private function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function mapPdoError(PDOException $e, array $data): string
    {
        if ((int) $e->getCode() === 23000) {
            if (!empty($data['plate_number'])) {
                return 'Plate number already exists.';
            }
        }

        return 'Database error: ' . $e->getMessage();
    }
}
