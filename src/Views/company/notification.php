<?php
// --- Initial Notifications ---
if (!isset($_SESSION['notifications'])) {
    $_SESSION['notifications'] = [
        ["id" => 1, "text" => "Your bid on Lot 900kg Metal was successful!", "time" => "2 minutes ago", "status" => "unread", "category" => "bids"],
        ["id" => 2, "text" => "Payment received for Purchase ID #PUR007", "time" => "15 minutes ago", "status" => "unread", "category" => "payment"],
        ["id" => 3, "text" => "New waste lot available: Plastic", "time" => "1 hour ago", "status" => "read", "category" => "system"],
        ["id" => 4, "text" => "System maintenance scheduled for 25th Aug, 2 AM - 4 AM", "time" => "Yesterday", "status" => "read", "category" => "system"],
        ["id" => 5, "text" => "Your company profile was verified successfully.", "time" => "2 days ago", "status" => "read", "category" => "system"],
        ["id" => 6, "text" => "Reminder: Confirm new bid submission.", "time" => "3 days ago", "status" => "unread", "category" => "bids"],
        ["id" => 7, "text" => "New waste lot available: Paper", "time" => "4 days ago", "status" => "read", "category" => "bids"],
        ["id" => 8, "text" => "Bid placed on Lot Glass 1200kg.", "time" => "5 days ago", "status" => "read", "category" => "bids"]
    ];
}

// --- Get notifications from session ---
$notifications = $_SESSION['notifications'];

// --- Actions ---
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$filter = $_GET['filter'] ?? 'all';

// Mark as Read
if ($action === 'mark_read' && $id) {
    foreach ($notifications as &$n) {
        if ($n['id'] == $id) $n['status'] = 'read';
    }
    $_SESSION['notifications'] = $notifications;
    header("Location: ?filter=$filter");
    exit;
}

// Mark All as Read
if ($action === 'mark_all') {
    foreach ($notifications as &$n) {
        $n['status'] = 'read';
    }
    $_SESSION['notifications'] = $notifications;
    header("Location: ?filter=$filter");
    exit;
}

// Delete
if ($action === 'delete' && $id) {
    $notifications = array_filter($notifications, fn($n) => $n['id'] != $id);
    $_SESSION['notifications'] = array_values($notifications);
    header("Location: ?filter=$filter");
    exit;
}

// View
if ($action === 'view' && $id) {
    foreach ($notifications as $n) {
        if ($n['id'] == $id) $viewNotification = $n;
    }
}

// --- Apply Filters ---
$filtered = array_filter($notifications, function ($n) use ($filter) {
    if ($filter === 'all') return true;
    if ($filter === 'unread') return $n['status'] === 'unread';
    return $n['category'] === $filter;
});
?>

<main class="content">
        <header class="page-header">
            <div class="page-header__content">
                    <h2 class="page-header__title">Notifications</h2>
            </div>
        </header>

    <!-- Filter Tabs -->
  <div class="c-nf-filters">
      <a href="?filter=all" class="nf-btn <?= $filter==='all'?'active':'' ?>">All</a>
      <a href="?filter=unread" class="nf-btn <?= $filter==='unread'?'active':'' ?>">Unread</a>
      <a href="?filter=payment" class="nf-btn <?= $filter==='payment'?'active':'' ?>">Payment</a>
      <a href="?filter=bids" class="nf-btn <?= $filter==='bids'?'active':'' ?>">Bids</a>
      <a href="?filter=system" class="nf-btn <?= $filter==='system'?'active':'' ?>">System</a>
  </div>

  <!-- Mark All as Read -->
  <div style="margin: 10px 0;">
      <a href="?action=mark_all&filter=<?= $filter ?>" class="mark-read-btn">Mark All as Read</a>
  </div>

  <!-- Notifications Table -->
  <table class="data-table">
    <thead>
      <tr>
        <th>Notification</th>
        <th>Type</th>
        <th>Date & Time</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($filtered)): ?>
      <tr><td colspan="5" style="text-align:center;">No notifications found.</td></tr>
    <?php else: ?>
        <?php foreach ($filtered as $n): ?>
        <tr class="<?= $n['status']==='unread'?'unread':'' ?>">
            <td><?= htmlspecialchars($n['text']) ?></td>
            <td><?= ucfirst($n['category']) ?></td>
            <td><?= htmlspecialchars($n['time']) ?></td>
            <td><?= ucfirst($n['status']) ?></td>
            <td>
            <?php if ($n['status']==="unread"): ?>
                <a href="?action=mark_read&id=<?= $n['id'] ?>&filter=<?= $filter ?>" class="btn-n-mark">Mark Read</a>
            <?php endif; ?>
            <a href="?action=view&id=<?= $n['id'] ?>&filter=<?= $filter ?>" class="btn-n-view">View</a>
            <a href="?action=delete&id=<?= $n['id'] ?>&filter=<?= $filter ?>" class="btn-n-delete" onclick="return confirm('Delete this notification?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</main>

<!-- View Modal -->
<?php if (!empty($viewNotification)): ?>
<div class="n-modal-overlay">
  <div class="n-modal-content">
    <div class="n-modal-header">
      <h3><?= htmlspecialchars($viewNotification['text']) ?></h3>
      <a href="?filter=<?= $filter ?>" class="n-modal-close">&times;</a>
    </div>
    <div class="n-modal-body">
      <p><strong>Category:</strong> <?= ucfirst($viewNotification['category']) ?></p>
      <p><strong>Status:</strong> <?= ucfirst($viewNotification['status']) ?></p>
      <p><strong>Time:</strong> <?= htmlspecialchars($viewNotification['time']) ?></p>
    </div>
    <div class="n-modal-footer">
      <?php if ($viewNotification['status']==="unread"): ?>
        <a href="?action=mark_read&id=<?= $viewNotification['id'] ?>&filter=<?= $filter ?>" class="btn-n-mark">Mark as Read</a>
      <?php endif; ?>
      <a href="?filter=<?= $filter ?>" class="btn-n-view">Close</a>
    </div>
  </div>
</div>
<?php endif; ?>