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

    .unread-dot { height: 8px; width: 8px; background-color: #ff4d4f; border-radius: 50%; display: inline-block; margin-right: 8px; }
    .notification-row.unread { background-color: #f8fbff; }
</style>

<main class="content">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title"><i class="fa-solid fa-bell"></i> Notifications</h2>
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
            <!-- <div class="action-buttons">
                <button onclick="markAllAsRead()" class="btn btn-primary">Mark All as Read</button>
            </div> -->

        <div class="table-container" style="overflow-x:auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <table class="notifications-table data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width:45%;">Notification</th>
                        <th style="width:15%;">Type</th>
                        <th style="width:20%;">Date</th>
                        <th style="width:20%; text-align: center;">Actions</th>
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
        <div style="margin-top: 2rem; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-primary" id="markNotificationReadBtn" onclick="markNotificationAsRead()">Mark as Read</button>
        </div>
    </div>
</div>

<script>
let notificationsState = <?= json_encode($normalized) ?>;
let activeFilter = 'all';

(function () {
    const endpoint = '/api/collector/notifications';
    const tbody = document.getElementById('notifications-tbody');

    function timeAgo(timestamp) {
        if (!timestamp) return '';
        const seconds = Math.floor((Date.now() - new Date(timestamp).getTime()) / 1000);
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        return new Date(timestamp).toLocaleDateString();
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
        notificationsState = data;
        tbody.innerHTML = '';

        // Filter based on active pill
        const filtered = data.filter(n => {
            if (activeFilter === 'unread') return n.status === 'unread';
            if (activeFilter === 'read') return n.status === 'read';
            return true;
        });

        // Update Pill Counts
        document.getElementById('count-all').textContent = data.length;
        document.getElementById('count-unread').textContent = data.filter(n => n.status === 'unread').length;
        document.getElementById('count-read').textContent = data.filter(n => n.status === 'read').length;

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:2rem;">No ${activeFilter} notifications found.</td></tr>`;
            return;
        }

        filtered.forEach(notif => {
            const isUnread = notif.status !== 'read';
            const tr = document.createElement('tr');
            tr.className = 'notification-row' + (isUnread ? ' unread' : '');
            tr.innerHTML = `
                <td>
                    <div class="notification-details">
                        ${isUnread ? '<span class="unread-dot"></span>' : ''}
                        <div class="notification-title" style="font-size: 14px;">${notif.title}</div>
                        <div style="font-size: 12px; color: #666;">${notif.message.substring(0, 60)}${notif.message.length > 60 ? '...' : ''}</div>
                    </div>
                </td>
                <td><span class="type-badge ${notif.type}">${notif.type}</span></td>
                <td>${timeAgo(notif.timestamp)}</td>
                <td class="actions-cell">
                    <div style="display:flex; gap:12px; justify-content: center;">
                        ${isUnread ? `<button class="icon-button" onclick="markAsReadDirect('${notif.id}')" title="Mark Read"><i class="fa-solid fa-circle-check"></i></button>` : ''}
                        <button class="icon-button" onclick="viewNotification('${notif.id}')" title="View"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    window.markAllAsRead = async function() {
        try {
            const res = await fetch('/api/notifications/read-all', { 
                method: 'PUT',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await res.json();
            if (result.success) {
                fetchNotifications(); 
            }
        } catch (e) { console.error("Update failed", e); }
    };

    window.viewNotification = function(id) {
        const notif = notificationsState.find(n => n.id == id);
        const modal = document.getElementById('notification-detail-modal');
        if (!notif || !modal) return;
        modal.querySelector('.nd-title').textContent = notif.title;
        modal.querySelector('.nd-message').textContent = notif.message;
        modal.querySelector('.nd-type').textContent = notif.type;
        modal.querySelector('.nd-date').textContent = new Date(notif.timestamp).toLocaleString();
        modal.querySelector('.nd-status').textContent = notif.status.toUpperCase();
        modal.setAttribute('data-current-id', notif.id);
        document.getElementById('markNotificationReadBtn').style.display = (notif.status === 'read') ? 'none' : 'block';
        modal.classList.add('open');
    };

    window.closeNotificationModal = () => document.getElementById('notification-detail-modal').classList.remove('open');

    window.markNotificationAsRead = async function() {
        const id = document.getElementById('notification-detail-modal').getAttribute('data-current-id');
        await processMarkRead(id);
        closeNotificationModal();
    };

    window.markAsReadDirect = async (id) => await processMarkRead(id);

    async function processMarkRead(id) {
        try {
            const res = await fetch(`/api/notifications/${id}/read`, { 
                method: 'PUT', 
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if ((await res.json()).success) fetchNotifications();
        } catch (e) { console.error("Update failed", e); }
    }

    async function fetchNotifications() {
        try {
            const res = await fetch(endpoint);
            const json = await res.json();
            if (json.status === 'success') renderNotifications(json.data);
        } catch (e) {}
    }

    fetchNotifications();
    setInterval(fetchNotifications, 15000);
})();
</script>