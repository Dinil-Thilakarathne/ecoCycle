<?php
$filter = $_GET['filter'] ?? 'all';
$showSettings = (($_GET['action'] ?? '') === 'settings');
?>

<div class="dashboard-page">
    <!-- Header -->
    <div class="header">
        <!-- header-actions removed -->
    </div>


    <!-- Stats Feature Cards (values populated from API) -->
    <div class="stats-grid">
        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Total Notifications</h3>
                <div class="feature-card__icon"><i class="fa-solid fa-bell"></i></div>
            </div>
            <p class="feature-card__body" id="totalNotifications">0</p>
            <div class="feature-card__footer"><span class="tag success">All time</span></div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Unread</h3>
                <div class="feature-card__icon"><i class="fa-solid fa-envelope-open"></i></div>
            </div>
            <p class="feature-card__body" id="unreadNotifications">0</p>
            <div class="feature-card__footer"><span class="tag success">Need attention</span></div>
        </div>

        <div class="feature-card">
            <div class="feature-card__header">
                <h3 class="feature-card__title">Today</h3>
                <div class="feature-card__icon"><i class="fa-solid fa-calendar-day"></i></div>
            </div>
            <p class="feature-card__body" id="todayNotifications">0</p>
            <div class="feature-card__footer"><span class="tag success">Received today</span></div>
        </div>
    </div>

    <!-- Filter Tabs + Actions -->
    <div class="action-buttons" style="margin-bottom:2rem;">
        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?>">All</a>
        <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">Unread
            (<span id="unreadCountInline">0</span>)</a>
        <a href="?filter=pickup"
            class="btn <?php echo $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">Pickup</a>
        <a href="?filter=payment"
            class="btn <?php echo $filter === 'payment' ? 'btn-primary' : 'btn-outline'; ?>">Payment</a>
        <button id="markAllReadBtn" class="btn btn-outline" style="margin-left:1rem;">Mark all read</button>
    </div>

    <!-- Notifications Table -->
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
            <tbody>
                <?php if (empty($filteredNotifications)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div class="empty-content">
                                <div class="empty-icon">📭</div>
                                <h3>No notifications found</h3>
                                <p>No notifications match your current filter.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tbody id="notificationsBody">
                <tr>
                    <td colspan="5" class="loading">Loading notifications…</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- View Notification Modal (populated by JS) -->
<div id="viewNotificationModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <a href="#" id="modalClose" class="modal-close">×</a>
        </div>
        <div class="modal-body">
            <div class="notification-detail">
                <div class="detail-content">
                    <div class="detail-meta">
                        <span id="modalCategory" class="type-badge"></span>
                        <span id="modalPriority" class="priority-badge"></span>
                        <span id="modalTime" class="time-badge"></span>
                    </div>
                    <p id="modalMessage" class="detail-message"></p>
                    <div class="detail-timestamp">
                        <strong>Received:</strong> <span id="modalReceived"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="modalMarkRead" class="btn-primary" style="display:none;">Mark as Read</button>
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
            const initialFilter = '<?php echo addslashes($filter); ?>';

            function timeAgo(ts) {
                const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
                if (diff < 60) return 'Just now';
                if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
                if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
                if (diff < 2592000) return Math.floor(diff / 86400) + ' days ago';
                return new Date(ts).toLocaleDateString();
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
                    const res = await fetch(url, { credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Failed to load notifications');
                    const data = await res.json();
                    let notifications = data.notifications || [];

                    if (!Array.isArray(notifications)) {
                        notifications = [];
                    }

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
                    renderNotifications(notifications);
                    renderStats(notifications);
                }
            }

            function renderStatsFromApi(stats) {
                document.getElementById('totalNotifications').textContent = stats.total || 0;
                document.getElementById('unreadNotifications').textContent = stats.unread || 0;
                document.getElementById('todayNotifications').textContent = stats.today || 0;
                const inline = document.getElementById('unreadCountInline');
                if (inline) inline.textContent = stats.unread || 0;
            }

            function renderStats(notifications) {
                const total = notifications.length;
                const unread = notifications.filter(n => !($booleanToBool(n.read ?? n.is_read ?? n.isRead))).length;
                const today = notifications.filter(n => new Date(n.timestamp || n.created_at).toDateString() === new Date().toDateString()).length;

                document.getElementById('totalNotifications').textContent = total;
                document.getElementById('unreadNotifications').textContent = unread;
                document.getElementById('todayNotifications').textContent = today;
                const inline = document.getElementById('unreadCountInline');
                if (inline) inline.textContent = unread;
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

            function renderNotifications(notifications) {
                const tbody = document.getElementById('notificationsBody');
                if (!tbody) return;
                const f = initialFilter;
                const filtered = notifications.filter(n => {
                    // hide system notifications unless specifically looked for or in all?
                    // if ((n.category || n.type || '').toLowerCase() === 'system') return false; 

                    if (f === 'unread') return !isRead(n);
                    if (f === 'pickup') return (n.category || n.type || '').toLowerCase() === 'pickup';
                    if (f === 'payment') return (n.category || n.type || '').toLowerCase() === 'payment';
                    return true;
                });

                if (!filtered.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="empty-content"><div class="empty-icon">📭</div><h3>No notifications found</h3><p>No notifications match your current filter.</p></div></td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                filtered.forEach(function (n) {
                    const id = n.id || n._id || n.notification_id;
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
                        <td>
                            <span class="type-badge ${escapeHtml(n.category || n.type || 'info')}">${escapeHtml((n.category || n.type || 'info').charAt(0).toUpperCase() + (n.category || n.type || 'info').slice(1))}</span>
                        </td>
                        <td class="time-cell">${escapeHtml(timeAgo(n.timestamp || n.created_at || ''))}</td>
                        <td><span class="status-badge">${read ? 'Read' : 'Unread'}</span></td>
                        <td class="actions-cell">
                            <div class="action-buttons">
                                ${read ? '' : `<button class="icon-button" data-action="mark-read" data-id="${escapeHtml(id)}" title="Mark Read"><i class="fa-solid fa-check"></i></button>`}
                                <button class="icon-button" data-action="view" data-id="${escapeHtml(id)}" title="View"><i class="fa-solid fa-eye"></i></button>
                                <button class="icon-button danger" data-action="delete" data-id="${escapeHtml(id)}" title="Delete"><i class="fa-solid fa-trash"></i></button>
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
                    const res = await fetch('/api/notifications/' + encodeURIComponent(id) + '/read', { method: 'PUT', credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Failed to mark as read');

                    // Update row UI immediately without refetch
                    const row = document.querySelector('.notification-row[data-id="' + id + '"]');
                    if (row) {
                        row.classList.remove('unread');
                        const btn = row.querySelector('button[data-action="mark-read"]'); if (btn) btn.remove();
                        const badge = row.querySelector('.status-badge'); if (badge) badge.textContent = 'Read';
                        const dot = row.querySelector('.unread-dot'); if (dot) dot.remove();
                    }

                    // Refresh counts (optional, but good for sync)
                    fetchNotifications();
                } catch (err) {
                    console.error(err);
                    alert('Could not mark notification as read');
                }
            }

            async function markAllRead() {
                try {
                    const res = await fetch('/api/notifications/read-all', { method: 'PUT', credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Failed');
                    // Refresh UI
                    fetchNotifications();
                } catch (err) {
                    console.error(err);
                    alert('Could not mark all as read');
                }
            }

            async function deleteNotification(id) {
                if (!confirm('Are you sure you want to delete this notification?')) return;

                try {
                    const res = await fetch('/api/notifications/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        credentials: 'same-origin'
                    });

                    if (!res.ok) throw new Error('Failed to delete');

                    // Remove row
                    const row = document.querySelector('.notification-row[data-id="' + id + '"]');
                    if (row) row.remove();

                    // Refresh counts
                    fetchNotifications();

                } catch (err) {
                    console.error(err);
                    alert('Could not delete notification');
                }
            }

            function openModal(notification) {
                document.getElementById('modalTitle').textContent = notification.title || '';
                document.getElementById('modalCategory').textContent = (notification.category || notification.type || '').charAt(0).toUpperCase() + (notification.category || notification.type || '').slice(1);
                document.getElementById('modalPriority').textContent = (notification.priority || 'Normal').charAt(0).toUpperCase() + (notification.priority || 'Normal').slice(1);
                document.getElementById('modalTime').textContent = timeAgo(notification.timestamp || notification.created_at || '');
                document.getElementById('modalMessage').textContent = notification.message || notification.body || '';
                document.getElementById('modalReceived').textContent = new Date(notification.timestamp || notification.created_at || '').toLocaleString();
                const markBtn = document.getElementById('modalMarkRead');
                if (markBtn) {
                    if (isRead(notification)) {
                        markBtn.style.display = 'none';
                    } else {
                        markBtn.style.display = '';
                        markBtn.onclick = function () {
                            markAsRead(notification.id || notification._id);
                            closeModal();
                        };
                    }
                }
                document.getElementById('viewNotificationModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('viewNotificationModal').style.display = 'none';
            }

            // Event delegation for action buttons using data-action attributes
            document.addEventListener('click', function (e) {
                const target = e.target;
                const actionEl = target.closest && target.closest('[data-action]');
                if (actionEl) {
                    e.preventDefault();
                    const action = actionEl.getAttribute('data-action');
                    const id = actionEl.getAttribute('data-id');
                    if (action === 'mark-read' && id) { return markAsRead(id); }
                    if (action === 'view' && id) { return openNotificationById(id); }
                    if (action === 'delete' && id) { return deleteNotification(id); }
                }

                const modalClose = target.closest && (target.closest('#modalClose') || target.closest('#modalClose2'));
                if (modalClose) { e.preventDefault(); closeModal(); return; }

                const markAll = target.closest && target.closest('#markAllReadBtn');
                if (markAll) { e.preventDefault(); if (confirm('Mark all notifications as read?')) markAllRead(); return; }
            });

            // Find a notification by id and open modal (uses already loaded list from the DOM)
            function openNotificationById(id) {
                // Re-fetch to be sure or find in current list
                // For simplicity, find in rendered rows or globally stored list. 
                // We didn't store list globally, so let's refetch single if possible, or just fetch all
                fetch('/api/notifications', { credentials: 'same-origin' }).then(r => r.json()).then(data => {
                    var found = (data.notifications || []).find(n => (n.id || n._id || n.notification_id) == id);
                    if (found) openModal(found);
                }).catch(err => console.error(err));
            }

            // Initialize
            fetchNotifications();


        })();
    </script>