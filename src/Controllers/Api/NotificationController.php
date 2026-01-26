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
        $user = auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $limit = (int) $request->input('limit', 20);
        if ($user['role'] === 'admin') {
            $notifications = $this->model->getAll();
        } else {
            $notifications = $this->model->forUser($user['id'], $limit);
        }
        $unreadCount = $this->model->getUnreadCount($user['id']);

        return $this->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Create a new notification (Admin only or internal use)
     */
    public function store(Request $request): Response
    {
        // Assuming only admins or system can create notifications via API for now
        // Or maybe this is an internal API. 
        // Let's add a basic check if needed, but for now I'll leave it open or rely on middleware in routes.

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

        $id = $this->model->create($data);

        return $this->success('Notification created', ['id' => $id]);
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
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Try multiple ways to get the ID depending on how your router works
        $id = null;

        // Method 1: From route params
        if (method_exists($request, 'getRouteParams')) {
            $params = $request->getRouteParams();
            $id = $params['id'] ?? null;
        }

        // Method 2: From URI segments
        if ($id === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/api/notifications/(\d+)/read#', $uri, $matches)) {
                $id = $matches[1];
            }
        }

        // Method 3: From request input
        if ($id === null) {
            $id = $request->input('id');
        }

        $id = (int) $id;

        error_log("Received ID: " . $id . " from URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Invalid notification ID'], 400);
        }

        try {
            $result = $this->model->markAsRead($id, $user['id']);

            return $this->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
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
    }

    /**
     * Get unread count
     */
    public function unreadCount(): Response
    {
        $user = auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $count = $this->model->getUnreadCount($user['id']);

        return $this->json(['count' => $count]);
    }
}
