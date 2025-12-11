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
}
