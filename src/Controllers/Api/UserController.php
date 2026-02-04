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
    public function createUser(Request $request): Response
    {
        // Require JSON input
        $input = $request->json();
        if (!is_array($input)) {
            return Response::errorJson('Invalid JSON payload', 400);
        }

        // Extract and validate required fields
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $type = trim((string) ($input['type'] ?? 'collector'));
        $password = (string) ($input['password'] ?? '');

        // Collector-specific fields
        $licenseNumber = trim((string) ($input['licenseNumber'] ?? ''));
        $nic = trim((string) ($input['nic'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));

        // Validation
        if (empty($name)) {
            return Response::errorJson('Name is required', 400);
        }

        if (empty($email)) {
            return Response::errorJson('Email is required', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::errorJson('Please provide a valid email address', 400);
        }

        if (empty($phone)) {
            return Response::errorJson('Phone number is required', 400);
        }

        if (empty($password)) {
            return Response::errorJson('Password is required', 400);
        }

        if (strlen($password) < 6) {
            return Response::errorJson('Password must be at least 6 characters', 400);
        }

        // Validate type
        $validTypes = ['customer', 'company', 'collector', 'admin'];
        if (!in_array($type, $validTypes, true)) {
            return Response::errorJson('Invalid user type', 400);
        }

        // Additional validation for collectors
        if ($type === 'collector') {
            if (empty($licenseNumber)) {
                return Response::errorJson('License number is required for collectors', 400);
            }
            if (empty($nic)) {
                return Response::errorJson('NIC is required for collectors', 400);
            }
            if (empty($address)) {
                return Response::errorJson('Address is required for collectors', 400);
            }
        }

        try {
            // Check if email already exists
            if ($this->userModel->emailExists($email)) {
                return Response::errorJson('An account with that email already exists', 409);
            }

            // Get role_id for the user type
            $roleId = null;
            try {
                $db = new \Core\Database();
                $row = $db->fetch('SELECT id FROM roles WHERE name = ? LIMIT 1', [$type]);
                if ($row && isset($row['id'])) {
                    $roleId = (int) $row['id'];
                }
            } catch (\Throwable $e) {
                // Role ID remains null if lookup fails
            }

            // Prepare user data
            $userData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'type' => $type,
                'password' => $password, // Will be hashed by User model
                'status' => 'active',
            ];

            // Add collector-specific fields to metadata
            if ($type === 'collector') {
                $userData['metadata'] = [
                    'licenseNumber' => $licenseNumber,
                    'nic' => $nic,
                    'address' => $address,
                ];
            }

            if ($roleId !== null) {
                $userData['role_id'] = $roleId;
            }

            // Create user
            $newUserId = $this->userModel->createUser($userData);

            if ($newUserId === false) {
                return Response::errorJson('Failed to create user account', 500);
            }

            // Fetch the created user to return complete data
            $createdUser = $this->userModel->findById((int) $newUserId);

            return Response::json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $createdUser
            ], 201);

        } catch (\Exception $e) {
            return Response::errorJson('An error occurred while creating user: ' . $e->getMessage(), 500);
        }
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
