<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\Vehicle;

class VehicleController extends BaseController
{
    private Vehicle $vehicles;
    private $userModel;
    private const VEHICLE_TYPE_CAPACITY = [
        'Pickup Truck' => 2000,
        'Small Truck' => 3000,
        'Large Truck' => 5000,
    ];
    private const ALLOWED_STATUSES = ['available', 'in-use', 'maintenance', 'removed'];

    public function __construct()
    {
        $this->vehicles = new Vehicle();
        $this->userModel = new \Models\User();
    }

    public function index(Request $request): Response
    {
        try {
            $records = $this->vehicles->listAll();
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load vehicles', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'vehicles' => $records,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Vehicle id must be provided', 400);
        }

        $vehicle = $this->vehicles->find($id);
        if (!$vehicle) {
            return Response::errorJson('Vehicle not found', 404);
        }

        return Response::json([
            'vehicle' => $vehicle,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->mergeJsonBody($request);

        $payload = $this->validatePayload($request, true);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        $data = $payload['data'];
        $assignedCollectorId = $data['assigned_collector_id'] ?? null;
        unset($data['assigned_collector_id']);

        try {
            if ($assignedCollectorId) {
                // Verify collector
                $collector = $this->userModel->findById($assignedCollectorId);
                if (!$collector || ($collector['type'] ?? '') !== 'collector') {
                    return Response::errorJson('Invalid collector assigned', 422);
                }

                // If collector has a vehicle, unassign it first
                if (!empty($collector['vehicleId'])) {
                    $this->vehicles->markStatus((int) $collector['vehicleId'], 'available');
                }

                $data['status'] = 'in-use';
            } else {
                $data['status'] = 'available';
            }

            $record = $this->vehicles->create($data);

            if ($assignedCollectorId) {
                $this->userModel->updateUser($assignedCollectorId, ['vehicle_id' => $record['id']]);
            }

        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique constraint')) {
                return Response::errorJson('A vehicle with this plate number already exists.', 409);
            }
            return Response::errorJson('Failed to create vehicle', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Vehicle created',
            'vehicle' => $record,
        ], 201);
    }

    public function update(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Vehicle id must be provided', 400);
        }

        $existing = $this->vehicles->find($id);
        if (!$existing) {
            return Response::errorJson('Vehicle not found', 404);
        }

        $this->mergeJsonBody($request);

        $payload = $this->validatePayload($request, false);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        $data = $payload['data'];
        $assignmentChanged = array_key_exists('assigned_collector_id', $data);
        $newCollectorId = $data['assigned_collector_id'] ?? null;
        unset($data['assigned_collector_id']);

        if (empty($data) && !$assignmentChanged) {
            return Response::json([
                'message' => 'No changes detected',
                'vehicle' => $existing,
            ]);
        }

        try {
            if ($assignmentChanged) {
                // Find currently assigned collector
                $currentCollector = $this->userModel->findByVehicleId($id);
                $currentCollectorId = $currentCollector ? $currentCollector['id'] : null;

                if ($newCollectorId !== $currentCollectorId) {
                    // Unassign current
                    if ($currentCollectorId) {
                        $this->userModel->updateUser($currentCollectorId, ['vehicle_id' => null]);
                    }

                    if ($newCollectorId) {
                        // Assign new
                        $collector = $this->userModel->findById($newCollectorId);
                        if (!$collector || ($collector['type'] ?? '') !== 'collector') {
                            return Response::errorJson('Invalid collector assigned', 422);
                        }

                        // If collector has a DIFFERENT vehicle, free it
                        if (!empty($collector['vehicleId']) && $collector['vehicleId'] != $id) {
                            $this->vehicles->markStatus((int) $collector['vehicleId'], 'available');
                        }

                        $this->userModel->updateUser($newCollectorId, ['vehicle_id' => $id]);
                        $data['status'] = 'in-use';
                    } else {
                        // Just unassigning, make vehicle available
                        $data['status'] = 'available';
                    }
                }
            }

            if (!empty($data)) {
                $ok = $this->vehicles->update($id, $data);
                if (!$ok) {
                    return Response::errorJson('Vehicle update failed', 500);
                }
            }
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique constraint')) {
                return Response::errorJson('Duplicated vehicle id.', 409);
            }
            return Response::errorJson('Failed to update vehicle', 500, ['detail' => $e->getMessage()]);
        }

        $fresh = $this->vehicles->find($id);

        return Response::json([
            'message' => 'Vehicle updated',
            'vehicle' => $fresh,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Vehicle id must be provided', 400);
        }

        $existing = $this->vehicles->find($id);
        if (!$existing) {
            return Response::errorJson('Vehicle not found', 404);
        }

        try {
            // Unassign any collector
            $currentCollector = $this->userModel->findByVehicleId($id);
            if ($currentCollector) {
                $this->userModel->updateUser($currentCollector['id'], ['vehicle_id' => null]);
            }

            $deleted = $this->vehicles->delete($id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to delete vehicle', 500, ['detail' => $e->getMessage()]);
        }

        if (!$deleted) {
            return Response::errorJson('Vehicle could not be deleted', 500);
        }

        return Response::json([
            'message' => 'Vehicle deleted',
            'vehicle' => $existing,
        ]);
    }

    public function listAvailable(Request $request): Response
    {
        try {
            // Can add filter logic here if Vehicles model supports it, e.g. listByStatus('available')
            // For now, fetching all and filtering in PHP or assumes listAll returns all status
            $all = $this->vehicles->listAll();
            $available = array_values(array_filter($all, fn($v) => ($v['status'] ?? '') === 'available'));
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load available vehicles', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'vehicles' => $available,
        ]);
    }

    public function assignSelf(Request $request): Response
    {
        $user = auth();

        if (!$user) {
            return Response::errorJson('Unauthorized', 401);
        }

        $input = $request->json();
        $vehicleId = $input['vehicleId'] ?? null;

        if (!$vehicleId) {
            return Response::errorJson('Vehicle ID is required', 400);
        }

        $vehicleId = (int) $vehicleId;

        // Verify vehicle is available
        $vehicle = $this->vehicles->find($vehicleId);
        if (!$vehicle) {
            return Response::errorJson('Vehicle not found', 404);
        }

        if (($vehicle['status'] ?? '') !== 'available') {
            return Response::errorJson('Vehicle is not available', 409);
        }

        try {
            // Handle current vehicle if exists
            $currentVehicleId = $user['vehicle_id'] ?? null; // NOTE: auth()->user() might return raw DB row or normalized. Adjust based on auth implementation.
            // If current user object from auth() is array:
            if (!$currentVehicleId && isset($user['vehicleId'])) {
                $currentVehicleId = $user['vehicleId'];
            }
            // If using session data which might not be fresh, we should refetch user
            $freshUser = $this->userModel->findById($user['id']);
            $currentVehicleId = $freshUser['vehicleId'] ?? null;

            if ($currentVehicleId) {
                $this->vehicles->markStatus((int) $currentVehicleId, 'available');
            }

            // Assign new
            $this->vehicles->markStatus($vehicleId, 'in-use');
            $this->userModel->updateUser($user['id'], ['vehicle_id' => $vehicleId]);

            return Response::json(['success' => true, 'message' => 'Vehicle assigned successfully']);

        } catch (\Throwable $e) {
            return Response::errorJson('Assignment failed', 500, ['detail' => $e->getMessage()]);
        }
    }

    public function releaseSelf(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthorized', 401);
        }

        try {
            $freshUser = $this->userModel->findById($user['id']);
            $currentVehicleId = $freshUser['vehicleId'] ?? null;

            if (!$currentVehicleId) {
                return Response::errorJson('No vehicle currently assigned', 400);
            }

            // Mark available
            $this->vehicles->markStatus((int) $currentVehicleId, 'available');
            // Unassign from user
            $this->userModel->updateUser($user['id'], ['vehicle_id' => null]);

            return Response::json(['success' => true, 'message' => 'Vehicle released successfully']);

        } catch (\Throwable $e) {
            return Response::errorJson('Release failed', 500, ['detail' => $e->getMessage()]);
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

    private function validatePayload(Request $request, bool $isCreate): array
    {
        $source = $request->all();
        $errors = [];
        $data = [];

        if ($isCreate || array_key_exists('plateNumber', $source)) {
            $plateNumber = strtoupper(trim((string) ($source['plateNumber'] ?? '')));
            if ($plateNumber === '') {
                if ($isCreate) {
                    $errors['plateNumber'] = 'Plate number is required.';
                }
            } elseif (!preg_match('/^[A-Z]{3}-[0-9]{4}$/', $plateNumber)) {
                $errors['plateNumber'] = 'Plate number must follow the format ABC-1234.';
            } else {
                $data['plate_number'] = $plateNumber;
            }
        }
        $typeProvided = false;
        if ($isCreate || array_key_exists('type', $source)) {
            $type = trim((string) ($source['type'] ?? ''));
            if ($type === '') {
                if ($isCreate) {
                    $errors['type'] = 'Vehicle type is required.';
                }
            } elseif (!array_key_exists($type, self::VEHICLE_TYPE_CAPACITY)) {
                $errors['type'] = 'Vehicle type is invalid.';
            } else {
                $typeProvided = true;
                $data['type'] = $type;
                $data['capacity'] = self::VEHICLE_TYPE_CAPACITY[$type];
            }
        }

        if ($typeProvided && array_key_exists('capacity', $source)) {
            $capacityRaw = $source['capacity'];
            if ($capacityRaw !== null && $capacityRaw !== '' && (int) $capacityRaw !== $data['capacity']) {
                $errors['capacity'] = 'Capacity must match the predefined value for the selected vehicle type.';
            }
        } elseif (!$typeProvided && ($isCreate || array_key_exists('capacity', $source))) {
            $capacityRaw = $source['capacity'] ?? null;
            if ($capacityRaw !== null && $capacityRaw !== '') {
                $errors['capacity'] = 'Capacity is assigned automatically based on vehicle type.';
            } elseif ($isCreate) {
                $errors['capacity'] = 'Capacity is required.';
            }
        }

        if ($isCreate || array_key_exists('status', $source)) {
            if ($isCreate) {
                // Initial status will be set based on assignment later
                $data['status'] = 'available';
            } else {
                $status = strtolower(trim((string) ($source['status'] ?? '')));
                if ($status === '') {
                    $errors['status'] = 'Vehicle status is required.';
                } elseif (!in_array($status, self::ALLOWED_STATUSES, true)) {
                    $errors['status'] = 'Invalid vehicle status provided.';
                } else {
                    $data['status'] = $status;
                }
            }
        }

        if (array_key_exists('assignedCollectorId', $source)) {
            $collectorId = $source['assignedCollectorId'];
            if ($collectorId !== null && $collectorId !== '') {
                if (!is_numeric($collectorId)) {
                    $errors['assignedCollectorId'] = 'Invalid collector ID format.';
                } else {
                    $data['assigned_collector_id'] = (int) $collectorId;
                }
            } else {
                $data['assigned_collector_id'] = null;
            }
        }

        return empty($errors) ? ['data' => $data] : ['errors' => $errors];
    }
}
