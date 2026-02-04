<?php
// Variables from controller:
// $notifications, $filter, $action, $notificationId, $authUser

$notifications = is_array($notifications ?? null) ? $notifications : [];
$filter = $filter ?? 'all';
$action = $action ?? null;
$notificationId = $notificationId ?? null;

// Normalize notifications for UI
$normalized = array_map(function ($n) {
    $timestamp = $n['timestamp'] ?? ($n['created_at'] ?? null);
    $isRead = ($n['status'] ?? '') === 'read';

    return [
        'id'        => (string)($n['id'] ?? ''),
        'title'     => $n['title'] ?? '',
        'message'   => $n['message'] ?? '',
        'timestamp' => $timestamp,
        'type'      => $n['type'] ?? 'general',
        'status'    => $n['status'] ?? 'unread',
        'isRead'    => $isRead,
    ];
}, $notifications);

function timeAgo($ts)
{
    if (!$ts) return '';
    $diff = time() - strtotime($ts);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    return date('M d, Y', strtotime($ts));
}
?>

<div class="dashboard-page">

<style>
.notification-row.unread { background: #f0fff4; }
.notification-title { font-weight: 600; }
.notification-row.unread .notification-title { font-weight: 800; }
.notifications-table tr:hover { background: #f7f7f7; }
.badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; }
.badge.unread { background: #16a34a; color: #fff; }
.badge.read { background: #9ca3af; color: #fff; }
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.6);
    display: flex; align-items: center; justify-content: center;
}
.modal-content {
    background: #fff; max-width: 500px; width: 90%;
    border-radius: 8px; padding: 20px;
}
.modal-header { display:flex; justify-content:space-between; }
.modal-close { font-size: 22px; text-decoration:none; }
</style>

<h2>Notifications</h2>

<div style="margin-bottom:1.5rem;">
    <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
    <a href="?filter=unread" class="btn <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline' ?>">
        Unread (<span id="stat-unread-count">0</span>)
    </a>
    <a href="?action=mark_all_read" class="btn btn-outline">Mark All Read</a>
</div>

<div class="table-container">
<table class="notifications-table data-table" style="width:100%;">
    <thead>
        <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="notifications-tbody">
        <?php if (!$normalized): ?>
            <tr><td colspan="5">No notifications</td></tr>
        <?php endif; ?>

        <?php foreach ($normalized as $n): ?>
        <tr class="notification-row <?= !$n['isRead'] ? 'unread' : '' ?>">
            <td>
                <div class="notification-title"><?= htmlspecialchars($n['title']) ?></div>
                <div><?= htmlspecialchars(mb_strimwidth($n['message'], 0, 80, '...')) ?></div>
            </td>
            <td><?= htmlspecialchars($n['type']) ?></td>
            <td><?= timeAgo($n['timestamp']) ?></td>
            <td>
                <span class="badge <?= $n['isRead'] ? 'read' : 'unread' ?>">
                    <?= $n['isRead'] ? 'Read' : 'Unread' ?>
                </span>
            </td>
            <td>
                <?php if (!$n['isRead']): ?>
                    <a href="?action=mark_read&id=<?= $n['id'] ?>&filter=<?= $filter ?>">Mark read</a> |
                <?php endif; ?>
                <a href="?action=view&id=<?= $n['id'] ?>&filter=<?= $filter ?>">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php
// Modal view
if ($action === 'view' && $notificationId):
    foreach ($normalized as $n) {
        if ($n['id'] === $notificationId) { $view = $n; break; }
    }
endif;
?>

<?php if (!empty($view)): ?>
<div class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?= htmlspecialchars($view['title']) ?></h3>
            <a href="?filter=<?= $filter ?>" class="modal-close">×</a>
        </div>
        <p><?= nl2br(htmlspecialchars($view['message'])) ?></p>
        <small>Received: <?= date('F j, Y g:i A', strtotime($view['timestamp'])) ?></small>
        <div style="margin-top:1rem;">
            <?php if (!$view['isRead']): ?>
                <a class="btn-primary" href="?action=mark_read&id=<?= $view['id'] ?>&filter=<?= $filter ?>">
                    Mark as Read
                </a>
            <?php endif; ?>
            <a class="btn-secondary" href="?filter=<?= $filter ?>">Close</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const endpoint = '/api/collector/notifications';
    const unreadEl = document.getElementById('stat-unread-count');

    async function refreshUnread() {
        const res = await fetch(endpoint, { credentials: 'same-origin' });
        const json = await res.json();
        if (json.status === 'success') {
            const unread = json.data.filter(n => n.status !== 'read').length;
            unreadEl.textContent = unread;
        }
    }

    refreshUnread();
    setInterval(refreshUnread, 10000);
})();
</script>
