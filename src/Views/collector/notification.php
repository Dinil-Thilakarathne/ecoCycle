<?php
/**
 * Collector Notifications View
 * Updated with Pill Tab Switching
 */

$notifications = is_array($notifications ?? null) ? $notifications : [];
$currentTab = $_GET['tab'] ?? 'all';

// Normalize notifications
$normalized = array_map(function ($n) {
    $timestamp = $n['timestamp'] ?? ($n['sent_at'] ?? $n['created_at'] ?? null);
    $isRead = $n['is_read'] ?? ($n['isRead'] ?? (($n['status'] ?? '') === 'read' ? true : false));
    return [
        'id' => (string) ($n['id'] ?? ''),
        'title' => $n['title'] ?? '',
        'message' => $n['message'] ?? '',
        'timestamp' => $timestamp,
        'status' => $isRead ? 'read' : 'unread',
        'type' => $n['type'] ?? 'general',
    ];
}, $notifications);

$totalCount = count($normalized);
$unreadCount = count(array_filter($normalized, fn($x) => $x['status'] === 'unread'));
$readCount = $totalCount - $unreadCount;
?>

<style>
    /* Pill Style Navigation */
    .tab-nav-wrapper {
        background-color: #f1f3f5;
        padding: 5px;
        border-radius: 12px;
        display: inline-flex;
        gap: 4px;
        margin-bottom: 1.5rem;
    }

    .tab-trigger {
        padding: 8px 18px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 500;
        color: #666;
        border-radius: 9px;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .tab-trigger.active {
        background-color: #ffffff;
        color: #000000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .notification-row.unread { background-color: #dff2e7; }

    .notifications-table th:first-child,
    .notifications-table td:first-child {
        text-align: left;
    }

    .notifications-table th:not(:first-child),
    .notifications-table td:not(:first-child) {
        text-align: center;
    }

    .notifications-table thead th {
        background-color: #f1f3f5;
        color: #6c757d;
    }
</style>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title"></i> Notifications</h2>
            <p class="page-header__description">Stay on top of your platform updates</p>
        </div>
    </header>

    <div class="dashboard-page">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
            <div class="tab-nav-wrapper">
                <button onclick="filterTable('all', this)" class="tab-trigger active">
                    Total (<span id="count-all"><?= $totalCount ?></span>)
                </button>
                <button onclick="filterTable('unread', this)" class="tab-trigger">
                    Unread (<span id="count-unread"><?= $unreadCount ?></span>)
                </button>
                <button onclick="filterTable('read', this)" class="tab-trigger">
                    Read (<span id="count-read"><?= $readCount ?></span>)
                </button>
            </div>
</div>
            <div class="action-buttons">
                <button onclick="markAllAsRead()" class="btn btn-primary">Mark All as Read</button>
            </div>

        <div class="table-container" style="overflow-x:auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <table class="notifications-table data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width:25%; text-align: left;">Notification</th>
                        <th style="width:25%; text-align: center;">Type</th>
                        <th style="width:25%; text-align: center;">Date</th>
                        <th style="width:25%; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="notifications-tbody">
                    </tbody>
            </table>
        </div>
    </div>
</main>

<div id="notification-detail-modal" class="user-modal" role="dialog" aria-hidden="true">
    <div class="user-modal__dialog">
        <button class="close" onclick="closeNotificationModal()">&times;</button>
        <h2 style="margin-bottom: 10px; color: var(--primary-color);">Notification Details</h2>
        <div class="user-modal__grid">
            <div><strong>Title</strong></div><div class="nd-title"></div>
            <div><strong>Message</strong></div><div class="nd-message"></div>
            <div><strong>Type</strong></div><div class="nd-type"></div>
            <div><strong>Date</strong></div><div class="nd-date"></div>
            <div><strong>Status</strong></div><div class="nd-status"></div>
        </div>
    </div>
</div>

<script>
let notificationsState = <?= json_encode($normalized) ?>;
let activeFilter = 'all';

(function () {
    const endpoint = '/api/collector/notifications';
    const tbody = document.getElementById('notifications-tbody');

    function normalizeNotification(raw) {
        const timestamp = raw?.timestamp ?? raw?.sent_at ?? raw?.created_at ?? null;
        const explicitReadFlag = raw?.is_read ?? raw?.isRead;
        const statusRaw = String(raw?.status ?? '').toLowerCase();
        const isRead = explicitReadFlag === true || explicitReadFlag === 1 || explicitReadFlag === '1' || explicitReadFlag === 'true' || statusRaw === 'read';

        return {
            id: String(raw?.id ?? ''),
            title: String(raw?.title ?? ''),
            message: String(raw?.message ?? ''),
            timestamp,
            status: isRead ? 'read' : 'unread',
            type: String(raw?.type ?? 'general')
        };
    }

    function normalizeNotifications(list) {
        if (!Array.isArray(list)) return [];
        return list.map(normalizeNotification);
    }

    function isUnreadNotification(notification) {
        return String(notification?.status || '').toLowerCase() !== 'read';
    }

function timeAgo(timestamp) {
  const date = new Date(timestamp);
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const month = months[date.getMonth()];
  const day = date.getDate();
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${month} ${day}, ${year} ${hours}:${minutes}`;
}

    // Tab Switching Logic
    window.filterTable = function(filter, btn) {
        activeFilter = filter;
        document.querySelectorAll('.tab-trigger').forEach(b => b.classList.remove('active'));
        if(btn) btn.classList.add('active');
        renderNotifications(notificationsState);
    };

    function renderNotifications(data) {
        if (!tbody) return;
        notificationsState = normalizeNotifications(data);
        tbody.innerHTML = '';

        // Filter based on active pill
        const filtered = notificationsState.filter(n => {
            if (activeFilter === 'unread') return isUnreadNotification(n);
            if (activeFilter === 'read') return !isUnreadNotification(n);
            return true;
        });

        // Calculate counts
        const totalCount = notificationsState.length;
        const unreadCount = notificationsState.filter(isUnreadNotification).length;
        const readCount = totalCount - unreadCount;
        
        console.log('Rendering notifications - Total:', totalCount, 'Unread:', unreadCount, 'Read:', readCount, 'Active filter:', activeFilter);

        // Update Pill Counts
        document.getElementById('count-all').textContent = totalCount;
        document.getElementById('count-unread').textContent = unreadCount;
        document.getElementById('count-read').textContent = readCount;

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:2rem;">No ${activeFilter} notifications found.</td></tr>`;
            return;
        }

        filtered.forEach(notif => {
            const isUnread = isUnreadNotification(notif);
            const tr = document.createElement('tr');
            tr.className = 'notification-row' + (isUnread ? ' unread' : '');
            
            tr.innerHTML = `
                <td style="text-align: left;">
                    <div class="notification-details">
                        <div class="notification-title" style="font-size: 14px;"><b>${notif.title}</b></div>
                        <div style="font-size: 12px; color: #666; margin-left: 0;">${notif.message.substring(0, 60)}${notif.message.length > 60 ? '...' : ''}</div>
                    </div>
                </td>
                <td style="text-align: center;"><span class="type-badge ${notif.type}">${notif.type}</span></td>
                <td style="text-align: center;">${timeAgo(notif.timestamp)}</td>
                <td class="actions-cell" style="text-align: center;">
                    <div style="display:flex; gap:12px; justify-content: center; align-items: center;">
                        <button class="icon-button" onclick="viewNotification('${notif.id}')" title="View"><i class="fa-solid fa-eye"></i></button>
                        <button class="icon-button" onclick="deleteNotification('${notif.id}')" title="Delete" aria-label="Delete notification"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    window.markAllAsRead = async function() {
        const unreadNotifications = notificationsState.filter(isUnreadNotification);

        if (unreadNotifications.length === 0) {
            return;
        }

        try {
            const requests = unreadNotifications.map((notif) =>
                fetch(`/api/collector/notifications/${encodeURIComponent(notif.id)}/read`, {
                    method: 'PUT',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                })
            );

            const responses = await Promise.allSettled(requests);
            const successCount = responses.filter(
                (result) => result.status === 'fulfilled' && result.value.ok
            ).length;

            if (successCount > 0) {
                // Optimistic local update so unread notifications immediately move to read.
                notificationsState = notificationsState.map((notif) => ({
                    ...notif,
                    status: isUnreadNotification(notif) ? 'read' : notif.status
                }));
                renderNotifications(notificationsState);
            }

            setTimeout(() => fetchNotifications(), 300);
        } catch (e) {
            console.error('Mark all as read failed', e);
            alert('Failed to mark all notifications as read. Please try again.');
        }
    };

    window.viewNotification = async function(id) {
        const notif = notificationsState.find(n => n.id == id);
        const modal = document.getElementById('notification-detail-modal');
        if (!notif || !modal) return;

        // Match company behavior: viewing an unread notification marks it as read.
        if (isUnreadNotification(notif)) {
            await processMarkRead(notif.id);
            notif.status = 'read';
        }

        modal.querySelector('.nd-title').textContent = notif.title;
        modal.querySelector('.nd-message').textContent = notif.message;
        modal.querySelector('.nd-type').textContent = notif.type;
        modal.querySelector('.nd-date').textContent = new Date(notif.timestamp).toLocaleString();
        modal.querySelector('.nd-status').textContent = notif.status.toUpperCase();
        modal.setAttribute('data-current-id', notif.id);
        modal.classList.add('open');
    };

    window.closeNotificationModal = () => document.getElementById('notification-detail-modal').classList.remove('open');

    window.deleteNotification = async function(id) {
        if (!id) return;
        if (!confirm('Are you sure you want to delete this notification?')) return;

        try {
            const res = await fetch(`/api/notifications/${encodeURIComponent(id)}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload?.message || 'Failed to delete notification');
            }

            // Optimistically update list, then sync with backend.
            notificationsState = notificationsState.filter(n => String(n.id) !== String(id));
            renderNotifications(notificationsState);

            const modal = document.getElementById('notification-detail-modal');
            if (modal && modal.getAttribute('data-current-id') === String(id)) {
                closeNotificationModal();
            }

            setTimeout(() => fetchNotifications(), 250);
        } catch (e) {
            console.error('Delete failed', e);
            alert('Failed to delete notification. Please try again.');
        }
    };

    async function processMarkRead(id) {
        console.log('Marking notification as read, ID:', id);
        try {
            const res = await fetch(`/api/collector/notifications/${id}/read`, { 
                method: 'PUT', 
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Response status:', res.status);
            const result = await res.json();
            console.log('Response data:', result);
            
            if (result.success) {
                console.log('Successfully marked as read, updating UI...');
                // Optimistic update: immediately update local state
                const notif = notificationsState.find(n => String(n.id) === String(id));
                console.log('Found notification:', notif);
                if (notif) {
                    notif.status = 'read';
                    renderNotifications(notificationsState);
                }
                // Wait a moment before fetching to ensure DB is updated
                setTimeout(() => fetchNotifications(), 300);
            } else {
                console.error("Failed to mark as read:", result.message);
                alert('Failed to mark notification as read: ' + (result.message || 'Unknown error'));
            }
        } catch (e) { 
            console.error("Update failed", e); 
            alert('Error marking notification as read. Please try again.');
        }
    }

    async function fetchNotifications() {
        try {
            const res = await fetch(endpoint);
            const json = await res.json();
            console.log('Fetched notifications:', json);
            if (json.status === 'success' && Array.isArray(json.data)) {
                console.log('Rendering', json.data.length, 'notifications');
                renderNotifications(normalizeNotifications(json.data));
            }
        } catch (e) {
            console.error("Failed to fetch notifications:", e);
        }
    }

    fetchNotifications();
    setInterval(fetchNotifications, 15000);
})();
</script>