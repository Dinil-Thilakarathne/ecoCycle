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

  <!-- <div id="alert-container"></div>

  <div id="notification-detail" style="display: none; margin-bottom: 1rem;">
    <div class="activity-card">
      <div class="activity-card__header">
        <h3 class="activity-card__title">
          <i class="fa-solid fa-envelope-open-text"></i>
          <span id="detail-title"></span>
        </h3>
        <button onclick="closeDetail()" style="background: none; border: none; cursor: pointer; font-size: 1.2rem;">&times;</button>
      </div>
      <div class="activity-card__content">
        <p style="margin-bottom: 0.5rem;">Type: <strong id="detail-type"></strong></p>
        <p style="margin-bottom: 0.5rem;">Date: <strong id="detail-date"></strong></p>
        <p style="margin: 0; line-height: 1.6;" id="detail-message"></p>
      </div>
    </div>
  </div> -->

   <!-- <div class="tabs">
    <div class="tabs-list">
      <button class="tabs-trigger<?= $currentTab === 'total' ? ' active' : '' ?>" onclick="showTab('total')" id="total-tab">
        Total (<span id="total-count">0</span>)
      </button>
      <button class="tabs-trigger<?= $currentTab === 'unread' ? ' active' : '' ?>" onclick="showTab('unread')" id="unread-tab">
        Unread (<span id="unread-count">0</span>)
      </button>
      <button class="tabs-trigger<?= $currentTab === 'read' ? ' active' : '' ?>" onclick="showTab('read')" id="read-tab">
        Read (<span id="read-count">0</span>)
      </button>
    </div>  -->

    <!-- <div class="tabs-content<?= $currentTab === 'total' ? ' active' : '' ?>" id="total-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="total-tbody"></tbody>
      </table>
    </div>

    <div class="tabs-content<?= $currentTab === 'unread' ? ' active' : '' ?>" id="unread-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="unread-tbody"></tbody>
      </table>
    </div>

    <div class="tabs-content<?= $currentTab === 'read' ? ' active' : '' ?>" id="read-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="read-tbody"></tbody>
      </table>
    </div> 
  </div>-->
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
    </div> 

    <!-- <div class="action-buttons" style="margin-bottom:2rem;">
        <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline'; ?>">Unread (<span id="stat-unread-count">0</span>)</a>
        <a href="?filter=pickup" class="btn <?php echo $filter === 'pickup' ? 'btn-primary' : 'btn-outline'; ?>">Pickup</a>
         <a href="?filter=payment" class="btn <?php echo $filter === 'payment' ? 'btn-primary' : 'btn-outline'; ?>">Payment</a> 
        <a href="?action=mark_all_read" class="btn btn-outline">Mark All Read</a> 
    </div> -->


    <div class="action-buttons" style="margin-bottom:2rem;">
    <a href="?tab=total" class="btn <?= $currentTab === 'total' ? 'btn-primary' : 'btn-outline' ?>">Total (<span id="total-count"><?= $totalNotifications ?></span>)</a>
    <a href="?tab=unread" class="btn <?= $currentTab === 'unread' ? 'btn-primary' : 'btn-outline' ?>">Unread (<span id="unread-count"><?= $unreadNotifications ?></span>)</a>
    <a href="?tab=read" class="btn <?= $currentTab === 'read' ? 'btn-primary' : 'btn-outline' ?>">Read (<span id="read-count"><?= $totalNotifications - $unreadNotifications ?></span>)</a>
</div>

 <div style="margin: 12px 0;">
      <button onclick="markAllAsRead()" class="btn btn-primary">Mark All as Read</button>
    </div>

    <div class="table-container" style="overflow-x:auto;">
        <table class="notifications-table data-table" style="width:100%; table-layout: fixed;">
            <thead>
                <!-- <tr>
                    <th>Notification</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr> -->
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

<?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])): ?>
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
<?php endif; ?>

<script>
  // Poll collector notifications endpoint and update in real time
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

    // function renderStats(notifications) {
    //   const total = notifications.length;
    //   const unread = notifications.filter(n => n.status !== 'read').length;
    //   const today = notifications.filter(n => {
    //     const nDate = new Date(n.created_at).toLocaleDateString();
    //     const nowDate = new Date().toLocaleDateString();
    //     return nDate === nowDate;
    //   }).length;

    //   const stats = [
    //     { title: 'Total Notifications', value: total, icon: 'fa-solid fa-bell', subtitle: 'All time' },
    //     { title: 'Unread', value: unread, icon: 'fa-solid fa-envelope-open', subtitle: 'Need attention' },
    //     { title: 'Today', value: today, icon: 'fa-solid fa-calendar-day', subtitle: 'Received today' }
    //   ];

    //   statsContainer.innerHTML = '';
    //   stats.forEach(stat => {
    //     const div = document.createElement('div');
    //     div.className = 'feature-card';
    //     div.innerHTML = `
    //       <div class="feature-card__header">
    //         <h3 class="feature-card__title">${stat.title}</h3>
    //         <div class="feature-card__icon"><i class="${stat.icon}"></i></div>
    //       </div>
    //       <p class="feature-card__body">${stat.value}</p>
    //       <div class="feature-card__footer"><span class="tag success">${stat.subtitle}</span></div>
    //     `;
    //     statsContainer.appendChild(div);
    //   });

    //   if (unreadCountEl) unreadCountEl.textContent = unread;
    // }

    function renderNotifications(notifications) {
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
          <td class="time-cell">${timeAgo(notif.created_at)}</td>
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

    // Initial fetch and interval
    fetchNotifications();
    setInterval(fetchNotifications, 10000);
  })();
</script> 