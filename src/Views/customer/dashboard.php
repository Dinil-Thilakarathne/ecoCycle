<?php

use function htmlspecialchars as e;

$initialPickupRequests = $pickupRequests ?? $recentPickups ?? [];
$pickupRequests = is_array($initialPickupRequests) ? array_values($initialPickupRequests) : [];
$filter = $_GET['filter'] ?? 'all';
$normalizedFilter = is_string($filter) ? strtolower($filter) : 'all';

$filteredRequests = $pickupRequests;
if ($normalizedFilter !== 'all') {
    $filteredRequests = array_values(array_filter(
        $pickupRequests,
        static function ($request) use ($normalizedFilter) {
            $status = strtolower((string) ($request['status'] ?? ''));
            return $status === $normalizedFilter;
        }
    ));
}

$pendingCount = 0;
$scheduledCount = 0;
$completedCount = 0;
foreach ($pickupRequests as $request) {
    $status = strtolower((string) ($request['status'] ?? ''));
    if ($status === 'pending') {
        $pendingCount++;
    }
    if (in_array($status, ['assigned', 'confirmed'], true)) {
        $scheduledCount++;
    }
    if ($status === 'completed') {
        $completedCount++;
    }
}
$totalCount = count($pickupRequests);

if (!function_exists('customer_pickup_status_class')) {
    function customer_pickup_status_class(string $status): string
    {
        $normalized = strtolower($status);
        switch ($normalized) {
            case 'pending':
                return 'pending';
            case 'assigned':
            case 'confirmed':
                return 'assigned';
            case 'completed':
                return 'completed';
            case 'cancelled':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('customer_pickup_format_datetime')) {
    function customer_pickup_format_datetime(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return date('M d, Y', $timestamp);
    }
}

$customerStats = [
    [
        'title' => 'Total Requests',
        'value' => $totalCount,
        'icon' => 'fa-solid fa-truck',
        'subtitle' => 'All time',
    ],
    [
        'title' => 'Pending',
        'value' => $pendingCount,
        'icon' => 'fa-solid fa-hourglass-half',
        'subtitle' => 'Awaiting confirmation',
    ],
    [
        'title' => 'Scheduled',
        'value' => $scheduledCount,
        'icon' => 'fa-solid fa-calendar-check',
        'subtitle' => 'Assigned / Confirmed',
    ],
    [
        'title' => 'Completed',
        'value' => $completedCount,
        'icon' => 'fa-solid fa-clipboard-check',
        'subtitle' => 'Finished pickups',
    ],
];
?>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content" style="display: flex; align-items: center; gap: 1.5rem;">
                <?php
                $profileData = $userProfile ?? [];
                $firstName = $profileData['firstName'] ?? ($user['name'] ?? 'Customer');
                $firstName = $firstName !== '' ? $firstName : ($user['name'] ?? 'Customer');
                $imagePath = $profileData['profileImage'] ?? null;
                $profilePic = $imagePath ? asset($imagePath) : asset('assets/logo-icon.png');
                ?>
                <img src="<?= htmlspecialchars($profilePic) ?>" class="avatar"
                    style="width:56px;height:56px;object-fit:cover;border-radius:50%;border:2px solid #e0f2fe;box-shadow:0 2px 8px rgba(34,197,94,0.08);">
                <h1 style="margin:0;">Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
            </div>
        </div>

        <!-- Stats Feature Cards -->
        <div class="stats-grid">
            <?php foreach ($customerStats as $stat): ?>
                <div class="feature-card">
                    <div class="feature-card__header">
                        <h3 class="feature-card__title">
                            <?= e($stat['title']) ?>
                        </h3>
                        <div class="feature-card__icon">
                            <i class="<?= e($stat['icon']) ?>"></i>
                        </div>
                    </div>
                    <p class="feature-card__body">
                        <?= e((string) $stat['value']) ?>
                    </p>
                    <div class="feature-card__footer">
                        <span class="tag success"><?= e($stat['subtitle']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Pickups Table -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">Recent Pickups</h2>
                <p class="section-subtitle">Your latest waste collection activities</p>
            </div>

            <div class="action-buttons" style="margin-bottom:1.5rem;">
                <?php
                $filters = [
                    'all' => 'All Requests',
                    'pending' => 'Pending',
                    'assigned' => 'Assigned',
                    'confirmed' => 'Confirmed',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ];
                foreach ($filters as $key => $label):
                    $isActive = $normalizedFilter === $key ? 'btn-primary' : 'btn-outline';
                    ?>
                    <a class="btn <?= $isActive ?>" href="?filter=<?= e($key) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="table-container"
                style="overflow-x:auto;box-shadow:0 2px 12px rgba(34,197,94,0.08);border-radius:16px;">
                <table class="data-table" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th style="width:60px;">PID</th>
                            <th>Address</th>
                            <th>Time Slot</th>
                            <th>Waste Categories</th>
                            <th>Created</th>
                            <th>Scheduled</th>
                            <th>Collector</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredRequests)): ?>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <div class="empty-content">
                                        <div class="empty-icon">📦</div>
                                        <h3>No pickup requests found</h3>
                                        <p>No pickup requests match your current filter.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredRequests as $request):
                                $status = (string) ($request['status'] ?? 'pending');
                                $collector = $request['collectorName'] ?? '';
                                $categoryListRaw = $request['wasteCategories'] ?? [];
                                $categoryList = is_array($categoryListRaw) ? $categoryListRaw : [];
                                ?>
                                <tr data-request-id="<?= e((string) $request['id']) ?>">
                                    <td><?= e((string) $request['id']) ?></td>
                                    <td><?= e((string) ($request['address'] ?? '')) ?></td>
                                    <td><?= e((string) ($request['timeSlot'] ?? '')) ?></td>
                                    <td>
                                        <?php
                                        $categoryNames = array_values(array_filter(array_map('strval', is_array($categoryList) ? $categoryList : [])));
                                        ?>
                                        <?php if (!empty($categoryNames)): ?>
                                            <div class="badge-group">
                                                <?php foreach ($categoryNames as $categoryName): ?>
                                                    <span class="tag"><?= e($categoryName) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e(customer_pickup_format_datetime($request['createdAt'] ?? null)) ?></td>
                                    <td><?= e(customer_pickup_format_datetime($request['scheduledAt'] ?? null)) ?></td>
                                    <td><?= e($collector !== '' ? $collector : '-') ?></td>
                                    <td>
                                        <span class="tag <?= e(customer_pickup_status_class($status)) ?>">
                                            <?= e(ucfirst($status)) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a class="btn btn-outline btn-sm" href="/customer/pickup">Manage</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>