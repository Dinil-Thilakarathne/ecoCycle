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
    $isRead = $n['is_read'] ?? ($n['isRead'] ?? (($n['status'] ?? '') === 'read'));
    $priority = $n['priority'] ?? ($n['status'] ?? 'normal');
    $category = $n['category'] ?? ($n['type'] ?? 'general');

    return [
        'id'        => (string)($n['id'] ?? ''),
        'title'     => $n['title'] ?? '',
        'message'   => $n['message'] ?? ($n['data']['message'] ?? ''),
        'timestamp' => $timestamp,
        'isRead'    => (bool)$isRead,
        'priority'  => $priority,
        'category'  => $category,
        'type'      => $n['type'] ?? 'general',
        'status'    => $n['status'] ?? 'unread',
    ];
}, $notifications);

// Calculate stats
$totalNotifications = count($normalized);
$unreadNotifications = count(array_filter($normalized, fn($x) => !$x['isRead']));
$todayNotifications = count(array_filter($normalized, fn($n) =>
    date('Y-m-d', strtotime($n['timestamp'] ?? '1970-01-01')) === date('Y-m-d')
));

function timeAgo($timestamp)
{
    if (!$timestamp) return '';
    $time = time() - strtotime($timestamp);

    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';

    return date('M j, Y', strtotime($timestamp));
}

function getStatusClass($priority, $isRead)
{
    if (!$isRead) return 'status-unread';

    switch ($priority) {
        case 'high': return 'status-high';
        case 'low': return 'status-low';
        default: return 'status-normal';
    }
}

function truncateMessage($message, $length = 80)
{
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

<div class="stats-grid" id="notification-stats"></div>

<div class="action-buttons" style="margin-bottom:2rem;">
    <a href="?filter=unread" class="btn <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">
        Unread (<span id="stat-unread-count">0</span>)
    </a>
    <a href="?filter=pickup" class="btn <?= $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">
        Pickup
    </a>
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
        <tbody id="notifications-tbody"></tbody>
    </table>
</div>
</div>

<?php if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'view'): ?>
<?php
$view = null;
foreach ($normalized as $n) {
    if ($n['id'] === $_GET['id']) {
        $view = $n;
        break;
    }
}
?>
<?php if ($view): ?>
<div class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?= htmlspecialchars($view['title']) ?></h2>
            <a href="?filter=<?= $filter ?>" class="modal-close">×</a>
        </div>
        <div class="modal-body">
            <p><?= htmlspecialchars($view['message']) ?></p>
            <div class="detail-timestamp">
                <strong>Received:</strong>
                <?= date('F j, Y \a\t g:i A', strtotime($view['timestamp'])) ?>
            </div>
        </div>
        <div class="modal-footer">
            <?php if (!$view['isRead']): ?>
                <a href="?action=mark_read&id=<?= $view['id'] ?>&filter=<?= $filter ?>" class="btn-primary">
                    Mark as Read
                </a>
            <?php endif; ?>
            <a href="?filter=<?= $filter ?>" class="btn-secondary">Close</a>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
(function () {
    const endpoint = '/api/collector/notifications';
    const statsContainer = document.getElementById('notification-stats');
    const tbody = document.getElementById('notifications-tbody');
    const unreadCountEl = document.getElementById('stat-unread-count');

    function timeAgo(ts) {
        const diff = Math.floor((Date.now() - new Date(ts)) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        return Math.floor(diff / 86400) + ' days ago';
    }

    function renderStats(data) {
        const unread = data.filter(n => n.status !== 'read').length;
        unreadCountEl.textContent = unread;
    }

    function renderNotifications(data) {
        tbody.innerHTML = '';
        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="5">No notifications found</td></tr>`;
            return;
        }

        data.forEach(n => {
            tbody.innerHTML += `
                <tr class="notification-row ${n.status !== 'read' ? 'unread' : ''}">
                    <td>
                        <div class="notification-title">${n.title}</div>
                        <div>${(n.message || '').slice(0, 80)}</div>
                    </td>
                    <td>${n.type}</td>
                    <td>${timeAgo(n.created_at)}</td>
                    <td>${n.status}</td>
                    <td>
                        ${n.status !== 'read'
                            ? `<a href="?action=mark_read&id=${n.id}">Mark Read</a>`
                            : ''}
                        <a href="?action=view&id=${n.id}">View</a>
                    </td>
                </tr>
            `;
        });
    }

    async function fetchNotifications() {
        const res = await fetch(endpoint, { credentials: 'same-origin' });
        const json = await res.json();
        if (json.status === 'success') {
            renderStats(json.data);
            renderNotifications(json.data);
        }
    }

    fetchNotifications();
    setInterval(fetchNotifications, 10000);
})();
</script>
