<?php

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
                            maxlength="500" rows="4"
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
                <div>
                    <h3 class="activity-card__title">
                        <i class="fa-solid fa-bell" style="margin-right: var(--space-2);"></i>
                        Recent Notifications
                    </h3>
                    <p class="activity-card__description">Recently sent notifications and their status</p>
                </div>
                <!-- Optional: Refresh Button -->
                <button onclick="fetchNotifications()" class="btn btn-sm btn-outline" style="margin-top:var(--space-2)">
                    <i class="fa-solid fa-sync"></i> Refresh
                </button>
            </div>
            <div class="activity-card__content">
                <div id="recent-notifications-list" style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <?php if (empty($recentNotifications)): ?>
                        <div style="padding:2rem;text-align:center;color:var(--neutral-500);">No recent notifications</div>
                    <?php else: ?>
                        <?php foreach ($recentNotifications as $notification): ?>
                            <div class="notification-item"
                                style="padding: var(--space-3); border-bottom: 1px solid var(--neutral-200);">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="font-weight: var(--font-weight-medium); margin-bottom: var(--space-1);">
                                            <?= htmlspecialchars($notification['title']) ?>
                                        </h4>
                                        <p
                                            style="font-size: var(--text-sm); color: var(--neutral-600); margin-bottom: var(--space-2);">
                                            <?= htmlspecialchars(substr($notification['message'], 0, 100)) . (strlen($notification['message']) > 100 ? '...' : '') ?>
                                        </p>
                                        <div style="font-size: var(--text-xs); color: var(--neutral-500);">
                                            <?= htmlspecialchars($notification['timestamp']) ?> • Via
                                            <?= htmlspecialchars($notification['type']) ?>
                                        </div>
                                    </div>
                                    <?= getAlertStatusTag($notification['status']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- All Notifications Table Section -->
    <div class="activity-card" style="margin-top: var(--space-8);">
        <div class="activity-card__header">
            <div>
                <h3 class="activity-card__title">
                    <i class="fa-solid fa-list" style="margin-right: var(--space-2);"></i>
                    All Notifications
                </h3>
                <p class="activity-card__description">Manage and view all system notifications</p>
            </div>
        </div>

        <div class="activity-card__content">
            <!-- Filter Bar -->
            <form method="GET" action=""
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-6); background: var(--neutral-50); padding: var(--space-4); border-radius: var(--radius-md);">

                <!-- Search -->
                <div>
                    <label class="form-label" style="font-size: var(--text-xs);">Search</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                        placeholder="Search title or message..." class="form-input"
                        style="width: 100%; padding: var(--space-2); border: 1px solid var(--neutral-300); border-radius: var(--radius-sm);">
                </div>

                <!-- Type -->
                <div>
                    <label class="form-label" style="font-size: var(--text-xs);">Type</label>
                    <select name="type" class="form-select"
                        style="width: 100%; padding: var(--space-2); border: 1px solid var(--neutral-300); border-radius: var(--radius-sm);">
                        <option value="">All Types</option>
                        <option value="info" <?= ($filters['type'] ?? '') === 'info' ? 'selected' : '' ?>>Information
                        </option>
                        <option value="alert" <?= ($filters['type'] ?? '') === 'alert' ? 'selected' : '' ?>>Alert</option>
                        <option value="system" <?= ($filters['type'] ?? '') === 'system' ? 'selected' : '' ?>>System Update
                        </option>
                        <option value="maintenance" <?= ($filters['type'] ?? '') === 'maintenance' ? 'selected' : '' ?>>
                            Maintenance</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="form-label" style="font-size: var(--text-xs);">Status</label>
                    <select name="status" class="form-select"
                        style="width: 100%; padding: var(--space-2); border: 1px solid var(--neutral-300); border-radius: var(--radius-sm);">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending
                        </option>
                        <option value="sent" <?= ($filters['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed
                        </option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="form-label" style="font-size: var(--text-xs);">Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                        class="form-input"
                        style="width: 100%; padding: var(--space-2); border: 1px solid var(--neutral-300); border-radius: var(--radius-sm);">
                </div>

                <div style="display: flex; align-items: flex-end; gap: var(--space-2);">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="/admin/notifications" class="btn btn-outline" style="text-decoration: none;">
                        Reset
                    </a>
                </div>
            </form>

            <!-- Data Table -->
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--neutral-200); text-align: left;">
                            <th style="padding: var(--space-3); font-size: var(--text-sm); color: var(--neutral-600);">
                                Title</th>
                            <th style="padding: var(--space-3); font-size: var(--text-sm); color: var(--neutral-600);">
                                Recipient</th>
                            <th style="padding: var(--space-3); font-size: var(--text-sm); color: var(--neutral-600);">
                                Sent At</th>
                            <th style="padding: var(--space-3); font-size: var(--text-sm); color: var(--neutral-600);">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allNotifications)): ?>
                            <tr>
                                <td colspan="4"
                                    style="padding: var(--space-8); text-align: center; color: var(--neutral-500);">
                                    No notifications found matching your filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allNotifications as $notif): ?>
                                <tr style="border-bottom: 1px solid var(--neutral-100); transition: background 0.2s;"
                                    onmouseover="this.style.background='var(--neutral-50)'"
                                    onmouseout="this.style.background='transparent'">
                                    <td style="padding: var(--space-3); font-weight: var(--font-weight-medium);">
                                        <?php
                                        $icon = 'fa-info-circle';
                                        $color = 'var(--primary-500)';
                                        switch ($notif['type']) {
                                            case 'alert':
                                                $icon = 'fa-exclamation-triangle';
                                                $color = 'var(--error-500)';
                                                break;
                                            case 'system':
                                                $icon = 'fa-cog';
                                                $color = 'var(--neutral-600)';
                                                break;
                                            case 'maintenance':
                                                $icon = 'fa-tools';
                                                $color = 'var(--warning-500)';
                                                break;
                                        }
                                        ?>
                                        <div style="display: flex; align-items: center; gap: var(--space-2);">
                                            <i class="fa-solid <?= $icon ?>" style="color: <?= $color ?>;"
                                                title="<?= htmlspecialchars($notif['type']) ?>"></i>
                                            <?= htmlspecialchars($notif['title']) ?>
                                        </div>
                                    </td>
                                    <td style="padding: var(--space-3); font-size: var(--text-sm);">
                                        <?php
                                        if (!empty($notif['recipients'])) {
                                            $count = count($notif['recipients']);
                                            echo $count > 1 ? $count . ' Recipients' : htmlspecialchars($notif['recipients'][0] ?? 'N/A');
                                        } else {
                                            echo 'All Users';
                                        }
                                        ?>
                                    </td>
                                    <td style="padding: var(--space-3); font-size: var(--text-sm); color: var(--neutral-600);">
                                        <?= htmlspecialchars($notif['timestamp'] ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: var(--space-3);">
                                        <button class="icon-button"
                                            onclick='viewNotificationDetails(<?= json_encode($notif) ?>)'>
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: var(--space-6); gap: var(--space-2);">
                    <?php if ($pagination['page'] > 1): ?>
                        <a href="?page=<?= $pagination['page'] - 1 ?>&<?= http_build_query($filters) ?>"
                            class="btn btn-sm btn-outline">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <span style="display: flex; align-items: center; padding: 0 var(--space-3); font-size: var(--text-sm);">
                        Page <?= $pagination['page'] ?> of <?= $pagination['last_page'] ?>
                    </span>

                    <?php if ($pagination['page'] < $pagination['last_page']): ?>
                        <a href="?page=<?= $pagination['page'] + 1 ?>&<?= http_build_query($filters) ?>"
                            class="btn btn-sm btn-outline">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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

    // Modal Functions
    function viewNotificationDetails(notification) {
        let recipientsText = 'All Users';
        if (notification.recipients && notification.recipients.length > 0) {
            recipientsText = notification.recipients.join(', ');
        }

        // Build content HTML safely
        const content = document.createElement('div');
        content.style.display = 'grid';
        content.style.gap = '1rem';

        content.innerHTML = `
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #6b7280; font-weight: bold;">Type</span>
                <div style="margin-top: 0.25rem; font-weight: 500;">${notification.type.charAt(0).toUpperCase() + notification.type.slice(1)}</div>
            </div>
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #6b7280; font-weight: bold;">Status</span>
                <div style="margin-top: 0.25rem;">
                     <span class="tag ${notification.status === 'active' || notification.status === 'sent' || notification.status === 'read' ? 'success' : 'secondary'}">
                        ${notification.status.charAt(0).toUpperCase() + notification.status.slice(1)}
                     </span>
                </div>
            </div>
             <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #6b7280; font-weight: bold;">Time</span>
                <div style="margin-top: 0.25rem;">${notification.timestamp || 'N/A'}</div>
            </div>
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #6b7280; font-weight: bold;">Message</span>
                <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem; margin-top: 0.25rem; white-space: pre-wrap;">${notification.message}</div>
            </div>
             <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #6b7280; font-weight: bold;">Recipients</span>
                <div style="margin-top: 0.25rem; font-size: 0.875rem; color: #374151;">${recipientsText}</div>
            </div>
        `;

        if (window.Modal) {
            window.Modal.open({
                title: notification.title,
                content: content,
                actions: [{ label: 'Close', variant: 'outline', dismiss: true }]
            });
        } else {
            console.warn('Modal API not found, falling back to alert');
            alert(`${notification.title}\n\n${notification.message}`);
        }
    }

    // Toast utility wrapper for cleaner code
    function showToast(message, type = 'info') {
        if (window.__createToast) {
            window.__createToast(message, type);
        } else {
            console.warn('Toast API not available, falling back to alert');
            alert(message);
        }
    }
</script>