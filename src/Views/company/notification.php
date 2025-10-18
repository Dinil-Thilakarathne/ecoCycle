<?php
$notifications = $notifications ?? [];
$filter = $_GET['filter'] ?? 'all';

$filtered = array_filter($notifications, function (array $notification) use ($filter): bool {
  if ($filter === 'all') {
    return true;
  }
  if ($filter === 'unread') {
    return ($notification['status'] ?? '') === 'unread';
  }
  return ($notification['type'] ?? '') === $filter;
});

$filtered = !empty($filtered) ? $filtered : $notifications;
?>

<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title">Notifications</h2>
    </div>
  </header>

  <!-- Filter Tabs -->
  <div class="c-nf-filters">
    <a href="?filter=all" class="nf-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    <a href="?filter=unread" class="nf-btn <?= $filter === 'unread' ? 'active' : '' ?>">Unread</a>
    <a href="?filter=payment" class="nf-btn <?= $filter === 'payment' ? 'active' : '' ?>">Payment</a>
    <a href="?filter=bids" class="nf-btn <?= $filter === 'bids' ? 'active' : '' ?>">Bids</a>
    <a href="?filter=system" class="nf-btn <?= $filter === 'system' ? 'active' : '' ?>">System</a>
  </div>

  <!-- Mark All as Read -->
  <div style="margin: 10px 0;">
    <span class="btn btn-primary outline" style="pointer-events: none; opacity: 0.5;">Mark All as Read</span>
  </div>

  <!-- Notifications Table -->
  <table class="data-table">
    <thead>
      <tr>
        <th>Notification</th>
        <th>Type</th>
        <th>Date & Time</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($filtered)): ?>
        <tr>
          <td colspan="4" style="text-align:center;">No notifications found.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($filtered as $n): ?>
          <tr class="<?= $n['status'] === 'unread' ? 'unread' : '' ?>">
            <td>
              <strong><?= htmlspecialchars($n['title'] ?? 'Notification') ?></strong><br>
              <small><?= htmlspecialchars($n['message'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars(ucfirst($n['type'] ?? 'info')) ?></td>
            <td><?= htmlspecialchars(!empty($n['timestamp']) ? date('Y-m-d H:i', strtotime($n['timestamp'])) : 'N/A') ?>
            </td>
            <td><?= htmlspecialchars(ucfirst($n['status'] ?? 'pending')) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</main>