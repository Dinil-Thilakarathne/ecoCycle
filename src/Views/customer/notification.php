<?php
use Models\Notification;

// Get current user
$user = auth();
$userId = $user['id'] ?? 0;
$userRole = $user['role'] ?? 'customer';

// Initialize model
$notificationModel = new Notification();

// Handle form submissions
$action = $_GET['action'] ?? '';
$notificationId = $_GET['id'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Handle actions
if ($userId && $action === 'mark_read' && $notificationId) {
    // Update the database
    $notificationModel->markAsRead((int) $notificationId, $userId);
    // Redirect to avoid resubmission
    header("Location: ?filter={$filter}");
    exit;
}

if ($userId && $action === 'mark_all_read') {
    // Update the database
    $notificationModel->markAllAsRead($userId);
    // Redirect to avoid resubmission
    header("Location: ?filter={$filter}");
    exit;
}

// Fetch real notifications from database
$allNotifications = $notificationModel->forUser($userId, $userRole, 50);

// Map DB format to View format if necessary (though they seem compatible)
// The model returns: id, type, title, message, timestamp, status ('pending'/'read'), recipients
// The view expects: isRead boolean, priority (we can derive), category (derive from type), icon
$notifications = array_map(function ($n) {
    $isRead = ($n['status'] === 'read');

    // Derive category/icon/priority from type
    $category = 'system';
    $icon = 'fa-solid fa-bell';
    $priority = 'normal';

    // Simple mapping based on type keywords
    $type = strtolower($n['type']);
    if (strpos($type, 'pickup') !== false) {
        $category = 'pickup';
        $icon = 'fa-solid fa-truck';
    } elseif (strpos($type, 'payment') !== false || strpos($type, 'money') !== false) {
        $category = 'payment';
        $icon = 'fa-solid fa-money-bill';
    } elseif (strpos($type, 'alert') !== false || strpos($type, 'warning') !== false) {
        $priority = 'high';
        $icon = 'fa-solid fa-triangle-exclamation';
    } elseif (strpos($type, 'info') !== false) {
        $priority = 'low';
        $icon = 'fa-solid fa-circle-info';
    }

    // Check if maintainance
    if ($type === 'maintenance') {
        $category = 'system';
        $icon = 'fa-solid fa-screwdriver-wrench';
    }

    return [
        'id' => $n['id'],
        'type' => $n['type'],
        'title' => $n['title'] ?: ucfirst($n['type']), // Fallback title
        'message' => $n['message'],
        'timestamp' => $n['timestamp'],
        'isRead' => $isRead,
        'priority' => $priority,
        'icon' => $icon, // Using FontAwesome class string instead of emoji for consistency if preferred, or map back to emoji
        'category' => $category,
        'status' => $n['status']
    ];
}, $allNotifications);


// Filter notifications based on selected filter
$filteredNotifications = $notifications;
// Note: System alerts might come from a separate method if needed, but forUser handles 'all'/'users' groups
// Excluding 'system' category if strictly requested, but usually users want to see system alerts too.
// Keeping strictly view logic below:

if ($filter === 'unread') {
    $filteredNotifications = array_filter($filteredNotifications, fn($n) => !$n['isRead']);
} elseif ($filter === 'pickup') {
    $filteredNotifications = array_filter($filteredNotifications, fn($n) => $n['category'] === 'pickup');
} elseif ($filter === 'payment') {
    $filteredNotifications = array_filter($filteredNotifications, fn($n) => $n['category'] === 'payment');
}

// Calculate stats
$totalNotifications = count($notifications);
$unreadNotifications = count(array_filter($notifications, fn($n) => !$n['isRead']));
$highPriorityNotifications = count(array_filter($notifications, fn($n) => $n['priority'] === 'high'));
$todayNotifications = count(array_filter($notifications, fn($n) => date('Y-m-d', strtotime($n['timestamp'])) === date('Y-m-d')));

// Function to get time ago
function timeAgo($timestamp)
{
    if (!$timestamp)
        return '';
    $time = time() - strtotime($timestamp);

    if ($time < 60)
        return 'Just now';
    if ($time < 3600)
        return floor($time / 60) . ' mins ago';
    if ($time < 86400)
        return floor($time / 3600) . ' hours ago';
    if ($time < 2592000)
        return floor($time / 86400) . ' days ago';

    return date('M j, Y', strtotime($timestamp));
}

// Function to get status class
function getStatusClass($priority, $isRead)
{
    if (!$isRead)
        return 'status-unread';

    switch ($priority) {
        case 'high':
            return 'status-high';
        case 'normal':
            return 'status-normal';
        case 'low':
            return 'status-low';
        default:
            return 'status-normal';
    }
}

// Function to truncate message
function truncateMessage($message, $length = 80)
{
    if (strlen($message) <= $length) {
        return $message;
    }
    return substr($message, 0, $length) . '...';
}

// Handle view notification
$viewNotification = null;
if ($action === 'view' && $notificationId) {
    foreach ($notifications as $notification) {
        // Loose comparison for ID as DB might be int, view param string
        if ($notification['id'] == $notificationId) {
            $viewNotification = $notification;
            // Mark as read if viewing? Optional, usually triggered by specific action or API
            // $notificationModel->markAsRead((int)$notificationId, $userId); 
            break;
        }
    }
}

// Handle settings modal
$showSettings = ($action === 'settings');
?>

<div class="dashboard-page">
    <!-- small styles to highlight unread notifications (dot + subtle background) -->
    <style>
        .notification-row.unread .notification-title {
            font-weight: 700;
        }

        .notification-row.unread {
            background: #dff4dbff;
        }

        /* keep table row hover readable */
        .notifications-table .notification-row:hover {
            background: #f5f5f5;
        }
    </style>
    <!-- Header -->
    <div class="header">
        <!-- header-actions removed -->
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
        <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">Unread
            (<?php echo $unreadNotifications; ?>)</a>
        <a href="?filter=pickup"
            class="btn <?php echo $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">Pickup</a>
        <a href="?filter=payment"
            class="btn <?php echo $filter === 'payment' ? 'btn-primary' : 'btn-outline'; ?>">Payment</a>
    </div>

    <!-- Notifications Table -->
    <div class="table-container" style="overflow-x:auto;">
        <table class="notifications-table data-table" style="min-width:800px;">
            <thead>
                <tr>
                    <th>Notification</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredNotifications)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="empty-content">
                                <div class="empty-icon">📭</div>
                                <h3>No notifications found</h3>
                                <p>No notifications match your current filter.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredNotifications as $notification): ?>
                        <tr class="notification-row <?php echo !$notification['isRead'] ? 'unread' : ''; ?>"
                            data-id="<?php echo $notification['id']; ?>">
                            <td class="notification-info">
                                <div class="notification-details">
                                    <?php if (!$notification['isRead']): ?>
                                        <span class="unread-dot" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars(truncateMessage($notification['message'])); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge <?php echo $notification['category']; ?>">
                                    <?php echo ucfirst($notification['category']); ?>
                                </span>
                            </td>
                            <td class="time-cell">
                                <?php echo timeAgo($notification['timestamp']); ?>
                            </td>
                            <td>
                                <span
                                    class="status-badge <?php echo getStatusClass($notification['priority'], $notification['isRead']); ?>">
                                    <?php echo !$notification['isRead'] ? 'Unread' : 'Read'; ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <?php if (!$notification['isRead']): ?>
                                    <a href="?action=mark_read&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>"
                                        class="action-btn mark-read">Mark Read</a>
                                <?php endif; ?>
                                <a href="?action=view&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>"
                                    class="action-btn view">View</a>
                                <a href="?action=delete&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>"
                                    data-id="<?php echo $notification['id']; ?>" class="action-btn delete">Delete</a>
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
                            <strong>Received:</strong>
                            <?php echo date('F j, Y \a\t g:i A', strtotime($viewNotification['timestamp'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if (!$viewNotification['isRead']): ?>
                    <a href="?action=mark_read&id=<?php echo $viewNotification['id']; ?>&filter=<?php echo $filter; ?>"
                        class="btn-primary">Mark as Read</a>
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

<script>
    (function () {
        // Helper to update counts in the stats cards (simple DOM text replace)
        function updateStats(deltaTotal, deltaUnread) {
            var totalEl = document.querySelector('.stats-grid .feature-card__body') || null;
            // More robust selectors: find cards by label text
            var cards = Array.from(document.querySelectorAll('.stats-grid .feature-card'));
            cards.forEach(function (card) {
                var title = (card.querySelector('.feature-card__title') || {}).textContent || '';
                var body = card.querySelector('.feature-card__body');
                if (!body) return;
                if (title.indexOf('Total') !== -1) {
                    body.textContent = parseInt(body.textContent || 0, 10) + deltaTotal;
                }
                if (title.indexOf('Unread') !== -1) {
                    body.textContent = parseInt(body.textContent || 0, 10) + deltaUnread;
                }
            });
        }

        function removeRowById(id) {
            var row = document.querySelector('.notification-row[data-id="' + id + '"]');
            if (!row) return false;
            var unread = row.classList.contains('unread');
            row.parentNode.removeChild(row);
            updateStats(-1, unread ? -1 : 0);
            // If table body empty, show empty state
            var tbody = document.querySelector('.notifications-table tbody');
            if (tbody && !tbody.querySelector('.notification-row')) {
                tbody.innerHTML = '\n                        <tr>\n                            <td colspan="5" class="empty-state">\n                                <div class="empty-content">\n                                    <div class="empty-icon">📭</div>\n                                    <h3>No notifications found</h3>\n                                    <p>No notifications match your current filter.</p>\n                                </div>\n                            </td>\n                        </tr>';
            }
            return true;
        }

        // Intercept delete link clicks
        document.addEventListener('click', function (e) {
            var el = e.target.closest && e.target.closest('.action-btn.delete');
            if (!el) return;
            e.preventDefault();
            var id = el.getAttribute('data-id');
            if (!id) return;
            if (!confirm('Are you sure you want to delete this notification?')) return;

            // Optimistic UI: remove from DOM
            var removed = removeRowById(id);
            if (!removed) return;

            // Send a background request to the server to delete (non-blocking)
            // Keep the existing query params for server-side compatibility
            var href = el.getAttribute('href');
            if (href) {
                fetch(href, { method: 'GET', credentials: 'same-origin' }).catch(function (err) {
                    // if server fails, we don't re-add the row here to avoid complexity
                    console.error('Delete request failed', err);
                });
            }
        });
    })();
</script>