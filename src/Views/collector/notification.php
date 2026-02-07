<?php
// Variables passed from controller:
// - $notifications: array of notification rows (may be empty)
// - $filter, $action, $notificationId, $authUser

$notifications = is_array($notifications ?? null) ? $notifications : [];
$filter = $filter ?? 'all';
$action = $action ?? null;
$notificationId = $notificationId ?? null;

// Normalize notifications to a uniform shape used by the view
$normalized = array_map(function ($n) {
    $timestamp = $n['timestamp'] ?? ($n['sent_at'] ?? $n['created_at'] ?? null);
    $isRead = $n['is_read'] ?? ($n['isRead'] ?? ($n['status'] === 'read' ? true : false));
    $priority = $n['priority'] ?? ($n['status'] ?? 'normal');
    $category = $n['category'] ?? ($n['type'] ?? 'general');
    return [
        'id' => (string) ($n['id'] ?? ''),
        'title' => $n['title'] ?? '',
        'message' => $n['message'] ?? ($n['data']['message'] ?? ''),
        'timestamp' => $timestamp,
        'isRead' => (bool) $isRead,
        'priority' => $priority,
        'category' => $category,
        'type' => $n['type'] ?? 'general',
        'status' => $n['status'] ?? 'unread',
    ];
}, $notifications);


// Calculate stats
$totalNotifications = count($normalized);
$unreadNotifications = count(array_filter($normalized, fn($x) => !$x['isRead'])) ;
$todayNotifications = count(array_filter($normalized, fn($n) => date('Y-m-d', strtotime($n['timestamp'] ?? '1970-01-01')) === date('Y-m-d')));

function timeAgo($timestamp) {
    if (!$timestamp) return '';
    $time = time() - strtotime($timestamp);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($timestamp));
}

function getStatusClass($priority, $isRead) {
    if (!$isRead) return 'status-unread';
    switch($priority) {
        case 'high': return 'status-high';
        case 'normal': return 'status-normal';
        case 'low': return 'status-low';
        default: return 'status-normal';
    }
}

function truncateMessage($message, $length = 80) {
    if (strlen($message) <= $length) return $message;
    return substr($message, 0, $length) . '...';
}
?>

<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title"><i class="fa-solid fa-bell"></i> Notifications</h2>
      <p class="page-header__description">Stay on top of your platform updates</p>
    </div>
  </header>
</main>

   <div class="dashboard-page">
    <style>
        .notification-row.unread .notification-title { font-weight: 700; }
        .notification-row.unread { background: #f0fff4; }
        .notifications-table .notification-row:hover { background: #f5f5f5; }
    </style>

    <div class="header"></div>

    <div class="stats-grid" id="notification-stats">
       <!--   Stats will be updated in real-time via JS  -->  
       <div class="action-buttons" style="margin-bottom:0.5rem;">
    <a href="?tab=total" class="tabs-trigger <?= $currentTab === 'total' ? 'btn-primary' : 'btn-outline' ?>">Total (<span id="total-count"><?= $totalNotifications ?></span>)</a>
    <a href="?tab=unread" class="tabs-trigger <?= $currentTab === 'unread' ? 'btn-primary' : 'btn-outline' ?>">Unread (<span id="unread-count"><?= $unreadNotifications ?></span>)</a>
    <a href="?tab=read" class="tabs-trigger <?= $currentTab === 'read' ? 'btn-primary' : 'btn-outline' ?>">Read (<span id="read-count"><?= $totalNotifications - $unreadNotifications ?></span>)</a>
</div>
</div>

 <div style="margin: 12px 0;">
      <button onclick="markAllAsRead()" class="btn btn-primary">Mark All as Read</button>
    </div>

    <div class="table-container" style="overflow-x:auto;">
        <table class="notifications-table data-table" style="width:100%; table-layout: fixed;">
            <thead>
                <tr>
                  <th style="width:40%;">Notification</th>
                <th style="width:20%;">Type</th>
                <th style="width:20%;">Date</th>
                <th style="width:20%;">Actions</th>
            </tr>
            </thead>
            <tbody id="notifications-tbody">
                 <!-- -Notifications will be rendered in real-time via JS  -->
            </tbody>
        </table>
    </div>
</div> 

<!-- Notification Detail Modal -->
<!-- <div id="notification-modal" class="modal-overlay" style="display:none;">
  <div class="modal-content" style="max-width:600px;">
    <div class="modal-header">
      <h2 id="modal-title"></h2>
      <button class="modal-close" onclick="closeNotificationModal()">×</button>
    </div>
    <div class="modal-body">
      <p id="modal-message"></p>
      <div class="detail-timestamp">
        <strong>Type:</strong> <span id="modal-type"></span><br>
        <strong>Received:</strong> <span id="modal-date"></span>
      </div>
    </div>
    <div class="modal-footer">
      <button id="modal-mark-read" class="btn-primary" onclick="">Mark as Read</button>
      <button class="btn-secondary" onclick="closeNotificationModal()">Close</button>
    </div>
  </div>
</div> -->

<div id="notification-detail" style="display:none; margin-bottom:1rem;">
  <div class="activity-card">
    <div class="activity-card__header">
      <h3 class="activity-card__title">
        <i class="fa-solid fa-envelope-open-text"></i>
        <span id="detail-title"></span>
      </h3>
      <button onclick="closeDetail()" style="background:none;border:none;font-size:1.2rem;">×</button>
    </div>

    <div class="activity-card__content">
      <p>Type: <strong id="detail-type"></strong></p>
      <p>Date: <strong id="detail-date"></strong></p>
      <p id="detail-message" style="line-height:1.6;"></p>
    </div>
  </div>
</div>


<!-- <?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])): ?>
    <?php $id = $_GET['id']; $view = null; foreach ($normalized as $n) { if ($n['id'] === $id) { $view = $n; break; }} ?>
    <?php if ($view): ?>
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header"><h2><?php echo htmlspecialchars($view['title']); ?></h2><a href="?filter=<?php echo $filter; ?>" class="modal-close">×</a></div>
                <div class="modal-body"><p><?php echo htmlspecialchars($view['message']); ?></p><div class="detail-timestamp"><strong>Received:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($view['timestamp'])); ?></div></div>
                <div class="modal-footer">
                    <?php if (!$view['isRead']): ?><a href="?action=mark_read&id=<?php echo $view['id']; ?>&filter=<?php echo $filter; ?>" class="btn-primary">Mark as Read</a><?php endif; ?>
                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?> -->

<script>
  // Poll collector notifications endpoint and update in real time
  const notificationsData = <?= json_encode($normalized) ?>;
  (function () {
    const endpoint = '/api/collector/notifications';
    const statsContainer = document.getElementById('notification-stats');
    const tbody = document.getElementById('notifications-tbody');
    const unreadCountEl = document.getElementById('stat-unread-count');

    function timeAgo(timestamp) {
      if (!timestamp) return '';
      const time = Math.floor((Date.now() - new Date(timestamp).getTime()) / 1000);
      if (time < 60) return 'Just now';
      if (time < 3600) return Math.floor(time / 60) + ' minutes ago';
      if (time < 86400) return Math.floor(time / 3600) + ' hours ago';
      if (time < 2592000) return Math.floor(time / 86400) + ' days ago';
      return new Date(timestamp).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function getStatusBadgeClass(status) {
      return status === 'read' ? 'status-normal' : 'status-unread';
    }

    function getStatusText(status) {
      return status === 'read' ? 'Read' : 'Unread';
    }

    function renderNotifications(notifications) {
      if (!tbody) return;
      tbody.innerHTML = '';
      if (notifications.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="empty-state"><div class="empty-content"><div class="empty-icon">📭</div><h3>No notifications found</h3><p>No notifications match your current filter.</p></div></td></tr>';
        return;
      }

      notifications.forEach(notif => {
        const isUnread = notif.status !== 'read';
        const tr = document.createElement('tr');
        tr.className = 'notification-row' + (isUnread ? ' unread' : '');
        tr.dataset.id = notif.id;
        tr.innerHTML = `
          <td class="notification-info">
            <div class="notification-details">
              ${isUnread ? '<span class="unread-dot" aria-hidden="true"></span>' : ''}
              <div class="notification-title">${notif.title}</div>
              <div class="notification-message">${(notif.message || '').substring(0, 80) + (notif.message && notif.message.length > 80 ? '...' : '')}</div>
            </div>
          </td>
          <td><span class="type-badge ${notif.type}">${notif.type}</span></td>
         <td class="time-cell">${timeAgo(notif.timestamp)}</td>
        <td class="actions-cell">
        <div style="display:flex; gap:8px; align-items:center; flex-wrap: wrap; justify-content: center;">
        ${isUnread ? `<button class="icon-button" onclick="markAsRead('${notif.id}')" title="Mark as Read">
            <i class="fa-solid fa-check"></i>
        </button>` : ''}
        <button class="icon-button" onclick="viewNotification('${notif.id}')" title="View Notification">
            <i class="fa-solid fa-eye"></i>
        </button>
        </div>
</td>


        `;
        tbody.appendChild(tr);
      });
    }

    async function fetchNotifications() {
      try {
        const res = await fetch(endpoint, { credentials: 'same-origin' });
        if (!res.ok) return;
        const json = await res.json();
        if (!json || json.status !== 'success' || !Array.isArray(json.data)) return;

        // renderStats(json.data);
        renderNotifications(json.data);
      } catch (e) {
        // silent fail
      }
    }


window.viewNotification = async function (id) {
  try {
    const res = await fetch(`/api/notifications/${id}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!res.ok) return;

    const json = await res.json();
    if (!json.success) return;

    const n = json.data;

    // Populate detail card
    document.getElementById('detail-title').textContent = n.title || 'Notification';
    document.getElementById('detail-message').textContent = n.message || '';
    document.getElementById('detail-type').textContent = n.type || 'general';
    document.getElementById('detail-date').textContent = new Date(n.timestamp).toLocaleString();

    // Show the card
    document.getElementById('notification-detail').style.display = 'block';

    // Mark as read if unread
    if (n.status !== 'read') {
      markAsRead(id);
    }

  } catch (e) {
    console.error('View failed', e);
  }
};

function closeDetail() {
  document.getElementById('notification-detail').style.display = 'none';
}


window.markAsRead = async function (id, refresh = false) {
  try {
    const res = await fetch(`/api/notifications/${id}/read`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const json = await res.json();
    if (!json.success) return;

    // Refresh table after marking read
    if (refresh) closeNotificationModal();
  } catch (e) {
    console.error('Mark read failed', e);
  }
}

window.closeNotificationModal = function () {
  document.getElementById('notification-modal').style.display = 'none';
}

function toggleDetail(id) {
  // Close all other open detail rows
  document.querySelectorAll('[id^="detail-row-"]').forEach(row => {
    if (row.id !== `detail-row-${id}`) {
      row.style.display = 'none';
    }
  });

  const detailRow = document.getElementById(`detail-row-${id}`);
  if (!detailRow) return;

  // Toggle current row
  detailRow.style.display =
    detailRow.style.display === 'none' ? 'table-row' : 'none';

  // Mark as read when opened
  const notification = notificationsState.find(n => n.id == id);
  if (notification && !notification.isRead) {
    markAsRead(id);
  }
}

    // Initial fetch and interval
    fetchNotifications();
    setInterval(fetchNotifications, 10000);
  })();
</script> 