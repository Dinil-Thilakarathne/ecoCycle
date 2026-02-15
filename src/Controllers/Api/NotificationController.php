<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Validator;
use Models\Notification;
use Models\User;

class NotificationController extends BaseController
{
    private Notification $model;
    private User $userModel;

    public function __construct()
    {
        $this->model = new Notification();
        $this->userModel = new User();
    }

    /**
     * List notifications for the authenticated user
     */
    /**
     * List notifications for the authenticated user
     */
    /**
     * List notifications for the authenticated user
     */
    public function index(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $limit = (int) $request->input('limit', 20);

        if ($user['role'] === 'admin') {
            $notifications = $this->model->getAll($limit);
        } else {
            // Fetch full user to get created_at
            $fullUser = $this->userModel->findById($user['id']);
            $createdAt = $fullUser['created_at'] ?? '2000-01-01 00:00:00';

            if ($user['role'] === 'company') {
                $notifications = $this->model->forCompany($user['id'], $createdAt, $limit);
            } else {
                $notifications = $this->model->forUser($user['id'], $user['role'], $createdAt, $limit);
            }
        }
        $unreadCount = $this->model->getUnreadCount($user['id'], $user['role']);

        return $this->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Create a new notification (Admin only)
     */
    public function store(Request $request): Response
    {
        $user = auth();
        if (!$user || $user['role'] !== 'admin') {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'string',
            'recipient_group' => 'string',
            'recipients' => 'array'
        ]);

        if ($validator->fails()) {
            return $this->json(['errors' => $validator->getErrors()], 422);
        }

        $data = $validator->getValidatedData();

        // Validate recipient_group if provided
        $validGroups = ['all', 'users', 'company', 'companies', 'customer', 'customers', 'collector', 'collectors'];
        if (!empty($data['recipient_group']) && !in_array($data['recipient_group'], $validGroups)) {
            return $this->json(['errors' => ['recipient_group' => 'Invalid recipient group']], 422);
        }

        $data['status'] = 'pending';

        $id = $this->model->create($data);

        return $this->json(['message' => 'Notification created', 'data' => ['id' => $id]]);
    }

    /**
     * Mark a notification as read
     */
    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Method 3: From request input
        $id = $request->route('id');
        if ($id === null) {
            $id = $request->input('id');
        }

        $id = (int) $id;

        error_log("Received ID: " . $id . " from URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Invalid notification ID'], 400);
        }
        $this->model->markAsRead($id, $user['id']);
        return $this->json(['message' => 'Notification marked as read']);
    }


    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        error_log("markAllAsRead called for user ID: " . $user['id']);

        try {
            $result = $this->model->markAllAsRead($user['id']);

            error_log("markAllAsRead result: " . ($result ? 'true' : 'false'));

            return $this->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            error_log("markAllAsRead exception: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
        return $this->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Get unread count
     */
    public function unreadCount(): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $count = $this->model->getUnreadCount($user['id'], $user['role']);

        return $this->json(['count' => $count]);
    }
}
