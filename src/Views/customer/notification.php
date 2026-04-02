<?php
$filter = $_GET['filter'] ?? 'all';
$showSettings = (($_GET['action'] ?? '') === 'settings');
$csrfToken = function_exists('csrf_token') ? csrf_token() : ''; // expose CSRF token to JS for API calls
?>

<style>
    .notifications-table th.col-center,
    .notifications-table td.col-center {
        text-align: center;
    }

    .notification-actions-header,
    .notification-actions-cell {
        text-align: left;
    }

    .notification-actions-cell {
        padding-left: 10px;
    }

    .notification-action-wrap {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
    }
</style>

<div class="dashboard-page">
    <header class="page-header">
        <div class="page-header__content">
            <h2 class="page-header__title">Notifications</h2>
            <p class="page-header__description">Stay on top of your platform updates</p>
        </div>
    </header>

    <!-- Filter Tabs + Actions -->
    <div class="notification-tabs-bar" style="margin-bottom: 6px;">
        <a href="?filter=all" class="notification-tab-link <?= $filter === 'all' ? 'active' : '' ?>">Total (<span id="totalCountInline">0</span>)</a>
        <a href="?filter=unread" class="notification-tab-link <?= $filter === 'unread' ? 'active' : '' ?>">Unread (<span id="unreadCountInline">0</span>)</a>
        <a href="?filter=read" class="notification-tab-link <?= $filter === 'read' ? 'active' : '' ?>">Read (<span id="readCountInline">0</span>)</a>
    </div>

    <div style="margin: 4px 0 10px;">
        <button id="markAllAsReadBtn" class="btn btn-primary">Mark All as Read</button>
    </div>

    <!-- Notifications Table -->
    <div class="table-container" style="overflow-x:auto;">
        <table class="notifications-table data-table" style="min-width:800px;">
            <thead>
                <tr>
                    <th style="width: 45%;">Notification</th>
                    <th class="col-center" style="width: 15%;">Type</th>
                    <th class="col-center" style="width: 25%;">Date</th>
                    <th class="notification-actions-header" style="width: 15%;">Actions</th>
                </tr>
            </thead>
            <tbody id="notificationsBody">
                <tr>
                    <td colspan="4" class="loading">Loading notifications…</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- View Notification Modal (populated by JS) -->
<div id="viewNotificationModal" class="modal-overlay" aria-hidden="true" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;align-items:center;justify-content:center;padding:1rem;z-index:1300;pointer-events:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <a href="#" id="modalClose" class="modal-close">×</a>
        </div>
        <div class="modal-body" style="padding: 1.5rem;">
            <div style="display:flex;flex-direction:column;gap:1.25rem;">
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">TYPE</div>
                    <div id="modalCategory" style="font-size: 0.95rem; color: #111827;"></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">TYPE</div>
                    <div id="modalCategory" style="font-size: 0.95rem; color: #111827;"></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">TIME</div>
                    <div id="modalTime" style="font-size: 0.95rem; color: #111827;"></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">MESSAGE</div>
                    <div id="modalMessage" style="font-size: 0.95rem; color: #111827; line-height: 1.5;"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" id="modalClose2" class="btn-secondary">Close</a>
        </div>
    </div>
</div>


<!-- Settings Modal -->
<?php if ($showSettings): ?>
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Notification Settings</h2>
                <a href="?filter=<?php echo $filter; ?>" class="modal-close">×</a>
            </div>
            <form method="POST" action="?action=save_settings">
                <div class="modal-body">
                    <div class="form-section">
                        <h3>Email Notifications</h3>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="email_pickup" checked>
                                <span class="checkmark"></span>
                                Pickup confirmations
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="email_payment" checked>
                                <span class="checkmark"></span>
                                Payment notifications
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="email_system">
                                <span class="checkmark"></span>
                                System updates
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Push Notifications</h3>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="push_reminders" checked>
                                <span class="checkmark"></span>
                                Pickup reminders
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="push_alerts" checked>
                                <span class="checkmark"></span>
                                High priority alerts
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <label for="frequency" class="form-label">Email frequency</label>
                        <select id="frequency" name="frequency" class="form-select">
                            <option value="immediate">Immediate</option>
                            <option value="daily" selected>Daily digest</option>
                            <option value="weekly">Weekly digest</option>
                            <option value="never">Never</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <script>
        (function () {
            const csrfToken = <?php echo json_encode($csrfToken, JSON_UNESCAPED_UNICODE); ?>;
            const initialFilter = '<?php echo addslashes($filter); ?>';
            let notificationsState = [];

            function formatDate(ts) {
                if (!ts) return '';
                const d = new Date(ts);
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                const hours = String(d.getHours()).padStart(2, '0');
                const minutes = String(d.getMinutes()).padStart(2, '0');
                const seconds = String(d.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            }

            function truncateMessage(msg, len = 80) {
                if (!msg) return '';
                return msg.length <= len ? msg : msg.substring(0, len) + '...';
            }

            // Dummy notifications used when API returns none or fails
            const dummyNotifications = [
                { id: 'n-1', title: 'Pickup scheduled', message: 'Your pickup is scheduled for tomorrow 9:00 AM.', category: 'pickup', timestamp: new Date().toISOString(), read: false, priority: 'normal' },
                { id: 'n-2', title: 'Payment received', message: 'You have received Rs 1,200 for your last recycling sale.', category: 'payment', timestamp: new Date(Date.now() - 3600 * 1000).toISOString(), read: false, priority: 'high' },
                { id: 'n-3', title: 'System update', message: 'We updated our terms of service.', category: 'system', timestamp: new Date(Date.now() - 86400 * 1000).toISOString(), read: true, priority: 'low' }
            ];

            async function fetchNotifications() {
                try {
                    const url = '/api/notifications?limit=100'; // Get more for client-side filtering if needed, or implement server-side filter
                    const res = await fetch(url, {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('Failed to load notifications');
                    const data = await res.json();
                    let notifications = data.notifications || [];

                    if (!Array.isArray(notifications)) {
                        notifications = [];
                    }

                    notificationsState = notifications;

                    // Render list
                    renderNotifications(notifications);

                    // Render stats from API if available, else calc
                    if (data.stats) {
                        renderStatsFromApi(data.stats);
                    } else {
                        renderStats(notifications);
                    }

                } catch (err) {
                    console.error(err);
                    const notifications = dummyNotifications.slice();
                    notificationsState = notifications;
                    renderNotifications(notifications);
                    renderStats(notifications);
                }
            }

            function renderStatsFromApi(stats) {
                const unreadInline = document.getElementById('unreadCountInline');
                if (unreadInline) unreadInline.textContent = stats.unread || 0;
                const totalInline = document.getElementById('totalCountInline');
                if (totalInline) totalInline.textContent = stats.total || 0;
                const readInline = document.getElementById('readCountInline');
                if (readInline) readInline.textContent = (stats.total || 0) - (stats.unread || 0);
            }

            function renderStats(notifications) {
                const total = notifications.length;
                const unread = notifications.filter(n => {
                    const v = n.status ?? n.read ?? n.is_read ?? n.isRead;
                    return !$booleanToBool(v);
                }).length;
                const read = total - unread;
                const unreadInline = document.getElementById('unreadCountInline');
                if (unreadInline) unreadInline.textContent = unread;
                const totalInline = document.getElementById('totalCountInline');
                if (totalInline) totalInline.textContent = total;
                const readInline = document.getElementById('readCountInline');
                if (readInline) readInline.textContent = read;
            }

            // Helper to defensively read boolean-like values
            function $booleanToBool(v) {
                if (v === true || v === 1 || v === '1' || v === 'true' || v === 'read') return true;
                if (v === 'unread' || v === 'pending') return false; // Handle status strings

                // If status field is present
                if (typeof v === 'string' && v.toLowerCase() === 'read') return true;

                return false;
            }

            function isRead(n) {
                const status = n.status || n.read || n.is_read || 'unread';
                return status === 'read' || status === true || status === 1 || status === '1';
            }

            function notificationId(n) {
                const id = n && (n.id ?? n._id ?? n.notification_id);
                return id === null || id === undefined ? '' : String(id);
            }

            function renderNotifications(notifications) {
                const tbody = document.getElementById('notificationsBody');
                if (!tbody) return;
                const f = initialFilter;
                const filtered = notifications.filter(n => {
                    // hide system notifications unless specifically looked for or in all?
                    // if ((n.category || n.type || '').toLowerCase() === 'system') return false; 

                    if (f === 'unread') return !isRead(n);
                    if (f === 'read') return isRead(n);
                    return true;
                });

                if (!filtered.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="empty-state"><div class="empty-content"><div class="empty-icon">📭</div><h3>No notifications found</h3><p>No notifications match your current filter.</p></div></td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                filtered.forEach(function (n) {
                    const id = notificationId(n);
                    const read = isRead(n);
                    const tr = document.createElement('tr');
                    tr.className = 'notification-row ' + (read ? '' : 'unread');
                    tr.setAttribute('data-id', id);
                    tr.innerHTML = `
                        <td class="notification-info">
                            <div class="notification-details">
                                ${read ? '' : '<span class="unread-dot" aria-hidden="true"></span>'}
                                <div class="notification-title">${escapeHtml(n.title || n.title_text || '')}</div>
                                <div class="notification-message">${escapeHtml(truncateMessage(n.message || n.body || ''))}</div>
                            </div>
                        </td>
                        <td class="col-center">
                            <span class="type-badge ${escapeHtml(n.category || n.type || 'info')}">${escapeHtml((n.category || n.type || 'info').charAt(0).toUpperCase() + (n.category || n.type || 'info').slice(1))}</span>
                        </td>
                        <td class="time-cell col-center">${escapeHtml(formatDate(n.timestamp || n.created_at || ''))}</td>
                        <td class="actions-cell notification-actions-cell">
                            <div class="notification-action-wrap">
                                ${!id ? '' : `<button class="icon-button" data-action="view" data-id="${escapeHtml(id)}" title="View"><i class="fa-solid fa-eye"></i></button>`}
                                ${!id ? '' : `<button class="icon-button" data-action="delete" data-id="${escapeHtml(id)}" title="Delete"><i class="fa-solid fa-trash"></i></button>`}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // Simple HTML escape to avoid injection
            function escapeHtml(s) {
                if (!s) return '';
                return ('' + s).replace(/[&"'<>]/g, function (c) { return { '&': '&amp;', '"': '&quot;', '\'': '&#39;', '<': '&lt;', '>': '&gt;' }[c]; });
            }

            async function markAsRead(id) {
                try {
                    const res = await fetch('/api/notifications/' + encodeURIComponent(id) + '/read', {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: { 
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': (typeof csrfToken !== 'undefined' ? csrfToken : ''),
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        // don't show an alert to the user; log and return so UI is already optimistic
                        const txt = await res.text().catch(() => '');
                        console.error('markAsRead failed', res.status, txt);
                        return;
                    }

                    // Update row UI immediately without refetch (idempotent)
                    const row = document.querySelector('.notification-row[data-id="' + id + '"]');
                    if (row) {
                        row.classList.remove('unread');
                        const btn = row.querySelector('button[data-action="mark-read"]'); if (btn) btn.remove();
                        const dot = row.querySelector('.unread-dot'); if (dot) dot.remove();
                    }

                    // Refresh counts (optional, but good for sync)
                    fetchNotifications();
                } catch (err) {
                    console.error(err);
                    // swallow alert to avoid interrupting UX; UI is already updated optimistically
                }
            }

            async function deleteNotification(id) {
                try {
                    const res = await fetch('/api/notifications/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': (typeof csrfToken !== 'undefined' ? csrfToken : ''),
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        const txt = await res.text().catch(() => '');
                        console.error('deleteNotification failed', res.status, txt);
                        return;
                    }

                    notificationsState = notificationsState.filter(function (n) {
                        return notificationId(n) !== String(id);
                    });

                    renderNotifications(notificationsState);
                    renderStats(notificationsState);
                } catch (err) {
                    console.error(err);
                }
            }

            // mark-all and delete are handled from table action buttons.

            function openModal(notification) {
                // Safer field fallbacks for modal content
                const title = notification.title || notification.title_text || notification.subject || '';
                const message = notification.message || notification.body || notification.text || '';
                const categoryText = (notification.category || notification.type || '').toString();
                const priorityText = (notification.priority || notification.level || 'Normal').toString();
                const timeVal = notification.timestamp || notification.created_at || notification.sent_at || '';

                // If unread: optimistic UI update + update in-memory state
                const id = notificationId(notification);
                if (id && !isRead(notification)) {
                    const row = document.querySelector('.notification-row[data-id="' + id + '"]');
                    if (row) {
                        row.classList.remove('unread');
                        const dot = row.querySelector('.unread-dot'); if (dot) dot.remove();
                        const markBtn = row.querySelector('button[data-action="mark-read"]'); if (markBtn) markBtn.remove();
                    }

                    for (let i = 0; i < notificationsState.length; i++) {
                        if (notificationId(notificationsState[i]) === String(id)) {
                            notificationsState[i].status = 'read';
                            break;
                        }
                    }

                    renderStats(notificationsState);
                    // persist change (async)
                    markAsRead(id);
                }

                // Build content element used by modal manager if available
                const container = document.createElement('div');
                container.style.cssText = 'display:flex;flex-direction:column;gap:1.25rem;padding:0.5rem;';
                
                const typeVal = categoryText ? escapeHtml(categoryText.charAt(0).toUpperCase() + categoryText.slice(1)) : 'Info';

                container.innerHTML = `
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">TYPE</div>
                        <div style="font-size: 0.95rem; color: #111827;">${typeVal}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">TIME</div>
                        <div style="font-size: 0.95rem; color: #111827;">${escapeHtml(formatDate(timeVal))}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; letter-spacing: 0.05em; margin-bottom: 4px;">MESSAGE</div>
                        <div style="font-size: 0.95rem; color: #111827; line-height: 1.5;">${escapeHtml(message)}</div>
                    </div>
                `;

                // If a global Modal manager (payments-style) is available, use it for consistent UI
                if (window.Modal && typeof window.Modal.open === 'function') {
                    window.Modal.open({
                        title: title || 'Notification',
                        size: 'md',
                        content: container,
                        actions: [
                            { label: 'Close', variant: 'plain' }
                        ]
                    });
                    return;
                }

                // Fallback: use existing inline modal if Modal manager isn't available
                document.getElementById('modalTitle').textContent = title;
                document.getElementById('modalCategory').textContent = categoryText ? (categoryText.charAt(0).toUpperCase() + categoryText.slice(1)) : 'Info';
                document.getElementById('modalTime').textContent = timeVal ? formatDate(timeVal) : '';
                document.getElementById('modalMessage').textContent = message;

                const modal = document.getElementById('viewNotificationModal');
                if (!modal) return;

                modal.classList.add('modal-open');
                modal.style.display = 'flex';
                modal.style.pointerEvents = 'auto';
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                const modal = document.getElementById('viewNotificationModal');
                if (!modal) return;

                modal.classList.remove('modal-open');
                modal.style.display = 'none';
                modal.style.pointerEvents = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }

            // Event delegation for action buttons using data-action attributes
            document.addEventListener('click', function (e) {
                const target = e.target;
                const actionEl = target.closest && target.closest('[data-action]');
                if (actionEl) {
                    e.preventDefault();
                    const action = actionEl.getAttribute('data-action');
                    const id = actionEl.getAttribute('data-id');
                    if (action === 'view' && id) { return openNotificationById(id); }
                    if (action === 'delete' && id) {
                        if (confirm('Delete this notification?')) {
                            return deleteNotification(id);
                        }
                        return;
                    }
                }

                const modalClose = target.closest && (target.closest('#modalClose') || target.closest('#modalClose2'));
                if (modalClose) { e.preventDefault(); closeModal(); return; }

                const modalOverlay = target.closest && target.closest('#viewNotificationModal');
                if (modalOverlay && target.id === 'viewNotificationModal') {
                    e.preventDefault();
                    closeModal();
                    return;
                }

                // removed: global mark-all-read button handler
            });

            // Find a notification by id and open modal (uses already loaded list from the DOM)
            function openNotificationById(id) {
                const local = notificationsState.find(function (n) {
                    return notificationId(n) === String(id);
                });

                if (local) {
                    openModal(local);
                    return;
                }

                fetch('/api/notifications?limit=100', {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(r => r.json()).then(data => {
                    var found = (data.notifications || []).find(n => notificationId(n) === String(id));
                    if (found) openModal(found);
                }).catch(err => console.error(err));
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            async function markAllAsRead() {
                try {
                    const res = await fetch('/api/notifications/read-all', {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': (typeof csrfToken !== 'undefined' ? csrfToken : ''),
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        const txt = await res.text().catch(() => '');
                        console.error('markAllAsRead failed', res.status, txt);
                        return;
                    }

                    const result = await res.json().catch(() => ({}));
                    if (result && result.success === false) {
                        console.error('markAllAsRead rejected by API', result);
                        return;
                    }

                    notificationsState = notificationsState.map(function (n) {
                        return Object.assign({}, n, { status: 'read', read: true, is_read: true, isRead: true });
                    });

                    renderNotifications(notificationsState);
                    renderStats(notificationsState);
                    fetchNotifications();
                } catch (err) {
                    console.error(err);
                }
            }

            const markAllButton = document.getElementById('markAllAsReadBtn');
            if (markAllButton) {
                markAllButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    markAllAsRead();
                });
            }

            // Initialize
            closeModal();
            fetchNotifications();


        })();
    </script>