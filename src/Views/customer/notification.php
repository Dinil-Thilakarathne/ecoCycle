<?php
// Handle form submissions
$action = $_GET['action'] ?? '';
$notificationId = $_GET['id'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Sample notifications data
$notifications = [
    [
        'id' => 'N001',
        'type' => 'pickup_confirmed',
        'title' => 'Pickup Request Confirmed',
        'message' => 'Your pickup request PR001 has been confirmed and scheduled for January 15, 2024 at 9:00 AM.',
        'timestamp' => '2024-01-10 14:30:00',
        'isRead' => false,
        'priority' => 'high',
        'icon' => '✅',
        'category' => 'pickup'
    ],
    [
        'id' => 'N002',
        'type' => 'pickup_completed',
        'title' => 'Pickup Completed',
        'message' => 'Your pickup PR002 has been completed successfully. You earned $62.00 from this collection.',
        'timestamp' => '2024-01-08 16:45:00',
        'isRead' => false,
        'priority' => 'normal',
        'icon' => '🚛',
        'category' => 'pickup'
    ],
    [
        'id' => 'N003',
        'type' => 'payment_processed',
        'title' => 'Payment Processed',
        'message' => 'Your payment of $127.50 has been processed and will be transferred to your account within 2-3 business days.',
        'timestamp' => '2024-01-07 10:15:00',
        'isRead' => true,
        'priority' => 'normal',
        'icon' => '💰',
        'category' => 'payment'
    ],
    [
        'id' => 'N004',
        'type' => 'pickup_reminder',
        'title' => 'Pickup Reminder',
        'message' => 'Reminder: Your scheduled pickup is tomorrow (January 15) at 9:00 AM. Please ensure your waste is ready for collection.',
        'timestamp' => '2024-01-14 18:00:00',
        'isRead' => true,
        'priority' => 'normal',
        'icon' => '🔔',
        'category' => 'pickup'
    ],
    [
        'id' => 'N005',
        'type' => 'system_update',
        'title' => 'System Maintenance',
        'message' => 'Scheduled system maintenance will occur on January 20, 2024 from 2:00 AM to 4:00 AM. Services may be temporarily unavailable.',
        'timestamp' => '2024-01-05 09:00:00',
        'isRead' => true,
        'priority' => 'low',
        'icon' => '⚙️',
        'category' => 'system'
    ],
    [
        'id' => 'N006',
        'type' => 'pickup_cancelled',
        'title' => 'Pickup Cancelled',
        'message' => 'Your pickup request PR005 has been cancelled due to weather conditions. We will reschedule automatically.',
        'timestamp' => '2024-01-03 12:20:00',
        'isRead' => true,
        'priority' => 'high',
        'icon' => '❌',
        'category' => 'pickup'
    ]
];

// Handle actions
if ($action === 'mark_read' && $notificationId) {
    // In a real app, you would update the database
    foreach ($notifications as &$notification) {
        if ($notification['id'] === $notificationId) {
            $notification['isRead'] = true;
            break;
        }
    }
    // Redirect to avoid resubmission
    header('Location: notifications-page.php');
    exit;
}

if ($action === 'mark_all_read') {
    // In a real app, you would update the database
    foreach ($notifications as &$notification) {
        $notification['isRead'] = true;
    }
    // Redirect to avoid resubmission
    header('Location: notifications-page.php');
    exit;
}

if ($action === 'delete' && $notificationId) {
    // In a real app, you would delete from database
    $notifications = array_filter($notifications, fn($n) => $n['id'] !== $notificationId);
    // Redirect to avoid resubmission
    header('Location: notifications-page.php');
    exit;
}

// Filter notifications based on selected filter
$filteredNotifications = $notifications;
switch ($filter) {
    case 'unread':
        $filteredNotifications = array_filter($notifications, fn($n) => !$n['isRead']);
        break;
    case 'pickup':
        $filteredNotifications = array_filter($notifications, fn($n) => $n['category'] === 'pickup');
        break;
    case 'payment':
        $filteredNotifications = array_filter($notifications, fn($n) => $n['category'] === 'payment');
        break;
    case 'system':
        $filteredNotifications = array_filter($notifications, fn($n) => $n['category'] === 'system');
        break;
}

// Calculate stats
$totalNotifications = count($notifications);
$unreadNotifications = count(array_filter($notifications, fn($n) => !$n['isRead']));
$highPriorityNotifications = count(array_filter($notifications, fn($n) => $n['priority'] === 'high'));
$todayNotifications = count(array_filter($notifications, fn($n) => date('Y-m-d', strtotime($n['timestamp'])) === date('Y-m-d')));

// Function to get time ago
function timeAgo($timestamp) {
    $time = time() - strtotime($timestamp);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($timestamp));
}

// Function to get status class
function getStatusClass($priority, $isRead) {
    if (!$isRead) return 'status-unread';
    
    switch($priority) {
        case 'high': return 'status-high';
        case 'normal': return 'status-normal';
        case 'low': return 'status-low';
        default: return 'status-normal';
    }
}

// Function to truncate message
function truncateMessage($message, $length = 80) {
    if (strlen($message) <= $length) {
        return $message;
    }
    return substr($message, 0, $length) . '...';
}

// Handle view notification
$viewNotification = null;
if ($action === 'view' && $notificationId) {
    foreach ($notifications as $notification) {
        if ($notification['id'] === $notificationId) {
            $viewNotification = $notification;
            break;
        }
    }
}

// Handle settings modal
$showSettings = ($action === 'settings');
?>

    <div class="dashboard-page">
        <!-- Header -->
        <div class="header">
            <div class="header-actions action-buttons">
                <a href="?action=mark_all_read" class="btn btn-outline">Mark All Read</a>
                <a href="?action=settings" class="btn btn-primary">⚙️ Settings</a>
            </div>
        </div>

        <!-- Stats Feature Cards -->
        <?php
        $notificationStats = [
            [
                'title' => 'Total Notifications',
                'value' => $totalNotifications,
                'icon' => 'fa-solid fa-bell',
                'subtitle' => 'All time',
            ],
            [
                'title' => 'Unread',
                'value' => $unreadNotifications,
                'icon' => 'fa-solid fa-envelope-open',
                'subtitle' => 'Need attention',
            ],
            [
                'title' => 'High Priority',
                'value' => $highPriorityNotifications,
                'icon' => 'fa-solid fa-exclamation-circle',
                'subtitle' => 'Critical alerts',
            ],
            [
                'title' => 'Today',
                'value' => $todayNotifications,
                'icon' => 'fa-solid fa-calendar-day',
                'subtitle' => 'Received today',
            ],
        ];
        ?>
        <div class="stats-grid">
            <?php foreach ($notificationStats as $stat): ?>
                <div class="feature-card">
                    <div class="feature-card__header">
                        <h3 class="feature-card__title">
                            <?= htmlspecialchars($stat['title']) ?>
                        </h3>
                        <div class="feature-card__icon">
                            <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                        </div>
                    </div>
                    <p class="feature-card__body">
                        <?= htmlspecialchars($stat['value']) ?>
                    </p>
                    <div class="feature-card__footer">
                        <span class="tag success"><?= htmlspecialchars($stat['subtitle']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filter Tabs -->
        <div class="action-buttons" style="margin-bottom:2rem;">
            <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">Unread (<?php echo $unreadNotifications; ?>)</a>
            <a href="?filter=pickup" class="btn <?php echo $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">Pickup</a>
            <a href="?filter=payment" class="btn <?php echo $filter === 'payment' ? 'btn-primary' : 'btn-outline'; ?>">Payment</a>
            <a href="?filter=system" class="btn <?php echo $filter === 'system' ? 'btn-primary' : 'btn-outline'; ?>">System</a>
        </div>

        <!-- Notifications Table -->
        <div class="table-container" style="overflow-x:auto;">
            <table class="notifications-table" style="min-width:800px;">
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredNotifications)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-content">
                                    <div class="empty-icon">📭</div>
                                    <h3>No notifications found</h3>
                                    <p>No notifications match your current filter.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filteredNotifications as $notification): ?>
                            <tr class="notification-row <?php echo !$notification['isRead'] ? 'unread' : ''; ?>">
                                <td class="notification-info">
                                    <div class="notification-details">
                                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="notification-message"><?php echo htmlspecialchars(truncateMessage($notification['message'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge <?php echo $notification['category']; ?>">
                                        <?php echo ucfirst($notification['category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge <?php echo $notification['priority']; ?>">
                                        <?php echo ucfirst($notification['priority']); ?>
                                    </span>
                                </td>
                                <td class="time-cell">
                                    <?php echo timeAgo($notification['timestamp']); ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($notification['priority'], $notification['isRead']); ?>">
                                        <?php echo !$notification['isRead'] ? 'Unread' : 'Read'; ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <?php if (!$notification['isRead']): ?>
                                        <a href="?action=mark_read&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>" class="action-btn mark-read">Mark Read</a>
                                    <?php endif; ?>
                                    <a href="?action=view&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>" class="action-btn view">View</a>
                                    <a href="?action=delete&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this notification?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Notification Modal -->
    <?php if ($viewNotification): ?>
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?php echo htmlspecialchars($viewNotification['title']); ?></h2>
                    <a href="?filter=<?php echo $filter; ?>" class="modal-close">×</a>
                </div>
                <div class="modal-body">
                    <div class="notification-detail">
                        <div class="detail-content">
                            <div class="detail-meta">
                                <span class="type-badge <?php echo $viewNotification['category']; ?>">
                                    <?php echo ucfirst($viewNotification['category']); ?>
                                </span>
                                <span class="priority-badge <?php echo $viewNotification['priority']; ?>">
                                    <?php echo ucfirst($viewNotification['priority']); ?>
                                </span>
                                <span class="time-badge"><?php echo timeAgo($viewNotification['timestamp']); ?></span>
                            </div>
                            <p class="detail-message"><?php echo htmlspecialchars($viewNotification['message']); ?></p>
                            <div class="detail-timestamp">
                                <strong>Received:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($viewNotification['timestamp'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (!$viewNotification['isRead']): ?>
                        <a href="?action=mark_read&id=<?php echo $viewNotification['id']; ?>&filter=<?php echo $filter; ?>" class="btn-primary">Mark as Read</a>
                    <?php endif; ?>
                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Settings Modal -->
    <?php if ($showSettings): ?>
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Notification Settings</h2>
                    <a href="?filter=<?php echo $filter; ?>" class="modal-close">×</a>
                </div>
                <form method="POST" action="?action=save_settings">
                    <div class="modal-body">
                        <div class="form-section">
                            <h3>Email Notifications</h3>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_pickup" checked>
                                    <span class="checkmark"></span>
                                    Pickup confirmations
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_payment" checked>
                                    <span class="checkmark"></span>
                                    Payment notifications
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="email_system">
                                    <span class="checkmark"></span>
                                    System updates
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Push Notifications</h3>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="push_reminders" checked>
                                    <span class="checkmark"></span>
                                    Pickup reminders
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="push_alerts" checked>
                                    <span class="checkmark"></span>
                                    High priority alerts
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <label for="frequency" class="form-label">Email frequency</label>
                            <select id="frequency" name="frequency" class="form-select">
                                <option value="immediate">Immediate</option>
                                <option value="daily" selected>Daily digest</option>
                                <option value="weekly">Weekly digest</option>
                                <option value="never">Never</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="?filter=<?php echo $filter; ?>" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>