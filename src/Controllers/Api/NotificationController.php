<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\Notification;

class NotificationController extends BaseController
{
    private Notification $model;

    public function __construct(\Core\Container $container)
    {
        parent::__construct($container);
        $this->model = new Notification();
    }

    /**
     * List notifications for the authenticated user
     */
    public function index(): Response
    {
        $user = $this->auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $limit = (int) $this->input('limit', 20);
        $notifications = $this->model->forUser($user['id'], $limit);
        $unreadCount = $this->model->getUnreadCount($user['id']);

        return $this->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Create a new notification (Admin only or internal use)
     */
    public function store(): Response
    {
        // Assuming only admins or system can create notifications via API for now
        // Or maybe this is an internal API. 
        // Let's add a basic check if needed, but for now I'll leave it open or rely on middleware in routes.
        
        $data = $this->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'type' => 'string',
            'recipient_group' => 'string',
            'recipients' => 'array'
        ]);

        $id = $this->model->create($data);

        return $this->success('Notification created', ['id' => $id]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $id): Response
    {
        $user = $this->auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->model->markAsRead($id, $user['id']);

        return $this->success('Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): Response
    {
        $user = $this->auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->model->markAllAsRead($user['id']);

        return $this->success('All notifications marked as read');
    }

    /**
     * Get unread count
     */
    public function unreadCount(): Response
    {
        $user = $this->auth();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $count = $this->model->getUnreadCount($user['id']);

        return $this->json(['count' => $count]);
    }
}
