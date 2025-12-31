<?php
// Variables passed from controller:
// - $notifications: array of notification rows (may be empty)
// - $filter, $action, $notificationId, $authUser

$notifications = is_array($notifications ?? null) ? $notifications : [];
$filter = $filter ?? 'all';
$action = $action ?? null;
$notificationId = $notificationId ?? null;

// Normalize notifications to a uniform shape used by the view
$normalized = array_map(function ($n) {
    $timestamp = $n['timestamp'] ?? ($n['sent_at'] ?? $n['created_at'] ?? null);
    $isRead = $n['is_read'] ?? ($n['isRead'] ?? false);
    $priority = $n['priority'] ?? ($n['status'] ?? 'normal');
    $category = $n['category'] ?? ($n['type'] ?? 'general');
    return [
        'id' => (string) ($n['id'] ?? ''),
        'title' => $n['title'] ?? '',
        'message' => $n['message'] ?? ($n['data']['message'] ?? ''),
        'timestamp' => $timestamp,
        'isRead' => (bool) $isRead,
        'priority' => $priority,
        'category' => $category,
    ];
}, $notifications);

// Calculate stats
$totalNotifications = count($normalized);
$unreadNotifications = count(array_filter($normalized, fn($x) => !$x['isRead'])) ;
$todayNotifications = count(array_filter($normalized, fn($n) => date('Y-m-d', strtotime($n['timestamp'] ?? '1970-01-01')) === date('Y-m-d')));

function timeAgo($timestamp) {
    if (!$timestamp) return '';
    $time = time() - strtotime($timestamp);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($timestamp));
}

function getStatusClass($priority, $isRead) {
    if (!$isRead) return 'status-unread';
    switch($priority) {
        case 'high': return 'status-high';
        case 'normal': return 'status-normal';
        case 'low': return 'status-low';
        default: return 'status-normal';
    }
}

function truncateMessage($message, $length = 80) {
    if (strlen($message) <= $length) return $message;
    return substr($message, 0, $length) . '...';
}
?>

<div class="dashboard-page">
    <style>
        .notification-row.unread .notification-title { font-weight: 700; }
        .notification-row.unread { background: #f0fff4; }
        .notifications-table .notification-row:hover { background: #f5f5f5; }
    </style>

    <div class="header"></div>

    <div class="stats-grid">
        <?php $stats = [
            ['title' => 'Total Notifications','value' => $totalNotifications,'icon' => 'fa-solid fa-bell','subtitle' => 'All time'],
            ['title' => 'Unread','value' => $unreadNotifications,'icon' => 'fa-solid fa-envelope-open','subtitle'=>'Need attention'],
            ['title' => 'Today','value' => $todayNotifications,'icon' => 'fa-solid fa-calendar-day','subtitle'=>'Received today'],
        ];
        foreach ($stats as $stat): ?>
            <div class="feature-card">
                <div class="feature-card__header">
                    <h3 class="feature-card__title"><?= htmlspecialchars($stat['title']) ?></h3>
                    <div class="feature-card__icon"><i class="<?= htmlspecialchars($stat['icon']) ?>"></i></div>
                </div>
                <p class="feature-card__body"><?= htmlspecialchars($stat['value']) ?></p>
                <div class="feature-card__footer"><span class="tag success"><?= htmlspecialchars($stat['subtitle']) ?></span></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="action-buttons" style="margin-bottom:2rem;">
        <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">Unread (<?php echo $unreadNotifications; ?>)</a>
        <a href="?filter=pickup" class="btn <?php echo $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">Pickup</a>
        <a href="?filter=payment" class="btn <?php echo $filter === 'payment' ? 'btn-primary' : 'btn-outline'; ?>">Payment</a>
        <a href="?action=mark_all_read" class="btn btn-outline">Mark All Read</a>
    </div>

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
                <?php if (empty($normalized)): ?>
                    <tr><td colspan="5" class="empty-state"><div class="empty-content"><div class="empty-icon">📭</div><h3>No notifications found</h3><p>No notifications match your current filter.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($normalized as $notification): ?>
                        <tr class="notification-row <?php echo !$notification['isRead'] ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                            <td class="notification-info">
                                <div class="notification-details">
                                    <?php if (!$notification['isRead']): ?><span class="unread-dot" aria-hidden="true"></span><?php endif; ?>
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="notification-message"><?php echo htmlspecialchars(truncateMessage($notification['message'])); ?></div>
                                </div>
                            </td>
                            <td><span class="type-badge <?php echo $notification['category']; ?>"><?php echo ucfirst($notification['category']); ?></span></td>
                            <td class="time-cell"><?php echo timeAgo($notification['timestamp']); ?></td>
                            <td><span class="status-badge <?php echo getStatusClass($notification['priority'], $notification['isRead']); ?>"><?php echo !$notification['isRead'] ? 'Unread' : 'Read'; ?></span></td>
                            <td class="actions-cell">
                                <?php if (!$notification['isRead']): ?>
                                    <a href="?action=mark_read&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>" class="action-btn mark-read">Mark Read</a>
                                <?php endif; ?>
                                <a href="?action=view&id=<?php echo $notification['id']; ?>&filter=<?php echo $filter; ?>" class="action-btn view">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])): ?>
    <?php $id = $_GET['id']; $view = null; foreach ($normalized as $n) { if ($n['id'] === $id) { $view = $n; break; }} ?>
    <?php if ($view): ?>
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header"><h2><?php echo htmlspecialchars($view['title']); ?></h2><a href="?filter=<?php echo $filter; ?>" class="modal-close">×</a></div>
                <div class="modal-body"><p><?php echo htmlspecialchars($view['message']); ?></p><div class="detail-timestamp"><strong>Received:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($view['timestamp'])); ?></div></div>
                <div class="modal-footer">
                    <?php if (!$view['isRead']): ?><a href="?action=mark_read&id=<?php echo $view['id']; ?>&filter=<?php echo $filter; ?>" class="btn-primary">Mark as Read</a><?php endif; ?>
                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>