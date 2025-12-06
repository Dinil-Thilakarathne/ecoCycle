<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$notifications = is_array($notifications ?? null) ? $notifications : [];
$action = $_GET['action'] ?? null;
$notificationId = $_GET['id'] ?? null;
$currentTab = $_GET['tab'] ?? 'total';
$msg = $_GET['msg'] ?? null;

if (!isset($_SESSION['company_notification_state'])) {
  $_SESSION['company_notification_state'] = [];
}

$state =& $_SESSION['company_notification_state'];

foreach ($notifications as $notification) {
  if (!isset($notification['id'])) {
    continue;
  }

  $id = (string) $notification['id'];
  if (!isset($state[$id])) {
    $status = strtolower((string) ($notification['status'] ?? ''));
    $state[$id] = [
      'isRead' => in_array($status, ['read', 'sent'], true),
      'isArchived' => false,
    ];
  }
}

$redirect = function (string $message, string $tab) {
  $query = http_build_query([
    'tab' => $tab,
    'msg' => $message,
  ]);
  header('Location: ?' . $query);
  exit;
};

$findNotification = function (?string $id) use ($notifications) {
  if (!$id) {
    return null;
  }

  foreach ($notifications as $notification) {
    if ((string) ($notification['id'] ?? '') === (string) $id) {
      return $notification;
    }
  }

  return null;
};

$selectedNotification = null;

if ($action === 'mark_read' && $notificationId) {
  $id = (string) $notificationId;
  if (isset($state[$id])) {
    $state[$id]['isRead'] = true;
  }
  call_user_func($redirect, 'Notification marked as read', $currentTab);
}

if ($action === 'mark_all_read') {
  foreach ($state as &$meta) {
    $meta['isRead'] = true;
  }
  unset($meta);
  call_user_func($redirect, 'All notifications marked as read', $currentTab);
}

if ($action === 'delete' && $notificationId) {
  $id = (string) $notificationId;
  if (isset($state[$id])) {
    $state[$id]['isArchived'] = true;
  }
  call_user_func($redirect, 'Notification archived', $currentTab);
}

if ($action === 'view' && $notificationId) {
  $selectedNotification = $findNotification($notificationId);
  $id = (string) $notificationId;
  if (isset($state[$id])) {
    $state[$id]['isRead'] = true;
  }
}

$enriched = array_map(function (array $notification) use ($state): array {
  $id = (string) ($notification['id'] ?? '');
  $meta = $state[$id] ?? ['isRead' => false, 'isArchived' => false];
  $type = strtolower((string) ($notification['type'] ?? 'info'));

  $priority = 'normal';
  if ($type === 'payment') {
    $priority = 'high';
  } elseif ($type === 'system') {
    $priority = 'low';
  }

  return array_merge($notification, [
    'isRead' => $meta['isRead'],
    'isArchived' => $meta['isArchived'],
    'priority' => $priority,
  ]);
}, $notifications);

$visibleNotifications = array_filter($enriched, fn(array $notification): bool => !$notification['isArchived']);
$total = array_values($visibleNotifications);
$unread = array_values(array_filter($visibleNotifications, fn(array $notification): bool => !$notification['isRead']));
$read = array_values(array_filter($visibleNotifications, fn(array $notification): bool => $notification['isRead']));
$archived = array_values(array_filter($enriched, fn(array $notification): bool => $notification['isArchived']));

if (!function_exists('renderCompanyNotifications')) {
  function renderCompanyNotifications(array $notifications, string $tab): void
  {
    if (empty($notifications)) {
      echo "<tr><td colspan='4' style='text-align:center;'>No notifications found</td></tr>";
      return;
    }

    foreach ($notifications as $notification) {
      $id = htmlspecialchars((string) ($notification['id'] ?? ''));
      $title = htmlspecialchars($notification['title'] ?? 'Notification');
      $message = htmlspecialchars($notification['message'] ?? '');
      $type = htmlspecialchars(ucfirst($notification['type'] ?? 'info'));
      $timestamp = $notification['timestamp'] ?? null;
      $formatted = $timestamp ? date('M j, Y H:i', strtotime((string) $timestamp)) : 'N/A';
      $rowClass = !empty($notification['isRead']) ? '' : 'unread';

      echo "<tr class='{$rowClass}' data-id='{$id}'>";
      echo "  <td><strong>{$title}</strong><br><small>{$message}</small></td>";
      echo "  <td>{$type}</td>";
      echo "  <td>" . htmlspecialchars($formatted) . "</td>";
      echo "  <td>";


      if (empty($notification['isRead'])) {
        echo "    <a href='?action=mark_read&id={$id}&tab=" . urlencode($tab) . "' class='action-btn'>Mark Read</a> ";
      }

      echo "    <a href='?action=view&id={$id}&tab=" . urlencode($tab) . "' class='action-btn'>View</a> ";
      echo "    <a href='?action=delete&id={$id}&tab=" . urlencode($tab) . "' class='action-btn delete' onclick=\"return confirm('Archive this notification?')\">Archive</a>";
      echo "  </td>";
      echo "</tr>";
    }
  }
}
?>

<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title"><i class="fa-solid fa-bell"></i> Notifications</h2>
      <p class="page-header__description">Stay on top of your platform updates</p>
    </div>
  </header>

  <?php if ($msg): ?>
    <div class="alert alert-success"
      style="margin: 16px 0; padding: 12px; border: 1px solid var(--success-400); background: var(--success-50); color: var(--success-700);">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if ($selectedNotification): ?>
    <div class="activity-card" style="margin-bottom: 1rem;">
      <div class="activity-card__header">
        <h3 class="activity-card__title"><i class="fa-solid fa-envelope-open-text"></i>
          <?= htmlspecialchars($selectedNotification['title'] ?? 'Notification Details') ?></h3>
      </div>
      <div class="activity-card__content">
        <p style="margin-bottom: 0.5rem;">Type:
          <strong><?= htmlspecialchars(ucfirst($selectedNotification['type'] ?? 'info')) ?></strong></p>
        <p style="margin-bottom: 0.5rem;">Date:
          <strong><?= htmlspecialchars($selectedNotification['timestamp'] ? date('M j, Y H:i', strtotime((string) $selectedNotification['timestamp'])) : 'N/A') ?></strong>
        </p>
        <p style="margin: 0; line-height: 1.6;"><?= nl2br(htmlspecialchars($selectedNotification['message'] ?? '')) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <div class="tabs">
    <div class="tabs-list">
      <button class="tabs-trigger<?= $currentTab === 'total' ? ' active' : '' ?>" onclick="showTab('total')"
        id="total-tab">Total (<?= count($total) ?>)</button>
      <button class="tabs-trigger<?= $currentTab === 'unread' ? ' active' : '' ?>" onclick="showTab('unread')"
        id="unread-tab">Unread (<?= count($unread) ?>)</button>
      <button class="tabs-trigger<?= $currentTab === 'read' ? ' active' : '' ?>" onclick="showTab('read')"
        id="read-tab">Read (<?= count($read) ?>)</button>
      <button class="tabs-trigger<?= $currentTab === 'archived' ? ' active' : '' ?>" onclick="showTab('archived')"
        id="archived-tab">Archived (<?= count($archived) ?>)</button>
    </div>

    <div style="margin: 12px 0;">
      <a href="?action=mark_all_read&tab=<?= urlencode($currentTab) ?>" class="btn btn-primary">Mark All as Read</a>
    </div>

    <div class="tabs-content<?= $currentTab === 'total' ? ' active' : '' ?>" id="total-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody><?php renderCompanyNotifications($total, 'total'); ?></tbody>
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
        <tbody><?php renderCompanyNotifications($unread, 'unread'); ?></tbody>
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
        <tbody><?php renderCompanyNotifications($read, 'read'); ?></tbody>
      </table>
    </div>

    <div class="tabs-content<?= $currentTab === 'archived' ? ' active' : '' ?>" id="archived-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody><?php renderCompanyNotifications($archived, 'archived'); ?></tbody>
      </table>
    </div>
  </div>
</main>

<script>
  function showTab(tab) {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    params.delete('msg');
    window.location.search = params.toString();
  }
</script>
