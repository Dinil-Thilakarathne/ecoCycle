<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\User;

class UserController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function suspend(Request $request): Response
    {
        // Require JSON input
        $input = $request->json();
        if (!is_array($input)) {
            return Response::errorJson('Invalid JSON payload', 400);
        }

        $userId = $input['userId'] ?? null;
        $reason = $input['reason'] ?? null; // reason is logged or potentially stored in metadata (future)

        if (empty($userId)) {
            return Response::errorJson('User ID is required', 400);
        }

        // Validate user exists
        $user = $this->userModel->findById((int) $userId);
        if (!$user) {
            return Response::errorJson('User not found', 404);
        }

        // Perform suspension
        try {
            // Update status to 'suspended'
            $success = $this->userModel->setStatus((int) $userId, 'suspended');

            if ($success) {
                // Return success response with updated user data
                $updatedUser = $this->userModel->findById((int) $userId);
                return Response::json([
                    'success' => true,
                    'message' => 'User suspended successfully',
                    'user' => $updatedUser
                ]);
            } else {
                return Response::errorJson('Failed to suspend user', 500);
            }
        } catch (\Exception $e) {
            return Response::errorJson('An error occurred while suspending user', 500, ['error' => $e->getMessage()]);
        }
    }

    public function findbyId(Request $request): Response
    {
        $userId = $request->route('id');

        if (!is_numeric($userId) || !$userId) {
            return Response::errorJson('Invalid user ID', 400);
        }

        $user = $this->userModel->findById((int) $userId);

        if (!$user) {
            return Response::errorJson('User not found', 404);
        }

        return Response::json($user);
    }

    public function findAll(Request $request): Response
    {
        $users = $this->userModel->findAll();

        if (!$users) {
            return Response::errorJson('No users found', 404);
        }

        return Response::json($users);
    }
    public function assignVehicle(Request $request): Response
    {
        // Require JSON input
        $input = $request->json();
        if (!is_array($input)) {
            return Response::errorJson('Invalid JSON payload', 400);
        }

        $userId = $input['userId'] ?? null;
        $vehicleId = $input['vehicleId'] ?? null;

        if (empty($userId)) {
            return Response::errorJson('User ID is required', 400);
        }

        // Validate user exists
        $user = $this->userModel->findById((int) $userId);
        if (!$user) {
            return Response::errorJson('User not found', 404);
        }

        $vehicleModel = new \Models\Vehicle();

        try {
            // Check if user already has a vehicle
            $currentVehicleId = $user['vehicleId'] ?? ($user['vehicle_id'] ?? null);

            // Logic 1: Unassigning (vehicleId is null/empty)
            if (empty($vehicleId)) {
                if ($currentVehicleId) {
                    // Mark old vehicle as available
                    $vehicleModel->markStatus((int) $currentVehicleId, 'available');
                    // Remove vehicle from user
                    $this->userModel->updateUser((int) $userId, ['vehicle_id' => null]);
                }
                return Response::json(['message' => 'Vehicle unassigned successfully']);
            }

            // Logic 2: Assigning a new vehicle
            $newVehicleId = (int) $vehicleId;

            // If functionality is "change vehicle", handle old one first
            if ($currentVehicleId && (int) $currentVehicleId !== $newVehicleId) {
                $vehicleModel->markStatus((int) $currentVehicleId, 'available');
            }

            // Check new vehicle availability
            $vehicle = $vehicleModel->find($newVehicleId);
            if (!$vehicle) {
                return Response::errorJson('Vehicle not found', 404);
            }

            // If we are re-assigning the SAME vehicle, do nothing or ensure it's in-use
            if ($currentVehicleId && (int) $currentVehicleId === $newVehicleId) {
                // Ensure status is correct just in case
                $vehicleModel->markStatus($newVehicleId, 'in-use');
                return Response::json(['message' => 'Vehicle already assigned to this user']);
            }

            if (($vehicle['status'] ?? '') !== 'available') {
                return Response::errorJson('Vehicle is not available for assignment', 409);
            }

            // Assign new vehicle
            $vehicleModel->markStatus($newVehicleId, 'in-use');
            $this->userModel->updateUser((int) $userId, ['vehicle_id' => $newVehicleId]);

            return Response::json([
                'success' => true,
                'message' => 'Vehicle assigned successfully',
                'vehicle' => $vehicle
            ]);

        } catch (\Exception $e) {
            return Response::errorJson('An error occurred while assigning vehicle', 500, ['error' => $e->getMessage()]);
        }
    }
}
