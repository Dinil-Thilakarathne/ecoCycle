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

// System alert configurations
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

// Helper functions
function getStatusTag($status)
{
    switch ($status) {
        case 'sent':
            return '<div class="tag success">Sent</div>';
        case 'pending':
            return '<div class="tag warning">Pending</div>';
        case 'failed':
            return '<div class="tag danger">Failed</div>';
        default:
            return '<div class="tag secondary">' . htmlspecialchars($status) . '</div>';
    }
}

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
                            <option value="admins">Administrators</option>
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
            </div>
            <div class="activity-card__content">
                <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <?php foreach ($recentNotifications as $notification): ?>
                        <?php
                        // Map notification type/status to alert-box type
                        $type = $notification['type'] ?? 'info';
                        $status = $notification['status'] ?? '';
                        if ($status === 'failed') {
                            $alertType = 'danger';
                        } else {
                            switch ($type) {
                                case 'alert':
                                    $alertType = 'danger';
                                    break;
                                case 'maintenance':
                                    $alertType = 'warning';
                                    break;
                                case 'info':
                                case 'system':
                                default:
                                    $alertType = 'info';
                            }
                        }

                        $statusClass = ($status === 'sent') ? 'success' : (($status === 'pending') ? 'warning' : (($status === 'failed') ? 'danger' : 'secondary'));
                        ?>

                        <alert-box type="<?= $alertType ?>" title="<?= htmlspecialchars($notification['title']) ?>"
                            dismissible>
                            <p style="margin:0; color: var(--neutral-700); font-size: var(--text-sm);">
                                <?= htmlspecialchars($notification['message']) ?>
                            </p>

                            <div style="margin-top: var(--space-2); font-size: var(--text-xs); color: var(--neutral-500);">
                                <span>To: <?= htmlspecialchars($notification['recipients']) ?></span>
                                &nbsp;&middot;&nbsp;
                                <span><?= htmlspecialchars($notification['timestamp']) ?></span>
                            </div>

                            <div class="tag <?= $statusClass ?> alert-action">
                                <?= ($status === 'sent') ? 'Sent' : (($status === 'pending') ? 'Pending' : (($status === 'failed') ? 'Failed' : htmlspecialchars($status))) ?>
                            </div>
                        </alert-box>
                    <?php endforeach; ?>
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
                    <div
                        style="display: flex; flex-direction: column; gap: var(--space-2); border: 1px solid var(--neutral-200); border-radius: var(--radius-lg); padding: var(--space-4);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <h4 class="font-medium"><?= htmlspecialchars($alert['name']) ?></h4>
                            <?= getAlertStatusTag($alert['status']) ?>
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

<script>
    // Handle notification form submission
    document.getElementById('notificationForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const recipient = document.getElementById('recipient').value;
        const notificationType = document.getElementById('notificationType').value;
        const message = document.getElementById('message').value;

        if (!recipient || !notificationType || !message.trim()) {
            alert('Please fill in all required fields');
            return;
        }

        // In a real application, this would send the data to your backend
        console.log('Sending notification:', {
            recipient: recipient,
            type: notificationType,
            message: message
        });

        // Show success message
        alert(`Notification sent successfully to ${recipient}!`);

        // Reset form
        document.getElementById('recipient').value = '';
        document.getElementById('notificationType').value = '';
        document.getElementById('message').value = '';

        // In a real application, you would make an API call:
        /*
        fetch('/api/notifications/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                recipient: recipient,
                type: notificationType,
                message: message,
                title: getNotificationTitle(notificationType)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Notification sent successfully!');
                // Refresh the page or update the recent notifications list
                location.reload();
            } else {
                alert('Failed to send notification: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to send notification. Please try again.');
        });
        */
    });

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
        alert(`Configuration panel for "${alertName}" would open here. This would allow you to:
        
• Set notification frequency
• Configure trigger conditions
• Customize message templates
• Manage recipient groups
• Schedule notifications`);

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
</script>