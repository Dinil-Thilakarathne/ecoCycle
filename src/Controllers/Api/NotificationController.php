<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Validator;
use Models\Notification;

class NotificationController extends BaseController
{
    private Notification $model;

    public function __construct()
    {
        $this->model = new Notification();
    }

    /**
     * List notifications for the authenticated user
     */
    public function index(Request $request): Response
    {
        return $this->notifications($request);
    }

    /**
     * Collector-friendly list endpoint.
     * Returns the shape expected by collector notification views.
     */
    public function notifications(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $limit = max(1, (int) $request->input('limit', 100));
        $role = strtolower((string) ($user['role'] ?? ''));

        if ($role === 'admin') {
            $notifications = $this->model->getAll($limit);
        } elseif ($role === 'company') {
            $notifications = $this->model->forCompany((int) $user['id'], '', $limit);
        } else {
            $notifications = $this->model->forUser((int) $user['id'], $role, '', $limit);
        }

        return $this->json([
            'status' => 'success',
            'data' => $notifications ?: [],
            'timestamp' => date('Y-m-d H:i:s'),
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
        $validGroups = ['all', 'users', 'company', 'companies', 'customer', 'customers', 'collector', 'collectors', 'admin', 'admins'];
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
    public function markAsRead(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        // Get notification ID from route parameter (keep as string)
        $id = $request->route('id');
        if ($id === null || $id === '') {
            $id = $request->input('id');
        }

        if (empty($id)) {
            return $this->json(['success' => false, 'message' => 'Invalid notification ID'], 400);
        }

        try {
            $notification = $this->model->findById($id);
            if (!$notification) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Notification not found'
                ], 404);
            }

            $result = $this->model->markAsRead($id, $user['id']);

            if ($result) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Notification marked as read',
                    'data' => ['id' => $id, 'status' => 'read']
                ]);
            } else {
                return $this->json([
                    'success' => false, 
                    'message' => 'Failed to update notification status'
                ], 500);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alias for auto-routed collector endpoints that call /read.
     */
    public function read(Request $request): Response
    {
        return $this->markAsRead($request);
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

        try {
            $this->model->markAllAsRead((int) $user['id'], (string) ($user['role'] ?? ''));

            return $this->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * Delete a notification
     */
    public function destroy(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $id = $request->route('id');
        if ($id === null || $id === '') {
            $id = $request->input('id');
        }

        if ($id === null || $id === '') {
            return $this->json(['success' => false, 'message' => 'Invalid notification ID'], 400);
        }

        $notification = $this->model->findById($id);
        if (!$notification) {
            return $this->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        $hasAccess = $this->model->canUserAccessNotification(
            $id,
            (int) ($user['id'] ?? 0),
            (string) ($user['role'] ?? '')
        );

        if (!$hasAccess) {
            return $this->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $deleted = $this->model->deleteById($id);
        if (!$deleted) {
            return $this->json(['success' => false, 'message' => 'Failed to delete notification'], 500);
        }

        return $this->json(['success' => true, 'message' => 'Notification deleted']);
    }
}
