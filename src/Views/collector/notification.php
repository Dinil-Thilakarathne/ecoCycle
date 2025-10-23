<?php
// Get parameters
$collectorId = $_GET['collector'] ?? 'COL001';
$action = $_GET['action'] ?? null;
$notificationId = $_GET['id'] ?? null;
$msg = $_GET['msg'] ?? null; // message parameter

// Example notifications
$notifications = [
    [
        'id' => 'N001',
        'collector_id' => 'COL001',
        'title' => 'Pickup Request Confirmed',
        'message' => 'Your pickup request PR001 has been confirmed.',
        'timestamp' => '2024-01-10',
        'isRead' => false,
        'isArchived' => false,
        'priority' => 'high',
    ],
    [
        'id' => 'N002',
        'collector_id' => 'COL001',
        'title' => 'Pickup Completed',
        'message' => 'Your pickup PR002 has been completed successfully.',
        'timestamp' => '2024-01-08',
        'isRead' => false,
        'isArchived' => false,
        'priority' => 'normal',
    ],
    [
        'id' => 'N003',
        'collector_id' => 'COL001',
        'title' => 'Payment Processed',
        'message' => 'Your payment of Rs 127.50 has been processed.',
        'timestamp' => '2024-01-07',
        'isRead' => true,
        'isArchived' => false,
        'priority' => 'normal',
    ],
    [
        'id' => 'N004',
        'collector_id' => 'COL001',
        'title' => 'Pickup Reminder',
        'message' => 'Reminder: Your scheduled pickup is tomorrow.',
        'timestamp' => '2024-01-14',
        'isRead' => true,
        'isArchived' => false,
        'priority' => 'low',
    ],
    [
        'id' => 'N005',
        'collector_id' => 'COL001',
        'title' => 'Pickup Cancelled',
        'message' => 'Your pickup request PR005 has been cancelled.',
        'timestamp' => '2024-01-03',
        'isRead' => true,
        'isArchived' => true,
        'priority' => 'high',
    ],
];

// Handle actions
if ($action === 'mark_read' && $notificationId) {
    foreach ($notifications as &$notification) {
        if ($notification['id'] === $notificationId) {
            $notification['isRead'] = true;
            break;
        }
    }
    header("Location: notifications-page.php?collector=$collectorId&msg=Notification+marked+as+read");
    exit;
}

if ($action === 'mark_all_read') {
    foreach ($notifications as &$notification) {
        $notification['isRead'] = true;
    }
    header("Location: notifications-page.php?collector=$collectorId&msg=All+notifications+marked+as+read");
    exit;
}

if ($action === 'delete' && $notificationId) {
    $notifications = array_filter($notifications, fn($n) => $n['id'] !== $notificationId);
    header("Location: notifications-page.php?collector=$collectorId&msg=Notification+deleted");
    exit;
}

// Filter only this collector's notifications
$collectorNotifications = array_filter($notifications, fn($n) => $n['collector_id'] === $collectorId);

// Categorize
$total = $collectorNotifications;
$unread = array_filter($collectorNotifications, fn($n) => !$n['isRead'] && !$n['isArchived']);
$read = array_filter($collectorNotifications, fn($n) => $n['isRead'] && !$n['isArchived']);
$archived = array_filter($collectorNotifications, fn($n) => $n['isArchived']);

// Functions
function renderNotifications($list, $filter = 'all')
{
    if (empty($list)) {
        echo "<tr><td colspan='5' style='text-align:center;'>No notifications found</td></tr>";
        return;
    }

    foreach ($list as $n) {
        echo "<tr>
            <td><strong>{$n['title']}</strong><br><small>{$n['message']}</small></td>
            <td>" . date('M j, Y', strtotime($n['timestamp'])) . "</td>
            <td>" . ($n['isRead'] ? 'Read' : 'Unread') . "</td>
            <td>";

        if (!$n['isRead']) {
            echo "<a href='?action=mark_read&id={$n['id']}&collector={$n['collector_id']}&filter={$filter}' 
                    class='btn btn-sm btn-info'>Mark Read</a> ";
        }

        echo "<a href='?action=view&id={$n['id']}&collector={$n['collector_id']}&filter={$filter}' 
                class='btn btn-sm btn-secondary'>View</a> ";

        echo "<a href='?action=delete&id={$n['id']}&collector={$n['collector_id']}&filter={$filter}' 
                class='btn btn-sm btn-danger' 
                onclick=\"return confirm('Are you sure you want to delete this notification?')\">Delete</a>";

        echo "</td></tr>";
    }
}
?>

<div>
    <div class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Notifications</h2>
            <p class="page-header__description">View and manage your notifications</p>
        </div>
    </div>


    <?php if ($msg): ?>
        <div class="alert alert-success"
            style="margin: 15px 0; padding: 10px; border: 1px solid green; background: #e6ffe6;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tabs-list">
            <button class="tabs-trigger active" onclick="showTab('total')" id="total-tab">Total
                (<?= count($total) ?>)</button>
            <button class="tabs-trigger" onclick="showTab('unread')" id="unread-tab">Unread
                (<?= count($unread) ?>)</button>
            <button class="tabs-trigger" onclick="showTab('read')" id="read-tab">Read (<?= count($read) ?>)</button>
            <button class="tabs-trigger" onclick="showTab('archived')" id="archived-tab">Archived
                (<?= count($archived) ?>)</button>
        </div>

        <div class="tabs-content active" id="total-content">
            <table class="table">
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php renderNotifications($total); ?></tbody>
            </table>
        </div>

        <div class="tabs-content" id="unread-content">
            <table class="table">
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php renderNotifications($unread); ?></tbody>
            </table>
        </div>

        <div class="tabs-content" id="read-content">
            <table class="table">
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php renderNotifications($read); ?></tbody>
            </table>
        </div>

        <div class="tabs-content" id="archived-content">
            <table class="table">
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php renderNotifications($archived); ?></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function showTab(tab) {
        document.querySelectorAll('.tabs-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tabs-trigger').forEach(b => b.classList.remove('active'));
        document.getElementById(tab + '-content').classList.add('active');
        document.getElementById(tab + '-tab').classList.add('active');
    }
</script>