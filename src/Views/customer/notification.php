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
            <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">Unread (<span id="unreadCountInline">0</span>)</a>
            <a href="?filter=pickup" class="btn <?php echo $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">Pickup</a>
            <a href="?filter=payment" class="btn <?php echo $filter === 'payment' ? 'btn-primary' : 'btn-outline'; ?>">Payment</a>
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
                <tbody id="notificationsBody">
                    <tr><td colspan="5" class="loading">Loading notifications…</td></tr>
                </tbody>
            </table>
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
        </div>
    <?php endif; ?>

    <script>
        (function(){
            const initialFilter = '<?php echo addslashes($filter); ?>';

            function timeAgo(ts) {
                const diff = Math.floor((Date.now() - new Date(ts).getTime())/1000);
                if (diff < 60) return 'Just now';
                if (diff < 3600) return Math.floor(diff/60) + ' minutes ago';
                if (diff < 86400) return Math.floor(diff/3600) + ' hours ago';
                if (diff < 2592000) return Math.floor(diff/86400) + ' days ago';
                return new Date(ts).toLocaleDateString();
            }

            function truncateMessage(msg, len=80){
                if (!msg) return '';
                return msg.length <= len ? msg : msg.substring(0, len) + '...';
            }

            async function fetchNotifications() {
                try {
                    const res = await fetch('/api/notifications', { credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Failed to load notifications');
                    const data = await res.json();
                    const notifications = data.notifications || [];
                    renderNotifications(notifications);
                    renderStats(notifications);
                } catch (err) {
                    console.error(err);
                    const tbody = document.getElementById('notificationsBody');
                    if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="empty-content"><div class="empty-icon">📭</div><h3>Unable to load</h3><p>There was an error fetching notifications.</p></div></td></tr>';
                }
            }

            function renderStats(notifications) {
                const total = notifications.length;
                const unread = notifications.filter(n => !($booleanToBool(n.read ?? n.is_read ?? n.isRead))).length;
                const today = notifications.filter(n => new Date(n.timestamp || n.created_at).toDateString() === new Date().toDateString()).length;
                document.getElementById('totalNotifications').textContent = total;
                document.getElementById('unreadNotifications').textContent = unread;
                document.getElementById('todayNotifications').textContent = today;
                const inline = document.getElementById('unreadCountInline'); if (inline) inline.textContent = unread;
            }

            // Helper to defensively read boolean-like values
            function $booleanToBool(v){
                if (v === true || v === 1 || v === '1' || v === 'true') return true;
                return false;
            }

            function renderNotifications(notifications){
                const tbody = document.getElementById('notificationsBody');
                if (!tbody) return;
                const f = initialFilter;
                const filtered = notifications.filter(n => {
                    // hide system notifications
                    if ((n.category || n.type || '').toLowerCase() === 'system') return false;
                    if (f === 'unread') return !($booleanToBool(n.read ?? n.is_read ?? n.isRead));
                    if (f === 'pickup') return (n.category || '').toLowerCase() === 'pickup';
                    if (f === 'payment') return (n.category || '').toLowerCase() === 'payment';
                    return true;
                });

                if (!filtered.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="empty-content"><div class="empty-icon">📭</div><h3>No notifications found</h3><p>No notifications match your current filter.</p></div></td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                filtered.forEach(function(n){
                    const id = n.id || n._id || n.notification_id;
                    const isRead = $booleanToBool(n.read ?? n.is_read ?? n.isRead);
                    const tr = document.createElement('tr');
                    tr.className = 'notification-row ' + (isRead ? '' : 'unread');
                    tr.setAttribute('data-id', id);
                    tr.innerHTML = `
                        <td class="notification-info">
                            <div class="notification-details">
                                ${isRead ? '' : '<span class="unread-dot" aria-hidden="true"></span>'}
                                <div class="notification-title">${escapeHtml(n.title || n.title_text || '')}</div>
                                <div class="notification-message">${escapeHtml(truncateMessage(n.message || n.body || ''))}</div>
                            </div>
                        </td>
                        <td>
                            <span class="type-badge ${escapeHtml(n.category || '')}">${escapeHtml((n.category || '').charAt(0).toUpperCase() + (n.category || '').slice(1))}</span>
                        </td>
                        <td class="time-cell">${escapeHtml(timeAgo(n.timestamp || n.created_at || ''))}</td>
                        <td><span class="status-badge">${isRead ? 'Read' : 'Unread'}</span></td>
                        <td class="actions-cell">
                            ${isRead ? '' : `<button class="action-btn mark-read" data-id="${escapeHtml(id)}">Mark Read</button>`}
                            <button class="action-btn view" data-id="${escapeHtml(id)}">View</button>
                            <button class="action-btn delete" data-id="${escapeHtml(id)}">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            // Simple HTML escape to avoid injection
            function escapeHtml(s){
                if (!s) return '';
                return (''+s).replace(/[&"'<>]/g, function(c){ return {'&':'&amp;','"':'&quot;','\'':'&#39;','<':'&lt;','>':'&gt;'}[c]; });
            }

            async function markAsRead(id){
                try {
                    const res = await fetch('/api/notifications/' + encodeURIComponent(id) + '/read', { method: 'PUT', credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Failed to mark as read');
                    // Update row UI
                    const row = document.querySelector('.notification-row[data-id="' + id + '"]');
                    if (row) {
                        row.classList.remove('unread');
                        const btn = row.querySelector('.action-btn.mark-read'); if (btn) btn.remove();
                        const badge = row.querySelector('.status-badge'); if (badge) badge.textContent = 'Read';
                    }
                    // Refresh counts
                    fetchNotifications();
                } catch (err) {
                    console.error(err);
                    alert('Could not mark notification as read');
                }
            }

            async function markAllRead(){
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

            function openModal(notification){
                document.getElementById('modalTitle').textContent = notification.title || '';
                document.getElementById('modalCategory').textContent = (notification.category || '').charAt(0).toUpperCase() + (notification.category || '').slice(1);
                document.getElementById('modalPriority').textContent = (notification.priority || '').charAt(0).toUpperCase() + (notification.priority || '').slice(1);
                document.getElementById('modalTime').textContent = timeAgo(notification.timestamp || notification.created_at || '');
                document.getElementById('modalMessage').textContent = notification.message || notification.body || '';
                document.getElementById('modalReceived').textContent = new Date(notification.timestamp || notification.created_at || '').toLocaleString();
                const markBtn = document.getElementById('modalMarkRead');
                if (markBtn) {
                    if ($booleanToBool(notification.read ?? notification.is_read ?? notification.isRead)) {
                        markBtn.style.display = 'none';
                    } else {
                        markBtn.style.display = '';
                        markBtn.onclick = function(){ markAsRead(notification.id || notification._id); };
                    }
                }
                document.getElementById('viewNotificationModal').style.display = 'block';
            }

            function closeModal(){
                document.getElementById('viewNotificationModal').style.display = 'none';
            }

            // Event delegation for action buttons
            document.addEventListener('click', function(e){
                const markBtn = e.target.closest && e.target.closest('.action-btn.mark-read');
                if (markBtn) { e.preventDefault(); const id = markBtn.getAttribute('data-id'); if (id) markAsRead(id); return; }

                const viewBtn = e.target.closest && e.target.closest('.action-btn.view');
                if (viewBtn) { e.preventDefault(); const id = viewBtn.getAttribute('data-id'); if (id) return openNotificationById(id); }

                const delBtn = e.target.closest && e.target.closest('.action-btn.delete');
                if (delBtn) {
                    e.preventDefault(); const id = delBtn.getAttribute('data-id'); if (!id) return; if (!confirm('Are you sure you want to delete this notification?')) return; const removed = removeRowById(id); if (!removed) return; const href = '?action=delete&id=' + encodeURIComponent(id) + '&filter=' + encodeURIComponent(initialFilter || 'all'); fetch(href, { method: 'GET', credentials: 'same-origin' }).catch(err=>console.error('Delete failed', err)); return; }

                const modalClose = e.target.closest && (e.target.closest('#modalClose') || e.target.closest('#modalClose2'));
                if (modalClose) { e.preventDefault(); closeModal(); return; }

                const markAll = e.target.closest && e.target.closest('#markAllReadBtn');
                if (markAll) { e.preventDefault(); if (confirm('Mark all notifications as read?')) markAllRead(); return; }
            });

            function removeRowById(id) {
                var row = document.querySelector('.notification-row[data-id="' + id + '"]');
                if (!row) return false;
                var unread = row.classList.contains('unread');
                row.parentNode.removeChild(row);
                // refresh counts by refetching
                fetchNotifications();
                return true;
            }

            // Find a notification by id and open modal (uses already loaded list from the DOM)
            function openNotificationById(id){
                // As a fallback, re-fetch and find
                fetch('/api/notifications', { credentials: 'same-origin' }).then(r=>r.json()).then(data=>{
                    var found = (data.notifications || []).find(n => (n.id || n._id || n.notification_id) == id);
                    if (found) openModal(found);
                }).catch(err=>console.error(err));
            }

            // Initialize
            fetchNotifications();

        })();
    </script>