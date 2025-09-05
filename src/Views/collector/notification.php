<?php function getStatusTag($status)
{
    switch ($status) {
        case 'completed':
            return '<div class="tag completed">Completed</div>';
        case 'pending':
            return '<div class="tag pending">Pending</div>';
        case 'failed':
            return '<div class="tag danger">Failed</div>';
        default:
            return '<div class="tag secondary">' . htmlspecialchars($status) . '</div>';
    }
}
?>

<h2>Notifications <span style="color:#777; font-size:14px;">3</span></h2>
<p class="subtitle">Stay updated with your tasks and alerts</p>

<div class="header-right">
    <button class="mark-all">Mark all read</button>
</div>

<!-- Tabs -->
<div class="tabs">
    <div class="tab active">All (5)</div>
    <div class="tab">Unread (3)</div>
    <div class="tab">Urgent (1)</div>
</div>

<!-- Notification List -->
<!--<div class="notification-card urgent">
    <div class="notification-text">
        <div class="notification-title">
            Route Change Alert <span>urgent</span>
        </div>
        <div class="notification-message">Your pickup route for today has been updated. Check task WP003 for new location details.</div>
        <div class="notification-time">5 minutes ago</div>
    </div>
    <div class="notification-actions">
        <div class="mark-read">Mark read</div>
        <button class="delete-btn">🗑</button>
    </div>
</div>

<div class="notification-card info">
    <div class="notification-text">
        <div class="notification-title">
            New Task Assigned <span>info</span>
        </div>
        <div class="notification-message">A new pickup task has been assigned to you for tomorrow at 2:00 PM.</div>
        <div class="notification-time">1 hour ago</div>
    </div>
    <div class="notification-actions">
        <div class="mark-read">Mark read</div>
        <button class="delete-btn">🗑</button>
    </div>
</div>

<div class="notification-card success">
    <div class="notification-text">
        <div class="notification-title">
            Task Completed <span>success</span>
        </div>
        <div class="notification-message">Great job! You've successfully completed pickup WP001. Customer rating: 5 stars.</div>
        <div class="notification-time">2 hours ago</div>
    </div>
    <div class="notification-actions">
        <button class="delete-btn">🗑</button>
    </div>
</div>

<div class="notification-card warning">
    <div class="notification-text">
        <div class="notification-title">
            Weather Alert <span>warning</span>
        </div>
        <div class="notification-message">Heavy rain expected in your area. Consider rescheduling outdoor pickups.</div>
        <div class="notification-time">3 hours ago</div>
    </div>
    <div class="notification-actions">
        <div class="mark-read">Mark read</div>
        <button class="delete-btn">🗑</button>
    </div>
</div>

<div class="notification-card info">
    <div class="notification-text">
        <div class="notification-title">
            System Maintenance <span>info</span>
        </div>
        <div class="notification-message">Scheduled maintenance tonight from 11 PM to 1 AM. App may be temporarily unavailable.</div>
        <div class="notification-time">1 day ago</div>
    </div>
    <div class="notification-actions">
        <button class="delete-btn">🗑</button>
    </div>
</div>-->

<?php
// Recent notifications data (in a real application, this would come from your database/models)
$recentNotifications = [
    [
        'id' => 'NOT001',
        'type' => 'system',
        'title' => 'System Maintenance Scheduled',
        'message' => 'Scheduled maintenance on Jan 20, 2024 from 2:00 AM to 4:00 AM',
        'timestamp' => '2024-01-15 10:30:00',
        'status' => 'sent',
        'recipients' => 'All Users'
    ],
    [
        'id' => 'NOT002',
        'type' => 'alert',
        'title' => 'High Bid Activity',
        'message' => 'Unusual bidding activity detected for Lot #1234',
        'timestamp' => '2024-01-15 09:15:00',
        'status' => 'sent',
        'recipients' => 'Administrators'
    ],
    [
        'id' => 'NOT003',
        'type' => 'info',
        'title' => 'New Company Registration',
        'message' => 'EcoRecycle Ltd. has registered and is pending approval',
        'timestamp' => '2024-01-15 08:45:00',
        'status' => 'sent',
        'recipients' => 'Administrators'
    ],
    [
        'id' => 'NOT004',
        'type' => 'maintenance',
        'title' => 'Vehicle Maintenance Due',
        'message' => 'Vehicle ABC-1234 is due for scheduled maintenance',
        'timestamp' => '2024-01-15 08:00:00',
        'status' => 'pending',
        'recipients' => 'Fleet Managers'
    ],
    [
        'id' => 'NOT005',
        'type' => 'alert',
        'title' => 'Payment Failed',
        'message' => 'Payment processing failed for invoice #INV-2024-001',
        'timestamp' => '2024-01-15 07:30:00',
        'status' => 'failed',
        'recipients' => 'Finance Team'
    ]
];
?>
<!-- Recent Notifications Card -->
<div class="activity-card">
    <div class="activity-card__header">
        <h3 class="activity-card__title">
            <i class="fa-solid fa-bell" style="margin-right: var(--space-2);"></i>
            Recent Notifications
        </h3>
        <p class="activity-card__description">Recently sent notifications and their status</p>
    </div>
    <div class="activity-card__content">
        <div style="display: flex; flex-direction: column; gap: var(--space-4);">
            <?php foreach ($recentNotifications as $notification): ?>
                <div
                    style="border: 1px solid var(--neutral-200); border-radius: var(--radius-md); padding: var(--space-4);">
                    <!-- Header with title and status -->
                    <div
                        style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: var(--space-2);">
                        <h4 class="font-medium" style="margin: 0;">
                            <?= htmlspecialchars($notification['title']) ?>
                        </h4>
                        <?= getStatusTag($notification['status']) ?>
                    </div>

                    <!-- Message -->
                    <p style="font-size: var(--text-sm); color: var(--neutral-600); margin-bottom: var(--space-2);">
                        <?= htmlspecialchars($notification['message']) ?>
                    </p>

                    <!-- Footer with recipient and timestamp -->
                    <div
                        style="display: flex; align-items: center; justify-content: space-between; font-size: var(--text-xs); color: var(--neutral-500);">
                        <span>To: <?= htmlspecialchars($notification['recipients']) ?></span>
                        <span><?= htmlspecialchars($notification['timestamp']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>