<?php
// System alert configurations (keeping these hardcoded for now as they seem to be feature toggles/static config)
$systemAlerts = [
    [
        'name' => 'Pickup Reminders',
        'description' => 'Automatically remind customers about scheduled pickups',
        'status' => 'active'
    ],
    [
        'name' => 'Bid Notifications',
        'description' => 'Notify companies about new bidding opportunities',
        'status' => 'active'
    ],
    [
        'name' => 'Payment Alerts',
        'description' => 'Alert users about payment status changes',
        'status' => 'active'
    ],
    [
        'name' => 'System Maintenance',
        'description' => 'Notify all users about scheduled maintenance',
        'status' => 'scheduled'
    ]
];

function getAlertStatusTag($status)
{
    switch ($status) {
        case 'active':
            return '<div class="tag success">Active</div>';
        case 'scheduled':
            return '<div class="tag warning">Scheduled</div>';
        case 'inactive':
            return '<div class="tag secondary">Inactive</div>';
        default:
            return '<div class="tag secondary">' . htmlspecialchars($status) . '</div>';
    }
}
?>

<div>
    <!-- Page Header -->
    <page-header title="Notification Manager" description="Send notifications and manage system alerts">
    </page-header>

    <!-- Main Content Grid -->
    <div style="display: grid; gap: var(--space-6); grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
        <!-- Send Notification Card -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-paper-plane" style="margin-right: var(--space-2);"></i>
                    Send Notification
                </h3>
                <p class="activity-card__description">Send targeted messages to users or system-wide alerts</p>
            </div>
            <div class="activity-card__content">
                <form id="notificationForm" style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <!-- Recipient Selection -->
                    <div>
                        <label
                            style="display: block; font-weight: var(--font-weight-medium); margin-bottom: var(--space-2); font-size: var(--text-sm);">
                            Recipient
                        </label>
                        <select id="recipient" name="recipient" class="form-select" required
                            style="width: 100%; padding: var(--space-3); border: 2px solid var(--neutral-300); border-radius: var(--radius-md);">
                            <option value="">Select recipient group</option>
                            <option value="all">All Users</option>
                            <option value="customers">Customers</option>
                            <option value="companies">Companies</option>
                            <option value="collectors">Collectors</option>
                            <option value="users">Specific Users (Not implemented)</option>
                            <!-- Adjusted logic in Controller supports 'users', 'all' etc -->
                        </select>
                    </div>

                    <!-- Notification Type -->
                    <div>
                        <label
                            style="display: block; font-weight: var(--font-weight-medium); margin-bottom: var(--space-2); font-size: var(--text-sm);">
                            Notification Type
                        </label>
                        <select id="notificationType" name="notificationType" class="form-select" required
                            style="width: 100%; padding: var(--space-3); border: 2px solid var(--neutral-300); border-radius: var(--radius-md);">
                            <option value="">Select notification type</option>
                            <option value="info">Information</option>
                            <option value="alert">Alert</option>
                            <option value="system">System Update</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label
                            style="display: block; font-weight: var(--font-weight-medium); margin-bottom: var(--space-2); font-size: var(--text-sm);">
                            Title
                        </label>
                        <input type="text" id="title" name="title" required placeholder="Enter notification title..."
                            style="width: 100%; padding: var(--space-3); border: 2px solid var(--neutral-300); border-radius: var(--radius-md);">
                    </div>

                    <!-- Message -->
                    <div>
                        <label
                            style="display: block; font-weight: var(--font-weight-medium); margin-bottom: var(--space-2); font-size: var(--text-sm);">
                            Message
                        </label>
                        <textarea id="message" name="message" required placeholder="Enter your notification message..."
                            rows="4"
                            style="width: 100%; padding: var(--space-3); border: 2px solid var(--neutral-300); border-radius: var(--radius-md); resize: vertical; font-family: inherit;"></textarea>
                    </div>

                    <!-- Send Button -->
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Notification
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Notifications Card -->
        <div class="activity-card">
            <div class="activity-card__header">
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-bell" style="margin-right: var(--space-2);"></i>
                    Recent Notifications
                </h3>
                <p class="activity-card__description">Recently sent notifications and their status</p>
                <!-- Optional: Refresh Button -->
                <button onclick="fetchNotifications()" class="btn btn-sm btn-outline" style="margin-top:var(--space-2)">
                    <i class="fa-solid fa-sync"></i> Refresh
                </button>
            </div>
            <div class="activity-card__content">
                <div id="recent-notifications-list" style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <!-- Notifications will be loaded here via JS -->
                    <div style="padding:2rem;text-align:center;color:var(--neutral-500);">Loading notifications...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Alerts Card -->
    <div class="activity-card" style="margin-top: var(--space-8);">
        <div class="activity-card__header">
            <h3 class="activity-card__title">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right: var(--space-2);"></i>
                System Alerts
            </h3>
            <p class="activity-card__description">Configure automatic system alerts and notifications</p>
        </div>
        <div class="activity-card__content">
            <div style="display: grid; gap: var(--space-4); grid-template-columns: repeat(3, 1fr);">
                <?php foreach ($systemAlerts as $alert): ?>
                    <div class="alert-box">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <h4 class="alert-box__title"><?= htmlspecialchars($alert['name']) ?></h4>
                            <!-- <?= getAlertStatusTag($alert['status']) ?> -->
                        </div>
                        <p style="font-size: var(--text-sm); color: var(--neutral-600);">
                            <?= htmlspecialchars($alert['description']) ?>
                        </p>
                        <div style="margin-top: var(--space-2);">
                            <button class="btn btn-sm btn-outline"
                                onclick="toggleAlert('<?= htmlspecialchars($alert['name']) ?>')">
                                Configure
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="/js/admin/notifications.js"></script>
<script>
    // Toggle system alert configuration (kept inline as it's UI-only mock for now)
    function toggleAlert(alertName) {
        showToast(`Opening configuration for "${alertName}"`, 'info');
    }
</script>

<script>
    // Toast utility wrapper for cleaner code
    function showToast(message, type = 'info') {
        if (window.__createToast) {
            window.__createToast(message, type);
        } else {
            console.warn('Toast API not available, falling back to alert');
            alert(message);
        }
    }


    // Generate notification title based on type
    function getNotificationTitle(type) {
        const titles = {
            'info': 'Information',
            'alert': 'Alert',
            'system': 'System Update',
            'maintenance': 'Maintenance Notice'
        };
        return titles[type] || 'Notification';
    }

    // Toggle system alert configuration
    function toggleAlert(alertName) {
        console.log(`Configuring alert: ${alertName}`);
        showToast(`Opening configuration for "${alertName}"`, 'info');

        // Configuration options that would be available:
        // • Set notification frequency
        // • Configure trigger conditions
        // • Customize message templates
        // • Manage recipient groups
        // • Schedule notifications

        // In a real application, this would open a configuration modal:
        /*
        window.location.href = `/admin/notifications/configure/${encodeURIComponent(alertName)}`;
        */
    }

    // Utility functions for future implementation
    function retryFailedNotification(notificationId) {
        console.log(`Retrying notification ${notificationId}`);
        // Implementation for retrying failed notifications
    }

    function viewNotificationDetails(notificationId) {
        console.log(`Viewing details for notification ${notificationId}`);
        // Implementation for viewing detailed notification information
    }

    function exportNotificationLogs() {
        console.log('Exporting notification logs');
        // Implementation for exporting notification history
    }

    // Auto-refresh recent notifications every 30 seconds
    // Can be disabled by setting HOT_RELOADER_PAGE_AUTO_REFRESH=false
    (function () {
        const envVal = (typeof window !== 'undefined' && (window.HOT_RELOADER_PAGE_AUTO_REFRESH !== undefined)) ? window.HOT_RELOADER_PAGE_AUTO_REFRESH : null;
        const serverToggle = <?= (getenv('HOT_RELOADER_PAGE_AUTO_REFRESH') === false || getenv('HOT_RELOADER_PAGE_AUTO_REFRESH') === 'false') ? 'false' : 'true' ?>;
        const enabled = envVal === null ? serverToggle : Boolean(envVal);
        if (!enabled) return;

        setInterval(function () {
            // In a real application, you would fetch updated notifications:
            /*
            fetch('/api/notifications/recent')
                .then(response => response.json())
                .then(data => {
                    updateRecentNotificationsList(data.notifications);
                })
                .catch(error => console.error('Error refreshing notifications:', error));
            */
        }, 30000);
    })();
</script>